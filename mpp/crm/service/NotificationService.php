<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use mpp\crm\model\Notification as NotificationModel;
use mpp\crm\model\Insight as InsightModel;
use think\facade\Db;

class NotificationService extends BaseService
{
    /** 从活跃 insight 生成通知（每日执行一次） */
    public function generateDigest(int $storeId): int
    {
        $count = 0;
        $today = date('Y-m-d');

        // 今日新生成的活跃 insight
        $insights = InsightModel::where('store_id', $storeId)
            ->where('status', 'active')
            ->where('create_date', $today)
            ->select()->toArray();

        if (empty($insights)) return 0;

        // 按用户分组
        $byUser = [];
        foreach ($insights as $insight) {
            $byUser[$insight['user_id']][] = $insight;
        }

        foreach ($byUser as $userId => $list) {
            // 合并同类通知：如果同类型 > 1 条，合并为一条摘要
            $byType = [];
            foreach ($list as $insight) {
                $byType[$insight['type']][] = $insight;
            }

            foreach ($byType as $type => $items) {
                $count += $this->createNotification($userId, $type, $items, $storeId);
            }
        }

        return $count;
    }

    protected function createNotification(int $userId, string $type, array $items, int $storeId): int
    {
        $now = time();
        $title = '';
        $content = '';

        if (count($items) == 1) {
            $item = $items[0];
            $title = $item['title'];
            $content = $item['summary'];
            $actionUrl = $item['action_url'];
            $sourceId = $item['id'];
        } else {
            $typeNames = [
                'followup_overdue' => '报价未回复',
                'repurchase_window' => '复购窗口',
                'pool_warning' => '公海预警',
                'churn_risk' => '流失风险',
                'dormant' => '沉睡客户',
            ];
            $typeName = $typeNames[$type] ?? $type;
            $title = count($items) . " 个客户处于{$typeName}状态";
            $content = implode('、', array_column($items, 'title'));
            // 合并通知点击跳转到工作台（工作台已展示洞察卡片）
            $actionUrl = '/index';
            $sourceId = 0;
        }

        $model = new NotificationModel();
        $model->user_id = $userId;
        $model->type = 'insight_alert';
        $model->title = $title;
        $model->content = $content;
        $model->source_type = 'insight';
        $model->source_id = $sourceId;
        $model->action_url = $actionUrl;
        $model->create_time = $now;
        $model->store_id = $storeId;
        $model->save();

        return 1;
    }

    /**
     * 推送一条站内通知（通用入口）
     *
     * 与 generateDigest 那条洞察链路互不影响：后者是把多条 insight 合并成一条摘要，
     * 本方法是「一个业务事件一条通知」，供工单等业务模块直接调用。
     *
     * @param int    $userId     收件人 store_user.store_user_id
     * @param int    $storeId    租户ID，必传。定时/CLI 进程无请求上下文，全局 scope 不生效
     * @param string $title      标题，超 200 字符截断
     * @param string $content    正文，超 500 字符截断（crm_notification.content 是 varchar(500)）
     * @param string $type       通知类型，如 workorder_assigned
     * @param string $sourceType 来源模块，如 workorder
     * @param int    $sourceId   来源业务ID
     * @param string $actionUrl  点击跳转地址，超 200 字符截断
     * @return int   新通知ID，参数不合法时返回 0
     */
    public function push(
        int $userId,
        int $storeId,
        string $title,
        string $content,
        string $type = 'system',
        string $sourceType = 'system',
        int $sourceId = 0,
        string $actionUrl = ''
    ): int {
        if ($userId <= 0 || $storeId <= 0) {
            return 0;
        }

        $model = new NotificationModel();
        $model->user_id = $userId;
        $model->store_id = $storeId;
        $model->type = mb_substr($type, 0, 50);
        $model->title = mb_substr($title, 0, 200);
        $model->content = mb_substr($content, 0, 500);
        $model->source_type = mb_substr($sourceType, 0, 50);
        $model->source_id = $sourceId;
        $model->action_url = mb_substr($actionUrl, 0, 200);
        $model->is_read = 0;
        $model->create_time = time();
        $model->save();

        return (int)$model->id;
    }

    /** 获取用户未读通知列表 */
    public function getUnread(int $userId): array
    {
        $list = NotificationModel::where('user_id', $userId)
            ->where('is_read', 0)
            ->order('create_time', 'desc')
            ->limit(20)
            ->select()->toArray();
        $count = NotificationModel::where('user_id', $userId)->where('is_read', 0)->count();
        return ['success' => true, 'data' => compact('list', 'count'), 'error' => ''];
    }

    /** 标记已读 */
    public function markAsRead(int $id, int $userId): array
    {
        $notification = NotificationModel::where('id', $id)->where('user_id', $userId)->find();
        if (!$notification) return ['success' => false, 'data' => null, 'error' => '通知不存在'];
        $notification->is_read = 1;
        $notification->read_time = time();
        $notification->save();
        return ['success' => true, 'data' => null, 'error' => ''];
    }

    /** 标记全部已读 */
    public function markAllRead(int $userId): array
    {
        NotificationModel::where('user_id', $userId)->where('is_read', 0)
            ->update(['is_read' => 1, 'read_time' => time()]);
        return ['success' => true, 'data' => null, 'error' => ''];
    }

    /**
     * 清理旧通知（30天已读 + 90天未读）
     * @param int $storeId 必须显式传租户：定时进程无请求上下文，全局 scope 不生效，不传会跨租户误删
     */
    public function cleanup(int $storeId): int
    {
        $count = 0;
        // 30天已读清理
        $cutoff30 = time() - 30 * 86400;
        $count += NotificationModel::where('store_id', $storeId)
            ->where('is_read', 1)
            ->where('create_time', '<', $cutoff30)->delete();

        // 90天未读标记已读
        $cutoff90 = time() - 90 * 86400;
        $count += NotificationModel::where('store_id', $storeId)
            ->where('is_read', 0)
            ->where('create_time', '<', $cutoff90)
            ->update(['is_read' => 1, 'read_time' => time()]);

        return $count;
    }
}
