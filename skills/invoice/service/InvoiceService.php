<?php
namespace skills\invoice\service;

use skills\invoice\model\Invoice;
use skills\invoice\model\InvoiceCompany;
use skills\invoice\model\InvoiceCompanyMember;
use think\facade\Db;

class InvoiceService
{
    // 税负率红线默认值（百分比整数，如 1 = 1%）
    const DEFAULT_TAX_BURDEN_MIN = 1;

    /**
     * 扫码识票：解析发票二维码并入库
     *
     * 中国电子发票二维码格式（逗号分隔）：
     *   01,10,发票代码,发票号码,不含税金额,开票日期YYYYMMDD,校验码,随机数
     *
     * @param string $qrText    扫码得到的原始文本
     * @param int    $storeId    租户ID
     * @param int    $memberId   会员ID
     * @param int    $companyId  销方公司ID（可选，用于自动判进/销）
     */
    public static function scanQR(string $qrText, int $storeId, int $memberId, int $companyId = 0): array
    {
        $parts = explode(',', trim($qrText));
        if (count($parts) < 6) {
            return ['success' => false, 'error' => '无法识别的二维码格式'];
        }

        // 解析各字段（兼容不同格式）
        $invoiceCode   = trim($parts[2] ?? '');
        $invoiceNumber = trim($parts[3] ?? '');
        $amount        = floatval($parts[4] ?? 0);
        $dateRaw       = trim($parts[5] ?? '');
        $checkCode     = trim($parts[6] ?? '');

        if (empty($invoiceCode) || empty($invoiceNumber) || $amount <= 0) {
            return ['success' => false, 'error' => '二维码数据不完整'];
        }

        // 日期格式转换：YYYYMMDD → YYYY-MM-DD
        $invoiceDate = null;
        if (preg_match('/^\d{8}$/', $dateRaw)) {
            $invoiceDate = substr($dateRaw, 0, 4) . '-' . substr($dateRaw, 4, 2) . '-' . substr($dateRaw, 6, 2);
        }

        // 去重：同发票号码已存在则拒绝
        $exist = (new Invoice)->db()
            ->where('invoice_number', $invoiceNumber)
            ->where('invoice_code', $invoiceCode)
            ->where('is_delete', 0)
            ->find();
        if ($exist) {
            return ['success' => false, 'error' => '该发票已存在'];
        }

        // 判进项/销项：匹配当前公司税号
        $direction = 2; // 默认销项
        if ($companyId > 0) {
            $company = (new InvoiceCompany)->detail($companyId);
            if ($company) {
                // 二维码不含税号信息，暂按销项处理，后续可手工改
                $direction = 2;
            }
        }

        // 快照销方信息
        $sellerName = '';
        $sellerTaxNumber = '';
        if ($companyId > 0) {
            $company = (new InvoiceCompany)->detail($companyId);
            if ($company) {
                $sellerName      = $company['name'];
                $sellerTaxNumber = $company['tax_number'];
            }
        }

        $model = new Invoice;
        $model->save([
            'store_id'          => $storeId,
            'member_id'         => $memberId,
            'company_id'        => $companyId,
            'direction'         => $direction,
            'qr_verified'       => 1,
            'invoice_type'      => $direction === 1 ? 2 : 2, // 默认普票
            'amount'            => $amount,
            'total_amount'      => $amount, // 二维码金额通常为不含税
            'invoice_code'      => $invoiceCode,
            'invoice_number'    => $invoiceNumber,
            'invoice_date'      => $invoiceDate,
            'seller_name'       => $sellerName,
            'seller_tax_number' => $sellerTaxNumber,
            'status'            => Invoice::STATUS_APPROVED, // 扫码直入，无需审核
            'ocr_raw_text'      => $qrText,
        ]);

        return [
            'success'    => true,
            'id'         => $model->id,
            'invoice_code'   => $invoiceCode,
            'invoice_number' => $invoiceNumber,
            'amount'         => $amount,
            'invoice_date'   => $invoiceDate,
            'direction'      => $direction,
        ];
    }

