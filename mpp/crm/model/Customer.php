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
        // 清除路由/分页等非业务字段，避免污染 WHERE
        unset($where['s'], $where['page'], $where['limit']);
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
        // keyword: 多字段模糊搜索（移动端 + PC 端通用）
        if (!empty($where['keyword'])) {
            $kw = $where['keyword'];
            $filterWhere[] = function ($q) use ($kw) {
                $q->where('customer_name', 'like', "%{$kw}%")
                  ->whereOr('short_name', 'like', "%{$kw}%")
                  ->whereOr('tax_number', 'like', "%{$kw}%")
                  ->whereOr('telephone', 'like', "%{$kw}%");
            };
            unset($where['keyword']);
        }
        // customer_name: 精确匹配改为模糊搜索
        if (!empty($where['customer_name'])) {
            $filterWhere[] = ['customer_name', 'like', '%' . $where['customer_name'] . '%'];
            unset($filterWhere['customer_name']); // 移除精确匹配，仅保留模糊
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
        // 排序：前端 sorter 参数 → sort_field / sort_order
        $sortField = $where['sort_field'] ?? $param['sort_field'] ?? 'id';
        $sortOrder = $where['sort_order'] ?? $param['sort_order'] ?? 'desc';
        $allowedSorts = ['customer_code','customer_name','industry','level_name','source','customer_group',
            'funnel_stage','last_followup_time','status','id','create_time'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'id';
        if (!in_array($sortOrder, ['asc', 'desc'])) $sortOrder = 'desc';

        return $this->applyPermissionFilter($this->where($filterWhere)->where('is_delete', 0))
            ->order([$sortField => $sortOrder])
            ->paginate($param);
    }

    /**
     * 应用数据权限过滤：管理员看全部，经理看部门+子部门，业务员看自己
     */
    protected function applyPermissionFilter($query)
    {
        $user = static::$currentUser;
        $userId = $user['store_user_id'] ?? ($user['uid'] ?? 0);
        // 主列表统一排除公海客户（公海有独立页面）
        $query->where('owner_user_id', '>', 0);
        // 管理员不过滤（但公海已排除）
        if ($this->isAdmin($user)) return $query;
        // 业务经理：看管理的部门及子部门
        if ($this->isManager($userId)) {
            $deptIds = $this->getManagedDeptIds($userId);
            if (!empty($deptIds)) {
                $query->where(function ($q) use ($deptIds, $userId) {
                    $q->whereIn('owner_department_id', $deptIds)
                      ->whereOr('owner_user_id', $userId);
                });
            } else {
                $query->where('owner_user_id', $userId);
            }
            return $query;
        }
        // 业务员：看自己 + 协作的（公海客户只在公海页面显示）
        $collabIds = \think\facade\Db::name('crm_customer_collaborator')
            ->where('user_id', $userId)
            ->column('customer_id');
        $query->where(function ($q) use ($userId, $collabIds) {
            $q->where('owner_user_id', $userId);
            if (!empty($collabIds)) {
                $q->whereOr('id', 'in', $collabIds);
            }
        });
        return $query;
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

    protected function getManagedDeptIds($userId)
    {
        $dept = \think\facade\Db::name('store_department')
            ->where('store_id', static::$storeId)
            ->where('manager_id', $userId)
            ->where('is_delete', 0)
            ->find();
        if (!$dept) return [];
        $ids = [$dept['id']];
        $this->getChildDeptIds($dept['id'], $ids);
        return $ids;
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
            ->field(['id', 'customer_name', 'customer_code'])
            ->order(['id' => 'desc'])
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
        $data['last_followup_time'] = time();
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
        $last = $this->where('customer_code', 'like', $prefix . '%')
            ->order(['id' => 'desc'])
            ->find();
        $seq = $last ? intval(substr($last['customer_code'], -3)) + 1 : 1;
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
        if (!empty($where['customer_name'])) {
            $query->where('customer_name', 'like', '%' . $where['customer_name'] . '%');
        }
        return $query->order(['enter_pool_time' => 'desc'])->paginate($param);
    }

    /** 协作客户列表：只看被共享给我的，带协作信息 */
    public function getCollabList($where = [], $param = [])
    {
        $uid = static::$currentUser['store_user_id'] ?? 0;
        return $this->alias('c')
            ->join('yoshop_crm_customer_collaborator cc', 'c.id=cc.customer_id')
            ->where('cc.user_id', $uid)
            ->where('c.is_delete', 0)
            ->field('c.*, cc.permission as collab_permission, cc.id as collab_id')
            ->where(function ($q) use ($where) {
                if (!empty($where['customer_name'])) {
                    $q->where('c.customer_name', 'like', '%' . $where['customer_name'] . '%');
                }
            })
            ->order(['c.id' => 'desc'])
            ->paginate($param);
    }
}
