<?php
namespace mpp\crm\model;

use cores\BaseModel;

class OperationLog extends BaseModel
{
    protected $name = 'operation_log';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = false;

    public function getList(array $where = [], array $param = [])
    {
        $query = $this->db()->where('store_id', static::$storeId);

        if (!empty($where['user_id'])) {
            $query->where('user_id', $where['user_id']);
        }
        if (!empty($where['action'])) {
            $query->where('action', $where['action']);
        }
        if (!empty($where['module'])) {
            $query->where('module', $where['module']);
        }
        if (!empty($where['target'])) {
            $query->where('target', $where['target']);
        }
        if (!empty($where['keyword'])) {
            $query->where('summary|user_name', 'like', '%' . $where['keyword'] . '%');
        }
        if (!empty($where['date_from'])) {
            $query->where('create_time', '>=', strtotime($where['date_from']));
        }
        if (!empty($where['date_to'])) {
            $query->where('create_time', '<=', strtotime($where['date_to'] . ' 23:59:59'));
        }

        return $query->order('id', 'desc')->paginate($param);
    }
}
