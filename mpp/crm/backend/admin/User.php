<?php

declare (strict_types=1);

namespace mpp\crm\backend\admin;

use app\platform\backend\BaseController;
use app\platform\auth\TokenService;
use app\platform\model\User as UserModel;

/**
 * CRM 管理员控制器
 *
 * 路由：/crm.admin.user/list
 * 不依赖 app/store/，只依赖 app/platform/。
 */
class User extends BaseController
{
    protected $methodRules = [
        'list'   => 'GET',
        'add'    => 'POST',
        'edit'   => 'POST',
        'delete' => 'POST',
    ];

    /**
     * 管理员列表
     * GET /crm.admin.user/list
     */
    public function list()
    {
        $model = new UserModel;
        $list = $model->getList($this->request->param());
        return $this->renderSuccess(compact('list'));
    }

    /**
     * 新增管理员
     * POST /crm.admin.user/add
     */
    public function add()
    {
        $model = new UserModel;
        if ($model->add($this->postForm())) {
            return $this->renderSuccess('添加成功');
        }
        return $this->renderError($model->getError() ?: '添加失败');
    }

    /**
     * 编辑管理员
     * POST /crm.admin.user/edit
     */
    public function edit()
    {
        $userId = $this->request->param('userId', 0);
        $model = UserModel::detail((int)$userId);
        if (!$model) {
            return $this->renderError('用户不存在');
        }
        if ($model->edit($this->postForm())) {
            return $this->renderSuccess('更新成功');
        }
        return $this->renderError($model->getError() ?: '更新失败');
    }

    /**
     * 删除管理员
     * POST /crm.admin.user/delete
     */
    public function delete()
    {
        $userId = $this->request->param('userId', 0);
        $model = UserModel::detail((int)$userId);
        if (!$model) {
            return $this->renderError('用户不存在');
        }
        if (!$model->setDelete()) {
            return $this->renderError($model->getError() ?: '删除失败');
        }
        return $this->renderSuccess('删除成功');
    }

    /**
     * 当前用户信息
     * GET /crm.admin.user/info
     */
    public function info()
    {
        $user = TokenService::getUser();
        if (!$user) {
            return $this->renderError('未登录');
        }

        $userId = (int)($user['store_user_id'] ?? 0);
        $isSuper = !empty($user['is_super']);

        // 构建权限列表
        $permissions = $isSuper ? $this->getAllPermissions() : $this->getUserPermissions($userId);

        return $this->renderSuccess([
            'userInfo' => [
                'store_user_id' => $userId,
                'user_name'     => $user['user_name'] ?? '',
                'real_name'     => $user['real_name'] ?? '',
                'is_super'      => (int)$isSuper,
            ],
            'roles' => [
                'isSuper'     => $isSuper,
                'permissions' => $permissions,
            ],
        ]);
    }

    private function getUserPermissions(int $userId): array
    {
        $roleIds = \app\platform\model\UserRole::getRoleIdsByUserId($userId);
        if (empty($roleIds)) return [];

        $menuIds = \app\platform\model\RoleMenu::getMenuIds($roleIds);
        if (empty($menuIds)) return [];

        return $this->buildPermissions($menuIds);
    }

    private function getAllPermissions(): array
    {
        $menuIds = \think\facade\Db::name('store_menu')
            ->where('module', 10)
            ->column('menu_id');
        return $this->buildPermissions($menuIds);
    }

    private function buildPermissions(array $menuIds): array
    {
        // 菜单项 (module=10)
        $menus = \think\facade\Db::name('store_menu')
            ->where('module', 10)
            ->whereIn('menu_id', $menuIds)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        // 操作项 (module=20) —— 每个菜单的子操作
        $actions = \think\facade\Db::name('store_menu')
            ->where('module', 20)
            ->whereIn('menu_id', $menuIds)
            ->select()
            ->toArray();

        $actionsByParent = [];
        foreach ($actions as $a) {
            $pid = $a['parent_id'] ?? 0;
            if ($pid) $actionsByParent[$pid][] = $a;
        }

        $result = [];
        foreach ($menus as $m) {
            $pid = $m['menu_id'];
            $actionSet = [];
            if (isset($actionsByParent[$pid])) {
                foreach ($actionsByParent[$pid] as $act) {
                    $actionSet[] = [
                        'describe' => $act['name'] ?? '',
                        'action'   => $act['action_mark'] ?? '',
                    ];
                }
            }
            $result[] = [
                'permissionId'   => $m['path'] ?? '',
                'name'           => $m['name'] ?? '',
                'actionEntitySet' => $actionSet,
            ];
        }
        return $result;
    }

    /**
     * 当前租户信息
     * GET /crm.admin.user/storeInfo
     */
    public function storeInfo()
    {
        $store = \think\facade\Db::name('store')
            ->where('store_id', $this->storeId)
            ->find();

        return $this->renderSuccess([
            'storeInfo' => $store ? [
                'store_id'   => (int)$store['store_id'],
                'store_name' => $store['store_name'] ?? '',
                'logo'       => $store['logo'] ?? '',
            ] : null,
        ]);
    }
}
