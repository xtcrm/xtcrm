<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\service\NotificationService;

class Notification extends BaseController
{
    protected $methodRules = [
        'unread' => 'GET',
        'markRead' => 'POST',
        'markAllRead' => 'POST',
    ];

    protected function getUserId(): int
    {
        return $this->store['store_user_id'] ?? ($this->store['uid'] ?? 0);
    }

    /** 获取未读通知列表和数量 */
    public function unread()
    {
        $result = (new NotificationService())->getUnread($this->getUserId());
        return $this->renderSuccess($result['data']);
    }

    /** 标记单条已读 */
    public function markRead()
    {
        $id = $this->request->param('id', 0);
        $result = (new NotificationService())->markAsRead($id, $this->getUserId());
        if ($result['success']) return $this->renderSuccess([], '已读');
        return $this->renderError($result['error']);
    }

    /** 全部已读 */
    public function markAllRead()
    {
        $result = (new NotificationService())->markAllRead($this->getUserId());
        return $this->renderSuccess([], '全部已读');
    }
}
