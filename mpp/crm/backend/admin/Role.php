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

        $model = new RoleModel;
        $model->save([
            'role_name'  => $data['role_name'],
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
        $model->save([
            'role_name'  => $data['role_name'] ?? $model['role_name'],
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
}
