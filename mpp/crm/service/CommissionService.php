<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use mpp\crm\model\CommissionShare as ShareModel;
use think\facade\Db;

/**
 * 业绩分账
 *
 * 三条不可破的规则：
 *   1. 比例在签约时锁定（share.ratio），回款只读，不再看配置
 *   2. 业绩按回款确认，归属月 = 回款到账月，不是签约月
 *   3. 退款生成负数记录冲销，不改历史行
 *
 * 比例 0 的一方不生成分账行；分账行缺失时不得静默跳过，宁可报错。
 */
class CommissionService extends BaseService
{
    const SOURCE_ORDER    = 1;   // 订单
    const SOURCE_CONTRACT = 2;   // 合同

    /** 默认比例（销售 / 客服），与 D9 一致 */
    const RATIO_DEFAULTS = [
        1 => ['sales' => 100, 'cs' => 0],   // 新签
        2 => ['sales' => 30,  'cs' => 70],  // 续费
        3 => ['sales' => 70,  'cs' => 30],  // 增购
    ];

    /** 业务类型 → 配置项名称 */
    const RATIO_CONFIG = [
        1 => 'commission_ratio_new',
        2 => 'commission_ratio_renew',
        3 => 'commission_ratio_upgrade',
    ];

    // ------------------------------------------------------------------
    // 比例
    // ------------------------------------------------------------------

    /**
     * 读取分账比例：租户配置优先，代码常量兜底
     * @return array ['sales' => int, 'cs' => int]
     */
    public function getRatios(int $bizType, int $storeId): array
    {
        $default = self::RATIO_DEFAULTS[$bizType] ?? self::RATIO_DEFAULTS[1];
        $name = self::RATIO_CONFIG[$bizType] ?? self::RATIO_CONFIG[1];

        $rows = Db::name('config')
            ->where('config_type', 'crm_setting')
            ->where('config_name', $name)
            ->where(function ($q) use ($storeId) {
                $q->where('store_id', $storeId)->whereOr('store_id', 0);
            })
            ->order(['store_id' => 'desc'])
            ->select()->toArray();
        if (empty($rows)) return $default;

        $raw = json_decode((string)$rows[0]['config_value'], true);
        if (!is_array($raw)) return $default;
        $sales = isset($raw['sales']) ? (float)$raw['sales'] : (float)$default['sales'];
        $cs    = isset($raw['cs'])    ? (float)$raw['cs']    : (float)$default['cs'];
        if (round($sales + $cs, 2) != 100) return $default;
        return ['sales' => $sales, 'cs' => $cs];
    }

    // ------------------------------------------------------------------
    // 分账计划
    // ------------------------------------------------------------------

