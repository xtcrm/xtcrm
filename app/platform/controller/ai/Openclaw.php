<?php

declare (strict_types=1);

namespace app\platform\controller\ai;

use app\platform\backend\BaseController;
use app\platform\model\ai\OpenClawNode as NodeModel;
use mpp\crm\service\OpenClawService;
use think\facade\Db;

/**
 * AI 助手控制器（Platform 入口，s=/platform）
 */
class Openclaw extends BaseController
{
    protected $allowAllAction = [
        'ai.openclaw/script',
        'ai.openclaw/nodeActivate',
        'ai.openclaw/nodeHeartbeat',
    ];

    protected $methodRules = [
        'history'        => 'GET',
        'send'           => 'POST',
        'streamSend'     => 'POST',
        'testConnection' => 'POST',
        'settings'       => 'GET',
        'saveSettings'   => 'POST',
        'nodes'          => 'GET',
        'addNode'        => 'POST',
        'editNode'       => 'POST',
        'deleteNode'     => 'POST',
        'generateScript' => 'POST',
        'script'         => 'GET',
        'nodeActivate'   => 'POST',
        'nodeHeartbeat'  => 'POST',
    ];

    // ── 对话 ──

    /**
     * 流式发送消息给 AI（SSE）
     */
    public function streamSend()
    {
        $content = $this->request->param('content', '');
        $nodeId  = (int)$this->request->param('node_id', 0);

        if (empty($content)) {
            return $this->renderError('请输入消息内容');
        }
        if (!$nodeId) {
            return $this->renderError('请选择节点');
        }

        // 保存用户消息
        $userId = $this->store['store_user_id'] ?? 0;
        $now = time();
        Db::name('ai_chat_message')->insert([
            'store_id' => $this->storeId, 'user_id' => $userId,
            'node_id' => $nodeId, 'role' => 'user',
            'content' => $content, 'create_time' => $now,
        ]);

        $node = NodeModel::getById($nodeId);
        if (!$node || empty($node['gateway_url'])) {
            return $this->renderError('节点不可用');
        }

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        $syncUrl = preg_replace('/:\d+/', ':18790', rtrim($node['gateway_url'], '/'));
        $fullReply = OpenClawService::streamToNode($syncUrl, $content, $node['auth_token'], function($token) {
            echo "data: " . json_encode(['token' => $token], JSON_UNESCAPED_UNICODE) . "\n\n";
            if (ob_get_level()) { ob_flush(); }
            flush();
        });

        // 保存 AI 回复
        if (!empty($fullReply)) {
            Db::name('ai_chat_message')->insert([
                'store_id' => $this->storeId, 'user_id' => $userId,
                'node_id' => $nodeId, 'role' => 'ai',
                'content' => $fullReply, 'create_time' => $now + 1,
            ]);
        }

        echo "data: " . json_encode(['done' => true]) . "\n\n";
        if (ob_get_level()) { ob_flush(); }
        flush();
    }

    /**
     * 发送消息给 AI
     */
    public function send()
    {
        $content = $this->request->param('content', '');
        $nodeId  = (int)$this->request->param('node_id', 0);

        if (empty($content)) {
            return $this->renderError('请输入消息内容');
        }
        if (!$nodeId) {
            return $this->renderError('请选择节点');
        }

        // 保存用户消息
        $userId = $this->store['store_user_id'] ?? 0;
        $now = time();
        Db::name('ai_chat_message')->insert([
            'store_id'    => $this->storeId,
            'user_id'     => $userId,
            'node_id'     => $nodeId,
            'role'        => 'user',
            'content'     => $content,
            'create_time' => $now,
        ]);

        set_time_limit(300);
        $result = OpenClawService::sendToNode($content, $nodeId);

        if ($result['success']) {
            $reply = $result['data'];
            // 保存 AI 回复
            Db::name('ai_chat_message')->insert([
                'store_id'    => $this->storeId,
                'user_id'     => $userId,
                'node_id'     => $nodeId,
                'role'        => 'ai',
                'content'     => $reply,
                'create_time' => $now + 1,
            ]);
            return $this->renderSuccess(['reply' => $reply], '');
        }
        return $this->renderError($result['error']);
    }

