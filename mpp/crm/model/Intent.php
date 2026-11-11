<?php
namespace mpp\crm\model;

use cores\BaseModel;
use think\facade\Db;

/**
 * 客户需求（Intent）
 *
 * 客户想要的东西：可未定价、可未成交、可搁置；金额不由本表维护，由承诺行派生。
 */
class Intent extends BaseModel
{
    protected $name = 'crm_intent';
    protected $autoWriteTimestamp = true;
    protected $dateFormat = false; // 时间字段保持 int 时间戳（前端按秒处理）
    protected $append = ['customer_name', 'assignee_user_name', 'sales_user_name', 'status_text', 'catalog_name'];

    const STATUS_DRAFT = 1;      // 草稿
    const STATUS_TO_QUOTE = 2;   // 待报价
    const STATUS_QUOTED = 3;     // 已报价
    const STATUS_WON = 4;        // 已成交
    const STATUS_HELD = 5;       // 搁置
    const STATUS_CLOSED = 6;     // 已取消（显示"已关闭"）

    /** 允许的状态迁移（见状态机文档） */
    const TRANSITIONS = [
        self::STATUS_DRAFT => [self::STATUS_TO_QUOTE, self::STATUS_CLOSED],
        self::STATUS_TO_QUOTE => [self::STATUS_QUOTED, self::STATUS_HELD, self::STATUS_CLOSED],
        self::STATUS_QUOTED => [self::STATUS_WON, self::STATUS_TO_QUOTE, self::STATUS_HELD],
        self::STATUS_WON => [self::STATUS_CLOSED, self::STATUS_TO_QUOTE],
        self::STATUS_HELD => [self::STATUS_TO_QUOTE, self::STATUS_CLOSED],
        self::STATUS_CLOSED => [],
    ];

