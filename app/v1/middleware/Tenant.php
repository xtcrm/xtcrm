<?php
namespace app\v1\middleware;

use cores\Request;

/**
 * /v1 网关 — 不校验 Session，从请求上下文取 store_id
 * store_id 由各 Controller 自行注入（opencrm 用 API Key，website 用 Token）
 */
class Tenant
{
    public function handle(Request $request, \Closure $next)
    {
        // 放行全部——鉴权下沉到各 Controller
        return $next($request);
    }
}
