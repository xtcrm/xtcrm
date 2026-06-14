<?php

declare (strict_types=1);

namespace app\platform\middleware;

use cores\Request;
use cores\exception\BaseException;
use app\platform\auth\TokenService;
use think\facade\Log;

/**
 * 平台中间件：租户识别与验证
 */
class Tenant
{
    const STORE_CACHE_TTL = 3600;
    const STORE_CACHE_PREFIX = 'store_info:';
    const INVALID_STORE_ID = 0;

    /**
     * @param Request $request
     * @param \Closure $next
     * @return mixed
     * @throws BaseException
     */
    public function handle(Request $request, \Closure $next)
    {
        if ($this->shouldSkipTenantValidation($request)) {
            return $next($request);
        }

        $storeId = TokenService::storeId();

        if ($this->shouldRejectRequest($storeId)) {
            $this->clearLoginState();
            $this->logSecurityEvent($request, $storeId);
            throw new BaseException(['message' => '请重新登录001', 'status' => 401]);
        }

        $request->setStoreId((int)$storeId);

        $loginUser = TokenService::getUser();
        if ($loginUser && !empty($loginUser['is_supplier']) && !empty($loginUser['supplier_id'])) {
            $request->setSupplierId((int)$loginUser['supplier_id']);
        }

        return $next($request);
    }

    private function shouldSkipTenantValidation(Request $request): bool
    {
        $pathInfo = $request->pathinfo();
        $method = $request->method();

        $skipRoutes = [
            'crm.passport/login'           => ['POST'],
            'crm.passport/logout'          => ['POST'],
            'passport/login'               => ['POST'],
            'passport/logout'              => ['POST'],
            'passport/wxworkCallback'      => ['GET'],
            'passport/wxworkLogin'         => ['GET'],
            'passport/getWxworkConfig'     => ['GET'],
        ];

        return isset($skipRoutes[$pathInfo]) && in_array($method, $skipRoutes[$pathInfo]);
    }

    private function shouldRejectRequest(?int $storeId): bool
    {
        return $storeId === null || $storeId === 0;
    }

    private function clearLoginState(): void
    {
        try {
            TokenService::logout();
        } catch (\Exception $e) {
            Log::error('[Store中间件] 清除登录状态失败', ['error' => $e->getMessage()]);
        }
    }

    private function logSecurityEvent(Request $request, ?int $storeId): void
    {
        Log::warning('[Store中间件] 租户验证失败', [
            'event_type' => 'tenant_validation_failed',
            'ip'         => $request->ip(),
            'url'        => $request->url(true),
            'store_id'   => $storeId,
            'timestamp'  => time(),
        ]);
    }
}