    /**
     * 计算税负率（某公司某月）
     *
     * 税负率 = (销项税额 - 进项税额) / 销项金额 × 100%
     * 返回百分比整数，如 1.89 表示 1.89%
     */
    public static function calcTaxBurden(int $companyId, int $storeId, string $month = null): array
    {
        if (!$month) $month = date('Y-m');
        $monthStart = strtotime($month . '-01');
        $monthEnd   = strtotime($month . '-01 +1 month');

        $t = 'yoshop_invoice_record';

        // 销项汇总
        $output = Db::table($t)
            ->where('store_id', $storeId)
            ->where('company_id', $companyId)
            ->where('direction', 2)
            ->where('status', 'in', [Invoice::STATUS_APPROVED, Invoice::STATUS_ISSUED])
            ->where('is_delete', 0)
            ->where('create_time', '>=', $monthStart)
            ->where('create_time', '<', $monthEnd)
            ->field('COUNT(*) as count, COALESCE(SUM(total_amount),0) as amount, COALESCE(SUM(tax_amount),0) as tax')
            ->find();

        // 进项汇总
        $input = Db::table($t)
            ->where('store_id', $storeId)
            ->where('company_id', $companyId)
            ->where('direction', 1)
            ->where('status', 'in', [Invoice::STATUS_APPROVED, Invoice::STATUS_ISSUED])
            ->where('is_delete', 0)
            ->where('create_time', '>=', $monthStart)
            ->where('create_time', '<', $monthEnd)
            ->field('COUNT(*) as count, COALESCE(SUM(total_amount),0) as amount, COALESCE(SUM(tax_amount),0) as tax')
            ->find();

        $outputAmount = floatval($output['amount'] ?? 0);
        $outputTax    = floatval($output['tax'] ?? 0);
        $inputAmount  = floatval($input['amount'] ?? 0);
        $inputTax     = floatval($input['tax'] ?? 0);

        $netTax = $outputTax - $inputTax;
        $burden = $outputAmount > 0 ? round(($netTax / $outputAmount) * 100, 2) : 0;

        return [
            'month'           => $month,
            'output_count'    => (int) ($output['count'] ?? 0),
            'output_amount'   => $outputAmount,
            'output_tax'      => $outputTax,
            'input_count'     => (int) ($input['count'] ?? 0),
            'input_amount'    => $inputAmount,
            'input_tax'       => $inputTax,
            'net_tax'         => round($netTax, 2),
            'tax_burden'      => $burden,
        ];
    }

    /**
     * 检查税负预警
     */
    public static function checkAlert(int $companyId, int $storeId): array
    {
        // 读阈值配置
        $threshold = self::getAlertThreshold($storeId);
        $enabled = self::isAlertEnabled($storeId);

        $current = self::calcTaxBurden($companyId, $storeId);

        $alerts = [];
        if ($enabled) {
            if ($current['tax_burden'] < $threshold && $current['output_amount'] > 0) {
                $alerts[] = [
                    'type'    => 'tax_burden_low',
                    'message' => "本月税负率 {$current['tax_burden']}%，低于安全线 {$threshold}%，",
                    'level'   => 'warning',
                ];
            }
            // 月末检查：当月无进项记录
            $day = (int) date('d');
            if ($day >= 25 && $current['input_count'] === 0 && $current['output_count'] > 0) {
                $alerts[] = [
                    'type'    => 'no_input',
                    'message' => '本月暂无进项发票记录，请及时采集',
                    'level'   => 'info',
                ];
            }
        }

        return [
            'threshold' => $threshold,
            'enabled'   => $enabled,
            'current'   => $current,
            'alerts'    => $alerts,
            'has_alert' => count($alerts) > 0,
        ];
    }

    /**
     * 仪表盘数据
     */
    public static function getDashboard(int $companyId, int $storeId): array
    {
        $alert = self::checkAlert($companyId, $storeId);
        $current = $alert['current'];

        // 近 6 个月趋势
        $trends = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = date('Y-m', strtotime("-{$i} month"));
            $trends[] = self::calcTaxBurden($companyId, $storeId, $m);
        }

