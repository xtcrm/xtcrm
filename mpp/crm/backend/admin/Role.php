<?php

declare (strict_types=1);

namespace mpp\crm\backend\admin;

use app\platform\backend\BaseController;
use app\platform\model\Role as RoleModel;
use app\platform\model\RoleMenu;

/**
 * CRM 角色管理控制器
 *
 * 路由：/crm.admin.role/list
 */
class Role extends BaseController
{
    protected $methodRules = [
        'list'   => 'GET',
        'add'    => 'POST',
        'edit'   => 'POST',
        'delete' => 'POST',
    ];

    /**
     * 角色列表
     */
    public function list()
    {
        $list = RoleModel::where('store_id', $this->storeId)
            ->order(['sort' => 'asc', 'create_time' => 'asc'])
            ->select()
            ->toArray();

        // 附加每个角色的菜单 ID
        foreach ($list as &$item) {
            $item['menuIds'] = RoleMenu::where('role_id', $item['role_id'])->column('menu_id');
        }
        unset($item);

        // 按 parent_id 组装成树：角色表/角色选择器/用户表单都依赖 children
        $list = self::buildTree($list);

        return $this->renderSuccess(compact('list'));
    }

    /**
     * 新增角色
     */
    public function add()
    {
        $data = $this->postForm();
        if (empty($data['role_name'])) {
            return $this->renderError('角色名称不能为空');
        }

        $parentId = (int)($data['parent_id'] ?? 0);
        if ($parentId > 0 && !$this->existsRole($parentId)) {
            return $this->renderError('上级角色不存在');
        }

        $model = new RoleModel;
        $model->save([
            'role_name'  => $data['role_name'],
            'parent_id'  => $parentId,
            'sort'       => (int)($data['sort'] ?? 100),
            'store_id'   => $this->storeId,
            'create_time' => time(),
        ]);

        // 保存菜单权限（前端发送 menus 字段）
        $menuIds = $data['menus'] ?? $data['menu_ids'] ?? [];
        if (!empty($menuIds)) {
            $menuIds = self::withParentMenus($menuIds);
            RoleMenu::increased((int)$model['role_id'], $menuIds);
        }

        return $this->renderSuccess('添加成功');
    }

    /**
     * 编辑角色
     */
    public function edit()
    {
        $roleId = $this->request->param('roleId', 0);
        $model = RoleModel::detail((int)$roleId);
        if (!$model) {
            return $this->renderError('角色不存在');
        }

        $data = $this->postForm();

        // 上级角色：必须存在，且不能是自己或自己的下级（否则成环，列表整棵树会丢）
        $parentId = (int)($data['parent_id'] ?? $model['parent_id'] ?? 0);
        if ($parentId > 0) {
            if (!$this->existsRole($parentId)) {
                return $this->renderError('上级角色不存在');
            }
            if ($parentId === (int)$roleId || $this->isDescendant($parentId, (int)$roleId)) {
                return $this->renderError('上级角色不能选自己或自己的下级');
            }
        }

        $model->save([
            'role_name'  => $data['role_name'] ?? $model['role_name'],
            'parent_id'  => $parentId,
            'sort'       => (int)($data['sort'] ?? $model['sort']),
        ]);

        $menuIds = $data['menus'] ?? $data['menu_ids'] ?? null;
        if ($menuIds !== null) {
            $menuIds = self::withParentMenus($menuIds);
            RoleMenu::updates((int)$roleId, $menuIds);
        }

        return $this->renderSuccess('更新成功');
    }

    /**
     * 自动补齐所有祖先菜单 ID（前端树组件可能只传叶子节点）
     */
    private static function withParentMenus(array $menuIds): array
    {
        if (empty($menuIds)) return $menuIds;
        $all = $menuIds;
        $current = $menuIds;
        while (!empty($current)) {
            $parents = \think\facade\Db::name('store_menu')
                ->whereIn('menu_id', $current)
                ->where('parent_id', '>', 0)
                ->column('parent_id');
            if (empty($parents)) break;
            $all = array_merge($all, $parents);
            $current = $parents;
        }
        return array_unique($all);
    }

    /**
     * 删除角色
     */
    public function delete()
    {
        $roleId = $this->request->param('roleId', 0);
        $model = RoleModel::detail((int)$roleId);
        if (!$model) {
            return $this->renderError('角色不存在');
        }

        // 删除角色及其菜单关联
        RoleMenu::where('role_id', $roleId)->delete();
        $model->delete();

        return $this->renderSuccess('删除成功');
    }

    /** 角色是否属于当前租户 */
    private function existsRole(int $roleId): bool
    {
        return $roleId > 0 && RoleModel::where('role_id', $roleId)->count() > 0;
    }

    /** $candidateId 是否是 $roleId 的下级（沿 parent_id 向上找，带环保护） */
    private function isDescendant(int $candidateId, int $roleId): bool
    {
        $seen = [];
        $cur = $candidateId;
        while ($cur > 0 && !isset($seen[$cur])) {
            if ($cur === $roleId) return true;
            $seen[$cur] = true;
            $cur = (int)RoleModel::where('role_id', $cur)->value('parent_id');
        }
        return false;
    }

    /**
     * 平铺角色 -> 树
     *
     * - parent_id 指向不存在的角色（脏数据/父角色被删）时按顶级处理，节点不丢
     * - 环保护：互相指向的角色按顶级兜底，避免整组角色从列表消失
     */
    private static function buildTree(array $rows): array
    {
        $byId = [];
        foreach ($rows as $row) {
            $row['children'] = [];
            $byId[(int)$row['role_id']] = $row;
        }

        $childrenOf = [];
        $roots = [];
        foreach ($byId as $id => $row) {
            $pid = (int)$row['parent_id'];
            if ($pid > 0 && $pid !== $id && isset($byId[$pid])) {
                $childrenOf[$pid][] = $id;
            } else {
                $roots[] = $id;
            }
        }

        $built = [];
        $walk = function ($id, array $path) use (&$walk, &$byId, &$childrenOf, &$built) {
            $node = $byId[$id];
            $built[$id] = true;
            $children = [];
            foreach ($childrenOf[$id] ?? [] as $cid) {
                if (isset($path[$cid])) continue; // 环保护
                $children[] = $walk($cid, $path + [$id => true]);
            }
            $node['children'] = $children;
            return $node;
        };

        $tree = [];
        foreach ($roots as $id) {
            $tree[] = $walk($id, []);
        }
        // 兜底：环里的节点没有任何根可达
        foreach ($byId as $id => $row) {
            if (!isset($built[$id])) {
                $tree[] = $walk($id, []);
            }
        }
        return $tree;
    }
}
