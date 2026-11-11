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

        // 允许的域名白名单
        $allowOrigins = [
            'https://my.xtocn.com',
            'http://crm.dingxintax.com',
            'https://crm.dingxintax.com',
            'http://localhost:8080',
            'https://store.xtocn.com',
            'http://192.168.10.17:9998',
            'http://172.18.0.1:9998',
            'http://192.168.10.17:8210',
            'http://crm.xtocn.com',
            'https://crm.xtocn.com',
            'https://m.xtocn.com',
            'http://yg.xtocn.com',
            'http://super.xtocn.com',
            'http://192.168.10.17:9999',
            'http://192.168.10.17:10000',
            'http://localhost:9527',
            'http://localhost:5173',
            'http://localhost:9999',
            'http://192.168.10.17:5173',
            'http://192.168.1.5:9999',
            'http://192.168.1.5:4321',
            'http://localhost:4321',
            'http://127.0.0.1:4321',
            'http://yj.xtocn.com',
            'http://192.168.1.5:4322',
            'http://192.168.1.5:8866',
            'http://localhost:8866',
            'http://127.0.0.1:8866',
            'http://192.168.1.3:9999',
            'http://192.168.1.3:4321',
            'http://weshop.dev.xtocn.com',
            'http://127.0.0.1:4399',
            'http://192.168.1.3:9998',
            'http://192.168.1.3:8210',
            'http://localhost:8080' 
        ];
        
        $origin = request()->header('origin', '');
        $allowOrigin = $allowOrigins[0]; // 默认：不在白名单时回退到第一个
        if (in_array($origin, $allowOrigins)) {
            $allowOrigin = $origin;
        } else {
            // 官网多租户：通配子域名 *.web.xtocn.com / *.yj.xtocn.com
            $patterns = [
                '#^https?://[a-z0-9][a-z0-9-]*\.web\.xtocn\.com(:\d+)?$#i',
                '#^https?://[a-z0-9][a-z0-9-]*\.yj\.xtocn\.com(:\d+)?$#i',
            ];
            foreach ($patterns as $p) {
                if (preg_match($p, $origin)) { $allowOrigin = $origin; break; }
            }
        }

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