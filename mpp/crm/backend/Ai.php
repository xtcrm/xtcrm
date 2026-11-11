<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\service\AiConversationService;
use mpp\crm\service\IntentClassifier;
use mpp\crm\service\AiToolRegistry;
use mpp\crm\service\AgentService;

/**
 * AI 侧边面板控制器
 *  POST /crm.ai/chatStart    — 同步对话
 *  GET  /crm.ai/taskStatus   — 异步任务轮询
 *  GET  /crm.ai/tools         — 工具列表（MCP 同步）
 *  GET  /crm.ai/conversations — 会话列表
 *  POST /crm.ai/newConversation — 新建会话
 */
class Ai extends BaseController
{
    protected $methodRules = [
        'chatStart'     => 'POST',
        'taskStatus'    => 'GET',
        'tools'         => 'GET',
        'conversations' => 'GET',
        'resolve'       => 'GET',
        'messages'      => 'GET',
        'newConversation' => 'POST',
    ];

    protected function getUserId(): int
    {
        return $this->store['user']['user_id'] ?? ($this->store['uid'] ?? 0);
    }

    /** @var AiConversationService */
    private $convService;

    private function initConv(): AiConversationService
    {
        if (!$this->convService) {
            $this->convService = new AiConversationService($this->storeId, $this->getUserId());
        }
        return $this->convService;
    }

    // ============================================================
    //  POST /crm.ai/chatStart
    //  同步对话 — 完整 tool-calling 后返回结果
    // ============================================================
    public function chatStart()
    {
        $message = input('message', '', 'trim');
        $context = $this->request->param('context', []);
        $roleKey = input('role_key', '', 'trim');

        if (empty($message)) {
            return $this->renderError('消息不能为空');
        }

        $userId  = $this->getUserId();
        $storeId = $this->storeId;

        // 1. 意图分类
        if (empty($roleKey)) {
            $roleKey = IntentClassifier::classify($message);
        }

        // 2. 会话 + 上下文
        $conv = $this->initConv();
        $sessionId = $conv->resolveSession($context);

        // 3. 组装 system prompt + history
        $systemPrompt = $conv->buildSystemPrompt($roleKey, $context);
        $history = $conv->loadHistory($sessionId, 30);

        // 4. 执行对话（同步，复用现有 AgentService）
        $agentService = new AgentService();
        $agentService->setRoleKey($roleKey);
        $agentService->setUserId($userId);

        try {
            $result = $agentService->chatWithContext(
                $systemPrompt, $history, $message, $userId, $storeId
            );
        } catch (\Throwable $e) {
            return $this->renderError('AI 服务异常: ' . $e->getMessage());
        }

        if (!$result['success']) {
            return $this->renderError($result['error'] ?? '对话失败');
        }

        // 5. 持久化
        $conv->saveUserMessage($sessionId, $message, $context);
        $conv->saveAssistantMessage(
            $sessionId,
            $result['data']['content'] ?? '',
            $result['data']['tools'] ?? [],
            $roleKey
        );

        return $this->renderSuccess([
            'content'    => $result['data']['content'] ?? '',
            'tools'      => $result['data']['tools'] ?? [],
            'session_id' => $sessionId,
            'role_key'   => $roleKey,
            'role_name'  => IntentClassifier::getRoleName($roleKey),
        ], 'ok');
    }

    // ============================================================
    //  GET /crm.ai/taskStatus?task_id=xxx
    // ============================================================
    public function taskStatus()
    {
        $taskId = input('task_id', '', 'trim');
        if (empty($taskId)) {
            return $this->renderError('task_id 不能为空');
        }

        $conv = $this->initConv();
        $status = $conv->checkTaskStatus($taskId);

        return $this->renderSuccess($status, 'ok');
    }

    // ============================================================
    //  GET /crm.ai/tools
    // ============================================================
    public function tools()
    {
        $schemas = AiToolRegistry::getAllSchemas();
        return $this->renderSuccess($schemas, 'ok');
    }

    // ============================================================
    //  GET /crm.ai/resolve
    //  根据当前页面上下文解析会话 + 加载历史消息
    //  面板打开时调用，确保刷新后聊天记录不丢失
    // ============================================================
    public function resolve()
    {
        $context = $this->request->param('context', []);

        $conv = $this->initConv();
        $sessionId = $conv->resolveSession($context);
        $messages = $conv->loadMessages($sessionId);

        return $this->renderSuccess([
            'session_id' => $sessionId,
            'messages'   => $messages,
        ], 'ok');
    }

    // ============================================================
    //  GET /crm.ai/conversations?limit=20
    // ============================================================
    public function conversations()
    {
        $limit = (int)input('limit', 20);
        $conv  = $this->initConv();
        $list  = $conv->listSessions($limit);

        return $this->renderSuccess($list, 'ok');
    }

    // ============================================================
    //  GET /crm.ai/messages?session_id=xxx
    // ============================================================
    public function messages()
    {
        $sessionId = input('session_id', '', 'trim');
        if (empty($sessionId)) {
            return $this->renderError('session_id 不能为空');
        }

        $conv = $this->initConv();
        $list = $conv->loadMessages($sessionId);

        return $this->renderSuccess(['list' => $list], 'ok');
    }

    // ============================================================
    //  POST /crm.ai/newConversation
    // ============================================================
    public function newConversation()
    {
        $context = $this->request->param('context', []);
        $conv = $this->initConv();
        $sessionId = $conv->newSession($context);

        return $this->renderSuccess(['session_id' => $sessionId], 'ok');
    }
}
