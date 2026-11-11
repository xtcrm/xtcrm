<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use mpp\crm\model\Payment as PaymentModel;
use think\facade\Db;

/**
 * 项目收款
 *
 * 每笔实际到账都留一条记录，并回写订单的已收/未收/收款状态。
 */
class PaymentService extends BaseService
{
    public function getList(int $projectId, int $orderId, int $storeId): array
    {
        $this->bindStore($storeId);
        return ['success' => true, 'data' => ['list' => (new PaymentModel())->getList($projectId, $orderId)]];
    }

    public function add(array $data, int $storeId, int $actorId): array
    {
        $this->bindStore($storeId);
        $orderId = (int)($data['order_id'] ?? 0);
        $amount = round((float)($data['amount'] ?? 0), 2);
        if ($orderId <= 0) return ['success' => false, 'error' => '请选择订单'];
        if ($amount <= 0) return ['success' => false, 'error' => '收款金额必须大于 0'];

        $order = Db::name('crm_order')
            ->where('store_id', $storeId)->where('id', $orderId)->where('is_delete', 0)->find();
        if (!$order) return ['success' => false, 'error' => '订单不存在'];
        if ((int)$order['status'] === 6) return ['success' => false, 'error' => '已取消的订单不能收款'];

        $projectId = (int)($data['project_id'] ?? 0) ?: (int)$order['project_id'];
        if ($projectId > 0 && (int)$order['project_id'] > 0 && $projectId !== (int)$order['project_id']) {
            return ['success' => false, 'error' => '订单不属于该项目'];
        }

        $unpaid = round((float)$order['unpaid_amount'], 2);
        if ($amount > $unpaid + 0.001) {
            return ['success' => false, 'error' => '收款金额不能超过未收金额 ¥' . number_format($unpaid, 2)];
        }

        $paidAt = !empty($data['paid_at']) ? (is_numeric($data['paid_at']) ? (int)$data['paid_at'] : strtotime((string)$data['paid_at'])) : time();
        if (!$paidAt) $paidAt = time();

        Db::startTrans();
        try {
            $paymentNo = $this->generateNo($storeId);
            $paymentId = Db::name('crm_payment')->insertGetId([
                'store_id' => $storeId,
                'payment_no' => $paymentNo,
                'project_id' => $projectId,
                'order_id' => $orderId,
                'customer_id' => (int)$order['customer_id'],
                'amount' => $amount,
                'method' => (string)($data['method'] ?? ''),
                'paid_at' => $paidAt,
                'remark' => (string)($data['remark'] ?? ''),
                'actor_user_id' => $actorId,
                'is_delete' => 0,
                'create_time' => time(),
                'update_time' => time(),
            ]);

            $paid = round((float)$order['paid_amount'] + $amount, 2);
            $newUnpaid = round(max((float)$order['final_amount'] - $paid, 0), 2);
            Db::name('crm_order')->where('store_id', $storeId)->where('id', $orderId)->update([
                'paid_amount' => $paid,
                'unpaid_amount' => $newUnpaid,
                'payment_status' => $newUnpaid <= 0.001 ? 3 : ($paid > 0 ? 2 : 1),
                'update_time' => time(),
            ]);

            EventService::record('payment_received', 'order', $orderId, [
                'payment_id' => $paymentId,
                'payment_no' => $paymentNo,
                'amount' => $amount,
                'method' => (string)($data['method'] ?? ''),
                'project_id' => $projectId,
            ], $actorId, $projectId);

            // 回款即出业绩：分账比例在签约时已锁定，这里只读 share.ratio
            $commission = (new CommissionService())->confirmPayment($paymentId, $storeId, $actorId);
            if (!$commission['success']) {
                throw new \RuntimeException($commission['error']);
            }

            Db::commit();
            return ['success' => true, 'data' => ['id' => $paymentId, 'payment_no' => $paymentNo]];
        } catch (\Throwable $e) {
            Db::rollback();
            return ['success' => false, 'error' => '收款失败：' . $e->getMessage()];
        }
    }

    /** 删除收款（冲正）：软删记录，并按剩余记录重算订单金额 */
    public function delete(int $id, int $storeId, int $actorId): array
    {
        $payment = Db::name('crm_payment')
            ->where('store_id', $storeId)->where('id', $id)->where('is_delete', 0)->find();
        if (!$payment) return ['success' => false, 'error' => '收款记录不存在'];

        $orderId = (int)$payment['order_id'];
        Db::startTrans();
        try {
            Db::name('crm_payment')->where('store_id', $storeId)->where('id', $id)->update([
                'is_delete' => 1,
                'update_time' => time(),
            ]);

            $order = Db::name('crm_order')->where('store_id', $storeId)->where('id', $orderId)->find();
            if ($order) {
                $paid = round((float)Db::name('crm_payment')
                    ->where('store_id', $storeId)->where('order_id', $orderId)->where('is_delete', 0)
                    ->sum('amount'), 2);
                $unpaid = round(max((float)$order['final_amount'] - $paid, 0), 2);
                Db::name('crm_order')->where('store_id', $storeId)->where('id', $orderId)->update([
                    'paid_amount' => $paid,
                    'unpaid_amount' => $unpaid,
                    'payment_status' => $unpaid <= 0.001 ? 3 : ($paid > 0 ? 2 : 1),
                    'update_time' => time(),
                ]);
            }

            EventService::record('payment_reversed', 'order', $orderId, [
                'payment_id' => $id,
                'amount' => $payment['amount'],
                'project_id' => (int)$payment['project_id'],
            ], $actorId, (int)$payment['project_id']);

            // 退款冲销：生成负数业绩记录，不改历史行
            $reversal = (new CommissionService())->reversePayment($id, $storeId, $actorId);
            if (!$reversal['success']) {
                throw new \RuntimeException($reversal['error']);
            }

            Db::commit();
            return ['success' => true, 'data' => null];
        } catch (\Throwable $e) {
            Db::rollback();
            return ['success' => false, 'error' => '删除失败：' . $e->getMessage()];
        }
    }

    private function generateNo(int $storeId): string
    {
        $prefix = 'SK-' . date('Ymd') . '-';
        $last = Db::name('crm_payment')
            ->where('store_id', $storeId)->where('payment_no', 'like', $prefix . '%')
            ->order(['id' => 'desc'])->value('payment_no');
        $seq = $last ? intval(substr($last, -3)) + 1 : 1;
        return $prefix . str_pad((string)$seq, 3, '0', STR_PAD_LEFT);
    }

    private function bindStore(int $storeId): void
    {
        PaymentModel::$storeId = $storeId;
    }
}
