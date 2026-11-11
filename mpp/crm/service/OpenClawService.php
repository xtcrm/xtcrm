<?php

declare (strict_types=1);

namespace mpp\crm\service;

use think\facade\Db;

/**
 * OpenClaw AI 通信服务（Platform 版）
 *
 * 支持多节点路由 + 向后兼容单节点全局配置
 */
class OpenClawService
{
    /**
     * 发送消息（多节点 + 向后兼容）
     *
     * @param string $content  消息内容
     * @param array  $context  上下文（预留）
     * @param int|null $nodeId 节点ID，null 时走全局配置
     */
    public static function sendMessage(string $content, array $context = [], ?int $nodeId = null): array
    {
        if ($nodeId) {
            return self::sendToNode($content, $nodeId, $context);
        }
        return self::sendToDefault($content);
    }

    /**
     * 发送到指定节点
     */
    public static function sendToNode(string $content, int $nodeId, array $context = []): array
    {
        $node = \app\platform\model\ai\OpenClawNode::getById($nodeId);
        if (!$node) {
            return ['success' => false, 'data' => null, 'error' => '节点不存在'];
        }
        if (empty($node['gateway_url']) || empty($node['auth_token'])) {
            return ['success' => false, 'data' => null, 'error' => '节点未配置'];
        }

        // 远程节点直接走 HTTP，不调本地 CLI
        return self::callGateway($content, [
            'gateway_url' => $node['gateway_url'],
            'auth_token'  => $node['auth_token'],
            'agent_id'    => $node['agent_id'] ?: 'main',
        ]);
    }

    /**
     * 发送到默认节点（向后兼容，走 ai_setting 全局配置）
     */
    public static function sendToDefault(string $content): array
    {
        $cfg = self::getGlobalConfig();
        if (!$cfg['enabled']) {
            return ['success' => false, 'data' => null, 'error' => '未启用'];
        }

        // 优先 CLI proxy
        $proxyResult = self::callProxy($content);
        if ($proxyResult['success']) {
            return $proxyResult;
        }

        // 备用 Gateway webhook
        return self::callGateway($content, $cfg);
    }

    /**
     * 测试节点连接
     */
    public static function testNodeConnection(int $nodeId): array
    {
        return self::sendToNode('你好，请回复CRM连接测试成功', $nodeId);
    }

    /**
     * 测试默认连接
     */
    public static function testConnection(): array
    {
        return self::sendToDefault('你好，请回复CRM连接测试成功');
    }

    /**
     * 在线节点列表（供控制台下拉）
     */
    public static function getOnlineNodes(int $storeId): array
    {
        return \app\platform\model\ai\OpenClawNode::where('status', 1)
            ->field(['id', 'name'])
            ->order(['create_time' => 'desc'])
            ->select()
            ->toArray();
    }

    // ── 私有：传输层 ──

    /**
     * CLI 代理（同机同步调用）
     */
    private static function callProxy(string $content): array
    {
        $base = rtrim(($_SERVER['REQUEST_SCHEME'] ?? 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '127.0.0.1'), '/');
        $url  = $base . '/openclaw_proxy.php';
        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode(['message' => $content], JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 120,
                CURLOPT_CONNECTTIMEOUT => 3,
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code === 200) {
                $data  = json_decode($resp, true);
                $reply = $data['reply'] ?? '';
                if (!empty($reply) && $reply !== '无回复') {
                    return ['success' => true, 'data' => $reply, 'error' => ''];
                }
            }
        } catch (\Exception $e) {
        }
        return ['success' => false, 'data' => null, 'error' => 'proxy unavailable'];
    }

    /**
     * Gateway webhook 调用
     */
    private static function callGateway(string $content, array $cfg): array
    {
        // 优先调同步代理（18790），不可用时回退 hooks（18789）
        $syncUrl = preg_replace('/:\d+/', ':18790', rtrim($cfg['gateway_url'], '/'));
        $result = self::callSyncProxy($syncUrl, $content, $cfg);
        if ($result['success']) return $result;

        // 回退：异步 hooks
        $url = rtrim($cfg['gateway_url'], '/') . '/hooks/agent';
        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode([
                    'agentId' => $cfg['agent_id'] ?? 'main',
                    'message' => $content,
                ], JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . ($cfg['auth_token'] ?? ''),
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 300,
                CURLOPT_PROXY          => '',
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code >= 400) {
                return ['success' => false, 'data' => null, 'error' => "HTTP $code"];
            }
            $data = json_decode($resp, true);
            if (is_array($data) && !empty($data['ok'])) {
                return ['success' => true, 'data' => '已发送 (runId: ' . substr($data['runId'] ?? '', 0, 12) . '...)', 'error' => ''];
            }
            return ['success' => true, 'data' => $resp, 'error' => ''];
        } catch (\Exception $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * 流式调用同步代理，每收到一个 token 回调 $onToken
     * @return string 完整回复文本
     */
    public static function streamToNode(string $syncUrl, string $content, string $token, callable $onToken): string
    {
        $fullReply = '';
        try {
            $ch = curl_init($syncUrl);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode(['message' => $content], JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $token,
                ],
                CURLOPT_RETURNTRANSFER => false,
                CURLOPT_TIMEOUT        => 300,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_PROXY          => '',
                CURLOPT_WRITEFUNCTION  => function($ch, $data) use (&$fullReply, $onToken) {
                    $lines = explode("\n", $data);
                    foreach ($lines as $line) {
                        if (strpos($line, 'data: ') !== 0) continue;
                        $json = substr($line, 6);
                        $payload = json_decode($json, true);
                        if (!$payload) continue;
                        if (!empty($payload['token'])) {
                            $fullReply .= $payload['token'];
                            $onToken($payload['token']);
                        }
                    }
                    return strlen($data);
                },
            ]);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Exception $e) {}
        return $fullReply;
    }

    /**
     * 调用同步代理（Node.js HTTP → CLI 桥接）
     */
    private static function callSyncProxy(string $url, string $content, array $cfg): array
    {
        try {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode(['message' => $content], JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . ($cfg['auth_token'] ?? ''),
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 300,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_PROXY          => '',
            ]);
            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            if ($code === 200 && $resp) {
                $data = json_decode($resp, true);
                $reply = $data['reply'] ?? '';
                if (!empty($reply)) {
                    return ['success' => true, 'data' => $reply, 'error' => ''];
                }
            }
            return ['success' => false, 'data' => null, 'error' => "sync proxy: HTTP $code, err: $err"];
        } catch (\Exception $e) { return ['success' => false, 'data' => null, 'error' => 'sync proxy exception: ' . $e->getMessage()]; }
    }

    /**
     * 读取全局配置（ai_setting，向后兼容）
     */
    private static function getGlobalConfig(): array
    {
        $rows = Db::name('config')
            ->where('config_type', 'ai_setting')
            ->select()
            ->toArray();
        $cfg = [
            'enabled'     => false,
            'gateway_url' => 'http://127.0.0.1:18789',
            'auth_token'  => '',
            'agent_id'    => 'main',
        ];
        foreach ($rows as $r) {
            switch ($r['config_name']) {
                case 'enabled':     $cfg['enabled']     = ($r['config_value'] == '1'); break;
                case 'gateway_url': $cfg['gateway_url'] = $r['config_value'] ?: $cfg['gateway_url']; break;
                case 'auth_token':  $cfg['auth_token']  = $r['config_value']; break;
                case 'agent_id':    $cfg['agent_id']    = $r['config_value'] ?: $cfg['agent_id']; break;
            }
        }
        return $cfg;
    }
}
