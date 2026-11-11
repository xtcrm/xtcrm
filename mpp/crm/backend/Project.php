<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\service\ProjectService;
use mpp\crm\service\ParticipantService;

/**
 * 服务项目
 *
 * URL：s=/platform/crm.project/{action}
 */
class Project extends BaseController
{
    protected $methodRules = [
        'lists' => 'GET',
        'detail' => 'GET',
        'add' => 'POST',
        'edit' => 'POST',
        'cancel' => 'POST',
        'archive' => 'POST',
        'aiSummary' => 'POST',
        'start' => 'POST',
        'complete' => 'POST',
        'reopen' => 'POST',
        'participants' => 'GET',
        'addParticipant' => 'POST',
        'removeParticipant' => 'POST',
        'updateParticipant' => 'POST',
        'submitReview' => 'POST',
        'assignReviewer' => 'POST',
        'reviewList' => 'GET',
        'reviewPass' => 'POST',
        'reviewReject' => 'POST',
        'reviewLog' => 'GET',
    ];

    protected function getUserId(): int
    {
        return (int)($this->store['store_user_id'] ?? ($this->store['uid'] ?? 0));
    }

    public function lists()
    {
        if (!$this->checkAction('/crm/project/lists', '项目列表')) return;
        $result = (new ProjectService())->getList($this->request->param(), (int)$this->storeId, $this->getUserId());
        return $this->renderSuccess($result);
    }

    public function detail()
    {
        if (!$this->checkAction('/crm/project/detail', '项目详情')) return;
        $id = (int)$this->request->param('id', 0);
        $result = (new ProjectService())->detail($id, (int)$this->storeId, $this->getUserId());
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data']);
    }

    public function add()
    {
        if (!$this->checkAction('/crm/project/add', '新增项目')) return;
        $result = (new ProjectService())->add($this->postData(), (int)$this->storeId, $this->getUserId());
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data'], '创建成功');
    }

    public function edit()
    {
        if (!$this->checkAction('/crm/project/edit', '编辑项目')) return;
        $id = (int)$this->request->param('id', 0);
        $data = $this->postData();
        unset($data['id']);
        $result = (new ProjectService())->edit($id, $data, (int)$this->storeId, $this->getUserId());
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess([], '保存成功');
    }

    public function cancel()
    {
        if (!$this->checkAction('/crm/project/cancel', '取消项目')) return;
        $id = (int)$this->request->param('id', 0);
        $reason = trim((string)$this->request->param('reason', ''));
        $result = (new ProjectService())->cancel($id, $reason, (int)$this->storeId, $this->getUserId());
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess([], '已取消');
    }

    public function aiSummary()
    {
        if (!$this->checkAction('/crm/project/detail', '项目详情')) return;
        $result = (new ProjectService())->aiSummary((int)$this->request->param('id', 0), (int)$this->storeId);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data']);
    }

    public function start()
    {
        if (!$this->checkAction('/crm/project/start', '开始项目')) return;
        $result = (new ProjectService())->changeProjectStatus((int)$this->request->param('id', 0), 'start', (int)$this->storeId, $this->getUserId());
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess([], '项目已开始');
    }

    public function complete()
    {
        if (!$this->checkAction('/crm/project/complete', '完成项目')) return;
        $result = (new ProjectService())->changeProjectStatus((int)$this->request->param('id', 0), 'complete', (int)$this->storeId, $this->getUserId());
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess([], '项目已完成');
    }

    public function reopen()
    {
        if (!$this->checkAction('/crm/project/reopen', '重新打开项目')) return;
        $result = (new ProjectService())->changeProjectStatus((int)$this->request->param('id', 0), 'reopen', (int)$this->storeId, $this->getUserId());
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess([], '项目已重新打开');
    }

    public function archive()
    {
        if (!$this->checkAction('/crm/project/archive', '归档项目')) return;
        $id = (int)$this->request->param('id', 0);
        $result = (new ProjectService())->archive($id, (int)$this->storeId, $this->getUserId());
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess([], '已归档');
    }

