<?php

namespace skills\quotecn\service;

use think\facade\Db;
use cores\SkillTemplate;
use mpp\crm\model\Quotation;
use mpp\crm\model\QuotationItem;
use mpp\crm\model\Customer;

/**
 * 中文报价单服务（多租户隔离，全部走 Model 查询）
 */
class QuotationCnService
{
    /**
     * 获取租户公司模板配置（只查租户，无全局默认）
     * 空值由前端占位符和模板 fallback 显示引导用户填写
     */
    private function loadCompanyConfig(int $storeId): array
    {
        if ($storeId <= 0) return [];

        $rows = Db::name('config')
            ->where('config_type', 'quotation_cn_template')
            ->where('store_id', $storeId)
            ->select()
            ->toArray();

        $config = [];
        foreach ($rows as $row) {
            $config[$row['config_name']] = $row['config_value'];
        }

        return $config;
    }

    /**
     * 渲染报价模板数据
     */
    private function buildTemplateData(Quotation $quotation, int $storeId): array
    {
        $customer = [];
        if (!empty($quotation['customer_id'])) {
            $customer = Customer::where('id', $quotation['customer_id'])
                ->where('is_delete', 0)->find();
        }

        $items = QuotationItem::where('quotation_id', $quotation['id'])
            ->where('is_delete', 0)
            ->order(['sort_order' => 'asc'])
            ->select()
            ->toArray();

        $company = $this->loadCompanyConfig($storeId);

        // 模板变量替换
        $validDays = $quotation['valid_days'] ?? 30;
        $termsText = $company['terms_text'] ?? '';
        $termsText = str_replace('{{valid_days}}', (string)$validDays, $termsText);
        $termsText = str_replace('{{delivery_days}}', '15', $termsText);
        $company['terms_text'] = $termsText;

        // 金额大写
        $finalAmount = floatval($quotation['final_amount'] ?? $quotation['total_amount'] ?? 0);
        $cnAmount = SkillTemplate::cnMoney($finalAmount);
        if (!empty($quotation['cn_amount_text'])) {
            $cnAmount = $quotation['cn_amount_text'];
        }

        // 币种符号
        $currencySymbols = ['CNY' => '¥', 'USD' => '$', 'EUR' => '€', 'JPY' => '¥', 'HKD' => 'HK$'];
        $currency = $quotation['currency'] ?? 'CNY';
        $currencySymbol = $currencySymbols[$currency] ?? $currency;

        // Logo 完整 URL
        $logoUrl = '';
        if (!empty($company['company_logo'])) {
            $domain = rtrim(($_SERVER['REQUEST_SCHEME'] ?? 'https') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'), '/');
            $logoUrl = $domain . '/uploads/' . ltrim($company['company_logo'], '/');
        }

        return [
            'quotation' => $quotation,
            'customer' => $customer ?: [],
            'items' => $items,
            'company' => $company,
            'cn_amount' => $cnAmount,
            'currency_symbol' => $currencySymbol,
            'logo_url' => $logoUrl,
        ];
    }

    // ============================
    //  公开 API
    // ============================

    public function preview(int $id, int $storeId): array
    {
        $quotation = Quotation::where('id', $id)->where('is_delete', 0)->find();
        if (empty($quotation)) {
            return ['success' => false, 'data' => null, 'error' => '报价单不存在'];
        }
        $data = $this->buildTemplateData($quotation, $storeId);
        try {
            $html = SkillTemplate::render('quotecn', 'quotation_cn.php', $data);
            return ['success' => true, 'data' => ['html' => $html], 'error' => ''];
        } catch (\RuntimeException $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    public function exportPdf(int $id, int $storeId): array
    {
        $quotation = Quotation::where('id', $id)->where('is_delete', 0)->find();
        if (empty($quotation)) {
            return ['success' => false, 'data' => null, 'error' => '报价单不存在'];
        }
        $data = $this->buildTemplateData($quotation, $storeId);
        $pdfContent = SkillTemplate::renderPdf('quotecn', 'quotation_cn.php', $data);

        if ($pdfContent === null) {
            try {
                $html = SkillTemplate::render('quotecn', 'quotation_cn.php', $data);
                return ['success' => true, 'data' => ['html' => $html, 'type' => 'html'], 'error' => ''];
            } catch (\RuntimeException $e) {
                return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
            }
        }

        $filename = ($quotation['quotation_no'] ?? 'quotation') . '.pdf';
        return ['success' => true, 'data' => [
            'pdf_base64' => base64_encode($pdfContent), 'filename' => $filename, 'type' => 'pdf',
        ], 'error' => ''];
    }

    public function exportWord(int $id, int $storeId): array
    {
        $quotation = Quotation::where('id', $id)->where('is_delete', 0)->find();
        if (empty($quotation)) {
            return ['success' => false, 'data' => null, 'error' => '报价单不存在'];
        }
        $data = $this->buildTemplateData($quotation, $storeId);
        try {
            $html = SkillTemplate::render('quotecn', 'quotation_cn.php', $data);
            $wordHtml = '<html xmlns:o="urn:schemas-microsoft-com:office:office"'
                . ' xmlns:w="urn:schemas-microsoft-com:office:word"'
                . ' xmlns="http://www.w3.org/TR/REC-html40">'
                . '<head><meta charset="utf-8"><title>报价单</title></head><body>'
                . $html . '</body></html>';
            $filename = ($quotation['quotation_no'] ?? 'quotation') . '.doc';
            return ['success' => true, 'data' => ['html' => $wordHtml, 'filename' => $filename, 'type' => 'word'], 'error' => ''];
        } catch (\RuntimeException $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    public function exportExcel(int $id, int $storeId): array
    {
        $quotation = Quotation::where('id', $id)->where('is_delete', 0)->find();
        if (empty($quotation)) {
            return ['success' => false, 'data' => null, 'error' => '报价单不存在'];
        }
        $data = $this->buildTemplateData($quotation, $storeId);
        $q = $data['quotation'];
        $customer = $data['customer'];
        $items = $data['items'];
        $company = $data['company'];

        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->setCellValue('A1', $company['company_name'] ?? '公司名称');
            $sheet->mergeCells('A1:H1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal('center');
            $sheet->setCellValue('A2', '报 价 单');
            $sheet->mergeCells('A2:H2');
            $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A2')->getAlignment()->setHorizontal('center');

            $sheet->setCellValue('A4', '客户：' . ($customer['customer_name'] ?? ''));
            $sheet->setCellValue('E4', '报价单号：' . ($q['quotation_no'] ?? ''));
            $sheet->setCellValue('A5', '报价日期：' . ($q['quotation_date'] ? date('Y-m-d', $q['quotation_date']) : ''));
            $sheet->setCellValue('E5', '有效期：' . ($q['valid_days'] ?? 30) . '天');
            $sheet->setCellValue('A6', '币种：' . ($q['currency'] ?? 'CNY'));

            $headers = ['序号', '产品名称', '规格型号', '单位', '数量', '单价', '金额', '备注'];
            $col = 'A';
            foreach ($headers as $h) { $sheet->setCellValue($col . '8', $h); $col++; }
            $sheet->getStyle('A8:H8')->getFont()->setBold(true);
            $sheet->getStyle('A8:H8')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFE0E0E0');

            $row = 9; $i = 0;
            foreach ($items as $item) {
                $i++;
                $sheet->setCellValue('A' . $row, $i);
                $sheet->setCellValue('B' . $row, $item['product_name'] ?? '');
                $sheet->setCellValue('C' . $row, $item['specification'] ?? '');
                $sheet->setCellValue('D' . $row, $item['unit'] ?? '');
                $sheet->setCellValue('E' . $row, floatval($item['quantity'] ?? 0));
                $sheet->setCellValue('F' . $row, floatval($item['unit_price'] ?? 0));
                $sheet->setCellValue('G' . $row, floatval($item['amount'] ?? 0));
                $sheet->setCellValue('H' . $row, $item['remark'] ?? '');
                $row++;
            }

            $sumRow = $row + 1;
            $sheet->setCellValue('F' . $sumRow, '合计：');
            $sheet->setCellValue('G' . $sumRow, floatval($q['total_amount'] ?? 0));
            $sheet->getStyle('F' . $sumRow . ':G' . $sumRow)->getFont()->setBold(true);
            if (floatval($q['discount_amount'] ?? 0) > 0) {
                $sumRow++;
                $sheet->setCellValue('F' . $sumRow, '折扣：');
                $sheet->setCellValue('G' . $sumRow, -floatval($q['discount_amount'] ?? 0));
            }
            $sumRow++;
            $sheet->setCellValue('F' . $sumRow, '总计：');
            $sheet->setCellValue('G' . $sumRow, floatval($q['final_amount'] ?? $q['total_amount'] ?? 0));
            $sheet->getStyle('F' . $sumRow . ':G' . $sumRow)->getFont()->setBold(true)->setSize(13);

            $sheet->getStyle('F9:G' . $sumRow)->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('E9:E' . ($sumRow - 2))->getNumberFormat()->setFormatCode('#,##0.00');
            $sheet->getStyle('A8:H' . ($sumRow - 2))->getBorders()->getAllBorders()
                ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            $sheet->getColumnDimension('A')->setWidth(6);
            $sheet->getColumnDimension('B')->setWidth(22);
            $sheet->getColumnDimension('C')->setWidth(16);
            $sheet->getColumnDimension('D')->setWidth(8);
            $sheet->getColumnDimension('E')->setWidth(10);
            $sheet->getColumnDimension('F')->setWidth(14);
            $sheet->getColumnDimension('G')->setWidth(14);
            $sheet->getColumnDimension('H')->setWidth(12);

            $dir = runtime_path() . 'cache' . DIRECTORY_SEPARATOR . 'skills';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $tmpFile = $dir . DIRECTORY_SEPARATOR . uniqid('excel_') . '.xlsx';
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($tmpFile);

            $content = file_get_contents($tmpFile);
            @unlink($tmpFile);

            $filename = ($q['quotation_no'] ?? 'quotation') . '.xlsx';
            return ['success' => true, 'data' => ['file_base64' => base64_encode($content), 'filename' => $filename, 'type' => 'excel'], 'error' => ''];
        } catch (\Throwable $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage()];
        }
    }

    /**
     * 保存公司模板配置（按租户隔离）
     */
    public function saveTemplate(array $config, int $storeId): array
    {
        $allowedKeys = ['company_name', 'company_address', 'company_phone',
            'bank_name', 'bank_account', 'tax_no', 'terms_text', 'footer_text',
            'company_logo', 'logo_file_id'];

        foreach ($config as $key => $value) {
            if (!in_array($key, $allowedKeys)) continue;

            $exists = Db::name('config')
                ->where('config_type', 'quotation_cn_template')
                ->where('config_name', $key)
                ->where('store_id', $storeId)
                ->find();

            if ($exists) {
                Db::name('config')
                    ->where('config_type', 'quotation_cn_template')
                    ->where('config_name', $key)
                    ->where('store_id', $storeId)
                    ->update(['config_value' => $value, 'update_time' => time()]);
            } else {
                Db::name('config')->insert([
                    'config_type' => 'quotation_cn_template',
                    'config_name' => $key,
                    'config_value' => $value,
                    'store_id' => $storeId,
                    'create_time' => time(),
                    'update_time' => time(),
                ]);
            }
        }

        return ['success' => true, 'data' => [], 'error' => ''];
    }

    /**
     * 获取公司模板配置
     */
    public function getTemplateConfig(int $storeId): array
    {
        $config = $this->loadCompanyConfig($storeId);
        return ['success' => true, 'data' => $config, 'error' => ''];
    }

    /**
     * AI 可调用：获取报价单数据
     */
    public function getQuotationData(array $args): array
    {
        $query = Quotation::where('is_delete', 0);

        if (!empty($args['id'])) {
            $query->where('id', (int)$args['id']);
        } elseif (!empty($args['quotation_no'])) {
            $query->where('quotation_no', 'like', '%' . $args['quotation_no'] . '%');
        } elseif (!empty($args['customer_name'])) {
            $customerIds = Customer::where('customer_name', 'like', '%' . $args['customer_name'] . '%')
                ->where('is_delete', 0)->column('id');
            if (empty($customerIds)) {
                return ['success' => false, 'data' => null, 'error' => '未找到匹配的报价单'];
            }
            $query->whereIn('customer_id', $customerIds);
        } elseif (!empty($args['keyword'])) {
            $kw = $args['keyword'];
            $query->where(function($q) use ($kw) {
                $q->where('quotation_no', 'like', "%{$kw}%");
            });
            $customerIds = Customer::where('customer_name', 'like', "%{$kw}%")
                ->where('is_delete', 0)->column('id');
            if (!empty($customerIds)) {
                $query->whereOr('customer_id', 'in', $customerIds);
            }
        } else {
            return ['success' => false, 'data' => null, 'error' => '请提供报价单ID、报价单号或客户名称'];
        }

        $list = $query->order('id', 'desc')->limit(5)->select()->toArray();

        if (empty($list)) {
            return ['success' => false, 'data' => null, 'error' => '未找到匹配的报价单'];
        }

        $statusMap = [1 => '草稿', 2 => '已发送', 3 => '已确认', 4 => '已拒绝', 5 => '已转订单'];
        foreach ($list as &$q) {
            $customer = Customer::where('id', $q['customer_id'])->find();
            $q['customer_name'] = $customer['customer_name'] ?? '';
            $q['status_text'] = $statusMap[$q['status']] ?? '未知';
            $q['quotation_date_cn'] = $q['quotation_date'] ? date('Y-m-d', $q['quotation_date']) : '';
        }

        if (count($list) === 1) {
            $quotation = Quotation::where('id', (int)$list[0]['id'])->find();
            $data = $this->buildTemplateData($quotation, 0);
            $items = $data['items'];
            $q = $data['quotation'];
            return ['success' => true, 'data' => [
                'found' => 1,
                'quotation' => [
                    'id' => (int)$list[0]['id'], 'quotation_no' => $q['quotation_no'],
                    'customer_name' => $data['customer']['customer_name'] ?? '',
                    'currency' => $q['currency'],
                    'quotation_date' => $q['quotation_date'] ? date('Y-m-d', $q['quotation_date']) : '',
                    'valid_days' => (int)($q['valid_days'] ?? 30),
                    'total_amount' => (float)($q['total_amount'] ?? 0),
                    'discount_amount' => (float)($q['discount_amount'] ?? 0),
                    'final_amount' => (float)($q['final_amount'] ?? $q['total_amount'] ?? 0),
                    'cn_amount' => $data['cn_amount'],
                    'status_text' => $statusMap[$q['status']] ?? '未知',
                    'items_count' => count($items),
                    'items' => array_slice(array_map(function($item) {
                        return ['product_name' => $item['product_name'], 'specification' => $item['specification'] ?? '',
                            'quantity' => (float)$item['quantity'], 'unit_price' => (float)$item['unit_price'], 'amount' => (float)$item['amount']];
                    }, $items), 0, 10),
                    'remark' => $q['remark'] ?? '',
                ],
                'hint' => '用户可打开详情页预览中文报价，或导出PDF。链接：/crm/quotation/cn-preview?id=' . (int)$list[0]['id'],
            ], 'error' => ''];
        }

        return ['success' => true, 'data' => [
            'found' => count($list), 'list' => $list,
            'hint' => '找到多条记录，请用户选择其中一条',
        ], 'error' => ''];
    }
}
