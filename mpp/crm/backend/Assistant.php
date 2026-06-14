<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\service\AgentService;

class Assistant extends BaseController
{
    protected $methodRules = ['chat' => 'POST'];

    public function chat()
    {
        if(!$this->checkAction('/crm.assistant/chat','AI对话')) return;
        $message = $this->request->param('message', '');
        $customerId = (int)$this->request->param('customer_id', 0);
        if (empty($message)) return $this->renderError('请输入问题');

        if ($customerId > 0) {
            $c = \think\facade\Db::name('crm_customer')->where('id', $customerId)->find();
            if ($c) {
                $message = "【当前客户：{$c['customer_name']}，ID={$customerId}】" . $message;
            }
        }

        $userId = $this->store['store_user_id'] ?? 0;
        $agent = new AgentService();
        $result = $agent->chat($message, $userId);

        if (!$result['success']) {
            return $this->renderError($result['error'] ?? 'AI 响应失败');
        }

        return $this->renderSuccess(['reply' => $result['data']['content'] ?? '']);
    }
}