        // 本年度累计
        $yearStart = strtotime(date('Y-01-01'));
        $t = 'yoshop_invoice_record';
        $yearStats = Db::table($t)
            ->where('store_id', $storeId)
            ->where('company_id', $companyId)
            ->where('status', 'in', [Invoice::STATUS_APPROVED, Invoice::STATUS_ISSUED])
            ->where('is_delete', 0)
            ->where('create_time', '>=', $yearStart)
            ->field("
                SUM(CASE WHEN direction=1 THEN total_amount ELSE 0 END) as input_amount,
                SUM(CASE WHEN direction=2 THEN total_amount ELSE 0 END) as output_amount,
                COUNT(CASE WHEN direction=1 THEN 1 END) as input_count,
                COUNT(CASE WHEN direction=2 THEN 1 END) as output_count
            ")
            ->find();

        return [
            'current'     => $current,
            'trends'      => $trends,
            'alert'       => $alert['alerts'],
            'has_alert'   => $alert['has_alert'],
            'threshold'   => $alert['threshold'],
            'year_input_amount'  => floatval($yearStats['input_amount'] ?? 0),
            'year_output_amount' => floatval($yearStats['output_amount'] ?? 0),
            'year_input_count'   => (int) ($yearStats['input_count'] ?? 0),
            'year_output_count'  => (int) ($yearStats['output_count'] ?? 0),
        ];
    }

    /**
     * 导出申报就绪表（某月）
     */
    public static function exportReport(int $companyId, int $storeId, string $month = null): array
    {
        if (!$month) $month = date('Y-m');
        $monthStart = strtotime($month . '-01');
        $monthEnd   = strtotime($month . '-01 +1 month');

        $burden = self::calcTaxBurden($companyId, $storeId, $month);

        $t = 'yoshop_invoice_record';
        $rows = Db::table($t)
            ->where('store_id', $storeId)
            ->where('company_id', $companyId)
            ->where('status', 'in', [Invoice::STATUS_APPROVED, Invoice::STATUS_ISSUED])
            ->where('is_delete', 0)
            ->where('create_time', '>=', $monthStart)
            ->where('create_time', '<', $monthEnd)
            ->field('direction, invoice_code, invoice_number, invoice_date, buyer_name, seller_name, total_amount, tax_amount, goods_info')
            ->order('direction asc, invoice_date asc')
            ->select()
            ->toArray();

        return [
            'month'   => $month,
            'summary' => $burden,
            'items'   => $rows,
        ];
    }

    /**
     * 读税负率红线配置
     */
    private static function getAlertThreshold(int $storeId): float
    {
        $val = Db::name('config')
            ->where('store_id', $storeId)
            ->where('config_type', 'invoice_alert')
            ->where('config_name', 'tax_burden_min')
            ->value('config_value');
        return $val !== null ? floatval($val) : self::DEFAULT_TAX_BURDEN_MIN;
    }

    /**
     * 预警是否启用
     */
    private static function isAlertEnabled(int $storeId): bool
    {
        $val = Db::name('config')
            ->where('store_id', $storeId)
            ->where('config_type', 'invoice_alert')
            ->where('config_name', 'alert_enabled')
            ->value('config_value');
        return $val !== null ? ($val == '1') : true;
    }

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
        $data['status'] = Invoice::STATUS_APPROVED; // 票无忧：直接入库

        // OCR 原始记录
        if (!empty($data['ocr_raw_text'])) {
            $data['ocr_raw_text'] = $data['ocr_raw_text'];
        }
        if (!empty($data['ocr_images'])) {
            $data['ocr_images'] = is_array($data['ocr_images']) ? json_encode($data['ocr_images'], JSON_UNESCAPED_UNICODE) : $data['ocr_images'];
        }
        if (!empty($data['attach_urls'])) {
            $data['attach_urls'] = is_array($data['attach_urls']) ? json_encode($data['attach_urls'], JSON_UNESCAPED_UNICODE) : $data['attach_urls'];
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
        $t = 'yoshop_invoice_record';
        $monthStart = strtotime(date('Y-m-01'));

        $month = Db::table($t)
            ->where('store_id', $storeId)
            ->where('company_id', $companyId)
            ->where('status', Invoice::STATUS_ISSUED)
            ->where('is_delete', 0)
            ->where('create_time', '>=', $monthStart)
            ->field('COUNT(*) as count, COALESCE(SUM(total_amount),0) as amount')
            ->find();

        $total = Db::table($t)
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
