<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\service\CustomerService;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CustomerImport extends BaseController
{
    protected $methodRules = [
        'upload'   => 'POST',
        'confirm'  => 'POST',
        'aiParse'  => 'POST',
        'template' => 'GET',
    ];

    // 列头模糊匹配 → CRM 字段
    private static $fieldAliases = [
        '公司名称' => 'customer_name', '企业名称' => 'customer_name', '客户名称' => 'customer_name',
        '法定代表人' => 'legal_representative', '法人' => 'legal_representative', '法人代表' => 'legal_representative',
        '注册资本' => 'registered_capital',
        '实缴资本' => 'paid_in_capital',
        '成立日期' => 'established_date', '成立时间' => 'established_date',
        '经营范围' => 'business_scope',
        '注册地址' => 'registered_address',
        '公司地址' => 'address', '地址' => 'address',
        '统一社会信用代码' => 'tax_number', '信用代码' => 'tax_number',
        '登记状态' => 'registration_status', '经营状态' => 'registration_status',
        '工商注册号' => 'business_registration_no',
        '组织机构代码' => 'organization_code',
        '纳税人资质' => 'taxpayer_qualification',
        '参保人数' => 'insured_count',
        '公司电话' => 'telephone', '电话' => 'telephone',
        '公司邮箱' => 'email', '邮箱' => 'email',
        '网址' => 'website', '官网' => 'website',
        '公司简介' => 'introduction', '简介' => 'introduction',
        '营业期限' => 'business_term',
        '核准日期' => 'approval_date',
        '登记机关' => 'registration_authority',
        '国标行业' => 'national_industry',
        '简称' => 'short_name',
        '备注' => 'remark',
        // 字典字段（名称列，导入时自动匹配 ID）
        '行业' => 'industry', '主营行业' => 'industry',
        '客户等级' => 'level_name', '等级' => 'level_name',
        '客户来源' => 'source', '来源' => 'source',
        '客户分组' => 'customer_group', '分组' => 'customer_group',
    ];

    /** 上传 Excel 并返回预览数据 */
    public function upload()
    {
        $file = $_FILES['file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return $this->renderError('文件上传失败');
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
            return $this->renderError('仅支持 xlsx/xls/csv 格式');
        }

        // 保存到 runtime
        $tmpDir = \think\facade\App::getRuntimePath() . 'import/';
        if (!is_dir($tmpDir)) mkdir($tmpDir, 0755, true);
        $savePath = $tmpDir . uniqid('import_') . '.' . $ext;
        move_uploaded_file($file['tmp_name'], $savePath);

        try {
            $reader = $ext === 'csv' ? IOFactory::createReader('Csv') : IOFactory::createReader('Xlsx');
            $spreadsheet = $reader->load($savePath);
            $sheet = $spreadsheet->getActiveSheet();
            $rawRows = $sheet->toArray();
        } catch (\Throwable $e) {
            @unlink($savePath);
            return $this->renderError('Excel 解析失败：' . $e->getMessage());
        }
        @unlink($savePath);

        if (count($rawRows) < 2) {
            return $this->renderError('Excel 内容为空或只有表头');
        }

        $headers = array_map('trim', array_shift($rawRows));
        $rows = [];
        foreach ($rawRows as $i => $row) {
            $row = array_map('trim', $row);
            // 跳过全空行
            if (implode('', $row) === '') continue;
            $rows[] = $row;
        }

        // 自动匹配列映射
        $mapping = [];
        foreach ($headers as $idx => $h) {
            $mapping[$idx] = $this->matchField($h);
        }

        // 只返回前 50 行预览，避免传输过大
        return $this->renderSuccess([
            'headers'   => $headers,
            'mapping'   => $mapping,
            'rows'      => array_slice($rows, 0, 50),
            'total'     => count($rows),
            'fields'    => array_unique(array_values(self::$fieldAliases)),
        ]);
    }

    /** 确认导入 */
    public function confirm()
    {
        $rows       = $this->request->param('rows/a', []);
        $mapping    = $this->request->param('mapping/a', []);
        $useArchive = (int)$this->request->param('use_archive', 0) === 1;
        if (empty($rows)) return $this->renderError('没有数据');

        // 预加载字典数据（名称→ID 映射）
        $dicts = $this->loadDicts();

        $svc = new CustomerService();
        $userId = $this->getUserId();
        $success = $skip = $error = 0;
        $errors = [];

        foreach ($rows as $i => $row) {
            $data = ['owner_user_id' => $userId, 'creator_user_id' => $userId];
            foreach ($mapping as $colIdx => $field) {
                if ($field && isset($row[$colIdx]) && $row[$colIdx] !== '') {
                    $data[$field] = $row[$colIdx];
                }
            }

            // 跳过无名称的行
            if (empty($data['customer_name'])) {
                $skip++;
                $errors[] = ['row' => $i + 1, 'reason' => '公司名称为空'];
                continue;
            }

            // 导入核验：只查本地企业档案（不调接口、不产生费用）
            if ($useArchive) {
                $check = (new \skills\entquery\service\CompanyArchive())->verifyAgainst(
                    (string)($data['customer_name'] ?? ''),
                    (string)($data['tax_number'] ?? '')
                );
                if ($check['found'] && !$check['match']) {
                    $skip++;
                    $errors[] = ['row' => $i + 1, 'reason' => '信用代码与企业名称不匹配（档案名称：' . $check['archive_name'] . '）'];
                    continue;
                }
                if ($check['found']) {
                    foreach ($check['fields'] as $k => $v) {
                        if (!isset($data[$k]) || $data[$k] === '') $data[$k] = $v;
                    }
                }
            }

            // 字典字段：名称→ID 匹配（未匹配时自动创建字典项）
            $dictErrors = [];
            $this->resolveDict($data, 'industry_id', 'industry', $dicts['industry'] ?? [], $dictErrors, 'industry');
            $this->resolveDict($data, 'level_id', 'level_name', $dicts['customer_level'] ?? [], $dictErrors, 'customer_level');
            $this->resolveDict($data, 'source_id', 'source', $dicts['customer_source'] ?? [], $dictErrors, 'customer_source');
            $this->resolveDict($data, 'group_id', 'customer_group', $dicts['customer_group'] ?? [], $dictErrors, 'customer_group');
            if (!empty($dictErrors)) {
                $skip++;
                $errors[] = ['row' => $i + 1, 'reason' => implode('；', $dictErrors)];
                continue;
            }

            // 去重检查
            $exists = \think\facade\Db::name('crm_customer')
                ->where('store_id', $this->storeId)
                ->where('customer_name', $data['customer_name'])
                ->where('is_delete', 0)
                ->find();
            if ($exists) {
                $skip++;
                continue;
            }

            $result = $svc->add($data, $userId);
            if ($result['success']) {
                $success++;
            } else {
                $error++;
                $errors[] = ['row' => $i + 1, 'reason' => $result['error']];
            }
        }

        // 导入过程中自动创建了字典项则清缓存
        if (!empty($this->_needClearCache)) {
            \think\facade\Cache::clear();
            \think\facade\Db::name('config')->where('config_type', 'crm_setting')->cache(true);
        }

        return $this->renderSuccess(compact('success', 'skip', 'error', 'errors'));
    }

    /** 加载字典数据 */
    private function loadDicts(): array
    {
        $types = ['industry', 'customer_level', 'customer_source', 'customer_group'];
        $dicts = [];
        foreach ($types as $type) {
            $rows = \think\facade\Db::name('config')
                ->where('store_id', $this->storeId)
                ->where('config_type', $type)
                ->field('config_name, config_value')
                ->select()->toArray();
            $map = [];
            foreach ($rows as $r) {
                $map[trim($r['config_name'])] = intval($r['config_value']);
            }
            $dicts[$type] = $map;
        }
        return $dicts;
    }

    /** 字典名称→ID 匹配（未匹配时自动创建） */
    private function resolveDict(array &$data, string $idField, string $nameField, array $dict, array &$errors, string $dictType = ''): void
    {
        $name = trim($data[$nameField] ?? '');
        if ($name === '') return;

        if (isset($dict[$name])) {
            $data[$idField] = $dict[$name];
            return;
        }
        // 模糊匹配
        foreach ($dict as $dictName => $dictId) {
            if (mb_strpos($name, $dictName) !== false || mb_strpos($dictName, $name) !== false) {
                $data[$idField] = $dictId;
                $data[$nameField] = $dictName;
                return;
            }
        }
        // 未匹配 → 自动创建字典项
        if ($dictType) {
            $maxRow = \think\facade\Db::name('config')->where('config_type', $dictType)
                ->orderRaw('CAST(config_value AS UNSIGNED) DESC')->find();
            $newId = $maxRow ? intval($maxRow['config_value']) + 1 : 1;
            \think\facade\Db::name('config')->insert([
                'store_id' => $this->storeId,
                'config_type' => $dictType,
                'config_name' => $name,
                'config_value' => $newId,
                'sort_order' => 100,
                'create_time' => time(),
                'update_time' => time(),
            ]);
            $data[$idField] = $newId;
            // 更新本地缓存以便后续行匹配
            $dict[$name] = $newId;
            // 标记需要清缓存
            $this->_needClearCache = true;
            return;
        }
        // 无 dictType 时：提示可用值
        $available = implode('、', array_keys($dict));
        $label = ['industry'=>'行业','level_name'=>'客户等级','source'=>'客户来源','customer_group'=>'客户分组'][$nameField] ?? $nameField;
        $errors[] = "{$label}「{$name}」不在可选范围（可用：{$available}）";
    }

    /** AI 智能识别：粘贴企查查等文本 → 提取结构化字段 */
    public function aiParse()
    {
        $text = $this->request->param('text', '');
        if (empty($text) || mb_strlen($text) < 10) {
            return $this->renderError('文本内容太少，请粘贴完整的企查查/天眼查页面内容');
        }

        try {
            $result = \app\common\service\AiService::extractCompanyInfo($text);
            if (!$result['success']) {
                return $this->renderError('AI 解析失败：' . ($result['error'] ?? '未知错误'));
            }
            $data = $result['data'];

            // 自动匹配字典（未匹配自动创建）
            $dicts = $this->loadDicts();
            $dictErrors = [];
            $this->resolveDict($data, 'industry_id', 'industry', $dicts['industry'] ?? [], $dictErrors, 'industry');
            if (!empty($data['national_industry']) && empty($data['industry'])) {
                $data['industry'] = $data['national_industry'];
                $this->resolveDict($data, 'industry_id', 'industry', $dicts['industry'] ?? [], $dictErrors, 'industry');
            }

            return $this->renderSuccess($data);
        } catch (\Throwable $e) {
            return $this->renderError('AI 调用失败：' . $e->getMessage());
        }
    }

    /** 下载空模板 Excel */
    public function template()
    {
        $headers = ['公司名称', '简称', '法定代表人', '注册资本', '实缴资本', '成立日期',
            '经营范围', '注册地址', '详细地址', '统一社会信用代码', '登记状态',
            '工商注册号', '组织机构代码', '公司电话', '公司邮箱', '网址',
            '纳税人资质', '参保人数', '公司简介', '营业期限', '备注'];

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('客户导入模板');
        foreach ($headers as $i => $h) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue($col . '1', $h);
            $sheet->getColumnDimension($col)->setWidth(mb_strlen($h) * 2 + 4);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filePath = \think\facade\App::getRuntimePath() . 'customer_template.xlsx';
        $writer->save($filePath);

        return download($filePath, '客户导入模板.xlsx')->force(false);
    }

    /** 表头 → CRM 字段 */
    private function matchField(string $header): string
    {
        $header = trim($header);
        // 精确匹配
        if (isset(self::$fieldAliases[$header])) {
            return self::$fieldAliases[$header];
        }
        // 模糊匹配
        foreach (self::$fieldAliases as $alias => $field) {
            if (mb_strpos($header, $alias) !== false || mb_strpos($alias, $header) !== false) {
                return $field;
            }
        }
        return '';
    }
}
