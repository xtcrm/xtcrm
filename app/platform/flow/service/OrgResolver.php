<?php

declare(strict_types=1);

namespace app\platform\flow\service;

use think\facade\Db;

/**
 * 组织解析器
 *
 * 把节点上的指派规则解析成具体用户。
 * 只依赖平台组织表：store_user / store_user_role / store_role / store_department。
 */
class OrgResolver
{
    private int $storeId;

    public function __construct(int $storeId)
    {
        $this->storeId = $storeId;
    }

    /**
     * @param array|string|null $rules 指派规则（JSON 或数组）
     * @param array $context 变量、业务快照、发起人
     * @return array<int, array{id:int,name:string,type:string,role_id:int,dept_id:int}>
     */
    public function resolve($rules, array $context = []): array
    {
        $rules = $this->normalizeRules($rules);
        if (empty($rules)) return [];

        $users = [];
        foreach ($rules as $rule) {
            if (!is_array($rule)) continue;
            foreach ($this->resolveRule($rule, $context) as $user) {
                $id = (int)$user['id'];
                if ($id <= 0) continue;
                if (!isset($users[$id])) {
                    $users[$id] = $user;
                } else {
                    // 更具体的来源优先（user > field > manager > creator > dept > role）
                    if ($this->priority($user['type']) > $this->priority($users[$id]['type'])) {
                        $users[$id] = $user;
                    }
                }
            }
        }
        return array_values($users);
    }

    private function priority(string $type): int
    {
        return [
            'user' => 60,
            'field' => 50,
            'manager' => 40,
            'creator' => 30,
            'dept' => 20,
            'role' => 10,
        ][$type] ?? 0;
    }

    private function normalizeRules($rules): array
    {
        if (is_string($rules)) {
            $decoded = json_decode($rules, true);
            $rules = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($rules)) return [];
        // 单个规则对象也兼容
        if (isset($rules['type'])) return [$rules];
        return array_values(array_filter($rules, 'is_array'));
    }

    private function resolveRule(array $rule, array $context): array
    {
        $type = (string)($rule['type'] ?? '');
        switch ($type) {
            case 'user':
                return $this->usersByIds([(int)($rule['id'] ?? 0)]);

            case 'creator':
                $id = (int)($context['initiator_id'] ?? 0);
                return $this->usersByIds([$id]);

            case 'field':
                $field = (string)($rule['field'] ?? '');
                $value = $this->contextValue($field, $context);
                return $this->usersByIds($this->toIds($value));

            case 'manager':
                $field = (string)($rule['of_field'] ?? '');
                $value = $this->contextValue($field, $context);
                $ids = $this->toIds($value);
                $managerIds = [];
                foreach ($ids as $uid) {
                    $managerIds[] = $this->managerOf($uid);
                }
                return $this->usersByIds($managerIds);

            case 'role':
                $roleIds = (int)($rule['id'] ?? 0);
                $roleIds = [(int)$roleIds];
                if (!empty($rule['include_children'])) {
                    $roleIds = $this->expandRoleChildren($roleIds);
                }
                return $this->usersByRoles($roleIds);

            case 'role_name':
                // 运行时按角色名解析：角色改名/删除后不会留下失效的 role_id
                $name = trim((string)($rule['name'] ?? ''));
                if ($name === '') return [];
                $roleIds = Db::name('store_role')
                    ->where('store_id', $this->storeId)
                    ->where('role_name', $name)
                    ->column('role_id');
                return $this->usersByRoles($roleIds);

            case 'dept':
                $deptIds = [(int)($rule['id'] ?? 0)];
                if (($rule['include_children'] ?? true) !== false) {
                    $deptIds = $this->expandDeptChildren($deptIds);
                }
                return $this->usersByDepts($deptIds);
        }
        return [];
    }

    private function contextValue(string $field, array $context)
    {
        if ($field === '') return null;
        if (array_key_exists($field, $context['variables'] ?? [])) {
            return $context['variables'][$field];
        }
        if (array_key_exists($field, $context['snapshot'] ?? [])) {
            return $context['snapshot'][$field];
        }
        return null;
    }

    private function toIds($value): array
    {
        if ($value === null || $value === '') return [];
        if (is_array($value)) {
            return array_values(array_filter(array_map('intval', $value)));
        }
        if (is_string($value) && strpos($value, ',') !== false) {
            return array_values(array_filter(array_map('intval', explode(',', $value))));
        }
        return [(int)$value];
    }

    public function usersByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (empty($ids)) return [];

