<?php
namespace skills\invoice\backend;

use app\platform\backend\BaseController;
use skills\invoice\model\Invoice as InvoiceModel;
use skills\invoice\service\InvoiceService;

class Invoice extends BaseController
{
    protected $methodRules = [
        'lists'    => 'GET',
        'detail'   => 'GET',
        'add'      => 'POST',
        'addOcr'   => 'POST',
        'delete'   => 'POST',
        'audit'    => 'POST',
        'complete' => 'POST',
        'void'     => 'POST',
        'notify'   => 'POST',
        'upload'   => 'POST',
    ];

    /**
     * 后台手工录入发票
     */
    public function add()
    {
        $data = $this->request->param();
        $storeId = $this->storeId;

        // 必填校验
        if (empty($data['company_id'])) return $this->renderError('请选择公司');
        if (empty($data['total_amount'])) return $this->renderError('请填写金额');

        // 自动取销方信息（从公司档案）
        $company = (new \skills\invoice\model\InvoiceCompany)->detail($data['company_id']);
        $sellerName = $company['name'] ?? '';
        $sellerTaxNumber = $company['tax_number'] ?? '';
        $sellerAddress = $company['address'] ?? '';
        $sellerBank = ($company['bank_name'] ?? '') . ($company['bank_account'] ?? '');

        $model = new InvoiceModel;
        $model->save([
            'store_id'          => $storeId,
            'member_id'         => $data['member_id'] ?? 0,
            'company_id'        => $data['company_id'],
            'direction'         => $data['direction'] ?? 2,
            'buyer_name'        => $data['buyer_name'] ?? '',
            'buyer_tax_number'  => $data['buyer_tax_number'] ?? '',
            'seller_name'       => $data['seller_name'] ?? $sellerName,
            'seller_tax_number' => $data['seller_tax_number'] ?? $sellerTaxNumber,
            'seller_address'    => $data['seller_address'] ?? $sellerAddress,
            'seller_bank'       => $data['seller_bank'] ?? $sellerBank,
            'attach_urls'       => !empty($data['attach_urls']) ? (is_array($data['attach_urls']) ? json_encode($data['attach_urls'], JSON_UNESCAPED_UNICODE) : $data['attach_urls']) : null,
            'amount'            => $data['amount'] ?? 0,
            'tax_rate'          => floatval($data['tax_rate'] ?? 0) / 100, // 百分比转小数
            'total_amount'      => $data['total_amount'],
            'tax_amount'        => $data['tax_amount'] ?? 0,
            'invoice_type'      => $data['invoice_type'] ?? 2,
            'invoice_code'      => $data['invoice_code'] ?? '',
            'invoice_number'    => $data['invoice_number'] ?? '',
            'invoice_date'      => $data['invoice_date'] ?? null,
            'remark'            => $data['remark'] ?? '',
            'status'            => InvoiceModel::STATUS_APPROVED, // 直接已录入
        ]);

        return $this->renderSuccess(['id' => $model->id], '录入成功');
    }

