<?php

namespace mpp\crm\service;

use app\platform\BaseService;
use think\facade\Db;
use think\facade\Log;

/**
 * 客户触点服务（统一时间轴）
 *
 * 约定见 docs/projects/CRM-制造业基础版/客户触点与角色交互底层设计.md：
 *  - 业务表是真相源，crm_touchpoint 是投影
 *  - 所有角色交互统一走 record()
 *  - 客户事件由 EventService 自动投影，其他模块直接调用
 */
class TouchpointService extends BaseService
{
    /** 触点分类 */
    const CATEGORY_LABELS = [
        'sales'      => '销售',
        'service'    => '服务',
        'aftersales' => '售后',
        'tech'       => '技术',
        'finance'    => '财务',
        'presales'   => '售前',
        'delivery'   => '交付',
        'marketing'  => '营销',
        'system'     => '系统',
    ];

    /** @var array 单次请求内的用户名缓存 */
    private static $userNameCache = [];

    /**
     * 写一条客户触点
     *
     * @param int    $customerId   客户ID
     * @param string $category     分类，见 CATEGORY_LABELS
     * @param string $eventType    事件类型
     * @param string $title        中文标题
     * @param array  $detail       结构化数据
     * @param int|null $actorUserId 操作人
     * @param int|null $storeId     租户，CLI/定时任务必须显式传
     * @param array  $options      source_type/source_id/project_id/contact_id/occurred_at/
     *                             action_url/visibility/owner_user_id/dept_id/tags/severity/csat/dedup_key
     * @return int 触点ID，失败或重复返回 0
     */
    public static function record(
        int $customerId,
        string $category,
        string $eventType,
        string $title,
        array $detail = [],
        ?int $actorUserId = null,
        ?int $storeId = null,
        array $options = []
    ): int {
        if ($customerId <= 0) return 0;

        $instance = new static();
        $sid = (int)($storeId ?? $instance->storeId);
        if ($sid <= 0) return 0;

        $now = time();
        $row = [
            'store_id'       => $sid,
            'customer_id'    => $customerId,
            'contact_id'     => (int)($options['contact_id'] ?? 0),
            'project_id'     => (int)($options['project_id'] ?? 0),
            'category'       => isset(self::CATEGORY_LABELS[$category]) ? $category : 'system',
            'source_type'    => (string)($options['source_type'] ?? ''),
            'source_id'      => (int)($options['source_id'] ?? 0),
            'event_type'     => mb_substr($eventType, 0, 50),
            'title'          => mb_substr($title, 0, 200),
            'summary'        => mb_substr((string)($options['summary'] ?? ''), 0, 500),
            'detail'         => empty($detail) ? null : json_encode($detail, JSON_UNESCAPED_UNICODE),
            'action_url'     => mb_substr((string)($options['action_url'] ?? ''), 0, 255),
            'occurred_at'    => (int)($options['occurred_at'] ?? $now),
            'create_time'    => $now,
            'actor_user_id'  => (int)($actorUserId ?? 0),
            'owner_user_id'  => (int)($options['owner_user_id'] ?? 0),
            'dept_id'        => (int)($options['dept_id'] ?? 0),
            'visibility'     => (int)($options['visibility'] ?? 1),
            'tags'           => mb_substr((string)($options['tags'] ?? ''), 0, 255),
            'severity'       => (int)($options['severity'] ?? 0),
            'csat'           => (int)($options['csat'] ?? 0),
            'voided_at'      => 0,
            'dedup_key'      => mb_substr((string)($options['dedup_key'] ?? ''), 0, 64),
        ];

        try {
            return (int)Db::name('crm_touchpoint')->insertGetId($row);
        } catch (\Throwable $e) {
            // 唯一键冲突 = 已经投影过；其他异常记录日志，避免静默丢数据
            if (strpos($e->getMessage(), 'Duplicate') === false) {
                Log::warning('[touchpoint] record failed: ' . $e->getMessage());
            }
            return 0;
        }
    }

