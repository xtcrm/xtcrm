<?php

declare (strict_types=1);

namespace app\platform\backend;

use cores\BaseController as CoreBaseController;
use app\platform\auth\TokenService;
use app\platform\permission\PermissionChecker;

/**
 * 平台后台控制器基类
 *
 * 所有业务模块（CRM、Fiscal、未来模块）的 PC 后台 Controller 继承此类。
 *
 * 提供：
 *   - 租户上下文解析（$storeId）
 *   - 登录状态校验（$store）
 *   - 路由信息解析（$controller / $action / $routeUri / $group）
 *   - HTTP 方法强制校验（$methodRules）
 *   - 菜单操作权限检查（checkAction）
 */
abstract class BaseController extends CoreBaseController
{
    /** @var array 当前登录用户 {uid, sid, exp} */
    protected $store;

    /** @var int 当前租户 ID */
    protected $storeId;

    /** @var string 控制器名称 */
    protected $controller = '';

    /** @var string 方法名称 */
    protected $action = '';

    /** @var string 完整路由 "controller/action" */
    protected $routeUri = '';

    /** @var string 模块分组（点号前的部分） */
    protected $group = '';

    /**
     * 登录验证白名单（子类覆盖）
     * @var array<string>
     */
    protected $allowAllAction = [];

    /**
     * HTTP 方法强制校验（子类覆盖）
     * @var array<string, string|array>
     */
    protected $methodRules = [];

    // ── 生命周期 ──

    public function initialize()
    {
        $this->resolveTenant();
        $this->setLoginUser();
        $this->resolveRouteInfo();
        $this->checkLogin();
        $this->checkMethodRules();
    }

    // ── 租户 ──

    protected function resolveTenant(): int
    {
        if (is_null($this->storeId)) {
            $this->storeId = $this->request->storeId();
        }
        return (int)$this->storeId;
    }

    // ── 登录 ──

    protected function setLoginUser(): void
    {
        $this->store = TokenService::verify() ?? [];
    }

    protected function checkLogin(): void
    {
        if (in_array($this->routeUri, $this->allowAllAction)) return;
        if (empty($this->store)) {
            throwError('请先登录后再访问', config('status.not_logged'));
        }
    }

    // ── 路由 ──

    protected function resolveRouteInfo(): void
    {
        $this->controller = uncamelize($this->request->controller());
        $this->action     = $this->request->action();

        $groupStr    = strstr($this->controller, '.', true);
        $this->group = $groupStr !== false ? $groupStr : $this->controller;

        $this->routeUri = "{$this->controller}/$this->action";
    }

    // ── HTTP 方法校验 ──

    protected function checkMethodRules(): void
    {
        if (!isset($this->methodRules[$this->action])) return;

        $rule = $this->methodRules[$this->action];
        if (empty($rule)) return;

        $method = $this->request->method();
        if (is_array($rule) && in_array($method, $rule)) return;
        if (is_string($rule) && $rule == $method) return;

        throwError('illegal request method');
    }

    // ── 用户 ──

    protected function getUserId(): int
    {
        return (int)($this->store['uid'] ?? 0);
    }

    // ── 权限 ──

    protected function checkAction(string $menuPath, string $actionName = ''): bool
    {
        PermissionChecker::require($menuPath, $actionName);
        return true;
    }
}