    /**
     * 后台 OCR 识图（复用 API 层 OCR 逻辑）
     */
    public function addOcr()
    {
        $file = $this->request->file('image');
        if (!$file) return $this->renderError('请上传发票图片');

        try {
            $rawContent = file_get_contents($file->getRealPath());
            $imageBase64 = base64_encode($rawContent);

            // 保存图片
            $ext = strtolower(pathinfo($file->getOriginalName(), PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'])) $ext = 'jpg';
            $dir = '/uploads/invoice/';
            $absDir = root_path('public') . $dir;
            if (!is_dir($absDir)) mkdir($absDir, 0755, true);
            $filename = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            file_put_contents($absDir . $filename, $rawContent);
            $imageUrl = $this->request->scheme() . '://' . $this->request->host() . $dir . $filename;

            $ocrResult = \app\platform\ai\OcrGateway::recognize($imageBase64);
            if (!$ocrResult->success) {
                return $this->renderError('OCR 识别失败: ' . $ocrResult->error);
            }

            $ocrText = $ocrResult->content;
            if (!mb_check_encoding($ocrText, 'UTF-8')) {
                $ocrText = mb_convert_encoding($ocrText, 'UTF-8', 'GBK');
            }

            // 调 AI 解析
            $aiResponse = \app\platform\ai\AiGateway::request($this->aiPrompt(), $ocrText);
            $parsed = json_decode($aiResponse->content, true) ?: [];

            // 如果 AI 返回的 JSON 被包裹在 markdown 代码块中，提取出来
            if (empty($parsed) && $aiResponse->content) {
                $content = $aiResponse->content;
                if (preg_match('/```(?:json)?\s*\n?(.*?)\n?```/s', $content, $m)) {
                    $parsed = json_decode(trim($m[1]), true) ?: [];
                }
                // 尝试直接匹配 JSON 对象
                if (empty($parsed) && preg_match('/\{.*\}/s', $content, $m)) {
                    $parsed = json_decode($m[0], true) ?: [];
                }
            }

            // 返回识别结果 + 图片 URL
            $parsed['image_url'] = $imageUrl;
            $parsed['raw_text'] = $ocrText;
            return $this->renderSuccess($parsed);

        } catch (\Throwable $e) {
            return $this->renderError('识别异常: ' . $e->getMessage());
        }
    }

    private function aiPrompt(): string
    {
        return <<<'PROMPT'
你是发票识别助手。从发票/收据/订单的文字中提取结构化信息。

【识别规则】
- counterparty_name: 对方公司名称（购买方或销售方，取非本公司的一方）
- counterparty_tax_number: 对方税号
- amount: 不含税金额
- tax_rate: 税率（百分比整数，如13表示13%）
- total_amount: 价税合计
- invoice_type: 1=增值税专票 2=普票 3=电子发票 4=数电票，默认2
- goods_info: 每项含 name(名称)、quantity(规格数量)、price(单价)、amount(金额)、category(开票类目)、tax_rate(该项税率)
- remark: 发票备注或补充信息
- summary: 一句话摘要（如"技术服务费普票，¥5,000，税率6%"）

必须只返回 JSON，不要任何额外文字：
{
  "counterparty_name": "",
  "counterparty_tax_number": "",
  "amount": "",
  "tax_rate": "",
  "total_amount": "",
  "invoice_type": 2,
  "goods_info": [{"name": "", "quantity": "", "price": "", "amount": "", "category": "", "tax_rate": ""}],
  "remark": "",
  "summary": ""
}
PROMPT;
    }

    public function lists()
    {
        $param = $this->request->param();
        $where = [];
        if (!empty($param['status'])) $where['status'] = $param['status'];
        if (!empty($param['company_id'])) $where['company_id'] = $param['company_id'];
        if (!empty($param['invoice_type'])) $where['invoice_type'] = $param['invoice_type'];
        if (!empty($param['direction'])) $where['direction'] = $param['direction'];

        $list = (new InvoiceModel)->getList($where, $param);
        return $this->renderSuccess(compact('list'));
    }

    public function detail()
    {
        $id = $this->request->param('id', 0);
        $detail = (new InvoiceModel)->detail($id);
        if (!$detail) return $this->renderError('发票不存在');
        $data = $detail instanceof \think\Model ? $detail->toArray() : $detail;
        if (!empty($data['goods_info']) && is_string($data['goods_info'])) {
            $data['goods_info'] = json_decode($data['goods_info'], true) ?: $data['goods_info'];
        }
        return $this->renderSuccess(['detail' => $data]);
    }

    public function audit()
    {
        $id = $this->request->param('id', 0);
        $status = (int) $this->request->param('status', 4);
        $remark = $this->request->param('audit_remark', '');
        $userId = $this->getUserId();

        $result = InvoiceService::audit($id, $status, $remark, $userId, $this->storeId);
        if ($result['success']) return $this->renderSuccess([], '操作成功');
        return $this->renderError($result['error'] ?? '操作失败');
    }

    public function complete()
    {
        $id = $this->request->param('id', 0);
        $data = $this->request->param();

        $result = InvoiceService::complete($id, $data, $this->storeId);
        if ($result['success']) return $this->renderSuccess([], '开票完成');
        return $this->renderError($result['error'] ?? '操作失败');
    }

    public function void()
    {
        $id = $this->request->param('id', 0);
        $model = new InvoiceModel;
        if ($model->void($id)) return $this->renderSuccess([], '作废成功');
        return $this->renderError($model->getError() ?: '作废失败');
    }

    public function upload()
    {
        $file = $this->request->file('iFile');
        if (!$file) return $this->renderError('请选择文件');
        $ext = strtolower(pathinfo($file->getOriginalName(), PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            return $this->renderError('不支持的文件格式');
        }
        $dir = '/uploads/invoice/';
        $absDir = root_path('public') . $dir;
        if (!is_dir($absDir)) mkdir($absDir, 0755, true);
        $filename = date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $file->move($absDir, $filename);
        $relativeUrl = $dir . $filename;
        $host = $this->request->host();
        $scheme = $this->request->scheme();
        $fullUrl = "{$scheme}://{$host}{$relativeUrl}";
        return $this->renderSuccess(['url' => $fullUrl, 'preview_url' => $fullUrl], '上传成功');
    }

    public function delete()
    {
        $id = $this->request->param('id', 0);
        $model = new InvoiceModel;
        $model->setDelete($id);
        return $this->renderSuccess([], '删除成功');
    }

    public function notify()
    {
        $id = $this->request->param('id', 0);
        $result = InvoiceService::notify($id, $this->storeId);
        if ($result['success']) return $this->renderSuccess([], '短信已发送');
        return $this->renderError($result['error'] ?? '发送失败');
    }

    /**
     * PC 后台仪表盘
     */
    public function dashboard()
    {
        $companyId = $this->request->param('company_id', 0);
        if (empty($companyId)) {
            return $this->renderError('请选择公司');
        }
        $data = InvoiceService::getDashboard($companyId, $this->storeId);
        $company = (new \skills\invoice\model\InvoiceCompany)->detail($companyId);
        $data['company_name'] = $company['name'] ?? '';
        return $this->renderSuccess($data);
    }
}