    /**
     * 把 crm_event_log 的客户事件投影成触点（由 EventService 调用）
     */
    public static function projectCustomerEvent(
        int $logId,
        int $storeId,
        int $customerId,
        string $eventType,
        array $data,
        ?int $actorUserId = null,
        ?int $projectId = null
    ): void {
        if ($logId <= 0 || $storeId <= 0 || $customerId <= 0) return;

        $title = trim((string)($data['title'] ?? ''));
        if ($title === '') {
            $title = self::buildTitle($storeId, $eventType, $data);
        }

        self::record(
            $customerId,
            self::eventCategory($eventType),
            $eventType,
            $title,
            $data,
            $actorUserId,
            $storeId,
            [
                'source_type' => 'event_log',
                'source_id'   => $logId,
                'project_id'  => (int)($projectId ?? 0),
                'dedup_key'   => '',
            ]
        );
    }

    /**
     * 客户时间轴
     */
    public function getTimeline(int $customerId, array $params = []): array
    {
        $query = Db::name('crm_touchpoint')->alias('t')
            ->leftJoin('store_user u', 'u.store_user_id = t.actor_user_id AND u.store_id = t.store_id')
            ->where('t.store_id', $this->storeId)
            ->where('t.customer_id', $customerId)
            ->where('t.voided_at', 0);

        $category = (string)($params['category'] ?? '');
        if ($category !== '' && isset(self::CATEGORY_LABELS[$category])) {
            $query->where('t.category', $category);
        }
        $sourceType = (string)($params['source_type'] ?? '');
        if ($sourceType !== '') {
            $query->where('t.source_type', $sourceType);
        }

        $page = max(1, (int)($params['page'] ?? 1));
        $size = min(100, max(1, (int)($params['limit'] ?? 20)));

        $total = (clone $query)->count();
        $rows = $query
            ->field('t.*, u.real_name, u.user_name')
            ->order(['t.occurred_at' => 'desc', 't.id' => 'desc'])
            ->page($page, $size)
            ->select()->toArray();

        $list = [];
        foreach ($rows as $row) {
            $detail = json_decode((string)($row['detail'] ?? ''), true);
            $detail = is_array($detail) ? $detail : [];
            $actorName = (string)(($row['real_name'] ?? '') ?: ($row['user_name'] ?? ''));
            $list[] = [
                'id'             => (int)$row['id'],
                'key'            => 't' . $row['id'],
                'kind'           => $row['source_type'] === 'claim_log' ? 'claim' : 'event',
                'category'       => $row['category'],
                'category_label' => self::CATEGORY_LABELS[$row['category']] ?? $row['category'],
                'source_type'    => $row['source_type'],
                'source_id'      => (int)$row['source_id'],
                'event_type'     => $row['event_type'],
                'title'          => $row['title'],
                'remark'         => (string)($detail['remark'] ?? ''),
                'actor'          => (int)$row['actor_user_id'] > 0
                    ? ($actorName !== '' ? $actorName : ('用户#' . $row['actor_user_id']))
                    : '系统',
                'time'           => (int)$row['occurred_at'],
                'time_text'      => (int)$row['occurred_at'] > 0 ? date('Y-m-d H:i', (int)$row['occurred_at']) : '',
                'action_url'     => $row['action_url'],
                'severity'       => (int)$row['severity'],
                'tags'           => $row['tags'] !== '' ? explode(',', $row['tags']) : [],
            ];
        }

        return [
            'list'      => $list,
            'total'     => (int)$total,
            'page'      => $page,
            'page_size' => $size,
        ];
    }