    public static function statusMap(): array
    {
        return [
            self::STATUS_DRAFT => '草稿',
            self::STATUS_TO_QUOTE => '待报价',
            self::STATUS_QUOTED => '已报价',
            self::STATUS_WON => '已成交',
            self::STATUS_HELD => '搁置',
            self::STATUS_CLOSED => '已关闭',
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

    public function getAssigneeUserNameAttr($value, $data)
    {
        return $this->userName($data['assignee_user_id'] ?? 0);
    }

    public function getSalesUserNameAttr($value, $data)
    {
        return $this->userName($data['sales_user_id'] ?? 0);
    }

    public function getStatusTextAttr($value, $data)
    {
        return self::statusMap()[$data['status'] ?? 0] ?? '';
    }

    public function getCatalogNameAttr($value, $data)
    {
        if (!empty($data['service_name'])) return (string)$data['service_name'];
        if (empty($data['catalog_id'])) return '';
        return (string)Db::name('workorder_type')
            ->where('store_id', static::$storeId)
            ->where('id', $data['catalog_id'])
            ->value('name');
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

    public function getList(array $where = [], array $param = [])
    {
        if (!empty($param['limit']) && empty($param['list_rows'])) {
            $param['list_rows'] = intval($param['limit']);
        }
        unset($where['page'], $where['limit'], $where['s']);

        $query = $this->where('is_delete', 0);

        foreach (['status', 'assignee_user_id', 'sales_user_id', 'customer_id', 'project_id', 'catalog_id'] as $field) {
            if (isset($where[$field]) && $where[$field] !== '' && $where[$field] !== null) {
                $query->where($field, $where[$field]);
            }
        }

        if (!empty($where['keyword'])) {
            $kw = trim((string)$where['keyword']);
            $query->where(function ($q) use ($kw) {
                $q->where('intent_no', 'like', "%{$kw}%")
                    ->whereOr('title', 'like', "%{$kw}%");
            });
        }

        // 未立项需求（project_id=0）在项目列表里也要能按客户筛出来
        if (!empty($where['unassigned_project'])) {
            $query->where('project_id', 0);
        }

        if (isset($where['intent_ids']) && is_array($where['intent_ids'])) {
            $query->whereIn('id', $where['intent_ids']);
        }

        // 数据权限：我是承接人/销售 + 可见项目下的需求 + 未立项但属于我负责的客户
        if (array_key_exists('scope_project_ids', $where)) {
            $projectIds = array_map('intval', (array)$where['scope_project_ids']);
            $customerIds = array_map('intval', (array)($where['scope_customer_ids'] ?? []));
            $viewerId = (int)($where['viewer_user_id'] ?? 0);
            $query->where(function ($q) use ($projectIds, $customerIds, $viewerId) {
                $q->where('assignee_user_id', $viewerId)->whereOr('sales_user_id', $viewerId);
                if (!empty($projectIds)) {
                    $q->whereOr(function ($q2) use ($projectIds) {
                        $q2->whereIn('project_id', $projectIds);
                    });
                }
                if (!empty($customerIds)) {
                    $q->whereOr(function ($q2) use ($customerIds) {
                        $q2->where('project_id', 0)->whereIn('customer_id', $customerIds);
                    });
                }
            });
        }

        if (!empty($where['view']) && $where['view'] === 'mine') {
            $userId = intval($where['viewer_user_id'] ?? 0);
            $query->where(function ($q) use ($userId) {
                $q->where('assignee_user_id', $userId)->whereOr('sales_user_id', $userId);
            });
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
        if (trim((string)($data['title'] ?? '')) === '') { $this->error = '请输入需求标题'; return false; }

        $customer = Db::name('crm_customer')
            ->where('store_id', static::$storeId)
            ->where('id', $data['customer_id'])
            ->where('is_delete', 0)->find();
        if (!$customer) { $this->error = '客户不存在'; return false; }

        $project = null;
        if (!empty($data['project_id'])) {
            $project = Db::name('crm_project')
                ->where('store_id', static::$storeId)
                ->where('id', $data['project_id'])
                ->where('is_delete', 0)->find();
            if (!$project) { $this->error = '项目不存在'; return false; }
            if ((int)$project['customer_id'] !== (int)$data['customer_id']) {
                $this->error = '需求与项目不属于同一客户'; return false;
            }
        }

        if (!empty($data['catalog_id'])) {
            $typeName = Db::name('workorder_type')
                ->where('store_id', static::$storeId)
                ->where('id', $data['catalog_id'])
                ->where('is_delete', 0)->value('name');
            if ($typeName === null) { $this->error = '服务品类不存在'; return false; }
            $data['service_name'] = (string)$typeName;
        }

        if (!empty($data['expected_date']) && !is_numeric($data['expected_date'])) {
            $data['expected_date'] = strtotime((string)$data['expected_date']) ?: null;
        }

        $data['quantity'] = $data['quantity'] ?? 1;
        $data['unit'] = $data['unit'] ?? '';
        $data['sales_user_id'] = intval($data['sales_user_id'] ?? 0)
            ?: intval($project['sales_user_id'] ?? 0)
            ?: intval($customer['owner_user_id'] ?? 0);
        $data['assignee_user_id'] = intval($data['assignee_user_id'] ?? 0);
        $data['status'] = self::STATUS_DRAFT;
        $data['source'] = intval($data['source'] ?? 1);
        $data['source_id'] = intval($data['source_id'] ?? 0);

        $lastError = '';
        for ($i = 0; $i < 3; $i++) {
            $data['intent_no'] = $this->generateNo();
            $model = new static();
            try {
                if ($model->save($data)) {
                    $this->id = $model->id;
                    $this->intent_no = $model->intent_no;
                    return true;
                }
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
            }
        }
        $this->error = $lastError ?: '创建需求失败';
        return false;
    }

    public function edit(int $id, array $data): bool
    {
        $intent = $this->detail($id);
        if (!$intent) { $this->error = '需求不存在'; return false; }

        $allow = ['title', 'description', 'catalog_id', 'quantity', 'unit', 'expected_date', 'assignee_user_id', 'project_id'];
        $save = [];
        foreach ($allow as $field) {
            if (array_key_exists($field, $data)) {
                $save[$field] = $data[$field];
            }
        }
        if (array_key_exists('title', $save) && trim((string)$save['title']) === '') {
            $this->error = '请输入需求标题'; return false;
        }
        if (!empty($save['catalog_id'])) {
            $typeName = Db::name('workorder_type')
                ->where('store_id', static::$storeId)
                ->where('id', $save['catalog_id'])
                ->where('is_delete', 0)->value('name');
            if ($typeName === null) { $this->error = '服务品类不存在'; return false; }
            $save['service_name'] = (string)$typeName;
        }
        if (!empty($save['expected_date']) && !is_numeric($save['expected_date'])) {
            $save['expected_date'] = strtotime((string)$save['expected_date']) ?: null;
        }
        if (array_key_exists('project_id', $save) && !empty($save['project_id'])) {
            $project = Db::name('crm_project')
                ->where('store_id', static::$storeId)
                ->where('id', $save['project_id'])
                ->where('is_delete', 0)->find();
            if (!$project) { $this->error = '项目不存在'; return false; }
            if ((int)$project['customer_id'] !== (int)$intent['customer_id']) {
                $this->error = '需求与项目不属于同一客户'; return false;
            }
        }
        return $intent->save($save) !== false;
    }

    /** 指派承接人 */
    public function assign(int $id, int $assigneeUserId): bool
    {
        $intent = $this->detail($id);
        if (!$intent) { $this->error = '需求不存在'; return false; }
        if ($assigneeUserId <= 0) { $this->error = '请选择承接人'; return false; }
        return $intent->save(['assignee_user_id' => $assigneeUserId]) !== false;
    }

    /**
     * 状态迁移（只允许状态机里的路径）
     */
    public function changeStatus(int $id, int $to, string $reason = '', ?int $heldUntil = null): bool
    {
        $intent = $this->detail($id);
        if (!$intent) { $this->error = '需求不存在'; return false; }

        $from = (int)$intent['status'];
        if (!in_array($to, self::TRANSITIONS[$from] ?? [], true)) {
            $this->error = '当前状态不能执行该操作（' . (self::statusMap()[$from] ?? $from) . ' → ' . (self::statusMap()[$to] ?? $to) . '）';
            return false;
        }

        $save = ['status' => $to];
        if ($to === self::STATUS_HELD) {
            $save['held_until'] = $heldUntil ?: null;
            if ($reason !== '') $save['closed_reason'] = '';
        } else {
            $save['held_until'] = null;
        }
        if ($to === self::STATUS_CLOSED) {
            $save['closed_reason'] = $reason;
        }
        return $intent->save($save) !== false;
    }

    protected function generateNo(): string
    {
        $prefix = 'RQ-' . date('Ymd') . '-';
        $last = $this->where('intent_no', 'like', $prefix . '%')->order(['id' => 'desc'])->value('intent_no');
        $seq = $last ? intval(substr($last, -3)) + 1 : 1;
        return $prefix . str_pad((string)$seq, 3, '0', STR_PAD_LEFT);
    }
}
