<?php
namespace mpp\crm\model;

use cores\BaseModel;
use think\facade\Db;

/**
 * 服务项目
 *
 * 一个项目 = 为客户办理的一件事或一组事；项目是需求/承诺/交付/活动的聚合根。
 * 状态由子对象推导（见 ProjectService::syncStatus），不要手工维护百分比。
 */
class Project extends BaseModel
{
    protected $name = 'crm_project';
    protected $autoWriteTimestamp = true;
    protected $dateFormat = false; // 时间字段保持 int 时间戳（前端按秒处理）
    protected $append = ['customer_name', 'owner_user_name', 'sales_user_name', 'status_text'];

    const STATUS_POTENTIAL = 1;
    const STATUS_ACTIVE = 2;
    const STATUS_DONE = 3;
    const STATUS_CANCELLED = 4;

    public static function statusMap(): array
    {
        return [
            self::STATUS_POTENTIAL => '潜在',
            self::STATUS_ACTIVE => '进行中',
            self::STATUS_DONE => '已完成',
            self::STATUS_CANCELLED => '已取消',
        ];
    }

    public function getCustomerNameAttr($value, $data)
    {
        if (empty($data['customer_id'])) return '';
        return (string)Db::name('crm_customer')
            ->where('store_id', static::$storeId)
            ->where('id', $data['customer_id'])
            ->value('customer_name');
    }

    public function getOwnerUserNameAttr($value, $data)
    {
        return $this->userName($data['owner_user_id'] ?? 0);
    }

    public function getSalesUserNameAttr($value, $data)
    {
        return $this->userName($data['sales_user_id'] ?? 0);
    }

    public function getStatusTextAttr($value, $data)
    {
        return self::statusMap()[$data['status'] ?? 0] ?? '';
    }

    private function userName($userId): string
    {
        if (empty($userId)) return '';
        $user = Db::name('store_user')
            ->where('store_id', static::$storeId)
            ->where('store_user_id', $userId)
            ->field(['real_name', 'user_name'])->find();
        return $user ? (string)($user['real_name'] ?: $user['user_name']) : '';
    }

    /**
     * @param array $where 过滤条件
     * @param array $param 分页参数（page/limit）
     */
    public function getList(array $where = [], array $param = [])
    {
        if (!empty($param['limit']) && empty($param['list_rows'])) {
            $param['list_rows'] = intval($param['limit']);
        }
        unset($where['page'], $where['limit'], $where['s']);

        $query = $this->where('is_delete', 0);

        // 归档：默认只看未归档；archived=1 只看已归档；archived=all 全部
        $archived = (string)($where['archived'] ?? '0');
        if ($archived === '1') {
            $query->whereNotNull('archived_at');
        } elseif ($archived !== 'all') {
            $query->whereNull('archived_at');
        }

        foreach (['status', 'customer_id', 'owner_user_id', 'sales_user_id'] as $field) {
            if (isset($where[$field]) && $where[$field] !== '' && $where[$field] !== null) {
                $query->where($field, $where[$field]);
            }
        }

        if (!empty($where['keyword'])) {
            $kw = trim((string)$where['keyword']);
            $query->where(function ($q) use ($kw) {
                $q->where('project_no', 'like', "%{$kw}%")
                    ->whereOr('title', 'like', "%{$kw}%");
            });
        }

        // 我的 = 交付负责人 ∪ 销售负责人 ∪ 客服负责人 ∪ 参与人
        if (!empty($where['mine_user_id'])) {
            $uid = (int)$where['mine_user_id'];
            $participantIds = \think\facade\Db::name('crm_project_participant')
                ->where('store_id', static::$storeId)->where('user_id', $uid)
                ->column('project_id');
            $query->where(function ($q) use ($uid, $participantIds) {
                $q->where('owner_user_id', $uid)
                  ->whereOr('sales_user_id', $uid)
                  ->whereOr('cs_user_id', $uid);
                if (!empty($participantIds)) {
                    $q->whereOr('id', 'in', $participantIds);
                }
            });
        }

        // 岗位工作台：我的交付（只算交付负责人与参与人）
        if (($where['scope'] ?? '') === 'delivery') {
            $uid = (int)($where['mine_user_id'] ?? 0);
            if ($uid > 0) {
                $participantIds = \think\facade\Db::name('crm_project_participant')
                    ->where('store_id', static::$storeId)->where('user_id', $uid)
                    ->column('project_id');
                $query->where(function ($q) use ($uid, $participantIds) {
                    $q->where('owner_user_id', $uid);
                    if (!empty($participantIds)) {
                        $q->whereOr('id', 'in', $participantIds);
                    }
                });
            }
        }

        if (isset($where['project_ids']) && is_array($where['project_ids'])) {
            $query->whereIn('id', $where['project_ids']);
        }

        return $query->order(['id' => 'desc'])->paginate($param);
    }

    public function detail(int $id)
    {
        return $this->where('id', $id)->where('is_delete', 0)->find();
    }

