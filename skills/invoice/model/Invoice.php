<?php
namespace skills\invoice\model;

use cores\BaseModel;

class Invoice extends BaseModel
{
    protected $name = 'invoice_record';
    protected $autoWriteTimestamp = true;

    // 状态常量
    const STATUS_PENDING  = 0; // 待审核
    const STATUS_APPROVED = 1; // 审核通过（待开票）
    const STATUS_ISSUED   = 2; // 已开票
    const STATUS_VOIDED   = 3; // 已作废
    const STATUS_REJECTED = 4; // 已驳回

    /**
     * goods_info 自动解析为数组
     */
    public function getGoodsInfoAttr($value)
    {
        if (empty($value)) return [];
        if (is_array($value)) return $value;
        $decoded = json_decode($value, true);
        return $decoded ?: [];
    }

    /**
     * goods_info 写入时序列化
     */
    public function setGoodsInfoAttr($value)
    {
        if (is_array($value)) return json_encode($value, JSON_UNESCAPED_UNICODE);
        return $value;
    }

    public function getList(array $where = [], array $param = [])
    {
        $allowed = ['id', 'company_id', 'member_id', 'invoice_type', 'status', 'invoice_date', 'invoice_number'];
        $fw = [];
        foreach ($where as $k => $v) {
            if (in_array($k, $allowed) && $v !== '' && $v !== null) {
                $fw[$k] = $v;
            }
        }
        return $this->db()
            ->where($fw)
            ->where('is_delete', 0)
            ->order('id desc')
            ->paginate($param);
    }

    public function detail(int $id)
    {
        return $this->db()->where('id', $id)->find();
    }

    public function add(array $data)
    {
        $data['store_id'] = static::$storeId;
        if (empty($data['company_id'])) { $this->error = '销方公司不能为空'; return false; }
        if (empty($data['buyer_name'])) { $this->error = '购方名称不能为空'; return false; }
        if (empty($data['total_amount'])) { $this->error = '开票金额不能为空'; return false; }
        $data['status'] = self::STATUS_PENDING;
        return $this->save($data);
    }

    public function edit(array $data)
    {
        $id = $data['id'];
        unset($data['id']);
        return $this->db()->where('id', $id)->update($data) !== false;
    }

    public function setDelete(int $id)
    {
        return $this->where('id', $id)->update(['is_delete' => 1]);
    }

    /**
     * 审核
     */
    public function audit(int $id, int $status, string $remark = '', int $userId = 0): bool
    {
        $data = [
            'status'        => $status,
            'audit_remark'  => $remark,
            'audit_time'    => time(),
            'audit_user_id' => $userId,
        ];
        return (bool) $this->where('id', $id)->update($data);
    }

    /**
     * 完成开票（回传票图/文字）
     */
    public function complete(int $id, array $data): bool
    {
        $row = $this->db()->where('id', $id)->find();
        if (!$row || $row['status'] != self::STATUS_APPROVED) {
            $this->error = '仅待开票状态可完成';
            return false;
        }
        $update = ['status' => self::STATUS_ISSUED];
        foreach (['invoice_code', 'invoice_number', 'invoice_date', 'invoice_image_url', 'invoice_pdf_url', 'invoice_text'] as $f) {
            if (!empty($data[$f])) $update[$f] = $data[$f];
        }
        if (empty($update['invoice_image_url']) && empty($update['invoice_text'])) {
            $this->error = '至少上传发票图片或填写开票文字';
            return false;
        }
        return (bool) $this->where('id', $id)->update($update);
    }

    /**
     * 作废
     */
    public function void(int $id): bool
    {
        $row = $this->db()->where('id', $id)->find();
        if (!$row || $row['status'] != self::STATUS_ISSUED) {
            $this->error = '仅已开票状态可作废';
            return false;
        }
        return (bool) $this->where('id', $id)->update(['status' => self::STATUS_VOIDED]);
    }

    /**
     * 更新通知状态
     */
    public function updateNotifyStatus(int $id, int $status): bool
    {
        return (bool) $this->where('id', $id)->update([
            'notify_status' => $status,
            'notify_time'   => time(),
        ]);
    }
}