        $rows = Db::name('store_user')
            ->where('store_id', $this->storeId)
            ->where('is_delete', 0)
            ->whereIn('store_user_id', $ids)
            ->field('store_user_id,real_name,user_name,department_id')
            ->select()
            ->toArray();

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'id' => (int)$row['store_user_id'],
                'name' => (string)($row['real_name'] ?: $row['user_name']),
                'type' => 'user',
                'role_id' => 0,
                'dept_id' => (int)($row['department_id'] ?? 0),
            ];
        }
        return $result;
    }

    private function usersByRoles(array $roleIds): array
    {
        $roleIds = array_values(array_unique(array_filter(array_map('intval', $roleIds))));
        if (empty($roleIds)) return [];

        $userIds = Db::name('store_user_role')
            ->where('store_id', $this->storeId)
            ->whereIn('role_id', $roleIds)
            ->column('store_user_id');
        $userIds = array_values(array_unique(array_map('intval', $userIds)));
        if (empty($userIds)) return [];

        $rows = Db::name('store_user')
            ->where('store_id', $this->storeId)
            ->where('is_delete', 0)
            ->whereIn('store_user_id', $userIds)
            ->field('store_user_id,real_name,user_name,department_id')
            ->select()
            ->toArray();

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'id' => (int)$row['store_user_id'],
                'name' => (string)($row['real_name'] ?: $row['user_name']),
                'type' => 'role',
                'role_id' => (int)($roleIds[0] ?? 0),
                'dept_id' => (int)($row['department_id'] ?? 0),
            ];
        }
        return $result;
    }

    private function usersByDepts(array $deptIds): array
    {
        $deptIds = array_values(array_unique(array_filter(array_map('intval', $deptIds))));
        if (empty($deptIds)) return [];

        $rows = Db::name('store_user')
            ->where('store_id', $this->storeId)
            ->where('is_delete', 0)
            ->whereIn('department_id', $deptIds)
            ->field('store_user_id,real_name,user_name,department_id')
            ->select()
            ->toArray();

        $result = [];
        foreach ($rows as $row) {
            $result[] = [
                'id' => (int)$row['store_user_id'],
                'name' => (string)($row['real_name'] ?: $row['user_name']),
                'type' => 'dept',
                'role_id' => 0,
                'dept_id' => (int)($row['department_id'] ?? 0),
            ];
        }
        return $result;
    }

    /**
     * 直属部门负责人（供 SLA 升级用）
     */
    public function managerOfUser(int $userId): int
    {
        return $this->managerOf($userId);
    }

    private function managerOf(int $userId): int
    {
        if ($userId <= 0) return 0;
        $user = Db::name('store_user')
            ->where('store_id', $this->storeId)
            ->where('store_user_id', $userId)
            ->where('is_delete', 0)
            ->find();
        if (!$user || empty($user['department_id'])) return 0;

        $dept = Db::name('store_department')
            ->where('store_id', $this->storeId)
            ->where('id', (int)$user['department_id'])
            ->find();
        return (int)($dept['manager_id'] ?? 0);
    }

    private function expandDeptChildren(array $deptIds): array
    {
        $all = Db::name('store_department')
            ->where('store_id', $this->storeId)
            ->where('is_delete', 0)
            ->field('id,parent_id')
            ->select()
            ->toArray();

        $byParent = [];
        foreach ($all as $row) {
            $byParent[(int)$row['parent_id']][] = (int)$row['id'];
        }

        $result = [];
        $queue = $deptIds;
        while ($queue) {
            $id = (int)array_shift($queue);
            if ($id <= 0 || in_array($id, $result, true)) continue;
            $result[] = $id;
            foreach ($byParent[$id] ?? [] as $childId) {
                $queue[] = $childId;
            }
        }
        return $result;
    }

    private function expandRoleChildren(array $roleIds): array
    {
        $all = Db::name('store_role')
            ->where('store_id', $this->storeId)
            ->field('role_id,parent_id')
            ->select()
            ->toArray();

        $byParent = [];
        foreach ($all as $row) {
            $byParent[(int)$row['parent_id']][] = (int)$row['role_id'];
        }

        $result = [];
        $queue = $roleIds;
        while ($queue) {
            $id = (int)array_shift($queue);
            if ($id <= 0 || in_array($id, $result, true)) continue;
            $result[] = $id;
            foreach ($byParent[$id] ?? [] as $childId) {
                $queue[] = $childId;
            }
        }
        return $result;
    }

    /**
     * 用户下拉（设计器用）
     */
    public static function userOptions(int $storeId, string $keyword = '', int $limit = 500): array
    {
        $query = Db::name('store_user')
            ->where('store_id', $storeId)
            ->where('is_delete', 0);
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('real_name', 'like', "%{$keyword}%")
                  ->whereOr('user_name', 'like', "%{$keyword}%");
            });
        }
        $rows = $query->order('store_user_id', 'asc')->limit($limit)
            ->field('store_user_id,real_name,user_name,department_id')->select()->toArray();

        return array_map(fn($r) => [
            'id' => (int)$r['store_user_id'],
            'name' => (string)($r['real_name'] ?: $r['user_name']),
            'department_id' => (int)($r['department_id'] ?? 0),
        ], $rows);
    }

    /**
     * 角色下拉（设计器用）
     */
    public static function roleOptions(int $storeId): array
    {
        $rows = Db::name('store_role')
            ->where('store_id', $storeId)
            ->order('sort', 'asc')
            ->field('role_id,role_name')
            ->select()
            ->toArray();

        $counts = [];
        $countRows = Db::name('store_user_role')
            ->where('store_id', $storeId)
            ->group('role_id')
            ->field('role_id, COUNT(*) AS cnt')
            ->select()
            ->toArray();
        foreach ($countRows as $row) $counts[(int)$row['role_id']] = (int)$row['cnt'];

        return array_map(fn($r) => [
            'id' => (int)$r['role_id'],
            'name' => (string)$r['role_name'],
            'user_count' => $counts[(int)$r['role_id']] ?? 0,
        ], $rows);
    }

    /**
     * 部门树（设计器用）
     */
    public static function deptTree(int $storeId): array
    {
        $rows = Db::name('store_department')
            ->where('store_id', $storeId)
            ->where('is_delete', 0)
            ->order('sort', 'asc')
            ->field('id,parent_id,department_name')
            ->select()
            ->toArray();

        $children = [];
        foreach ($rows as $row) {
            $children[(int)$row['parent_id']][] = $row;
        }
        $build = function (int $parentId) use (&$build, $children): array {
            $list = [];
            foreach ($children[$parentId] ?? [] as $row) {
                $list[] = [
                    'id' => (int)$row['id'],
                    'name' => (string)$row['department_name'],
                    'children' => $build((int)$row['id']),
                ];
            }
            return $list;
        };
        return $build(0);
    }
}
