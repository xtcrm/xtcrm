<?php
declare (strict_types=1);

namespace cores\middleware;

use Closure;
use think\Config;
use think\Request;
use think\Response;

/**
 * 跨域请求支持
 * Class AllowCrossDomain
 * @package cores\middleware 
 */
class AllowCrossDomain
{
    // cookie的所属域名
    protected $cookieDomain; 

    /**
     * 构造方法
     * AllowCrossDomain constructor.
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->cookieDomain = $config->get('cookie.domain', '');
    }

    /**
     * 获取允许跨域的header参数 [自定义]
     * @return array
     */
    private function getCustomHeader(): array
    {
        return [
            'Access-Token',
            'storeId',
            'platform',
        ];
    }

    /**
     * 获取允许跨域的header参数
     * @return array
     */
    private function getHeader(): array
    {
        $headers = array_merge([
            'Authorization', 'Content-Type', 'X-CSRF-TOKEN', 'X-Requested-With',
            'If-Match', 'If-Modified-Since', 'If-None-Match', 'If-Unmodified-Since'
        ], $this->getCustomHeader());

        // 允许的域名白名单（通过环境变量 CORS_ALLOW_ORIGINS 配置，逗号分隔）
        $allowOrigins = array_filter(explode(',', env('cors.allow_origins', 'http://localhost:8080')));
        
        $origin = request()->header('origin', '');
        $allowOrigin = in_array($origin, $allowOrigins) ? $origin : $allowOrigins[0];

        return [
            // 只允许白名单域名访问
            'Access-Control-Allow-Origin' => $allowOrigin,
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age' => 1800,
            'Access-Control-Allow-Methods' => 'GET, POST, PATCH, PUT, DELETE, OPTIONS',
            'Access-Control-Allow-Headers' => implode(',', $headers),
        ];
    }

    /**
     * 允许跨域请求
     * @access public
     * @param Request $request
     * @param Closure $next
     * @param array|null $header
     * @return Response
     */
    public function handle(Request $request, Closure $next, ?array $header = []): Response
    {
        $header = !empty($header) ? array_merge($this->getHeader(), $header) : $this->getHeader();
        if (!isset($header['Access-Control-Allow-Origin'])) {
            $origin = $request->header('origin');

            if ($origin && ('' == $this->cookieDomain || strpos($origin, $this->cookieDomain))) {
                $header['Access-Control-Allow-Origin'] = $origin;
            } else {
                $header['Access-Control-Allow-Origin'] = '*';
            }
        }
        return $next($request)->header($header);
    }
}