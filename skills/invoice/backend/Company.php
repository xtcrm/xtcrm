<?php
namespace skills\invoice\backend;

use app\platform\backend\BaseController;
use skills\invoice\model\InvoiceCompany;
use skills\invoice\model\InvoiceCompanyMember;
use skills\invoice\service\InvoiceService;

class Company extends BaseController
{
    protected $methodRules = [
        'lists'         => 'GET',
        'detail'        => 'GET',
        'add'           => 'POST',
        'edit'          => 'POST',
        'toggleStatus'  => 'POST',
        'bindMember'    => 'POST',
        'unbindMember'  => 'POST',
    ];

    public function lists()
    {
        $param = $this->request->param();
        $where = ['store_id' => $this->storeId];
        if (!empty($param['status'])) $where['status'] = $param['status'];

        $list = (new InvoiceCompany)->getList($where, $param);
        return $this->renderSuccess(compact('list'));
    }

    public function detail()
    {
        $id = $this->request->param('id', 0);
        $company = (new InvoiceCompany)->detail($id);
        if (!$company) return $this->renderError('公司不存在');

        // 发票统计
        $stats = InvoiceService::stats($id, $this->storeId);

        // 已绑定会员
        $members = (new InvoiceCompanyMember)->getMembers($id);

        return $this->renderSuccess([
            'company' => $company,
            'stats'   => $stats,
            'members' => $members,
        ]);
    }

    public function add()
    {
        $data = $this->request->param();
        $data['source'] = 2; // 后台建档
        $model = new InvoiceCompany;
        if ($model->add($data)) {
            return $this->renderSuccess(['id' => $model->id], '建档成功');
        }
        return $this->renderError($model->getError() ?: '建档失败');
    }

    public function edit()
    {
        $id = $this->request->param('id', 0);
        $company = (new InvoiceCompany)->detail($id);
        if (!$company) return $this->renderError('公司不存在');
        $data = $this->request->param();
        $allow = ['name', 'tax_number', 'address', 'phone', 'bank_name', 'bank_account'];
        $update = ['id' => $id];
        foreach ($allow as $f) { if (isset($data[$f])) $update[$f] = $data[$f]; }
        if ((new InvoiceCompany)->edit($update)) return $this->renderSuccess([], '修改成功');
        return $this->renderError('修改失败');
    }

    public function toggleStatus()
    {
        $id = $this->request->param('id', 0);
        $model = new InvoiceCompany;
        if ($model->toggleStatus($id)) return $this->renderSuccess([], '操作成功');
        return $this->renderError($model->getError() ?: '操作失败');
    }

    public function bindMember()
    {
        $companyId = $this->request->param('company_id', 0);
        $memberId = $this->request->param('member_id', 0);
        if ((new InvoiceCompanyMember)->bind($companyId, $memberId, $this->storeId)) {
            return $this->renderSuccess([], '绑定成功');
        }
        return $this->renderError('绑定失败');
    }

    public function unbindMember()
    {
        $companyId = $this->request->param('company_id', 0);
        $memberId = $this->request->param('member_id', 0);
        if ((new InvoiceCompanyMember)->unbind($companyId, $memberId)) {
            return $this->renderSuccess([], '解绑成功');
        }
        return $this->renderError('解绑失败');
    }
}
