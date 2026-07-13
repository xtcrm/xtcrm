<?php

declare (strict_types=1);

namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use app\platform\auth\TokenService;
use app\platform\model\User as UserModel;
use think\facade\Db;

/**
 * CRM 登录控制器
 *
 * 路由：/crm.passport/login
 */
class Passport extends BaseController
{
    protected $allowAllAction = [
        'crm.passport/login',
        'crm.passport/logout',
    ];

    protected $methodRules = [
        'login'  => 'POST',
        'logout' => 'POST',
    ];

    /**
     * 用户登录
     * POST /crm.passport/login
     */
    public function login()
    {
        $data = $this->postData();
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';

        if (empty($username) || empty($password)) {
            return $this->renderError('请输入用户名和密码');
        }

        // 验证用户名密码
        $user = UserModel::withoutGlobalScope()
            ->where('user_name', $username)
            ->where('is_delete', 0)
            ->find();

        if (!$user) {
            return $this->renderError('用户名或密码错误');
        }

        if (!password_verify($password, $user['password'])) {
            return $this->renderError('用户名或密码错误');
        }

        // 校验租户状态
        $store = Db::name('store')->where('store_id', $user['store_id'])->find();
        if (!$store || !empty($store['is_delete'])) {
            return $this->renderError('登录失败，未找到当前租户信息');
        }
        if (!empty($store['is_recycle'])) {
            return $this->renderError('登录失败，当前租户已删除');
        }

        // 生成 token
        $token = TokenService::login($user->toArray());

        return $this->renderSuccess([
            'userId'    => (int)$user['store_user_id'],
            'token'     => $token,
            'expiresIn' => TokenService::getExpireSeconds(),
            'expiresAt' => time() + TokenService::getExpireSeconds(),
        ], '登录成功');
    }

    /**
     * 退出登录
     */
    public function logout()
    {
        TokenService::logout();
        return $this->renderSuccess('操作成功');
    }
}