    /**
     * 聊天记录
     */
    public function history()
    {
        $nodeId = (int)$this->request->param('node_id', 0);
        if (!$nodeId) {
            return $this->renderSuccess(['list' => []]);
        }
        $list = Db::name('ai_chat_message')
            ->where('store_id', $this->storeId)
            ->where('node_id', $nodeId)
            ->order('create_time', 'asc')
            ->limit(200)
            ->select()
            ->toArray();
        return $this->renderSuccess(['list' => $list]);
    }

    /**
     * 测试连接
     */
    public function testConnection()
    {
        $nodeId = (int)$this->request->param('node_id', 0);
        if ($nodeId) {
            $result = OpenClawService::testNodeConnection($nodeId);
        } else {
            $result = OpenClawService::testConnection();
        }
        if ($result['success']) {
            return $this->renderSuccess(['reply' => $result['data']], '连接成功');
        }
        return $this->renderError($result['error']);
    }

    // ── 设置 ──

    /**
     * 读取 AI 设置
     */
    public function settings()
    {
        $rows = Db::name('config')
            ->where('config_type', 'ai_setting')
            ->select()
            ->toArray();
        $settings = [];
        foreach ($rows as $r) {
            $settings[$r['config_name']] = $r['config_value'];
        }
        return $this->renderSuccess([
            'enabled'     => $settings['enabled'] ?? '0',
            'gateway_url' => $settings['gateway_url'] ?? 'http://127.0.0.1:18789',
            'auth_token'  => $settings['auth_token'] ?? '',
            'agent_id'    => $settings['agent_id'] ?? 'main',
        ]);
    }

