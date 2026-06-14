<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\model\Projection as ProjectionModel;

class Projection extends BaseController
{
    protected $methodRules = ['get' => 'GET'];

    /** 获取指定类型的投影数据 */
    public function get()
    {
        $type = $this->request->param('type', '');
        $key = $this->request->param('key', 'overall');

        $query = ProjectionModel::where('type', $type);
        if ($key) $query->where('key', $key);

        $rows = $query->order('calculated_at', 'desc')->limit(1)->select()->toArray();
        $data = !empty($rows) ? json_decode($rows[0]['data'], true) : [];

        return $this->renderSuccess($data);
    }
}
