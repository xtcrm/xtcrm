<?php
namespace mpp\crm\api;

use mpp\crm\service\AgentService;

class Assistant extends Base
{
    protected $methodRules = ['chat' => 'POST'];

    public function chat()
    {
        $message = $this->request->param('message', '');
        $customerId = (int)$this->request->param('customer_id', 0);
        if (empty($message)) return $this->renderError('请输入问题');

        // 如有客户上下文，前置注入
        if ($customerId > 0) {
            $c = \think\facade\Db::name('crm_customer')->where('id', $customerId)->find();
            if ($c) {
                $message = "【当前客户：{$c['customer_name']}，ID={$customerId}】" . $message;
            }
        }

        $agent = new AgentService();
        $result = $agent->chat($message, $this->userId);

        if (!$result['success']) {
            return $this->renderError($result['error'] ?? 'AI 响应失败');
        }

        return $this->renderSuccess([
            'reply' => $result['data']['content'] ?? '',
        ]);
    }
}