    /**
     * 保存 AI 设置
     */
    public function saveSettings()
    {
        $all  = $this->postData();
        $data = $all['form'] ?? $all;
        $fields = ['enabled', 'gateway_url', 'auth_token', 'agent_id'];
        $now = time();

        Db::startTrans();
        try {
            foreach ($fields as $key) {
                $value = strval($data[$key] ?? '');
                $row = Db::name('config')
                    ->where('config_type', 'ai_setting')
                    ->where('config_name', $key)
                    ->find();
                if ($row) {
                    Db::name('config')->where('id', $row['id'])->update([
                        'config_value' => $value, 'update_time' => $now,
                    ]);
                } else {
                    Db::name('config')->insert([
                        'store_id'    => 0,
                        'config_type' => 'ai_setting',
                        'config_name' => $key,
                        'config_value'=> $value,
                        'sort_order'  => 0,
                        'is_system'   => 1,
                        'status'      => 1,
                        'create_time' => $now,
                        'update_time' => $now,
                    ]);
                }
            }
            Db::commit();
            return $this->renderSuccess([], '保存成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->renderError('保存失败：' . $e->getMessage());
        }
    }

    // ── 节点管理 ──

    /**
     * 节点列表
     */
    public function nodes()
    {
        $model   = new NodeModel;
        $keyword = $this->request->param('keyword', '');
        $status  = $this->request->param('status', '');
        $list    = $model->getList(['keyword' => $keyword, 'status' => $status]);
        return $this->renderSuccess(['list' => $list]);
    }

    /**
     * 新增节点
     */
    public function addNode()
    {
        $all  = $this->postData();
        $data = $all['form'] ?? $all;

        if (empty($data['name'])) {
            return $this->renderError('节点名称不能为空');
        }

        $model = new NodeModel;
        $saveData = [
            'name'       => trim($data['name']),
            'agent_id'   => $data['agent_id'] ?: 'main',
            'gateway_url'=> $data['gateway_url'] ?? '',
            'remark'     => $data['remark'] ?? '',
        ];

        if (!$model->add($saveData)) {
            return $this->renderError('新增失败');
        }

        // 新增后返回完整信息，auth_token 和 reg_token 只展示一次
        $node = NodeModel::getById((int)$model->id);
        return $this->renderSuccess([
            'id'               => $node['id'],
            'name'             => $node['name'],
            'auth_token'       => $node['auth_token'],
            'reg_token'        => $node['reg_token'],
            'reg_token_expire' => $node['reg_token_expire'],
        ], '节点创建成功');
    }

    /**
     * 编辑节点
     */
    public function editNode()
    {
        $all  = $this->postData();
        $data = $all['form'] ?? $all;

        $id = (int)($data['id'] ?? 0);
        if (!$id) {
            return $this->renderError('参数错误');
        }

        $node = NodeModel::getById($id);
        if (!$node) {
            return $this->renderError('节点不存在');
        }

        $model = new NodeModel;
        $model->id = $id;
        $model->edit([
            'name'       => trim($data['name'] ?? $node['name']),
            'agent_id'   => $data['agent_id'] ?? $node['agent_id'],
            'gateway_url'=> $data['gateway_url'] ?? $node['gateway_url'],
            'remark'     => $data['remark'] ?? $node['remark'],
        ]);

        return $this->renderSuccess([], '保存成功');
    }

    /**
     * 删除节点
     */
    public function deleteNode()
    {
        $all  = $this->postData();
        $id   = (int)($all['id'] ?? 0);
        if (!$id) {
            return $this->renderError('参数错误');
        }

        $node = NodeModel::getById($id);
        if (!$node) {
            return $this->renderError('节点不存在');
        }

        $model = new NodeModel;
        $model->id = $id;
        if (!$model->setDelete()) {
            return $this->renderError('删除失败');
        }
        return $this->renderSuccess([], '删除成功');
    }

    /**
     * 生成安装脚本
     */
    public function generateScript()
    {
        $all = $this->postData();
        $id  = (int)($all['id'] ?? 0);
        if (!$id) {
            return $this->renderError('参数错误');
        }

        $node = NodeModel::getById($id);
        if (!$node) {
            return $this->renderError('节点不存在');
        }

        // 每次生成脚本都刷新 reg_token，避免过期或已被消耗
        $regToken = NodeModel::generateRegToken();
        $model = new NodeModel;
        $model->id = $id;
        $model->where('id', $id)->update([
            'reg_token'        => $regToken['token'],
            'reg_token_expire'  => $regToken['expire'],
            'update_time'       => time(),
        ]);
        $node['reg_token']        = $regToken['token'];
        $node['reg_token_expire'] = $regToken['expire'];

        $crmUrl = rtrim(($this->request->domain() ?: 'https://my.xtocn.com'), '/');
        $script = $this->buildInstallScript($crmUrl, $node);

        $oneLiner = "bash <(curl -fsSL \"{$crmUrl}/api/ai.openclaw/script?token={$node['reg_token']}\")";

        return $this->renderSuccess([
            'script'    => $script,
            'one_liner' => $oneLiner,
        ]);
    }

    // ── 公开回调（无需登录） ──

    /**
     * 返回安装脚本文本（供 bash <(curl ...) 远程调用，reg_token 鉴权）
     */
    public function script()
    {
        $token = $this->request->param('token', '');
        if (empty($token)) {
            return response('missing token', 400)->contentType('text/plain');
        }

        $node = NodeModel::findByRegToken($token);
        if (!$node) {
            return response('invalid or expired token', 401)->contentType('text/plain');
        }

        $crmUrl = rtrim(($this->request->domain() ?: 'https://my.xtocn.com'), '/');
        $script = $this->buildInstallScript($crmUrl, $node);

        return response($script, 200)->contentType('text/plain');
    }

    /**
     * 节点激活（安装脚本回调，token 鉴权）
     */
    public function nodeActivate()
    {
        $token      = $this->request->param('token', '');
        $gatewayUrl = $this->request->param('gateway_url', '');

        if (empty($token)) {
            return $this->renderError('缺少认证 Token');
        }

        $node = NodeModel::findByAuthToken($token);
        if (!$node) {
            return $this->renderError('无效的 Token');
        }

        $model = new NodeModel;
        $model->id = $node['id'];
        $model->activate($gatewayUrl ?: $node['gateway_url']);

        return $this->renderSuccess([
            'node_id'     => $node['id'],
            'node_name'   => $node['name'],
            'auth_token'  => $node['auth_token'],
        ], '节点激活成功');
    }

    /**
     * 节点心跳（token 鉴权）
     */
    public function nodeHeartbeat()
    {
        $token = $this->request->param('token', '');

        if (empty($token)) {
            return $this->renderError('缺少认证 Token');
        }

        $node = NodeModel::findByAuthToken($token);
        if (!$node) {
            return $this->renderError('无效的 Token');
        }

        $model = new NodeModel;
        $model->id = $node['id'];
        $model->heartbeat();

        return $this->renderSuccess(['time' => time()], 'ok');
    }

    // ── 私有 ──

    /**
     * 构建 bash 安装脚本
     */
    private function buildInstallScript(string $crmUrl, array $node): string
    {
        $token  = $node['auth_token'];
        $apiKey = $node['api_key'] ?? '';

        $skillSection = '';
        $totalSteps = 6;
        $b  = 4;   // Gateway 步骤号
        $b1 = 5;   // Sync Proxy 步骤号
        $b2 = 6;   // 注册 步骤号
        if (!empty($apiKey)) {
            $totalSteps = 7;
            $skillSection = "\n# 4. 安装 CRM 数据 Skill\necho \"[4/{$totalSteps}] 安装 CRM 数据 Skill...\"\nopenclaw skills install @xtoyun/opencrm\nexport OPENCRM_KEY=\"{$apiKey}\"\necho \"  OPENCRM_KEY 已配置\"";
            $b  = 5; $b1 = 6; $b2 = 7;
        }

        return <<<BASH
#!/bin/bash
set -e
# === XTOCN CRM OpenClaw 接入脚本 ===
# 生成时间: {$crmUrl} 后台 → 节点管理 → 安装脚本
# 安全提示: NODE_TOKEN 为节点认证凭据，请勿泄露

CRM_URL="{$crmUrl}"
NODE_TOKEN="{$token}"

echo "=== 雄韬 CRM OpenClaw 接入脚本 ==="

# 1. Node.js (>=24)
if ! command -v node &>/dev/null; then
    echo "[1/{$totalSteps}] 安装 Node.js v24..."
    if command -v apt-get &>/dev/null; then
        curl -fsSL https://deb.nodesource.com/setup_24.x | bash -
        apt-get install -y nodejs
    elif command -v yum &>/dev/null; then
        curl -fsSL https://rpm.nodesource.com/setup_24.x | bash -
        yum install -y nodejs
    else
        echo "  请手动安装 Node.js >=24: https://nodejs.org"
        exit 1
    fi
else
    echo "[1/{$totalSteps}] Node.js 已安装: \$(node -v)"
fi

# 2. OpenClaw
if ! command -v openclaw &>/dev/null; then
    echo "[2/{$totalSteps}] 安装 OpenClaw..."
    npm install -g openclaw
    openclaw onboard --non-interactive --accept-risk
    openclaw daemon install 2>/dev/null || echo "  跳过 daemon（可能已安装或非 systemd 系统）"
else
    echo "[2/{$totalSteps}] OpenClaw 已安装"
fi

# 3. Gateway 认证
echo "[3/{$totalSteps}] 配置 Gateway 认证..."
openclaw config set gateway.auth.token "\$NODE_TOKEN"
openclaw config set hooks.token "\$NODE_TOKEN"
{$skillSection}

# {$b}. 重启 Gateway 使配置生效
echo "[{$b}/{$totalSteps}] 重启 Gateway..."
if openclaw daemon status &>/dev/null; then
    openclaw daemon restart
else
    openclaw daemon install
    openclaw daemon start
fi
sleep 3

# {$b1}. 启动同步代理 (HTTP → CLI 桥接，CRM 通过此端口同步调用 Agent)
echo "[{$b1}/{$totalSteps}] 启动同步代理..."
lsof -ti:18790 | xargs kill -9 2>/dev/null || fuser -k 18790/tcp 2>/dev/null || true
cat > /tmp/openclaw-sync-proxy.js << 'SYNCEOF'
const http = require('http');
const { execFile } = require('child_process');
const fs = require('fs'), os = require('os'), path = require('path');
const PORT = 18790;
const TOKEN = process.env.OPENCLAW_SYNC_TOKEN || '';

http.createServer((req, res) => {
  if (req.method !== 'POST') { res.writeHead(405); return res.end(); }
  if (TOKEN && req.headers['authorization'] !== 'Bearer ' + TOKEN) {
    res.writeHead(401); return res.end(JSON.stringify({ error: 'unauthorized' }));
  }
  let body = '';
  req.on('data', c => body += c);
  req.on('end', () => {
    try {
      const { message } = JSON.parse(body);
      if (!message || typeof message !== 'string') {
        res.writeHead(400); return res.end(JSON.stringify({ error: 'message required' }));
      }
      const tmpFile = path.join(os.tmpdir(), 'oc-msg-' + process.pid + '-' + Date.now() + '.txt');
      fs.writeFileSync(tmpFile, message, 'utf-8');
      execFile('openclaw', ['agent', '--agent', 'main', '--message-file', tmpFile], {
        timeout: 300000, maxBuffer: 10 * 1024 * 1024
      }, (err, stdout) => {
        try { fs.unlinkSync(tmpFile); } catch {}
        if (err) {
          res.writeHead(500); res.end(JSON.stringify({ error: err.message }));
          return;
        }
        const reply = (stdout || '').replace(/^[│◇].*\n/gm, '').replace(/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/g, '').trim();
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ reply: reply || '无回复' }));
      });
    } catch(e) {
      res.writeHead(500); res.end(JSON.stringify({ error: e.message }));
    }
  });
}).listen(PORT, () => console.log('sync proxy ready on ' + PORT));
SYNCEOF

