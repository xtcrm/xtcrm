<?php

declare (strict_types=1);

namespace app\platform\model;

use cores\BaseModel;

/**
 * 平台部门模型（store_department 表）
 */
class Department extends BaseModel
{
    protected $name = 'store_department';
    protected $autoWriteTimestamp = true;

    // ── 关联 ──

    public function children()
    {
        return $this->hasMany(static::class, 'parent_id', 'id')
            ->where('is_delete', 0)
            ->order(['sort' => 'asc', 'id' => 'asc']);
    }

    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id', 'store_user_id');
    }

    // ── 查询 ──

    public static function detail($id)
    {
        return static::where('id', $id)->where('is_delete', 0)->find();
    }

    /**
     * 获取部门树
     */
    public function getTreeList(array $where = []): array
    {
        $allowed = ['store_id', 'parent_id', 'department_name', 'department_code', 'status', 'manager_id'];
        $filtered = [];
        foreach ($where as $k => $v) {
            if (in_array($k, $allowed)) $filtered[$k] = $v;
        }
        $filtered['store_id'] = $filtered['store_id'] ?? self::$storeId;

        $list = $this->where($filtered)
            ->where('is_delete', 0)
            ->order(['sort' => 'asc', 'id' => 'asc'])
            ->select()
            ->toArray();

        return self::buildTree($list);
    }

    // ── 写入 ──

    public function add(array $data): bool
    {
        if (empty($data['department_name'])) {
            $this->error = '部门名称不能为空';
            return false;
        }

        $exists = $this->where('parent_id', $data['parent_id'] ?? 0)
            ->where('department_name', $data['department_name'])
            ->where('is_delete', 0)
            ->find();
        if ($exists) {
            $this->error = '同级部门下已存在同名部门';
            return false;
        }

        $data['store_id']  = self::$storeId;
        $data['is_delete'] = 0;
        $data['status']    = $data['status'] ?? 1;
        $data['sort']      = $data['sort'] ?? 100;

        return $this->save($data);
    }

    public function edit(array $data): bool
    {
        if (empty($data['department_name'])) {
            $this->error = '部门名称不能为空';
            return false;
        }

        $exists = $this->where('parent_id', $data['parent_id'] ?? $this->parent_id)
            ->where('department_name', $data['department_name'])
            ->where('id', '<>', $this->id)
            ->where('is_delete', 0)
            ->find();
        if ($exists) {
            $this->error = '同级部门下已存在同名部门';
            return false;
        }

        unset($data['id']);
        $data['status'] = $data['status'] ?? 1;
        $data['sort']   = $data['sort'] ?? 100;

        return $this->save($data);
    }

    public function setDelete(): bool
    {
        $hasChildren = $this->where('parent_id', $this->id)->where('is_delete', 0)->find();
        if ($hasChildren) {
            $this->error = '该部门下存在子部门，无法删除';
            return false;
        }

        $hasUsers = UserDepartment::where('department_id', $this->id)
            ->where('store_id', self::$storeId)
            ->find();
        if ($hasUsers) {
            $this->error = '该部门下存在员工，无法删除';
            return false;
        }

        return $this->save(['is_delete' => 1]);
    }

    public function setManager(int $managerId): bool
    {
        $user = User::where('store_user_id', $managerId)
            ->where('store_id', self::$storeId)
            ->where('is_delete', 0)
            ->find();
        if (!$user) {
            $this->error = '指定的用户不存在';
            return false;
        }
        return $this->save(['manager_id' => $managerId]);
    }

    /**
     * 获取部门员工列表
     */
    public function getUsers(int $departmentId): array
    {
        return \think\facade\Db::name('store_user_department')->alias('ud')
            ->join('store_user u', 'u.store_user_id = ud.user_id')
            ->where('ud.department_id', $departmentId)
            ->where('ud.store_id', self::$storeId)
            ->where('u.is_delete', 0)
            ->field('u.*, ud.is_manager')
            ->select()
            ->toArray();
    }

    /**
     * 分配员工到部门
     */
    public function assignUser(int $userId, int $departmentId, int $isManager = 0): bool
    {
        $existing = UserDepartment::getByUserId($userId);
        if ($existing) {
            return $existing->save(['department_id' => $departmentId, 'is_manager' => $isManager]);
        }
        return UserDepartment::addUserToDept($userId, $departmentId, $isManager);
    }

    // ── 工具 ──

    private static function buildTree(array $list, int $parentId = 0): array
    {
        $tree = [];
        foreach ($list as $item) {
            if ($item['parent_id'] == $parentId) {
                $children = self::buildTree($list, (int)$item['id']);
                if ($children) $item['children'] = $children;
                $tree[] = $item;
            }
        }
        return $tree;
    }
}
