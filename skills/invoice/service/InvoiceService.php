<?php
namespace skills\invoice\service;

use skills\invoice\model\Invoice;
use skills\invoice\model\InvoiceCompany;
use skills\invoice\model\InvoiceCompanyMember;
use think\facade\Db;

class InvoiceService
{
    /**
     * 提交开票申请
     */
    public static function submit(array $data, int $storeId, int $memberId): array
    {
        // 校验会员已绑定该公司
        $company = (new InvoiceCompany)->getByMemberId($memberId, $storeId);
        if (!$company) {
            return ['success' => false, 'error' => '请先建档公司信息'];
        }
        if ($company['id'] != ($data['company_id'] ?? 0)) {
            return ['success' => false, 'error' => '无权替该公司提交发票'];
        }
        if ($company['status'] != 1) {
            return ['success' => false, 'error' => '公司已被禁用'];
        }

        // 快照销方信息
        $data['seller_name']       = $company['name'];
        $data['seller_tax_number'] = $company['tax_number'];
        $data['seller_address']    = $company['address'] ?? '';
        $data['seller_bank']       = ($company['bank_name'] ?? '') . ($company['bank_account'] ?? '');

        // 税率：小程序传百分比（如 6 表示 6%），转换为小数（0.06）
        $taxRatePercent = floatval($data['tax_rate'] ?? 0);
        $taxRate = $taxRatePercent / 100;
        $data['tax_rate'] = $taxRate;

        // 校验金额
        $amount = floatval($data['amount'] ?? 0);
        $data['tax_amount'] = round($amount * $taxRate, 2);
        $data['member_id'] = $memberId;
        $data['store_id'] = $storeId;

        // OCR 原始记录
        if (!empty($data['ocr_raw_text'])) {
            $data['ocr_raw_text'] = $data['ocr_raw_text'];
        }
        if (!empty($data['ocr_images'])) {
            $data['ocr_images'] = is_array($data['ocr_images']) ? json_encode($data['ocr_images'], JSON_UNESCAPED_UNICODE) : $data['ocr_images'];
        }

        $model = new Invoice;
        $model->save($data);

        return ['success' => true, 'id' => $model->id];
    }

    /**
     * 审核
     */
    public static function audit(int $id, int $status, string $remark, int $userId, int $storeId): array
    {
        $model = new Invoice;
        $row = $model->detail($id);
        if (!$row) return ['success' => false, 'error' => '发票不存在'];
        if ($row['status'] != Invoice::STATUS_PENDING) {
            return ['success' => false, 'error' => '仅待审核状态可操作'];
        }
        $model->audit($id, $status, $remark, $userId);
        return ['success' => true];
    }

    /**
     * 完成开票
     */
    public static function complete(int $id, array $data, int $storeId): array
    {
        $model = new Invoice;
        if ($model->complete($id, $data)) {
            return ['success' => true];
        }
        return ['success' => false, 'error' => $model->getError() ?: '操作失败'];
    }

    /**
     * 发票统计（某公司）
     */
    public static function stats(int $companyId, int $storeId): array
    {
        $monthStart = strtotime(date('Y-m-01'));

        $month = Db::name('invoice_record')
            ->where('store_id', $storeId)
            ->where('company_id', $companyId)
            ->where('status', Invoice::STATUS_ISSUED)
            ->where('is_delete', 0)
            ->where('create_time', '>=', $monthStart)
            ->field('COUNT(*) as count, COALESCE(SUM(total_amount),0) as amount')
            ->find();

        $total = Db::name('invoice_record')
            ->where('store_id', $storeId)
            ->where('company_id', $companyId)
            ->where('status', Invoice::STATUS_ISSUED)
            ->where('is_delete', 0)
            ->field('COUNT(*) as count, COALESCE(SUM(total_amount),0) as amount')
            ->find();

        return [
            'month_count'   => (int) ($month['count'] ?? 0),
            'month_amount'  => (float) ($month['amount'] ?? 0),
            'total_count'   => (int) ($total['count'] ?? 0),
            'total_amount'  => (float) ($total['amount'] ?? 0),
        ];
    }

    /**
     * 发短信通知
     */
    public static function notify(int $id, int $storeId): array
    {
        $model = new Invoice;
        $row = $model->detail($id);
        if (!$row) return ['success' => false, 'error' => '发票不存在'];

        // 读短信模板
        $templates = Db::name('config')
            ->where('store_id', $storeId)
            ->where('config_type', 'invoice_sms')
            ->select()
            ->toArray();

        $template = '';
        foreach ($templates as $t) {
            if ($t['config_name'] === 'completed') $template = $t['config_value'];
        }
        if (empty($template)) {
            $template = '【雄韬财税】您的发票已开具。发票号码：{invoice_number}，金额：{total_amount}元。请登录小程序查看详情。';
        }

        // 替换变量
        $content = str_replace(
            ['{invoice_number}', '{total_amount}', '{audit_remark}'],
            [$row['invoice_number'] ?? '', $row['total_amount'] ?? '', $row['audit_remark'] ?? ''],
            $template
        );

        // 获取会员手机号
        $member = Db::name('user')
            ->where('user_id', $row['member_id'])
            ->where('store_id', $storeId)
            ->field('mobile')
            ->find();

        if (empty($member['mobile'])) {
            return ['success' => false, 'error' => '未找到会员手机号'];
        }

        // 读短信配置
        $smsConfig = \think\facade\Db::name('config')
            ->where('store_id', $storeId)
            ->where('config_type', 'sms_setting')
            ->select()->toArray();

        $signName = ''; $templateCode = '';
        foreach ($smsConfig as $c) {
            if ($c['config_name'] === 'sms_sign') $signName = $c['config_value'];
            if ($c['config_name'] === 'sms_template') $templateCode = $c['config_value'];
        }
        if (empty($signName) || empty($templateCode)) {
            return ['success' => false, 'error' => '未配置短信签名和模板'];
        }

        // 调用阿里云短信
        $result = \app\platform\notification\SmsGateway::send(
            $member['mobile'],
            $signName,
            $templateCode,
            [
                'invoice_number' => $row['invoice_number'] ?? '',
                'total_amount'   => $row['total_amount'] ?? '',
            ],
            $storeId
        );

        if ($result['success']) {
            $model->updateNotifyStatus($id, 1);
            return ['success' => true];
        }

        $model->updateNotifyStatus($id, 2);
        return ['success' => false, 'error' => $result['error'] ?? '发送失败'];
    }
}