# 注册为 systemd 服务（自动重启 + 开机启动）
cat > /etc/systemd/system/openclaw-sync-proxy.service << UNITEOF
[Unit]
Description=OpenClaw Sync Proxy for CRM
After=network.target
[Service]
Type=simple
Environment="OPENCLAW_SYNC_TOKEN=\$NODE_TOKEN"
ExecStart=/usr/bin/node /tmp/openclaw-sync-proxy.js
Restart=always
RestartSec=10
[Install]
WantedBy=multi-user.target
UNITEOF
systemctl daemon-reload
systemctl enable openclaw-sync-proxy
systemctl restart openclaw-sync-proxy
sleep 1
echo "  同步代理已启动 (systemd)"

# {$b2}. 注册节点到 CRM
echo "[{$b2}/{$totalSteps}] 注册节点..."
NODE_IP=\$(curl -s --connect-timeout 5 -4 ifconfig.me 2>/dev/null || curl -s --connect-timeout 5 ip.sb 2>/dev/null || hostname -I 2>/dev/null | awk '{print \$1}')
curl -s --retry 3 --retry-delay 2 -X POST "\${CRM_URL}/api/ai.openclaw/nodeActivate" \\
  -H "Content-Type: application/json" \\
  -d "{\"token\":\"\$NODE_TOKEN\",\"gateway_url\":\"http://\${NODE_IP}:18790\"}"

echo ""
echo "✅ 接入完成！Gateway: http://\${NODE_IP}:18789 | Sync Proxy: http://\${NODE_IP}:18790"
echo "   节点已注册到 CRM，可在 AI 控制台中与该节点对话。"
BASH;
    }
}