    /**
     * 为单据生成分账计划（幂等：已有生效组则直接返回）
     *
     * @param int $sourceType 1订单 2合同
     * @param int $sourceId
     */
    public function ensureShares(int $sourceType, int $sourceId, int $storeId): array
    {
        $existing = (new ShareModel())->getActiveGroup($sourceType, $sourceId);
        if (!empty($existing)) return ['success' => true, 'data' => ['list' => $existing, 'created' => 0]];

        $sourceType === self::SOURCE_CONTRACT
            ? $source = Db::name('crm_contract')->where('store_id', $storeId)->where('id', $sourceId)->where('is_delete', 0)->find()
            : $source = Db::name('crm_order')->where('store_id', $storeId)->where('id', $sourceId)->where('is_delete', 0)->find();
        if (!$source) return ['success' => false, 'error' => '业务单据不存在'];

        $customerId = (int)$source['customer_id'];
        $customer = Db::name('crm_customer')->where('store_id', $storeId)->where('id', $customerId)->find();
        $bizType = (int)($source['biz_type'] ?? 1) ?: 1;

        // 覆盖申请优先（只有已通过的才生效）
        $override = Db::name('crm_commission_override')
            ->where('store_id', $storeId)
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('status', 1)
            ->order(['id' => 'desc'])->find();
        if ($override) {
            $ratios = ['sales' => (float)$override['sales_ratio'], 'cs' => (float)$override['cs_ratio']];
            $overrideId = (int)$override['id'];
        } else {
            if (Db::name('crm_commission_override')->where('store_id', $storeId)
                ->where('source_type', $sourceType)->where('source_id', $sourceId)
                ->where('status', 0)->count() > 0) {
                return ['success' => false, 'error' => '该单据有未审批的比例覆盖申请，请先审批'];
            }
            $ratios = $this->getRatios($bizType, $storeId);
            $overrideId = 0;
        }
        if (round($ratios['sales'] + $ratios['cs'], 2) != 100) {
            return ['success' => false, 'error' => '分账比例合计必须为 100%'];
        }

        $baseAmount = round((float)($sourceType === self::SOURCE_CONTRACT
            ? $source['contract_amount'] : $source['final_amount']), 2);
        $salesUserId = (int)$source['owner_user_id'];
        $csUserId = (int)($customer['cs_user_id'] ?? 0);
        $projectId = (int)($source['project_id'] ?? 0);

        // 有比例没归属人 = 无法确认业绩，必须报错，不能静默丢业绩
        if ($ratios['sales'] > 0 && $salesUserId <= 0) {
            return ['success' => false, 'error' => '单据未设置销售负责人，无法生成分账'];
        }
        if ($ratios['cs'] > 0 && $csUserId <= 0) {
            return ['success' => false, 'error' => '客户未分配客服责任人，无法生成分账；请先在客户列表指定客服'];
        }

        $sourceNo = (string)($source[$sourceType === self::SOURCE_CONTRACT ? 'contract_no' : 'order_no'] ?? '');
        $now = time();
        $created = 0;
        Db::startTrans();
        try {
            foreach ([['sales', $salesUserId], ['cs', $csUserId]] as $item) {
                list($post, $uid) = $item;
                $ratio = (float)$ratios[$post];
                if ($ratio <= 0) continue;   // 0% 不生成行
                Db::name('crm_commission_share')->insert([
                    'store_id'         => $storeId,
                    'biz_type'         => $bizType,
                    'source_type'      => $sourceType,
                    'source_id'        => $sourceId,
                    'source_no'        => $sourceNo,
                    'customer_id'      => $customerId,
                    'project_id'       => $projectId,
                    'user_id'          => $uid,
                    'post_code'        => $post,
                    'role'             => $post === ($bizType === 2 ? 'cs' : 'sales') ? 1 : 2,
                    'ratio'            => $ratio,
                    'base_amount'      => $baseAmount,
                    'share_amount'     => round($baseAmount * $ratio / 100, 2),
                    'confirmed_amount' => 0,
                    'status'           => 1,
                    'group_no'         => 0,
                    'lock_time'        => $now,
                    'override_id'      => $overrideId,
                    'legacy'           => 0,
                    'remark'           => '',
                    'create_time'      => $now,
                    'update_time'      => $now,
                ]);
                $created++;
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return ['success' => false, 'error' => '生成分账计划失败：' . $e->getMessage()];
        }
        return ['success' => true, 'data' => ['list' => (new ShareModel())->getActiveGroup($sourceType, $sourceId), 'created' => $created]];
    }

    // ------------------------------------------------------------------
    // 回款 → 业绩
    // ------------------------------------------------------------------

    /**
     * 按一笔回款生成业绩记录
     * 幂等：同一 payment × share 只生成一条正向记录
     */
    public function confirmPayment(int $paymentId, int $storeId, int $actorId = 0): array
    {
        $payment = Db::name('crm_payment')
            ->where('store_id', $storeId)->where('id', $paymentId)->where('is_delete', 0)->find();
        if (!$payment) return ['success' => false, 'error' => '收款记录不存在'];

        $sourceType = self::SOURCE_ORDER;
        $sourceId = (int)$payment['order_id'];
        if ((int)($payment['contract_id'] ?? 0) > 0) {
            $sourceType = self::SOURCE_CONTRACT;
            $sourceId = (int)$payment['contract_id'];
        }
        if ($sourceId <= 0) return ['success' => true, 'data' => ['count' => 0, 'note' => '收款未关联单据']];

        $shares = (new ShareModel())->getActiveGroup($sourceType, $sourceId);
        if (empty($shares)) {
            $ensured = $this->ensureShares($sourceType, $sourceId, $storeId);
            if (!$ensured['success']) return $ensured;
            $shares = $ensured['data']['list'];
        }
        if (empty($shares)) return ['success' => true, 'data' => ['count' => 0, 'note' => '该单据没有分账计划']];

        $paidAt = (int)($payment['paid_at'] ?: time());
        $month = date('Y-m', $paidAt);
        $amount = round((float)$payment['amount'], 2);
        $count = 0;

        Db::startTrans();
        try {
            foreach ($shares as $share) {
                $already = Db::name('crm_commission_record')
                    ->where('store_id', $storeId)
                    ->where('payment_id', $paymentId)
                    ->where('share_id', (int)$share['id'])
                    ->where('reversal_of_id', 0)
                    ->count();
                if ($already > 0) continue;

                $remaining = round((float)$share['share_amount'] - (float)$share['confirmed_amount'], 2);
                $value = round($amount * (float)$share['ratio'] / 100, 2);
                if ($value > $remaining) $value = $remaining;   // R3：累计不超过应分总额
                if ($value == 0.0) continue;

                Db::name('crm_commission_record')->insert([
                    'store_id'       => $storeId,
                    'share_id'       => (int)$share['id'],
                    'payment_id'     => $paymentId,
                    'customer_id'    => (int)$share['customer_id'],
                    'project_id'     => (int)$share['project_id'],
                    'user_id'        => (int)$share['user_id'],
                    'post_code'      => (string)$share['post_code'],
                    'biz_type'       => (int)$share['biz_type'],
                    'ratio'          => (float)$share['ratio'],
                    'base_amount'    => $amount,
                    'amount'         => $value,
                    'confirm_month'  => $month,
                    'confirmed_at'   => $paidAt,
                    'reversal_of_id' => 0,
                    'legacy'         => 0,
                    'remark'         => '',
                    'create_time'    => time(),
                ]);
                Db::name('crm_commission_share')->where('store_id', $storeId)->where('id', (int)$share['id'])
                    ->inc('confirmed_amount', $value)->update();
                $count++;
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return ['success' => false, 'error' => '生成业绩失败：' . $e->getMessage()];
        }
        return ['success' => true, 'data' => ['count' => $count]];
    }

    /**
     * 冲销某笔回款产生的业绩（生成负数记录，不改历史行）
     */
    public function reversePayment(int $paymentId, int $storeId, int $actorId = 0): array
    {
        $records = Db::name('crm_commission_record')
            ->where('store_id', $storeId)->where('payment_id', $paymentId)
            ->where('reversal_of_id', 0)->select()->toArray();
        if (empty($records)) return ['success' => true, 'data' => ['count' => 0]];

        $month = date('Y-m');
        $count = 0;
        Db::startTrans();
        try {
            foreach ($records as $record) {
                $reversed = Db::name('crm_commission_record')
                    ->where('store_id', $storeId)->where('reversal_of_id', (int)$record['id'])->count();
                if ($reversed > 0) continue;

                $value = -1 * (float)$record['amount'];
                Db::name('crm_commission_record')->insert([
                    'store_id'       => $storeId,
                    'share_id'       => (int)$record['share_id'],
                    'payment_id'     => $paymentId,
                    'customer_id'    => (int)$record['customer_id'],
                    'project_id'     => (int)$record['project_id'],
                    'user_id'        => (int)$record['user_id'],
                    'post_code'      => (string)$record['post_code'],
                    'biz_type'       => (int)$record['biz_type'],
                    'ratio'          => (float)$record['ratio'],
                    'base_amount'    => -1 * (float)$record['base_amount'],
                    'amount'         => $value,
                    'confirm_month'  => $month,
                    'confirmed_at'   => time(),
                    'reversal_of_id' => (int)$record['id'],
                    'legacy'         => 0,
                    'remark'         => '收款冲销',
                    'create_time'    => time(),
                ]);
                Db::name('crm_commission_share')->where('store_id', $storeId)->where('id', (int)$record['share_id'])
                    ->inc('confirmed_amount', $value)->update();
                $count++;
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return ['success' => false, 'error' => '冲销业绩失败：' . $e->getMessage()];
        }
        return ['success' => true, 'data' => ['count' => $count]];
    }

    // ------------------------------------------------------------------
    // 查询
    // ------------------------------------------------------------------

    /**
     * 业绩明细：月度业绩 = Σ record.amount
     */
    public function getRecords(array $filters, int $storeId): array
    {
        $query = Db::name('crm_commission_record')->where('store_id', $storeId);
        if (!empty($filters['user_id']))       $query->where('user_id', (int)$filters['user_id']);
        if (!empty($filters['biz_type']))      $query->where('biz_type', (int)$filters['biz_type']);
        if (!empty($filters['customer_id']))   $query->where('customer_id', (int)$filters['customer_id']);
        if (!empty($filters['month']))         $query->where('confirm_month', (string)$filters['month']);
        if (!empty($filters['start_month']))   $query->where('confirm_month', '>=', (string)$filters['start_month']);
        if (!empty($filters['end_month']))     $query->where('confirm_month', '<=', (string)$filters['end_month']);

        $page = max(1, (int)($filters['page'] ?? 1));
        $size = min(200, max(10, (int)($filters['list_rows'] ?? 20)));
        $total = (clone $query)->count();
        $list = $query->order(['id' => 'desc'])->page($page, $size)->select()->toArray();
        return ['success' => true, 'data' => ['list' => $list, 'total' => $total, 'page' => $page, 'list_rows' => $size]];
    }

    /**
     * 汇总：按人 / 业务类型 / 月份
     */
    public function summary(array $filters, int $storeId): array
    {
        $query = Db::name('crm_commission_record')->where('store_id', $storeId);
        if (!empty($filters['start_month'])) $query->where('confirm_month', '>=', (string)$filters['start_month']);
        if (!empty($filters['end_month']))   $query->where('confirm_month', '<=', (string)$filters['end_month']);
        if (!empty($filters['month']))       $query->where('confirm_month', (string)$filters['month']);

        $byUser = (clone $query)->field('user_id, SUM(amount) AS total')
            ->group('user_id')->order('total', 'desc')->select()->toArray();
        $byBiz = (clone $query)->field('biz_type, SUM(amount) AS total')
            ->group('biz_type')->select()->toArray();
        $byMonth = (clone $query)->field('confirm_month, SUM(amount) AS total')
            ->group('confirm_month')->order('confirm_month', 'asc')->select()->toArray();
        return ['success' => true, 'data' => ['by_user' => $byUser, 'by_biz' => $byBiz, 'by_month' => $byMonth]];
    }

    // ------------------------------------------------------------------
    // 单笔覆盖申请（D10）
    // ------------------------------------------------------------------

    public function applyOverride(array $data, int $storeId, int $userId): array
    {
        $sourceType = (int)($data['source_type'] ?? self::SOURCE_ORDER);
        $sourceId = (int)($data['source_id'] ?? 0);
        if ($sourceId <= 0) return ['success' => false, 'error' => '请选择单据'];

        $sales = round((float)($data['sales_ratio'] ?? 0), 2);
        $cs = round((float)($data['cs_ratio'] ?? 0), 2);
        if ($sales < 0 || $cs < 0 || $sales > 100 || $cs > 100) return ['success' => false, 'error' => '比例必须在 0–100 之间'];
        if (round($sales + $cs, 2) != 100) return ['success' => false, 'error' => '销售与客服比例合计必须为 100%'];
        $reason = trim((string)($data['reason'] ?? ''));
        if ($reason === '') return ['success' => false, 'error' => '请填写申请理由'];

        if ((new \mpp\crm\model\CommissionOverride())->hasPending($sourceType, $sourceId)) {
            return ['success' => false, 'error' => '该单据已有待审批的覆盖申请'];
        }

        $source = $sourceType === self::SOURCE_CONTRACT
            ? Db::name('crm_contract')->where('store_id', $storeId)->where('id', $sourceId)->where('is_delete', 0)->find()
            : Db::name('crm_order')->where('store_id', $storeId)->where('id', $sourceId)->where('is_delete', 0)->find();
        if (!$source) return ['success' => false, 'error' => '业务单据不存在'];

        $bizType = (int)($source['biz_type'] ?? 1) ?: 1;
        $defaults = $this->getRatios($bizType, $storeId);
        $id = Db::name('crm_commission_override')->insertGetId([
            'store_id'            => $storeId,
            'source_type'         => $sourceType,
            'source_id'           => $sourceId,
            'source_no'           => (string)($source[$sourceType === self::SOURCE_CONTRACT ? 'contract_no' : 'order_no'] ?? ''),
            'customer_id'         => (int)$source['customer_id'],
            'biz_type'            => $bizType,
            'default_sales_ratio' => $defaults['sales'],
            'default_cs_ratio'    => $defaults['cs'],
            'sales_ratio'         => $sales,
            'cs_ratio'            => $cs,
            'reason'              => $reason,
            'apply_user_id'       => $userId,
            'apply_time'          => time(),
            'approve_user_id'     => 0,
            'approve_time'        => 0,
            'approve_remark'      => '',
            'status'              => 0,
            'create_time'         => time(),
            'update_time'         => time(),
        ]);
        return ['success' => true, 'data' => ['id' => $id]];
    }

    /**
     * 审批：approve=true 通过，false 驳回。驳回只改状态，不删行。
     */
    public function approveOverride(int $id, int $storeId, int $userId, bool $approve, string $remark = ''): array
    {
        $row = Db::name('crm_commission_override')->where('store_id', $storeId)->where('id', $id)->find();
        if (!$row) return ['success' => false, 'error' => '申请不存在'];
        if ((int)$row['status'] !== 0) return ['success' => false, 'error' => '该申请已处理'];
        if ((int)$row['apply_user_id'] === $userId) return ['success' => false, 'error' => '不能审批自己提交的申请'];

        Db::name('crm_commission_override')->where('store_id', $storeId)->where('id', $id)->update([
            'status'          => $approve ? 1 : 2,
            'approve_user_id' => $userId,
            'approve_time'    => time(),
            'approve_remark'  => $remark,
            'update_time'     => time(),
        ]);
        return ['success' => true, 'data' => null];
    }

    // ------------------------------------------------------------------
    // 规则配置
    // ------------------------------------------------------------------

    /** 三类业务当前生效比例 */
    public function getRatiosAll(int $storeId): array
    {
        $out = [];
        foreach ([1, 2, 3] as $biz) {
            $out[$biz] = $this->getRatios($biz, $storeId);
        }
        return ['success' => true, 'data' => $out];
    }

    /** 保存比例规则（改了只影响新签的单据，已锁定的历史不受影响） */
    public function saveRatios(array $data, int $storeId): array
    {
        $map = ['new' => 1, 'renew' => 2, 'upgrade' => 3];
        Db::startTrans();
        try {
            foreach ($map as $key => $biz) {
                if (!isset($data[$key]) || !is_array($data[$key])) continue;
                $sales = round((float)($data[$key]['sales'] ?? 0), 2);
                $cs = round((float)($data[$key]['cs'] ?? 0), 2);
                if ($sales < 0 || $cs < 0 || round($sales + $cs, 2) != 100) {
                    throw new \RuntimeException('销售与客服比例合计必须为 100%');
                }
                $this->putConfig(self::RATIO_CONFIG[$biz],
                    json_encode(['sales' => $sales, 'cs' => $cs], JSON_UNESCAPED_UNICODE), $storeId);
            }
            Db::commit();
        } catch (\Throwable $e) {
            Db::rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
        return ['success' => true, 'data' => null];
    }

    private function putConfig(string $name, string $value, int $storeId): void
    {
        $row = Db::name('config')->where('config_type', 'crm_setting')
            ->where('config_name', $name)->where('store_id', $storeId)->find();
        if ($row) {
            Db::name('config')->where('id', $row['id'])
                ->update(['config_value' => $value, 'update_time' => time()]);
            return;
        }
        Db::name('config')->insert([
            'store_id' => $storeId, 'config_type' => 'crm_setting', 'config_name' => $name,
            'config_value' => $value, 'sort_order' => 0, 'is_system' => 0, 'status' => 1,
            'create_time' => time(), 'update_time' => time(),
        ]);
    }

    /** 覆盖申请列表 */
    public function getOverrideList(array $filters, int $storeId): array
    {
        $query = Db::name('crm_commission_override')->where('store_id', $storeId);
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', (int)$filters['status']);
        }
        $page = max(1, (int)($filters['page'] ?? 1));
        $size = min(100, max(10, (int)($filters['list_rows'] ?? 20)));
        $total = (clone $query)->count();
        $list = $query->order(['id' => 'desc'])->page($page, $size)->select()->toArray();

        $userIds = array_values(array_unique(array_merge(
            array_column($list, 'apply_user_id'), array_column($list, 'approve_user_id')
        )));
        $users = empty($userIds) ? [] : Db::name('store_user')->where('store_id', $storeId)
            ->whereIn('store_user_id', $userIds)->column('user_name,real_name', 'store_user_id');
        foreach ($list as &$row) {
            $a = isset($users[(int)$row['apply_user_id']]) ? $users[(int)$row['apply_user_id']] : [];
            $b = isset($users[(int)$row['approve_user_id']]) ? $users[(int)$row['approve_user_id']] : [];
            $row['apply_user_name'] = (string)(($a['real_name'] ?? '') ?: ($a['user_name'] ?? ''));
            $row['approve_user_name'] = (string)(($b['real_name'] ?? '') ?: ($b['user_name'] ?? ''));
        }
        unset($row);
        return ['success' => true, 'data' => ['list' => $list, 'total' => $total, 'page' => $page, 'list_rows' => $size]];
    }

    /** 单据作废时让覆盖申请失效 */
    public function invalidateOverride(int $sourceType, int $sourceId, int $storeId): void
    {
        Db::name('crm_commission_override')
            ->where('store_id', $storeId)->where('source_type', $sourceType)->where('source_id', $sourceId)
            ->whereIn('status', [0, 1])->update(['status' => 3, 'update_time' => time()]);
    }
}
