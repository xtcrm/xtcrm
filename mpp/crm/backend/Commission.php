<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\service\CommissionService;

/**
 * 业绩分账
 *
 * URL：s=/platform/crm.commission/{action}
 */
class Commission extends BaseController
{
    protected $methodRules = [
        'lists'          => 'GET',
        'export'         => 'GET',
        'summary'        => 'GET',
        'ratios'         => 'GET',
        'overrides'      => 'GET',
        'saveRule'       => 'POST',
        'applyOverride'  => 'POST',
        'approveOverride' => 'POST',
    ];

    protected function getUserId(): int
    {
        return (int)($this->store['store_user_id'] ?? ($this->store['uid'] ?? 0));
    }

    public function lists()
    {
        if (!$this->checkAction('/crm/commission/lists', '查询业绩')) return;
        $result = (new CommissionService())->getRecords($this->request->param(), (int)$this->storeId);
        return $this->renderSuccess($result['data']);
    }

    /** 导出用：一次取足量明细（前端转 CSV） */
    public function export()
    {
        if (!$this->checkAction('/crm/commission/export', '导出业绩')) return;
        $params = $this->request->param();
        $params['page'] = 1;
        $params['list_rows'] = 200;
        $result = (new CommissionService())->getRecords($params, (int)$this->storeId);
        return $this->renderSuccess($result['data']);
    }

    public function summary()
    {
        if (!$this->checkAction('/crm/commission/lists', '查询业绩')) return;
        $result = (new CommissionService())->summary($this->request->param(), (int)$this->storeId);
        return $this->renderSuccess($result['data']);
    }

    public function ratios()
    {
        if (!$this->checkAction('/crm/commission/lists', '查询业绩')) return;
        $result = (new CommissionService())->getRatiosAll((int)$this->storeId);
        return $this->renderSuccess($result['data']);
    }

    public function overrides()
    {
        if (!$this->checkAction('/crm/commission/lists', '查询业绩')) return;
        $result = (new CommissionService())->getOverrideList($this->request->param(), (int)$this->storeId);
        return $this->renderSuccess($result['data']);
    }

    public function saveRule()
    {
        if (!$this->checkAction('/crm/commission/saveRule', '保存分账规则')) return;
        $result = (new CommissionService())->saveRatios($this->postData(), (int)$this->storeId);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess([], '已保存');
    }

    public function applyOverride()
    {
        if (!$this->checkAction('/crm/commission/applyOverride', '提交比例覆盖')) return;
        $result = (new CommissionService())->applyOverride(
            $this->postData(), (int)$this->storeId, $this->getUserId()
        );
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data'], '已提交，等待审批');
    }

    public function approveOverride()
    {
        if (!$this->checkAction('/crm/commission/approveOverride', '审批比例覆盖')) return;
        $data = $this->postData();
        $pass = (int)($data['pass'] ?? 1) === 1;
        $result = (new CommissionService())->approveOverride(
            (int)($data['id'] ?? 0), (int)$this->storeId, $this->getUserId(), $pass,
            (string)($data['remark'] ?? '')
        );
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess([], $pass ? '已通过' : '已驳回');
    }
}