    public function add(array $data): bool
    {
        $data['store_id'] = static::$storeId;
        if (empty($data['customer_id'])) { $this->error = '请选择客户'; return false; }
        if (trim((string)($data['title'] ?? '')) === '') { $this->error = '请输入项目名称'; return false; }

        $customer = Db::name('crm_customer')
            ->where('store_id', static::$storeId)
            ->where('id', $data['customer_id'])
            ->where('is_delete', 0)->find();
        if (!$customer) { $this->error = '客户不存在'; return false; }

        $data['owner_user_id'] = intval($data['owner_user_id'] ?? 0) ?: intval($customer['owner_user_id'] ?? 0);
        $data['sales_user_id'] = intval($data['sales_user_id'] ?? 0) ?: intval($customer['owner_user_id'] ?? 0);
        $data['status'] = self::STATUS_POTENTIAL;

        foreach (['service_start_time', 'service_end_time'] as $field) {
            if (!empty($data[$field]) && !is_numeric($data[$field])) {
                $data[$field] = strtotime((string)$data[$field]) ?: null;
            }
        }

        $lastError = '';
        for ($i = 0; $i < 3; $i++) {
            $data['project_no'] = $this->generateNo();
            $model = new static();
            try {
                if ($model->save($data)) {
                    $this->id = $model->id;
                    $this->project_no = $model->project_no;
                    return true;
                }
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }
        $this->error = $lastError ?: '创建项目失败';
        return false;
    }

    public function edit(int $id, array $data): bool
    {
        $project = $this->detail($id);
        if (!$project) { $this->error = '项目不存在'; return false; }

        $allow = ['title', 'owner_user_id', 'sales_user_id', 'service_start_time', 'service_end_time', 'remark'];
        $save = [];
        foreach ($allow as $field) {
            if (array_key_exists($field, $data)) {
                $save[$field] = $data[$field];
            }
        }
        if (array_key_exists('title', $save) && trim((string)$save['title']) === '') {
            $this->error = '请输入项目名称'; return false;
        }
        foreach (['service_start_time', 'service_end_time'] as $field) {
            if (array_key_exists($field, $save) && !empty($save[$field]) && !is_numeric($save[$field])) {
                $save[$field] = strtotime((string)$save[$field]) ?: null;
            }
            if (array_key_exists($field, $save) && $save[$field] === '') {
                $save[$field] = null;
            }
        }
        return $project->save($save) !== false;
    }

    public function setStatus(int $id, int $status, array $extra = []): bool
    {
        $project = $this->detail($id);
        if (!$project) { $this->error = '项目不存在'; return false; }
        if (!isset(self::statusMap()[$status])) { $this->error = '无效状态'; return false; }

        $project->status = $status;
        if ($status === self::STATUS_ACTIVE && empty($project->started_at)) {
            $project->started_at = time();
        }
        if ($status === self::STATUS_DONE) {
            $project->completed_at = time();
        }
        foreach ($extra as $k => $v) {
            if (in_array($k, ['started_at', 'completed_at', 'archived_at'], true)) {
                $project->$k = $v;
            }
        }
        return $project->save() !== false;
    }

    /** 手动开始项目（潜在 → 进行中） */
    public function start(int $id): bool
    {
        $project = $this->detail($id);
        if (!$project) { $this->error = '项目不存在'; return false; }
        if ((int)$project['status'] === self::STATUS_CANCELLED) { $this->error = '已取消的项目不能直接开始'; return false; }
        return $project->save([
            'status' => self::STATUS_ACTIVE,
            'started_at' => $project['started_at'] ?: time(),
        ]) !== false;
    }

    /** 手动完成项目 */
    public function complete(int $id): bool
    {
        $project = $this->detail($id);
        if (!$project) { $this->error = '项目不存在'; return false; }
        return $project->save([
            'status' => self::STATUS_DONE,
            'completed_at' => time(),
        ]) !== false;
    }

    /** 重新打开（已完成 → 进行中） */
    public function reopen(int $id): bool
    {
        $project = $this->detail($id);
        if (!$project) { $this->error = '项目不存在'; return false; }
        return $project->save([
            'status' => self::STATUS_ACTIVE,
            'completed_at' => null,
            'started_at' => $project['started_at'] ?: time(),
        ]) !== false;
    }

    public function archive(int $id): bool
    {
        $project = $this->detail($id);
        if (!$project) { $this->error = '项目不存在'; return false; }
        return $project->save(['archived_at' => time()]) !== false;
    }

    protected function generateNo(): string
    {
        $prefix = 'PJ-' . date('Ymd') . '-';
        $last = $this->where('project_no', 'like', $prefix . '%')->order(['id' => 'desc'])->value('project_no');
        $seq = $last ? intval(substr($last, -3)) + 1 : 1;
        return $prefix . str_pad((string)$seq, 3, '0', STR_PAD_LEFT);
    }
}
