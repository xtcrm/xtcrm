<?php
namespace skills\invoice\api;

use app\api\controller\Controller;
use skills\invoice\model\InvoiceCompany;
use skills\invoice\model\InvoiceCompanyMember;

class Company extends Controller
{
    /**
     * 首次建档（绑定当前会员到公司）
     */
    public function bind()
    {
        $data = $this->request->param();
        $storeId = $this->storeId;

        try {
            $memberId = \app\api\service\User::getCurrentLoginUserId();
        } catch (\Exception $e) {
            return $this->renderError('请先登录');
        }

        // 检查会员是否已绑定其他公司
        $existCompany = (new InvoiceCompany)->getByMemberId($memberId, $storeId);
        if ($existCompany) {
            return $this->renderError('您已绑定公司: ' . $existCompany['name']);
        }

        // 创建公司
        $model = new InvoiceCompany;
        $data['source'] = 1; // 小程序自助
        if ($model->add($data)) {
            // 自动绑定当前会员
            (new InvoiceCompanyMember)->bind($model->id, $memberId, $storeId);
            return $this->renderSuccess([
                'company_id' => $model->id,
                'name'       => $data['name'],
                'tax_number' => $data['tax_number'] ?? '',
            ], '建档成功');
        }
        return $this->renderError($model->getError() ?: '建档失败');
    }

    /**
     * 我的公司详情
     */
    public function detail()
    {
        $storeId = $this->storeId;
        try {
            $memberId = \app\api\service\User::getCurrentLoginUserId();
        } catch (\Exception $e) {
            return $this->renderError('请先登录');
        }

        $company = (new InvoiceCompany)->getByMemberId($memberId, $storeId);
        if (!$company) {
            return $this->renderSuccess([]);
        }

        return $this->renderSuccess($company instanceof \think\Model ? $company->toArray() : $company);
    }

    /**
     * 修改公司信息
     */
    public function edit()
    {
        $data = $this->request->param();
        $storeId = $this->storeId;

        try {
            $memberId = \app\api\service\User::getCurrentLoginUserId();
        } catch (\Exception $e) {
            return $this->renderError('请先登录');
        }

        $company = (new InvoiceCompany)->getByMemberId($memberId, $storeId);
        if (!$company) return $this->renderError('未绑定公司');

        // 只允许修改自己的公司
        $data['id'] = $company['id'];
        if ((new InvoiceCompany)->edit($data)) {
            return $this->renderSuccess([], '修改成功');
        }
        return $this->renderError('修改失败');
    }
}
