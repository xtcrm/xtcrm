<?php
declare(strict_types=1);

namespace mpp\crm\api;

use app\platform\model\User as UserModel;

/**
 * 移动端认证
 * URL: /api/crm.auth/login
 */
class Auth extends Base
{
    protected $methodRules = [
        'login'          => 'POST',
        'userInfo'       => 'GET',
        'logout'         => 'POST',
        'changePassword' => 'POST',
    ];

    /** POST /api/crm.auth/login */
    public function login()
    {
        $model = new UserModel();
        $userInfo = $model->login($this->postData());

        if ($userInfo === false) {
            return $this->renderError($model->getError() ?: '登录失败');
        }

        return $this->renderSuccess([
            'userId'    => (int)$userInfo['store_user_id'],
            'token'     => $model->getToken(),
            'expiresIn' => \app\platform\auth\TokenService::getExpireSeconds(),
            'expiresAt' => time() + \app\platform\auth\TokenService::getExpireSeconds(),
        ], '登录成功');
    }

    /** GET /api/crm.auth/userInfo */
    public function userInfo()
    {
        $user = $this->store ?: [];
        return $this->renderSuccess([
            'userId'   => $this->userId,
            'storeId'  => $this->storeId,
            'userName' => $user['user_name'] ?? '',
            'realName' => $user['real_name'] ?? '',
            'avatar'   => $user['avatar'] ?? '',
            'roleName' => $user['role_name'] ?? '',
            'isSuper'  => (int)($user['is_super'] ?? 0),
        ]);
    }

    /** POST /api/crm.auth/changePassword */
    public function changePassword()
    {
        $data = $this->postData();
        $password = $data['password'] ?? '';
        $passwordConfirm = $data['password_confirm'] ?? '';

        if (empty($password)) return $this->renderError('请输入新密码');
        if (mb_strlen($password) < 6) return $this->renderError('密码至少6个字符');
        if ($password !== $passwordConfirm) return $this->renderError('两次输入的密码不一致');

        $user = (new UserModel())->where('store_user_id', $this->userId)->find();
        if (!$user) return $this->renderError('用户不存在');

        $user->password = encryption_hash($password);
        if ($user->save()) return $this->renderSuccess([], '密码修改成功');
        return $this->renderError('修改失败，请稍后重试');
    }

    /** POST /api/crm.auth/logout */
    public function logout()
    {
        $header = $this->request->header('Authorization', '');
        $token = str_replace('Bearer ', '', $header);
        if (!empty($token)) {
            \think\facade\Cache::delete(trim($token));
        }
        return $this->renderSuccess('已退出登录');
    }
}
