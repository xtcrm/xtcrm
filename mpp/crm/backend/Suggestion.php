<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\model\AiSuggestion;
use mpp\crm\model\Insight as InsightModel;

class Suggestion extends BaseController
{
    protected $methodRules = [
        'accept' => 'POST',
        'reject' => 'POST',
        'stats' => 'GET',
    ];

    protected function getUserId(): int
    {
        return $this->store['store_user_id'] ?? ($this->store['uid'] ?? 0);
    }

    /** 采纳 AI 建议 */
    public function accept()
    {
        $id = $this->request->param('id', 0);
        $suggestion = AiSuggestion::where('id', $id)->find();
        if (!$suggestion) return $this->renderError('建议不存在');

        $suggestion->accepted = 1;
        $suggestion->accepted_by = $this->getUserId();
        $suggestion->accepted_time = time();
        $suggestion->save();

        // 如果关联 insight，标记 insight 的 suggestion_id 状态
        if ($suggestion->insight_id) {
            $insight = InsightModel::where('id', $suggestion->insight_id)->find();
            if ($insight) {
                $insight->suggestion_id = $suggestion->id;
                $insight->update_time = time();
                $insight->save();
            }
        }

        return $this->renderSuccess([], '已采纳');
    }

    /** 拒绝 AI 建议 */
    public function reject()
    {
        $id = $this->request->param('id', 0);
        $suggestion = AiSuggestion::where('id', $id)->find();
        if (!$suggestion) return $this->renderError('建议不存在');

        $suggestion->accepted = 0;
        $suggestion->accepted_time = time();
        $suggestion->save();

        return $this->renderSuccess([], '已忽略');
    }

    /** 采纳率统计 */
    public function stats()
    {
        $total = AiSuggestion::where('store_id', $this->storeId)->count();
        $accepted = AiSuggestion::where('store_id', $this->storeId)->where('accepted', 1)->count();
        $rejected = AiSuggestion::where('store_id', $this->storeId)->where('accepted', 0)->count();
        $converted = AiSuggestion::where('store_id', $this->storeId)->where('outcome', 'converted')->count();

        return $this->renderSuccess([
            'total' => $total,
            'accepted' => $accepted,
            'rejected' => $rejected,
            'converted' => $converted,
            'accept_rate' => $total > 0 ? round($accepted / $total * 100, 1) : 0,
        ]);
    }
}
