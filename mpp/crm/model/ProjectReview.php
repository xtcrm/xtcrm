<?php
namespace mpp\crm\model;

use cores\BaseModel;

/**
 * 项目审核记录（全检，按轮次）
 */
class ProjectReview extends BaseModel
{
    protected $name = 'crm_project_review';
    protected $autoWriteTimestamp = true;
    protected $dateFormat = false;

    /** 0 待审 1 通过 2 退回 */
    const RESULT_PENDING = 0;
    const RESULT_PASS    = 1;
    const RESULT_REJECT  = 2;
}
