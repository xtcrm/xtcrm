<?php
namespace skills\contentengine\backend;

use app\platform\backend\BaseController;
use skills\contentengine\service\ContentGenerator;

class Editor extends BaseController
{
    protected $methodRules = [
        'generate' => 'POST', 'save' => 'POST', 'outputs' => 'GET',
        'calendar' => 'GET', 'stats' => 'GET',
    ];

    protected function getUserId(): int
    {
        return $this->store['store_user_id'] ?? ($this->store['uid'] ?? 0);
    }

    public function generate()
    {
        if (!$this->checkAction('/crm.editor/generate', 'AI生成')) return;
        $topicId = $this->request->param('topic_id', 0);
        if ($topicId <= 0) return $this->renderError('请选择选题');
        $agentId = $this->request->param('agent_id', '');
        $result = (new ContentGenerator())->generate((int)$topicId, $this->storeId, $agentId);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess($result['data'], '生成完成');
    }

    public function outputs()
    {
        if (!$this->checkAction('/crm.editor/generate', '查看内容')) return;
        $topicId = $this->request->param('topic_id', 0);
        $result = (new ContentGenerator())->getOutputs((int)$topicId, $this->storeId);
        return $this->renderSuccess($result['data']);
    }

    public function save()
    {
        if (!$this->checkAction('/crm.editor/save', '保存内容')) return;
        $id = $this->request->param('id', 0);
        $content = $this->request->param('content', '');
        $result = (new ContentGenerator())->saveOutput((int)$id, $content, $this->storeId);
        if (!$result['success']) return $this->renderError($result['error']);
        return $this->renderSuccess([], '已保存');
    }

    /** 内容日历：返回指定月份的内容 */
    public function calendar()
    {
        if (!$this->checkAction('/crm.editor/generate', '查看内容')) return;
        $month = $this->request->param('month', date('Y-m'));
        $start = strtotime($month . '-01');
        $end = strtotime($month . '-' . date('t', $start) . ' 23:59:59');

        // 有排期的用publish_time，没有的用create_time兜底
        $rows = \think\facade\Db::name('crm_content_output')
            ->where('store_id', $this->storeId)
            ->where(function($q) use ($start, $end) {
                $q->where('publish_time', '>=', $start)->where('publish_time', '<=', $end)
                  ->whereOr(function($q2) use ($start, $end) {
                      $q2->where('publish_time', 0)->whereOr('publish_time', null)
                         ->where('create_time', '>=', $start)->where('create_time', '<=', $end);
                  });
            })
            ->field('id,topic_id,title,format,publish_time,create_time,status')
            ->order('publish_time', 'asc')
            ->select()->toArray();

        // 按日期分组
        $byDate = [];
        foreach ($rows as $r) {
            $ts = $r['publish_time'] ?: $r['create_time'];
            $d = date('Y-m-d', $ts);
            $r['scheduled'] = !empty($r['publish_time']);
            $byDate[$d][] = $r;
        }

        return $this->renderSuccess(['month' => $month, 'days' => count($byDate), 'schedule' => $byDate]);
    }

    /** 效果统计 */
    public function stats()
    {
        if (!$this->checkAction('/crm.editor/generate', '查看内容')) return;

        $total = \think\facade\Db::name('crm_content_output')
            ->where('store_id', $this->storeId)->count();
        $published = \think\facade\Db::name('crm_content_output')
            ->where('store_id', $this->storeId)->where('status', 3)->count();
        $totalViews = \think\facade\Db::name('crm_content_output')
            ->where('store_id', $this->storeId)->sum('views');
        $totalInquiries = \think\facade\Db::name('crm_content_output')
            ->where('store_id', $this->storeId)->sum('inquiries');

        // 按格式统计
        $byFormat = \think\facade\Db::name('crm_content_output')
            ->where('store_id', $this->storeId)
            ->field('format,count(*) as cnt,sum(views) as v,sum(inquiries) as iq')
            ->group('format')->select()->toArray();

        // Top 10 高阅读
        $topViewed = \think\facade\Db::name('crm_content_output')
            ->where('store_id', $this->storeId)->where('views', '>', 0)
            ->field('id,title,format,views,inquiries')
            ->order('views', 'desc')->limit(10)->select()->toArray();

        // 最近30天趋势
        $trend = \think\facade\Db::name('crm_content_output')
            ->where('store_id', $this->storeId)
            ->where('create_time', '>=', time() - 30 * 86400)
            ->field("FROM_UNIXTIME(create_time,'%Y-%m-%d') as d, count(*) as cnt")
            ->group('d')->order('d', 'asc')->select()->toArray();

        return $this->renderSuccess([
            'total' => $total, 'published' => $published,
            'views' => (int)$totalViews, 'inquiries' => (int)$totalInquiries,
            'by_format' => $byFormat, 'top_viewed' => $topViewed, 'trend' => $trend,
        ]);
    }
}
