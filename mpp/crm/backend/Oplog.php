<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\model\OperationLog as OperationLogModel;
use mpp\crm\service\OperationLogService;

class Oplog extends BaseController
{
    protected $methodRules = [
        'lists' => 'GET',
    ];

    public function lists()
    {
        if (!$this->checkAction('/crm/oplog/lists', '查询操作日志')) return;
        $model = new OperationLogModel();
        $model::$storeId = $this->storeId;
        $list = $model->getList($this->request->param());
        // 格式化时间
        if (!empty($list['data'])) {
            foreach ($list['data'] as &$row) {
                $row['time_text'] = !empty($row['create_time']) ? date('Y-m-d H:i:s', (int)$row['create_time']) : '';
            }
        }
        return $this->renderSuccess(['list' => $list]);
    }
}
