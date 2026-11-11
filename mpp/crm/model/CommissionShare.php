<?php
namespace mpp\crm\model;

use cores\BaseModel;

/**
 * 业绩分账计划（签订时生成，比例锁定）
 */
class CommissionShare extends BaseModel
{
    protected $name = 'crm_commission_share';
    protected $autoWriteTimestamp = true;
    protected $dateFormat = false;

    /**
     * 某单据当前生效的分账组（group_no 最大的一组）
     */
    public function getActiveGroup(int $sourceType, int $sourceId)
    {
        $groupNo = $this->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('status', 1)
            ->max('group_no');
        if ($groupNo === null) return [];
        return $this->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->where('status', 1)
            ->where('group_no', (int)$groupNo)
            ->select()->toArray();
    }
}
