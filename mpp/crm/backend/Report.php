<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\service\ReportService;

/**
 * 报表
 *
 * URL：s=/platform/crm.report/{action}
 * 全部只读，统一用「查看报表」一个权限点。
 */
class Report extends BaseController
{
    protected $methodRules = [
        'overview'     => 'GET',
        'revenueTrend' => 'GET',
        'ranking'      => 'GET',
        'funnel'       => 'GET',
        'quotation'    => 'GET',
        'renewal'      => 'GET',
        'delivery'     => 'GET',
        'receivable'   => 'GET',
    ];

    private function guard()
    {
        return $this->checkAction('/crm/report/view', '查看报表');
    }

    public function overview()
    {
        if (!$this->guard()) return;
        return $this->renderSuccess((new ReportService())->overview((int)$this->storeId)['data']);
    }

    public function revenueTrend()
    {
        if (!$this->guard()) return;
        return $this->renderSuccess((new ReportService())->revenueTrend((int)$this->storeId)['data']);
    }

    public function ranking()
    {
        if (!$this->guard()) return;
        $month = (string)$this->request->param('month', '');
        return $this->renderSuccess((new ReportService())->ranking((int)$this->storeId, $month)['data']);
    }

    public function funnel()
    {
        if (!$this->guard()) return;
        return $this->renderSuccess((new ReportService())->funnel((int)$this->storeId)['data']);
    }

    public function quotation()
    {
        if (!$this->guard()) return;
        $start = (string)$this->request->param('start_month', '');
        $end = (string)$this->request->param('end_month', '');
        return $this->renderSuccess((new ReportService())->quotation((int)$this->storeId, $start, $end)['data']);
    }

    public function renewal()
    {
        if (!$this->guard()) return;
        $days = (int)$this->request->param('days', 90);
        return $this->renderSuccess((new ReportService())->renewal((int)$this->storeId, $days)['data']);
    }

    public function delivery()
    {
        if (!$this->guard()) return;
        return $this->renderSuccess((new ReportService())->delivery((int)$this->storeId)['data']);
    }

    public function receivable()
    {
        if (!$this->guard()) return;
        return $this->renderSuccess((new ReportService())->receivable((int)$this->storeId)['data']);
    }
}
