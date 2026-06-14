<?php
namespace skills\invoice\api;

use app\api\controller\Controller;
use app\platform\ai\OcrGateway;
use app\platform\ai\AiGateway;
use skills\invoice\model\Invoice as InvoiceModel;
use skills\invoice\model\InvoiceCompany;
use skills\invoice\service\InvoiceService;

class Invoice extends Controller
{
    /**
     * AI 识图：上传文档图片 → OCR 提取文字 → DeepSeek 结构化
     */
    public function ocr()
    {
        $storeId = $this->storeId;

        // 方式1: 直接传文字 → AI 分析
        $text = $this->request->param('text', '');
        if (!empty(trim($text))) {
            return $this->aiAnalyze($text);
        }

        // 方式2: 传图片 → OCR 提取文字 → AI 分析
        $file = $this->request->file('image');
        if (!$file) return $this->renderError('请上传图片或输入文字');

        try {
            $imageBase64 = base64_encode(file_get_contents($file->getRealPath()));

            $ocrResult = OcrGateway::recognize($imageBase64);
            if (!$ocrResult->success) {
                return $this->renderError('OCR 识别失败: ' . $ocrResult->error);
            }

            return $this->aiAnalyze($ocrResult->content);

        } catch (\Throwable $e) {
            return $this->renderError('识别异常: ' . $e->getMessage());
        }
    }

    /**
     * AI 结构化分析
     */
    private function aiAnalyze(string $text): \think\response\Json
    {
        $systemPrompt = <<<'PROMPT'
你是资深财税专家。从用户提供的发票/收据文字中提取信息，并根据商品类型给出专业建议。

【税率推荐】
根据货物或服务类型智能推荐税率：
- 货物销售（日用品、电子产品、设备等）→ 13%
- 农产品、图书、暖气等民生类 → 9%
- 建筑、交通运输、邮政 → 9%
- 技术服务、咨询、现代服务业 → 6%
- 生活服务（餐饮、住宿等）→ 6%

【开票类目建议】
根据货物名称推荐类目（category），如：办公用品、电子产品、咨询服务、技术服务、建筑材料等。

【发票类型建议】
- 购买方是一般纳税人（税号18位）→ 建议增值税专票(1)
- 购买方是小规模或个人 → 建议普票(2)
- 不确定 → 普票(2)

【字段说明】
- buyer_name/buyer_tax_number: 购买方（客户）名称和税号
- amount: 不含税合计金额
- tax_rate: 推荐税率，如 13 表示 13%
- total_amount: 价税合计
- goods_info: 每项含 name(名称)、quantity(规格数量)、price(单价)、amount(金额)、category(开票类目)、tax_rate(该项税率)
- suggest_invoice_type: 1=专票 2=普票
- suggestion: 一句话建议（如"建议开13%货物销售普票，类目：办公用品"）

必须返回 JSON：
{
  "buyer_name": "",
  "buyer_tax_number": "",
  "amount": "",
  "tax_rate": "",
  "total_amount": "",
  "goods_info": [{"name": "", "quantity": "", "price": "", "amount": "", "category": "", "tax_rate": ""}],
  "suggest_invoice_type": 2,
  "suggestion": ""
}
PROMPT;

        $aiResult = AiGateway::request($systemPrompt, $text);
        if (!$aiResult->success) {
            return $this->renderSuccess(['raw_text' => $text], 'AI 解析失败，请手动填写');
        }

        $parsed = json_decode($aiResult->content, true);
        if (!$parsed) {
            return $this->renderSuccess(['raw_text' => $text], 'AI 结果解析失败，请手动填写');
        }

        $parsed['raw_text'] = $text;
        return $this->renderSuccess($parsed);
    }

    /**
     * 提交开票申请
     */
    public function submit()
    {
        $data = $this->request->param();
        $storeId = $this->storeId;

        try {
            $memberId = \app\api\service\User::getCurrentLoginUserId();
        } catch (\Exception $e) {
            return $this->renderError('请先登录');
        }

        $result = InvoiceService::submit($data, $storeId, $memberId);
        if ($result['success']) {
            return $this->renderSuccess(['id' => $result['id']], '提交成功');
        }
        return $this->renderError($result['error'] ?? '提交失败');
    }

    /**
     * 我的发票列表
     */
    public function lists()
    {
        $storeId = $this->storeId;
        try {
            $memberId = \app\api\service\User::getCurrentLoginUserId();
        } catch (\Exception $e) {
            return $this->renderError('请先登录');
        }

        // 获取会员绑定的公司
        $company = (new InvoiceCompany)->getByMemberId($memberId, $storeId);
        if (!$company) {
            return $this->renderSuccess(['list' => [], 'total' => 0]);
        }

        $param = $this->request->param();
        $where = ['member_id' => $memberId, 'company_id' => $company['id']];
        if (!empty($param['status'])) $where['status'] = $param['status'];

        $list = (new InvoiceModel)->getList($where, $param);
        return $this->renderSuccess([
            'list' => $list->items(),
            'total' => $list->total(),
            'page' => $list->currentPage(),
            'pageSize' => $list->listRows(),
        ]);
    }

    /**
     * 发票详情
     */
    public function detail()
    {
        $id = $this->request->param('id', 0);
        $detail = (new InvoiceModel)->detail($id);
        if (!$detail) return $this->renderError('发票不存在');
        $data = $detail instanceof \think\Model ? $detail->toArray() : $detail;
        // 解析 goods_info JSON 字符串为数组，前端直接渲染
        if (!empty($data['goods_info']) && is_string($data['goods_info'])) {
            $data['goods_info'] = json_decode($data['goods_info'], true) ?: $data['goods_info'];
        }
        return $this->renderSuccess(['detail' => $data]);
    }
}
