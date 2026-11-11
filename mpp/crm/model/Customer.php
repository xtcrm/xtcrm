<?php
namespace mpp\crm\model;

use cores\BaseModel;

class Customer extends BaseModel
{
    protected $name = 'crm_customer';
    protected $autoWriteTimestamp = true;
    protected $append = ['owner_user_name', 'creator_user_name', 'status_text', 'funnel_stage_text', 'level_name', 'industry_name', 'group_name', 'source_name'];
    
    /** @var array|null 当前登录用户信息，控制器调用前设置 */
    public static $currentUser = null;

    public static function setCurrentUser($user)
    {
        static::$currentUser = $user;
    }

    public function getList($where = [], $param = [])
    {
        $this->fixPaginationParam($where, $param);
        $allowedFields = [
            'id', 'customer_name', 'customer_code', 'industry', 'industry_id', 'level_id', 'level_name',
            'source', 'source_id', 'customer_group', 'group_id', 'tax_number', 'province', 'city', 'area',
            'status', 'owner_user_id', 'owner_department_id', 'creator_user_id',
            'funnel_stage', 'last_followup_time', 'enter_pool_time', 'is_delete', 'create_time', 'update_time'
        ];
        $filterWhere = [];
        foreach ($where as $key => $value) {
            if (in_array($key, $allowedFields) && $value !== '' && $value !== null) {
                $filterWhere[$key] = $value;
            }
        }
        // 统一关键字搜索（客户字段 + 联系人姓名/手机）
        $kw = $where['keyword'] ?? $where['customer_name'] ?? '';  // TODO: 前端统一传 keyword 后移除 customer_name
        if (!empty($kw)) {
            $filterWhere[] = function ($q) use ($kw) {
                $q->where(function ($q2) use ($kw) {
                    $q2->where('customer_name', 'like', "%{$kw}%")
                       ->whereOr('short_name', 'like', "%{$kw}%")
                       ->whereOr('tax_number', 'like', "%{$kw}%")
                       ->whereOr('telephone', 'like', "%{$kw}%")
                       ->whereOr('email', 'like', "%{$kw}%")
                       ->whereOr('customer_code', 'like', "%{$kw}%");
                });
                // 联系人匹配：姓名/手机号 → 客户
                $contactIds = \think\facade\Db::name('crm_contact')
                    ->where(function ($cq) use ($kw) {
                        $cq->where('contact_name', 'like', "%{$kw}%")
                           ->whereOr('mobile', 'like', "%{$kw}%");
                    })->column('id');
                if (!empty($contactIds)) {
                    $customerIds = \think\facade\Db::name('crm_contact_customer')
                        ->whereIn('contact_id', $contactIds)
                        ->column('customer_id');
                    if (!empty($customerIds)) {
                        $q->whereOr('id', 'in', array_unique($customerIds));
                    }
                }
            };
            unset($where['keyword'], $where['customer_name']);
        }
        // 未跟进天数筛选
        if (!empty($where['follow_days']) && $where['follow_days'] > 0) {
            $deadline = time() - intval($where['follow_days']) * 86400;
            $filterWhere[] = function ($q) use ($deadline) {
                $q->where('last_followup_time', '<', $deadline)->whereOr('last_followup_time', null);
            };
        }
        // 时间范围搜索
        if (!empty($where['create_time_from'])) {
            $filterWhere[] = ['create_time', '>=', strtotime($where['create_time_from'])];
        }
        if (!empty($where['create_time_to'])) {
            $filterWhere[] = ['create_time', '<=', strtotime($where['create_time_to'] . ' 23:59:59')];
        }
        // 按标签筛选（AND 逻辑，CustomerService 预处理后传入）
        if (!empty($where['_filter_customer_ids'])) {
            $filterWhere[] = ['id', 'in', $where['_filter_customer_ids']];
            unset($where['_filter_customer_ids']);
        }
        // 排序：默认按"最近活跃时间降序"（创建 OR 跟进，哪个新用哪个）
        $sortField = $where['sort_field'] ?? $param['sort_field'] ?? 'last_activity_time';
        $sortOrder = $where['sort_order'] ?? $param['sort_order'] ?? 'desc';
        $allowedSorts = ['customer_code','customer_name','industry','level_name','source','customer_group',
            'funnel_stage','last_followup_time','last_activity_time','status','id','create_time'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'id';
        if (!in_array($sortOrder, ['asc', 'desc'])) $sortOrder = 'desc';

        $query = $this->applyPermissionFilter($this->buildFilterQuery($filterWhere))
            ->when(!empty($where['exclude_owner']), function ($q) use ($where) {
                $q->where('owner_user_id', '<>', (int)$where['exclude_owner']);
            });

        // 岗位工作台：只看「我服务的」客户（与可见性并集不同，这是收窄）
        if (($where['scope'] ?? '') === 'cs') {
            $user = static::$currentUser;
            $uid = (int)($user['store_user_id'] ?? ($user['uid'] ?? 0));
            $query->where('cs_user_id', $uid);
        }

        // 智能排序：last_activity_time = GREATEST(create_time, last_followup_time)
        if ($sortField === 'last_activity_time') {
            return $query->orderRaw('GREATEST(create_time, IFNULL(last_followup_time, 0)) ' . $sortOrder)
                ->paginate($param);
        }
        return $query->order([$sortField => $sortOrder])->paginate($param);
    }

    /**
     * 构建筛选查询 — 分离字符串 key 和数字 key，防止 ThinkPHP strpos(0,'|') 崩溃
     */
    protected function buildFilterQuery(array $filterWhere)
    {
        $query = $this->where('is_delete', 0);
        foreach ($filterWhere as $key => $value) {
            if (is_string($key)) {
                $query->where($key, $value);
            } else {
                $query->where([$value]);
            }
        }
        return $query;
    }

    /**
     * 应用数据权限过滤
     *
     * 可见性由「我在这个对象上的关系」决定，不由系统角色决定：
     *   客户可见 = owner_user_id = me                      （我是销售）
     *            ∪ cs_user_id = me                          （我是客服）
     *            ∪ 我是其下项目的负责人 / 参与人             （交付）
     *            ∪ 经理：客户 owner / cs 部门在管辖子树内
     *            ∪ 管理员
     * 参与人是协作，不参与归属判定；公海在主列表统一排除。
     */
    protected function applyPermissionFilter($query)
    {
        $user = static::$currentUser;
        $userId = $user['store_user_id'] ?? ($user['uid'] ?? 0);
        // 主列表统一排除公海客户（公海有独立页面）
        $query->where('owner_user_id', '>', 0);
        // 管理员不过滤（但公海已排除）
        if ($this->isAdmin($user)) return $query;

        $deptIds = $this->isManager($userId) ? $this->getManagedDeptIds($userId) : [];
        $projectCustomerIds = $this->getMyProjectCustomerIds($userId);
        $collabIds = $this->getMyCollaboratorCustomerIds($userId);

        $query->where(function ($q) use ($userId, $deptIds, $projectCustomerIds, $collabIds) {
            $q->where('owner_user_id', $userId)
              ->whereOr('cs_user_id', $userId);
            if (!empty($deptIds)) {
                $q->whereOr(function ($q2) use ($deptIds) {
                    $q2->whereIn('owner_department_id', $deptIds)
                       ->whereOr('cs_department_id', 'in', $deptIds);
                });
            }
            if (!empty($projectCustomerIds)) {
                $q->whereOr('id', 'in', $projectCustomerIds);
            }
            if (!empty($collabIds)) {
                $q->whereOr('id', 'in', $collabIds);
            }
        });
        return $query;
    }

    /**
     * 我负责 / 参与的项目所关联的客户（交付岗位的客户可见范围）
     */
    protected function getMyProjectCustomerIds($userId)
    {
        if (empty($userId)) return [];
        $projectIds = \think\facade\Db::name('crm_project')
            ->where('store_id', static::$storeId)
            ->where('is_delete', 0)
            ->where(function ($q) use ($userId) {
                $q->where('owner_user_id', $userId)
                  ->whereOr('sales_user_id', $userId)
                  ->whereOr('cs_user_id', $userId);
            })->column('id');
        $participantIds = \think\facade\Db::name('crm_project_participant')
            ->where('store_id', static::$storeId)
            ->where('user_id', $userId)
            ->column('project_id');
        $ids = array_values(array_unique(array_merge($projectIds, $participantIds)));
        if (empty($ids)) return [];
        return \think\facade\Db::name('crm_project')
            ->where('store_id', static::$storeId)
            ->where('is_delete', 0)
            ->whereIn('id', $ids)
            ->where('customer_id', '>', 0)
            ->column('customer_id');
    }

    /**
     * 我协作的客户（协作 = 参与，不是责任位）
     */
    protected function getMyCollaboratorCustomerIds($userId)
    {
        if (empty($userId)) return [];
        return \think\facade\Db::name('crm_customer_collaborator')
            ->where('store_id', static::$storeId)
            ->where('user_id', $userId)
            ->column('customer_id');
    }

    protected function isAdmin($user)
    {
        return !empty($user['is_super']);
    }

    protected function isManager($userId)
    {
        return \think\facade\Db::name('store_department')
            ->where('store_id', static::$storeId)
            ->where('manager_id', $userId)
            ->where('is_delete', 0)
            ->count() > 0;
    }

    /**
     * 我管辖的部门子树 — 可能同时管多个中心，不能只取第一个
     */
    protected function getManagedDeptIds($userId)
    {
        $deptIds = \think\facade\Db::name('store_department')
            ->where('store_id', static::$storeId)
            ->where('manager_id', $userId)
            ->where('is_delete', 0)
            ->column('id');
        if (empty($deptIds)) return [];
        $ids = $deptIds;
        foreach ($deptIds as $deptId) {
            $this->getChildDeptIds($deptId, $ids);
        }
        return array_values(array_unique($ids));
    }

    protected function getChildDeptIds($parentId, &$ids)
    {
        $children = \think\facade\Db::name('store_department')
            ->where('store_id', static::$storeId)
            ->where('parent_id', $parentId)
            ->where('is_delete', 0)
            ->column('id');
        foreach ($children as $cid) {
            $ids[] = $cid;
            $this->getChildDeptIds($cid, $ids);
        }
    }

    public function select($where = [])
    {
        return $this->where('is_delete', 0)
            ->where('status', 1)
            ->where(function ($q) use ($where) {
                if (!empty($where['keyword'])) {
                    $kw = $where['keyword'];
                    $q->where('customer_name', 'like', "%{$kw}%")
                      ->whereOr('short_name', 'like', "%{$kw}%")
                      ->whereOr('customer_code', 'like', "%{$kw}%");
                }
            })
            ->field(['id', 'customer_name', 'customer_code'])
            ->order(['id' => 'desc'])
            ->limit(50)
            ->select();
    }

    // ——— Accessor：$append 字段 ———

    /** industry_name → 直接读 industry varchar 列 */
    public function getIndustryNameAttr($value, $data)
    {
        return $data['industry'] ?? '';
    }

    /** source_name → 直接读 source varchar 列 */
    public function getSourceNameAttr($value, $data)
    {
        return $data['source'] ?? '';
    }

    /** group_name → 直接读 customer_group varchar 列 */
    public function getGroupNameAttr($value, $data)
    {
        return $data['customer_group'] ?? '';
    }

    /** owner_user_name → 查 store_user 表 */
    public function getOwnerUserNameAttr($value, $data)
    {
        if (!empty($data['owner_user_id'])) {
            $user = \think\facade\Db::name('store_user')
                ->where('store_user_id', $data['owner_user_id'])
                ->field(['real_name', 'user_name'])
                ->find();
            return $user ? ($user['real_name'] ?: $user['user_name']) : '';
        }
        return '';
    }

    /** creator_user_name → 查 store_user 表 */
    public function getCreatorUserNameAttr($value, $data)
    {
        if (!empty($data['creator_user_id'])) {
            $user = \think\facade\Db::name('store_user')
                ->where('store_user_id', $data['creator_user_id'])
                ->field(['real_name', 'user_name'])
                ->find();
            return $user ? ($user['real_name'] ?: $user['user_name']) : '';
        }
        return '';
    }

    /** status_text */
    public function getStatusTextAttr($value, $data)
    {
        $map = [0 => '停用', 1 => '正常'];
        return $map[$data['status'] ?? 1] ?? '';
    }

    /** funnel_stage_text */
    public function getFunnelStageTextAttr($value, $data)
    {
        $map = [1 => '初步接触', 2 => '需求确认', 3 => '报价', 4 => '谈判', 5 => '成交'];
        return $map[$data['funnel_stage'] ?? 1] ?? '';
    }

    public function setDelete($id) { $this->is_delete = 1; return $this->save(); }

    public function detail($id, $from = '')
    {
        $detail = $this->where('id', $id)->find();
        if (!$detail) return null;
        // 协作客户场景：查询当前用户的协作权限
        if ($from === 'collab') {
            $userId = static::$currentUser['store_user_id'] ?? 0;
            $collab = \think\facade\Db::name('crm_customer_collaborator')
                ->where('customer_id', $id)
                ->where('user_id', $userId)
                ->field(['permission as collab_permission', 'id as collab_id'])
                ->find();
            $detail['collab_permission'] = $collab['collab_permission'] ?? '';
            $detail['collab_id'] = $collab['collab_id'] ?? 0;
        }
        return $detail;
    }

    public function add($data)
    {
        $data['store_id'] = static::$storeId;
        if (empty($data['customer_name'])) {
            $this->error = '客户名称不能为空';
            return false;
        }
        if ($this->nameExists($data['customer_name'])) {
            $this->error = '客户名称已存在';
            return false;
        }
        $data['customer_code'] = $this->generateCode();
        $data['creator_user_id'] = $data['creator_user_id'] ?? 0;
        $data['funnel_stage'] = 1;
        $data['last_followup_time'] = 0;
        return $this->save($data);
    }

    public function edit($data)
    {
        if (empty($data['customer_name'])) {
            $this->error = '客户名称不能为空';
            return false;
        }
        $excludeId = $data['id'] ?? 0;
        if ($this->nameExists($data['customer_name'], $excludeId)) {
            $this->error = '客户名称已存在';
            return false;
        }
        return $this->save($data);
    }

    /**
     * 检查客户名称是否已存在（同租户、未删除）
     */
    protected function nameExists(string $name, int $excludeId = 0): bool
    {
        $query = $this->where('customer_name', $name)
            ->where('is_delete', 0);
        if ($excludeId > 0) {
            $query->where('id', '<>', $excludeId);
        }
        return $query->count() > 0;
    }

    protected function generateCode()
    {
        $prefix = 'C-' . date('Ymd') . '-';
        $prefixLen = strlen($prefix); // 10 = C + 8 digits + '-'
        $last = $this->where('customer_code', 'like', $prefix . '%')
            ->order(['id' => 'desc'])
            ->find();
        $seq = $last ? intval(substr($last['customer_code'], $prefixLen)) + 1 : 1;
        return $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    /**
     * 获取公海客户列表
     */
    public function getPoolList($where = [], $param = [])
    {
        $query = $this->where('owner_user_id', 0)
            ->where('is_delete', 0)
            ->where('status', 1);
        $kw = trim((string)($where['keyword'] ?? $where['customer_name'] ?? ''));
        if ($kw !== '') {
            $query->where(function ($q) use ($kw) {
                $q->where('customer_name', 'like', "%{$kw}%")
                  ->whereOr('short_name', 'like', "%{$kw}%")
                  ->whereOr('customer_code', 'like', "%{$kw}%")
                  ->whereOr('telephone', 'like', "%{$kw}%");
            });
        }
        $this->fixPaginationParam($where, $param);
        return $query->order(['enter_pool_time' => 'desc'])->paginate($param);
    }

    /** 协作客户列表：只看被共享给我的，带协作信息 */
    public function getCollabList($where = [], $param = [])
    {
        $uid = static::$currentUser['store_user_id'] ?? 0;
        $this->fixPaginationParam($where, $param);
        // join 不走前缀自动补全，用 getTable() 拿真实表名；并带上 store_id 防跨租户
        $collabTable = \think\facade\Db::name('crm_customer_collaborator')->getTable();
        $query = $this->alias('c')
            ->join($collabTable . ' cc', 'c.id=cc.customer_id AND cc.store_id=c.store_id')
            ->where('c.store_id', static::$storeId)
            ->where('cc.user_id', $uid)
            ->where('c.is_delete', 0)
            ->field('c.*, cc.permission as collab_permission, cc.id as collab_id');

        $kw = trim((string)($where['keyword'] ?? $where['customer_name'] ?? ''));
        if ($kw !== '') {
            $query->where(function ($q) use ($kw) {
                $q->where('c.customer_name', 'like', "%{$kw}%")
                  ->whereOr('c.short_name', 'like', "%{$kw}%")
                  ->whereOr('c.telephone', 'like', "%{$kw}%");
            });
        }
        return $query->order(['c.id' => 'desc'])->paginate($param);
    }

    /** 公海总览：总数 / 7 天以上未认领 / 今日新进 */
    public function getPoolCounts(): array
    {
        $now = time();
        $row = \think\facade\Db::name('crm_customer')
            ->where('store_id', static::$storeId)
            ->where('owner_user_id', 0)->where('is_delete', 0)->where('status', 1)
            ->field('COUNT(*) AS total,
                     SUM(IFNULL(enter_pool_time,0) > 0 AND IFNULL(enter_pool_time,0) < ' . ($now - 7 * 86400) . ') AS stale,
                     SUM(IFNULL(enter_pool_time,0) >= ' . ($now - 86400) . ') AS today')
            ->find();
        return [
            'all'   => (int)($row['total'] ?? 0),
            'stale' => (int)($row['stale'] ?? 0),
            'today' => (int)($row['today'] ?? 0),
        ];
    }

    /** 协作客户总览：总数 / 可编辑 / 只读 */
    public function getCollabCounts(): array
    {
        $uid = static::$currentUser['store_user_id'] ?? 0;
        $collabTable = \think\facade\Db::name('crm_customer_collaborator')->getTable();
        $row = \think\facade\Db::name('crm_customer')
            ->alias('c')
            ->join($collabTable . ' cc', 'c.id=cc.customer_id AND cc.store_id=c.store_id')
            ->where('c.store_id', static::$storeId)
            ->where('cc.user_id', $uid)
            ->where('c.is_delete', 0)
            ->field('COUNT(*) AS total, SUM(cc.permission = 2) AS editable, SUM(cc.permission = 1) AS readonly')
            ->find();
        return [
            'all'      => (int)($row['total'] ?? 0),
            'editable' => (int)($row['editable'] ?? 0),
            'readonly' => (int)($row['readonly'] ?? 0),
        ];
    }

    private function fixPaginationParam(array &$where, array &$param): void
    {
        $limit = $param['limit'] ?? $where['limit'] ?? null;
        if ($limit && empty($param['list_rows'])) {
            $param['list_rows'] = intval($limit);
        }
        unset($where['s'], $where['page'], $where['limit']);
    }

    // 兜底：ThinkPHP paginate 认 list_rows，前端传的是 limit
    private function fixPaginateParam(array &$param): void
    {
        if (!empty($param['limit']) && empty($param['list_rows'])) {
            $param['list_rows'] = $param['limit'];
        }
    }
}
