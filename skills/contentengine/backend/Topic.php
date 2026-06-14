<?php
namespace skills\contentengine\backend;

use app\platform\backend\BaseController;
use skills\contentengine\service\TopicScanner;
use think\facade\Db;

class Topic extends BaseController
{
    protected $methodRules = [
        'lists' => 'GET', 'scan' => 'POST', 'approve' => 'POST', 'add' => 'POST',
    ];

    public function lists()
    {
        if (!$this->checkAction('/crm.topic/scan', '查看选题')) return;
        $params = $this->request->param();
        $query = Db::name('crm_content_topic')->where('store_id', $this->storeId);
        if (!empty($params['status'])) {
            // 支持逗号分隔多状态：2,3 → status IN (2,3)
            if (strpos($params['status'], ',') !== false) {
                $statuses = array_map('intval', explode(',', $params['status']));
                $query->whereIn('status', $statuses);
            } else {
                $query->where('status', (int)$params['status']);
            }
        }
        $list = $query->order(['create_time' => 'desc'])->paginate($params)->toArray();
        return $this->renderSuccess(compact('list'));
    }

    public function scan()
    {
        if (!$this->checkAction('/crm.topic/scan', 'AI扫描选题')) return;
        $agentId = $this->request->param('agent_id', '');
        $result = (new TopicScanner())->scan($this->storeId, $agentId);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data'], "新增{$result['data']['added']}条选题");
    }

    public function add()
    {
        if (!$this->checkAction('/crm.topic/scan', 'AI扫描选题')) return;
        $data = $this->postData();
        if (empty($data['title'])) return $this->renderError('选题标题不能为空');
        Db::name('crm_content_topic')->insert([
            'store_id' => $this->storeId,
            'title' => $data['title'],
            'content_type' => $data['content_type'] ?? 'article',
            'source_type' => '手动',
            'keywords' => $data['keywords'] ?? '',
            'angle' => $data['angle'] ?? '',
            'priority' => $data['priority'] ?? 3,
            'status' => 2, // 直接通过
            'creator_uid' => $this->getUserId(),
            'create_time' => time(),
            'update_time' => time(),
        ]);
        return $this->renderSuccess([], '添加成功');
    }

    public function approve()
    {
        if (!$this->checkAction('/crm.topic/approve', '审核选题')) return;
        $id = $this->request->param('id', 0);
        $status = $this->request->param('status', 0); // 2通过 5拒绝
        if (!in_array((int)$status, [2, 5])) return $this->renderError('无效状态');
        Db::name('crm_content_topic')->where('id', (int)$id)->where('store_id', $this->storeId)
            ->update(['status' => (int)$status, 'reviewer_uid' => $this->getUserId(), 'update_time' => time()]);
        return $this->renderSuccess([], $status == 2 ? '已通过' : '已拒绝');
    }
}
