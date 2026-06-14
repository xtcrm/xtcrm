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
        'delete'   => 'POST',
        'audit'    => 'POST',
        'complete' => 'POST',
        'void'     => 'POST',
        'notify'   => 'POST',
        'upload'   => 'POST',
    ];

    public function lists()
    {
        $param = $this->request->param();
        $where = [];
        if (!empty($param['status'])) $where['status'] = $param['status'];
        if (!empty($param['company_id'])) $where['company_id'] = $param['company_id'];
        if (!empty($param['invoice_type'])) $where['invoice_type'] = $param['invoice_type'];

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
}
