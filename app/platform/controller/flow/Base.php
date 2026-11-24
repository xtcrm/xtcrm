<?php

declare(strict_types=1);

namespace app\platform\controller\flow;

use app\platform\auth\TokenService;
use app\platform\backend\BaseController;
use app\platform\flow\FlowException;
use think\facade\Log;

/**
 * 流程引擎后台控制器基类
 */
abstract class Base extends BaseController
{
    protected function actor(): array
    {
        $user = TokenService::getUser() ?: [];
        $id = (int)($user['store_user_id'] ?? 0) ?: (int)$this->getUserId();
        $name = (string)($user['real_name'] ?: ($user['user_name'] ?? ''));

        return [
            'id' => $id,
            'name' => $name,
            'dept_id' => (int)($user['department_id'] ?? 0),
            'is_super' => !empty($user['is_super']),
        ];
    }

    /**
     * 统一异常处理：业务异常直接提示，系统异常记日志
     */
    protected function call(callable $fn)
    {
        try {
            $data = $fn();
            return $this->renderSuccess($data);
        } catch (FlowException $e) {
            return $this->renderError($e->getMessage());
        } catch (\Throwable $e) {
            Log::error('[flow] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            return $this->renderError('流程操作失败：' . $e->getMessage());
        }
    }

    protected function formData(): array
    {
        // 优先读原始 body：ThinkPHP 默认过滤会把 JSON 里的引号转成 &quot;，导致 json_decode 失败
        $body = $this->request->getContent();
        if (is_string($body) && $body !== '') {
            $json = json_decode($body, true);
            if (is_array($json) && array_key_exists('form_data', $json)) {
                $fd = $json['form_data'];
                if (is_array($fd)) return $fd;
                if (is_string($fd)) {
                    $decoded = json_decode($fd, true);
                    if (is_array($decoded)) return $decoded;
                }
            }
        }

        // 兼容表单提交；若已被 htmlspecialchars 处理，先还原实体
        $raw = $this->request->param('form_data', []);
        if (is_string($raw)) {
            $plain = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $decoded = json_decode($plain, true);
            return is_array($decoded) ? $decoded : [];
        }
        return is_array($raw) ? $raw : [];
    }
}
