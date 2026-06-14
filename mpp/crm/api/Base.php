<?php
declare(strict_types=1);

namespace mpp\crm\api;

use cores\BaseController;
use think\facade\Cache;

/**
 * 移动端 API 基类
 *
 * 用于 uni-app 移动端访问，Bearer Token 认证
 * URL 前缀: /api/crm.xxx/method（ThinkPHP 多应用路由）
 *
 * Token 复用 PC 后台 login 生成的缓存 token
 * 不进行菜单权限校验（移动端暂不细分操作权限）
 */
class Base extends BaseController
{
    /** @var int 当前租户ID */
    protected $storeId;

    /** @var array|null 登录用户信息（store_user 表记录） */
    protected $store;

    /** @var int 当前登录用户ID */
    protected $userId;

    /** @var string 当前路由 URI */
    protected $routeUri = '';

    /** @var array 免 token 验证的路由（全小写，点号分隔，如 crm.auth/login） */
    protected $allowAllAction = ['crm.auth/login'];

    protected function initialize()
    {
        // ThinkPHP controller() 返回驼峰如 crm.Auth → uncamelize → crm.auth
        $controller = uncamelize($this->request->controller());
        $action = $this->request->action();
        $this->routeUri = $controller . '/' . $action;

        if (in_array($this->routeUri, $this->allowAllAction)) {
            return;
        }

        $this->checkToken();
    }

    /**
     * 校验 Bearer Token 并设置用户身份
     */
    protected function checkToken(): void
    {
        // 优先 Authorization: Bearer xxx，fallback Access-Token（PC 后台共用 token）
        $header = $this->request->header('Authorization', '');
        $token = trim(str_replace('Bearer ', '', $header));
        if (empty($token)) {
            $token = $this->request->header('Access-Token', '');
        }
        $token = trim($token);

        if (empty($token)) {
            $this->renderJson(401, '请先登录')->send();
            exit;
        }

        $cache = Cache::get($token);
        if (empty($cache) || !is_array($cache)) {
            $this->renderJson(401, '登录已过期，请重新登录')->send();
            exit;
        }

        if (isset($cache['exp']) && time() > (int)$cache['exp']) {
            Cache::delete($token);
            $this->renderJson(401, '登录已过期，请重新登录')->send();
            exit;
        }

        $this->userId = (int)($cache['uid'] ?? 0);
        $this->storeId = (int)($cache['sid'] ?? 0);

        if ($this->userId <= 0 || $this->storeId <= 0) {
            $this->renderJson(401, '无效的登录凭证')->send();
            exit;
        }

        // 注入 request storeId，BaseModel 全局 scope 需要
        $this->request->setStoreId($this->storeId);

        $userModel = new \app\platform\model\User();
        $this->store = $userModel->where('store_user_id', $this->userId)->find();
        if (empty($this->store)) {
            $this->renderJson(401, '用户不存在')->send();
            exit;
        }
        $this->store = $this->store->toArray();
    }

    protected function getUserId(): int
    {
        return $this->userId;
    }

    protected function getStoreId(): int
    {
        return $this->storeId;
    }
}