    /**
     * 按来源实体取触点（客服单处理记录等）
     */
    public static function getBySource(int $storeId, string $sourceType, int $sourceId, int $limit = 100): array
    {
        if ($storeId <= 0 || $sourceId <= 0) return [];

        $rows = Db::name('crm_touchpoint')->alias('t')
            ->leftJoin('store_user u', 'u.store_user_id = t.actor_user_id AND u.store_id = t.store_id')
            ->where('t.store_id', $storeId)
            ->where('t.source_type', $sourceType)
            ->where('t.source_id', $sourceId)
            ->where('t.voided_at', 0)
            ->field('t.*, u.real_name, u.user_name')
            ->order(['t.occurred_at' => 'desc', 't.id' => 'desc'])
            ->limit($limit)
            ->select()->toArray();

        $list = [];
        foreach ($rows as $row) {
            $detail = json_decode((string)($row['detail'] ?? ''), true);
            $detail = is_array($detail) ? $detail : [];
            $actorName = (string)(($row['real_name'] ?? '') ?: ($row['user_name'] ?? ''));
            $list[] = [
                'id'             => (int)$row['id'],
                'event_type'     => $row['event_type'],
                'category'       => $row['category'],
                'category_label' => self::CATEGORY_LABELS[$row['category']] ?? $row['category'],
                'title'          => $row['title'],
                'content'        => (string)($detail['content'] ?? ''),
                'remark'         => (string)($detail['remark'] ?? ''),
                'actor'          => (int)$row['actor_user_id'] > 0
                    ? ($actorName !== '' ? $actorName : ('用户#' . $row['actor_user_id']))
                    : '系统',
                'time'           => (int)$row['occurred_at'],
                'time_text'      => (int)$row['occurred_at'] > 0 ? date('Y-m-d H:i', (int)$row['occurred_at']) : '',
                'action_url'     => $row['action_url'],
            ];
        }
        return $list;
    }

    // ------------------------------------------------------------------
    // 内部：事件 → 分类/标题
    // ------------------------------------------------------------------

    private static function eventCategory(string $eventType): string
    {
        if (strpos($eventType, 'payment') === 0) return 'finance';

        $systemTypes = [
            'customer_created', 'customer_updated', 'customer_deleted',
            'customer_claimed', 'customer_released', 'customer_transferred',
            'customer_cs_assigned', 'collaborator_added', 'collaborator_removed',
        ];
        if (in_array($eventType, $systemTypes, true)) return 'system';

        if (preg_match('/^(follow_up|contact|document|customer_tags|quotation|order|contract)/', $eventType)) {
            return 'sales';
        }
        return 'system';
    }

    private static function buildTitle(int $storeId, string $eventType, array $data): string
    {
        switch ($eventType) {
            case 'customer_created':
                return '创建客户';
            case 'customer_deleted':
                return '删除客户';
            case 'customer_claimed':
                return '认领客户';
            case 'customer_released':
                return '释放到公海';
            case 'customer_transferred':
                return '转让负责人：' . self::nameOf($storeId, (int)($data['from_owner'] ?? 0))
                    . ' → ' . self::nameOf($storeId, (int)($data['to_owner'] ?? 0));
            case 'customer_cs_assigned':
                $from = (int)($data['from_user_id'] ?? 0);
                $to = (int)($data['to_user_id'] ?? 0);
                return $from > 0
                    ? '变更客服责任人：' . self::nameOf($storeId, $from) . ' → ' . self::nameOf($storeId, $to)
                    : '分配客服责任人：' . self::nameOf($storeId, $to);
            case 'customer_updated':
                if (!empty($data['owner_changed'])) {
                    return '变更负责人：' . self::nameOf($storeId, (int)($data['from_owner'] ?? 0))
                        . ' → ' . self::nameOf($storeId, (int)($data['to_owner'] ?? 0));
                }
                if (!empty($data['status_changed'])) {
                    $map = [0 => '停用', 1 => '正常'];
                    $from = $map[(int)($data['from_status'] ?? -1)] ?? '未知';
                    $to = $map[(int)($data['to_status'] ?? -1)] ?? '未知';
                    return '变更客户状态：' . $from . ' → ' . $to;
                }
                return '编辑客户资料';
            case 'follow_up_completed':
                return '新增跟进';
            default:
                return $eventType !== '' ? $eventType : '客户动作';
        }
    }

    private static function nameOf(int $storeId, int $userId): string
    {
        if ($userId <= 0) return '未分配';
        $key = $storeId . ':' . $userId;
        if (!array_key_exists($key, self::$userNameCache)) {
            $user = Db::name('store_user')
                ->where('store_id', $storeId)
                ->where('store_user_id', $userId)
                ->field('real_name,user_name')
                ->find();
            self::$userNameCache[$key] = $user
                ? (string)(($user['real_name'] ?? '') ?: ($user['user_name'] ?? ''))
                : '';
        }
        $name = self::$userNameCache[$key];
        return $name !== '' ? $name : ('用户#' . $userId);
    }
}
