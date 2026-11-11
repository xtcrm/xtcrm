<?php
namespace mpp\crm\model;

use cores\BaseModel;

/**
 * 分账比例覆盖申请（D10：先申请、后锁定，审批通过才生效）
 */
class CommissionOverride extends BaseModel
{
    protected $name = 'crm_commission_override';
    protected $autoWriteTimestamp = true;
    protected $dateFormat = false;

    /**
     * 某单据是否有待审申请（同一单据同时只允许 1 条）
     */
    public function hasPending(int $sourceType, int $sourceId): bool
    {
        return $this->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('status', 0)
            ->count() > 0;
    }
}
