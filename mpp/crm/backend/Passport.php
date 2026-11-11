<?php

declare (strict_types=1);

namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use app\platform\auth\TokenService;
use app\platform\model\User as UserModel;
use mpp\crm\service\OperationLogService;
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
        'crm.passport/check',
    ];

    protected $methodRules = [
        'login'  => 'POST',
        'logout' => 'POST',
        'check'  => 'GET',
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
            OperationLogService::log('login_fail', '', 0, "登录失败：用户名「{$username}」不存在", [], null, $username);
            return $this->renderError('用户名或密码错误');
        }

        if (!password_verify($password, $user['password'])) {
            OperationLogService::log('login_fail', '', 0, "登录失败：用户名「{$username}」密码错误", [], null, $username);
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
        $storeId = (int)($user['store_id'] ?? 0);

        // 种同站登录 cookie(.xtocn.com 域),供 dsh.xtocn.com 工作台的
        // nginx auth_request 会话校验使用;httpOnly + secure,与 token 同过期
        cookie('crm_token', $token, [
            'expire'   => TokenService::getExpireSeconds(),
            'path'     => '/',
            'domain'   => '.xtocn.com',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'lax',
        ]);

        OperationLogService::log('login', '', 0, "用户「{$username}」登录成功", [], (int)$user['store_user_id'], $user['user_name'] ?? $username, $storeId);

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
        OperationLogService::log('logout', '', 0, '用户退出登录');
        TokenService::logout();
        // 清除同站登录 cookie
        cookie('crm_token', '', [
            'expire'   => -86400,
            'path'     => '/',
            'domain'   => '.xtocn.com',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'lax',
        ]);
        return $this->renderSuccess('操作成功');
    }

    /**
     * 会话校验端点(供 AI 工作台 dsh.xtocn.com 的 nginx auth_request 调用)
     *
     * 返回真实 HTTP 状态码:200 = 已登录,401 = 未登录(同时清理失效 cookie)。
     * GET /crm.passport/check
     */
    public function check()
    {
        $token = (string)$this->request->cookie('crm_token', '');
        if ($token !== '' && TokenService::verifyToken($token) !== null) {
            return json(['status' => 1, 'message' => 'ok', 'data' => []], 200);
        }
        // token 缺失或失效:清 cookie 并返回 401
        cookie('crm_token', '', [
            'expire'   => -86400,
            'path'     => '/',
            'domain'   => '.xtocn.com',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'lax',
        ]);
        return json(['status' => 401, 'message' => '未登录', 'data' => []], 401);
    }
}