    public function participants()
    {
        if (!$this->checkAction('/crm/project/participants', '管理参与人')) return;
        $projectId = (int)$this->request->param('project_id', 0);
        return $this->renderSuccess([
            'list' => (new ParticipantService())->listByProject($projectId, (int)$this->storeId),
        ]);
    }

    public function addParticipant()
    {
        if (!$this->checkAction('/crm/project/participants', '管理参与人')) return;
        $data = $this->postData();
        $projectId = (int)($data['project_id'] ?? 0);
        $userIds = $data['user_ids'] ?? ($data['user_id'] ?? []);
        if (is_string($userIds)) $userIds = array_filter(explode(',', $userIds));
        $userIds = array_map('intval', (array)$userIds);
        if (empty($userIds)) return $this->renderError('请选择参与人');

        $result = (new ParticipantService())->addMany(
            $projectId,
            $userIds,
            (int)($data['permission'] ?? 1),
            (int)($data['source'] ?? 1),
            $this->getUserId(),
            (int)$this->storeId,
            (string)($data['remark'] ?? '')
        );
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data'], '已添加');
    }

    public function updateParticipant()
    {
        if (!$this->checkAction('/crm/project/participants', '管理参与人')) return;
        $data = $this->postData();
        $result = (new ParticipantService())->updatePermission(
            (int)($data['project_id'] ?? 0),
            (int)($data['user_id'] ?? 0),
            (int)($data['permission'] ?? 1),
            $this->getUserId(),
            (int)$this->storeId
        );
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess([], '权限已更新');
    }

    // ------------------------------------------------------------------
    // 审核（全检 · 指派制）
    // ------------------------------------------------------------------

    public function submitReview()
    {
        if (!$this->checkAction('/crm/project/submitReview', '提交审核')) return;
        $result = (new ProjectService())->submitReview(
            (int)$this->request->param('id', 0), (int)$this->storeId, $this->getUserId()
        );
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data'], '已送审');
    }

    public function assignReviewer()
    {
        if (!$this->checkAction('/crm/project/assignReviewer', '指派审核人')) return;
        $data = $this->postData();
        $result = (new ProjectService())->assignReviewer(
            (int)($data['project_id'] ?? 0),
            (int)($data['reviewer_user_id'] ?? 0),
            (int)$this->storeId,
            $this->getUserId()
        );
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess([], '已指派');
    }

    public function reviewList()
    {
        if (!$this->checkAction('/crm/project/reviewLog', '审核记录')) return;
        $result = (new ProjectService())->getReviewList($this->request->get(), (int)$this->storeId);
        return $this->renderSuccess($result['data']);
    }

    public function reviewPass()
    {
        if (!$this->checkAction('/crm/project/reviewPass', '审核通过')) return;
        $data = $this->postData();
        $result = (new ProjectService())->review(
            (int)($data['review_id'] ?? 0), true, '', (int)$this->storeId, $this->getUserId()
        );
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess([], '审核通过');
    }

    public function reviewReject()
    {
        if (!$this->checkAction('/crm/project/reviewReject', '审核退回')) return;
        $data = $this->postData();
        $result = (new ProjectService())->review(
            (int)($data['review_id'] ?? 0), false, (string)($data['reason'] ?? ''),
            (int)$this->storeId, $this->getUserId()
        );
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess([], '已退回');
    }

    public function reviewLog()
    {
        if (!$this->checkAction('/crm/project/reviewLog', '审核记录')) return;
        $result = (new ProjectService())->getReviewLog(
            (int)$this->request->param('project_id', 0), (int)$this->storeId
        );
        return $this->renderSuccess($result['data']);
    }

    public function removeParticipant()
    {
        if (!$this->checkAction('/crm/project/participants', '管理参与人')) return;
        $data = $this->postData();
        $result = (new ParticipantService())->remove(
            (int)($data['project_id'] ?? 0),
            (int)($data['user_id'] ?? 0),
            $this->getUserId(),
            (int)$this->storeId,
            (string)($data['reason'] ?? '')
        );
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess([], '已移出');
    }
}
