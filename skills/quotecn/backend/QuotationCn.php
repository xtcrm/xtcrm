<?php

namespace skills\quotecn\backend;

use app\platform\backend\BaseController;
use skills\quotecn\service\QuotationCnService;

/**
 * 中文报价单控制器
 * @package skills\quotecn\backend\controller
 */
class QuotationCn extends BaseController
{
    protected $methodRules = [
        'preview' => 'GET',
        'exportPdf' => 'GET',
        'exportWord' => 'GET',
        'exportExcel' => 'GET',
        'saveTemplate' => 'POST',
        'getTemplate' => 'GET',
    ];

    /**
     * 预览中文报价单
     * GET /crm.quotation_cn/preview?id=123
     */
    public function preview()
    {
        if (!$this->checkAction('/crm/quotation_cn/preview', '中文报价预览')) return;
        $id = $this->request->param('id', 0);
        $result = (new QuotationCnService())->preview((int)$id, $this->storeId);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data']);
    }

    /**
     * 导出中文报价单 PDF
     * GET /crm.quotation_cn/exportPdf?id=123
     */
    public function exportPdf()
    {
        if (!$this->checkAction('/crm/quotation_cn/exportPdf', '导出中文报价')) return;
        $id = $this->request->param('id', 0);
        $result = (new QuotationCnService())->exportPdf((int)$id, $this->storeId);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data']);
    }

    /**
     * 导出 Word 文档
     * GET /crm.quotation_cn/exportWord?id=123
     */
    public function exportWord()
    {
        if (!$this->checkAction('/crm/quotation_cn/exportPdf', '导出中文报价')) return;
        $id = $this->request->param('id', 0);
        $result = (new QuotationCnService())->exportWord((int)$id, $this->storeId);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data']);
    }

    /**
     * 导出 Excel 表格
     * GET /crm.quotation_cn/exportExcel?id=123
     */
    public function exportExcel()
    {
        if (!$this->checkAction('/crm/quotation_cn/exportPdf', '导出中文报价')) return;
        $id = $this->request->param('id', 0);
        $result = (new QuotationCnService())->exportExcel((int)$id, $this->storeId);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data']);
    }

    /**
     * 保存公司模板配置
     * POST /crm.quotation_cn/saveTemplate
     */
    public function saveTemplate()
    {
        if (!$this->checkAction('/crm/quotation_cn/saveTemplate', '保存报价模板')) return;
        $data = $this->postData();
        $result = (new QuotationCnService())->saveTemplate($data, $this->storeId);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess([], '保存成功');
    }

    /**
     * 获取公司模板配置
     * GET /crm.quotation_cn/getTemplate
     */
    public function getTemplate()
    {
        if (!$this->checkAction('/crm/quotation_cn/preview', '中文报价预览')) return;
        $result = (new QuotationCnService())->getTemplateConfig($this->storeId);
        return $this->renderSuccess($result['data']);
    }
}
