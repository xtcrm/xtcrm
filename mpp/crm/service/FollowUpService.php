<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use mpp\crm\model\FollowUp as FollowUpModel;
use mpp\crm\service\OperationLogService;

class FollowUpService extends BaseService
{
    /** 动态前缀表名，避免硬编码 yoshop_ */
    private static function tb(string $name): string
    {
        return \think\facade\Db::getConfig('prefix') . $name;
    }

    public function getList(int $customerId = 0): array
    {
        $model = new FollowUpModel();
        $list = $model->getList($customerId);
        return ['success' => true, 'data' => compact('list'), 'error' => ''];
    }

    public function add(array $data, int $actorUserId): array
    {
        $data['owner_user_id'] = $actorUserId;

        // 关联需求时：校验归属，并按需求补齐项目
        $intentId = (int)($data['intent_id'] ?? 0);
        if ($intentId > 0) {
            $intent = \think\facade\Db::name('crm_intent')
                ->where('store_id', (int)$this->storeId)->where('id', $intentId)
                ->where('is_delete', 0)->find();
            if (!$intent) return ['success' => false, 'data' => null, 'error' => '关联需求不存在'];
            if (!empty($data['customer_id']) && (int)$intent['customer_id'] !== (int)$data['customer_id']) {
                return ['success' => false, 'data' => null, 'error' => '需求与客户不匹配'];
            }
            if (empty($data['project_id'])) $data['project_id'] = (int)$intent['project_id'];
        }

        $model = new FollowUpModel();
        if ($model->add($data)) {
            // 附件同时归档到文件袋（crm_document），归档失败不阻断跟进保存
            $this->archiveAttachment((int)$model->id, $data, $actorUserId);
            EventService::record('follow_up_completed', 'customer', $data['customer_id'] ?? 0, [
                'followup_id' => $model->id,
                'project_id' => (int)($data['project_id'] ?? 0),
                'intent_id' => (int)($data['intent_id'] ?? 0),
                'follow_type' => $data['follow_type'] ?? '',
                'result' => $data['result'] ?? '',
            ], $actorUserId, (int)($data['project_id'] ?? 0));
            OperationLogService::logAdd('跟进记录', $model->id, '客户ID:'.($data['customer_id']??0));
            try { InsightService::handleEvent('follow_up_completed', $data['customer_id'] ?? 0); } catch (\Throwable $e) {}
            return ['success' => true, 'data' => ['id' => $model->id], 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $model->getError() ?: '添加失败'];
    }

    /**
     * 跟进附件归档到文件袋（crm_document），并回写 crm_followup.attachment_doc_id
     *
     * 引用式统一：crm_document 是文件唯一档案，跟进记录只存引用；
     * crm_followup.attachment 路径字段保留，用于列表直接渲染 + 归档失败时兜底预览。
     * 归档失败只记日志，不影响跟进保存。
     */
    private function archiveAttachment(int $followupId, array $data, int $actorUserId): int
    {
        $path = trim((string)($data['attachment'] ?? ''));
        if ($path === '' || $followupId <= 0) return 0;

        $storeId = (int)$this->storeId;
        try {
            // 上传阶段 /upload/file 已落 upload_file，这里按路径找回 file_id 和原始文件名
            $file = \think\facade\Db::name('upload_file')
                ->where('store_id', $storeId)
                ->where('file_path', $path)
                ->where('is_delete', 0)
                ->order('file_id', 'asc')
                ->find();
            if (!$file) {
                \think\facade\Log::warning('[跟进附件归档] 未找到上传文件记录 followup=' . $followupId . ' path=' . $path);
                return 0;
            }

            $docId = (int)\think\facade\Db::name('crm_document')->insertGetId([
                'store_id'      => $storeId,
                'customer_id'   => (int)($data['customer_id'] ?? 0),
                'project_id'    => (int)($data['project_id'] ?? 0),
                'quotation_id'  => 0,
                'order_id'      => 0,
                'contract_id'   => 0,
                'file_id'       => (int)$file['file_id'],
                'category'      => '沟通附件',
                'title'         => $file['file_name'] ?: basename($path),
                'remark'        => '来自沟通记录 #' . $followupId,
                'owner_user_id' => $actorUserId,
                'is_delete'     => 0,
                'create_time'   => time(),
                'update_time'   => time(),
            ]);

            \think\facade\Db::name('crm_followup')
                ->where('store_id', $storeId)
                ->where('id', $followupId)
                ->update(['attachment_doc_id' => $docId]);

            return $docId;
        } catch (\Throwable $e) {
            \think\facade\Log::error('[跟进附件归档] followup=' . $followupId . ' ' . $e->getMessage());
            return 0;
        }
    }

    public function edit(int $id, array $data, int $actorUserId): array
    {
        $model = new FollowUpModel();
        $detail = $model->where('id', $id)->find();
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '跟进记录不存在'];
        if ($detail->edit($data)) {
            OperationLogService::logEdit('跟进记录', $id, 'ID:'.$id, $data);
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $detail->getError() ?: '更新失败'];
    }

    public function delete(int $id, int $actorUserId): array
    {
        $model = new FollowUpModel();
        $detail = $model->where('id', $id)->find();
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '跟进记录不存在'];
        $customerId = $detail['customer_id'] ?? 0;
        $detail->is_delete = 1;
        if ($detail->save()) {
            OperationLogService::logDelete('跟进记录', $id, 'ID:'.$id);
            EventService::record('customer_updated', 'customer', $customerId, [
                'followup_deleted' => true, 'followup_id' => $id,
            ], $actorUserId);
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => '删除失败'];
    }

    /** 日历事件：拜访 + 联系人生日 + 公司成立日 */
    public static function calendarEvents(int $storeId, string $start, string $end, int $userId = 0, int $customerId = 0): array
    {
        $startTs = strtotime($start);
        $endTs = strtotime($end) + 86399;
        $events = [];

        $yearStart = date('Y', $startTs);
        $yearEnd = date('Y', $endTs);

        // 1. 拜访事件
        $query = \think\facade\Db::name('crm_followup')->alias('f')
            ->join(self::tb('crm_customer') . ' c', 'c.id = f.customer_id AND c.is_delete = 0')
            ->leftJoin(self::tb('store_user') . ' cu', 'cu.store_user_id = c.owner_user_id')
            ->where('f.store_id', $storeId)->where('f.is_delete', 0)
            ->where('f.next_follow_date', '>=', $startTs)->where('f.next_follow_date', '<=', $endTs)
            ->field("f.id, f.customer_id, f.follow_type, f.next_follow_content, f.next_follow_date, f.follow_content, c.customer_name, cu.real_name as owner_user_name, cu.user_name, 'visit' as event_type")
            ->order('f.next_follow_date', 'asc');
        if ($userId > 0) $query->where('f.owner_user_id', $userId);
        if ($customerId > 0) $query->where('f.customer_id', $customerId);
        $events = array_merge($events, $query->select()->toArray());

        // 预加载客户负责人映射（一次查询，避免 N+1）
        $ownerMap = \think\facade\Db::name('crm_customer')
            ->alias('c')
            ->join(self::tb('store_user') . ' u', 'u.store_user_id = c.owner_user_id', 'LEFT')
            ->where('c.store_id', $storeId)->where('c.is_delete', 0)
            ->field('c.id, c.owner_user_id, COALESCE(u.real_name, u.user_name) as owner_name')
            ->select()->toArray();
        $ownerById = [];
        foreach ($ownerMap as $o) {
            $ownerById[$o['id']] = ['owner_user_id' => $o['owner_user_id'], 'owner_user_name' => $o['owner_name'] ?? ''];
        }

        // 2. 联系人生日（通过中间表关联客户，取首个关联客户）
        $birthdayContacts = \think\facade\Db::name('crm_contact')->alias('ct')
            ->leftJoin(self::tb('crm_contact_customer') . ' cc', 'cc.contact_id = ct.id')
            ->where('ct.store_id', $storeId)->where('ct.is_delete', 0)->where('ct.birthday', '<>', '')
            ->field("ct.id, ct.contact_name, ct.mobile, ct.birthday, ct.is_lunar, cc.customer_id, 'birthday' as event_type")
            ->group('ct.id')
            ->select()->toArray();

        foreach ($birthdayContacts as $c) {
            $bcCustomerId = (int)($c['customer_id'] ?? 0);
            $owner = $ownerById[$bcCustomerId] ?? [];
            $isLunar = (int)($c['is_lunar'] ?? 0);
            $mmdd = substr($c['birthday'], 5);
            for ($y = $yearStart; $y <= $yearEnd; $y++) {
                if ($isLunar) {
                    // 农历生日 → 转公历
                    $lunarMonth = (int)substr($mmdd, 0, 2);
                    $lunarDay = (int)substr($mmdd, 3, 2);
                    $solar = \cores\LunarCalendar::toSolar($y, $lunarMonth, $lunarDay);
                    if (!$solar) continue;
                    $ts = strtotime($solar);
                } else {
                    $ts = strtotime("{$y}-{$mmdd}");
                }
                if ($ts >= $startTs && $ts <= $endTs) {
                    $events[] = [
                        'id' => 'bday_' . $c['id'] . '_' . $y,
                        'customer_id' => $bcCustomerId,
                        'contact_name' => $c['contact_name'],
                        'mobile' => $c['mobile'],
                        'birthday' => $c['birthday'],
                        'is_lunar' => $isLunar,
                        'follow_type' => '生日' . ($isLunar ? '（农历）' : ''),
                        'next_follow_content' => $c['contact_name'] . ' 生日' . ($isLunar ? '（农历）' : ''),
                        'next_follow_date' => $ts,
                        'event_type' => 'birthday',
                        'owner_user_id' => $owner['owner_user_id'] ?? 0,
                        'owner_user_name' => $owner['owner_user_name'] ?? '',
                        'user_name' => '',
                        'customer_name' => '',
                    ];
                }
            }
        }

        // 3. 公司成立日
        $companies = \think\facade\Db::name('crm_customer')
            ->where('store_id', $storeId)->where('is_delete', 0)->where('established_date', '<>', '')
            ->field("id, customer_name, owner_user_id, established_date, 'established' as event_type")
            ->select()->toArray();

        foreach ($companies as $co) {
            $owner = $ownerById[$co['id']] ?? [];
            $mmdd = substr($co['established_date'], 5);
            for ($y = $yearStart; $y <= $yearEnd; $y++) {
                $ts = strtotime("{$y}-{$mmdd}");
                if ($ts >= $startTs && $ts <= $endTs) {
                    $events[] = [
                        'id' => 'est_' . $co['id'] . '_' . $y,
                        'customer_id' => $co['id'],
                        'customer_name' => $co['customer_name'],
                        'follow_type' => '周年',
                        'next_follow_content' => $co['customer_name'] . ' 成立' . ($y - intval(substr($co['established_date'], 0, 4))) . '周年',
                        'next_follow_date' => $ts,
                        'event_type' => 'established',
                        'owner_user_id' => $owner['owner_user_id'] ?? 0,
                        'owner_user_name' => $owner['owner_user_name'] ?? '',
                        'user_name' => '',
                    ];
                }
            }
        }

        // 4. 合同到期
        $contracts = \think\facade\Db::name('crm_contract')->alias('ct')
            ->join(self::tb('crm_customer') . ' c', 'c.id = ct.customer_id AND c.is_delete = 0')
            ->where('ct.store_id', $storeId)->where('ct.is_delete', 0)
            ->where('ct.end_date', '>', 0)
            ->where('ct.end_date', '>=', $startTs)->where('ct.end_date', '<=', $endTs)
            ->field("ct.id, ct.customer_id, c.customer_name, ct.contract_name, ct.contract_no, ct.end_date, 'contract_expiry' as event_type")
            ->select()->toArray();

        foreach ($contracts as $ct) {
            $owner = $ownerById[$ct['customer_id']] ?? [];
            $daysLeft = ceil(($ct['end_date'] - time()) / 86400);
            $label = $daysLeft > 0 ? "{$daysLeft}天后到期" : ($daysLeft === 0 ? '今天到期' : '已过期');
            $events[] = [
                'id' => 'ctexp_' . $ct['id'],
                'customer_id' => $ct['customer_id'],
                'customer_name' => $ct['customer_name'] ?? '',
                'follow_type' => '合同到期',
                'next_follow_content' => ($ct['contract_name'] ?: $ct['contract_no']) . ' ' . $label,
                'next_follow_date' => (int)$ct['end_date'],
                'event_type' => 'contract_expiry',
                'owner_user_id' => $owner['owner_user_id'] ?? 0,
                'owner_user_name' => $owner['owner_user_name'] ?? '',
                'user_name' => '',
            ];
        }

        // 4.5 工单期望完成（与「合同到期」同类，都是 deadline）
        //
        // 只取未完成的工单 —— 已完成/已取消的还留在日历上会把远期月份淹掉。
        // 权限口径：非超管（$userId > 0）只看「指派给我 或 我建的」。
        // 不能只按 assignee_user_id 过滤，否则自己建了还没指派的单会看不见。
        $woQuery = \think\facade\Db::name('workorder_ticket')->alias('t')
            ->leftJoin(self::tb('crm_customer') . ' c', 'c.id = t.customer_id AND c.is_delete = 0')
            ->where('t.store_id', $storeId)->where('t.is_delete', 0)
            ->whereNotIn('t.status', [4, 5])
            ->where('t.expect_date', '>', 0)
            ->where('t.expect_date', '>=', $startTs)->where('t.expect_date', '<=', $endTs)
            ->field('t.id, t.ticket_no, t.title, t.expect_date, t.status, t.priority, t.customer_id, t.assignee_user_id, t.submitter_user_id, t.type_name, c.customer_name');
        if ($userId > 0) {
            $woQuery->where(function ($q) use ($userId) {
                $q->where('t.assignee_user_id', $userId)->whereOr('t.submitter_user_id', $userId);
            });
        }
        if ($customerId > 0) {
            $woQuery->where('t.customer_id', $customerId);
        }

        $workorders = $woQuery->select()->toArray();

        // 处理人/建单人姓名，一次查出来（避免逐行查库）
        $woUserNames = [];
        if (!empty($workorders)) {
            $woUserIds = [];
            foreach ($workorders as $wo) {
                if ($wo['assignee_user_id'])  $woUserIds[] = (int)$wo['assignee_user_id'];
                if ($wo['submitter_user_id']) $woUserIds[] = (int)$wo['submitter_user_id'];
            }
            if (!empty($woUserIds)) {
                $uRows = \think\facade\Db::name('store_user')
                    ->whereIn('store_user_id', array_unique($woUserIds))
                    ->column('real_name,user_name', 'store_user_id');
                foreach ($uRows as $uid => $u) {
                    $woUserNames[(int)$uid] = $u['real_name'] ?: $u['user_name'];
                }
            }
        }

        foreach ($workorders as $wo) {
            $daysLeft = (int)ceil(($wo['expect_date'] - time()) / 86400);
            $label = $daysLeft > 0 ? "{$daysLeft}天后到期"
                : ($daysLeft === 0 ? '今天到期' : '已逾期' . abs($daysLeft) . '天');
            $typeName = $wo['type_name'] ?: '工单';

            // 工单的归属人按「处理人，没指派则建单人」算 —— 必须与上面的过滤口径一致。
            // 末尾还有一段「非超管只看 owner_user_id === 自己」的后置过滤，
            // 若这里填成客户负责人，「指派给我但客户不是我的」工单会被误杀。
            $woOwnerId = (int)$wo['assignee_user_id'] ?: (int)$wo['submitter_user_id'];

            $events[] = [
                'id' => 'wo_' . $wo['id'],
                'workorder_id' => (int)$wo['id'],
                'customer_id' => (int)$wo['customer_id'],
                'customer_name' => $wo['customer_name'] ?? '',
                'follow_type' => '工单',
                'next_follow_content' => $wo['ticket_no'] . ' ' . $typeName
                    . ($wo['title'] ? '「' . $wo['title'] . '」' : '') . ' · ' . $label,
                'next_follow_date' => (int)$wo['expect_date'],
                'event_type' => 'workorder',
                'owner_user_id' => $woOwnerId,
                'owner_user_name' => $woUserNames[$woOwnerId] ?? '',
                'user_name' => '',
            ];
        }

        // 5. 自定义日历事件（含重复规则展开）
        $customEvents = \think\facade\Db::name('crm_calendar_event')->alias('ce')
            ->leftJoin(self::tb('crm_customer') . ' c', 'c.id = ce.customer_id AND c.is_delete = 0')
            ->where('ce.store_id', $storeId)->where('ce.is_delete', 0)
            ->field('ce.*, c.customer_name')
            ->select()->toArray();

        foreach ($customEvents as $ce) {
            // 农历事件：先转为公历
            $isLunar = (int)($ce['is_lunar'] ?? 0);
            $origDate = $ce['event_date'];  // 保存原始日期
            if ($isLunar && !empty($ce['event_date'])) {
                $lunarDate = $ce['event_date'];
                $lunarMonth = (int)substr($lunarDate, 5, 2);
                $lunarDay = (int)substr($lunarDate, 8, 2);
                $solar = \cores\LunarCalendar::toSolar((int)substr($lunarDate, 0, 4), $lunarMonth, $lunarDay);
                if ($solar) $ce['event_date'] = $solar;
            }
            foreach (self::expandEventDates($ce, $startTs, $endTs) as $ts) {
                $cid = (int)($ce['customer_id'] ?? 0);
                // 自定义事件以创建者 owner_user_id 为准（不跟客户归属）
                $ownerUserId = $ce['owner_user_id'] ?? 0;
                $ownerUserName = $cid > 0 ? ($ownerById[$cid]['owner_user_name'] ?? '') : '';
                $events[] = [
                    'id' => 'cu_' . $ce['id'] . '_' . date('Ymd', $ts),
                    'customer_id' => $cid,
                    'customer_name' => $ce['customer_name'] ?? '',
                    'follow_type' => $ce['title'] . ($isLunar ? '（农历）' : ''),
                    'next_follow_content' => ($ce['content'] ?: '') . ($isLunar ? '（农历）' : ''),
                    'next_follow_date' => $ts,
                    'event_type' => 'custom',
                    '_color' => $ce['color'] ?? 'blue',
                    '_eventId' => (int)$ce['id'],
                    '_original_date' => $origDate,
                    '_is_lunar' => $isLunar,
                    '_repeat_type' => $ce['repeat_type'],
                    '_repeat_rule' => $ce['repeat_rule'],
                    '_end_date' => $ce['end_date'],
                    'owner_user_id' => $ownerUserId,
                    'owner_user_name' => $ownerUserName,
                    'user_name' => '',
                ];
            }
        }

        // 6. 加载已有祝福语
        $greetings = \think\facade\Db::name('crm_calendar_greeting')
            ->where('store_id', $storeId)
            ->where('event_date', '>=', $start)
            ->where('event_date', '<=', $end)
            ->select()->toArray();
        $greetingMap = [];
        foreach ($greetings as $g) {
            $key = $g['event_type'] . '|' . $g['event_date'] . '|' . $g['target_id'];
            $greetingMap[$key] = $g['greeting'];
        }

        // 注入已有祝福语
        foreach ($events as &$ev) {
            $date = date('Y-m-d', $ev['next_follow_date'] ?? 0);
            $tid = (string)($ev['id'] ?? '');
            $key = ($ev['event_type'] ?? 'visit') . '|' . $date . '|' . $tid;
            if (isset($greetingMap[$key])) {
                $ev['_greeting'] = $greetingMap[$key];
            }
            $ev['_event_date'] = $date;
            $ev['_target_id'] = $tid;
        }
        unset($ev);

        // 按用户过滤非跟进事件（生日/周年/合同/自定义事件）
        // 跟进事件已在 SQL 层过滤，此处统一兜底确保跨用户数据不泄漏
        if ($userId > 0) {
            $events = array_values(array_filter($events, function ($e) use ($userId) {
                $ownerId = $e['owner_user_id'] ?? 0;
                // owner=0 表示无归属（如未关联客户的联系人），所有人可见
                return $ownerId === 0 || $ownerId === $userId;
            }));
        }

        // 按时间排序
        usort($events, function ($a, $b) {
            return ($a['next_follow_date'] ?? 0) - ($b['next_follow_date'] ?? 0);
        });

        // 按客户过滤（适用于生日/成立日等非跟进事件）
        if ($customerId > 0) {
            $events = array_values(array_filter($events, fn($e) => ($e['customer_id'] ?? 0) == $customerId));
        }
        return ['list' => $events, 'total' => count($events)];
    }

    /** 周拜访：按日期范围查询 */
    public static function weekVisits(int $storeId, string $start, string $end, int $userId = 0): array
    {
        $startTs = strtotime($start);
        $endTs = strtotime($end) + 86399;
        $query = \think\facade\Db::name('crm_followup')->alias('f')
            ->join(self::tb('crm_customer') . ' c', 'c.id = f.customer_id AND c.is_delete = 0')
            ->leftJoin(self::tb('store_user') . ' cu', 'cu.store_user_id = c.owner_user_id')
            ->leftJoin(self::tb('store_user') . ' fu', 'fu.store_user_id = f.owner_user_id')
            ->where('f.store_id', $storeId)
            ->where('f.is_delete', 0)
            ->where('f.next_follow_date', '>=', $startTs)
            ->where('f.next_follow_date', '<=', $endTs)
            ->field('f.*, c.customer_name, cu.real_name as owner_user_name, cu.user_name, fu.real_name as followup_owner_name')
            ->order('f.next_follow_date', 'asc');
        if ($userId > 0) $query->where('f.owner_user_id', $userId);
        $list = $query->select()->toArray();
        return ['list' => $list, 'total' => count($list)];
    }

    /** 今日拜访：next_follow_date 在今日范围内的跟进记录 */
    public static function todayVisits(int $storeId, int $userId = 0): array
    {
        $todayStart = strtotime(date('Y-m-d'));
        $todayEnd = $todayStart + 86399;

        $query = \think\facade\Db::name('crm_followup')->alias('f')
            ->join(self::tb('crm_customer') . ' c', 'c.id = f.customer_id AND c.is_delete = 0')
            ->leftJoin(self::tb('store_user') . ' fu', 'fu.store_user_id = f.owner_user_id')
            ->leftJoin(self::tb('store_user') . ' cu', 'cu.store_user_id = c.owner_user_id')
            ->where('f.store_id', $storeId)
            ->where('f.is_delete', 0)
            ->where('f.next_follow_date', '>=', $todayStart)
            ->where('f.next_follow_date', '<=', $todayEnd)
            ->field('f.*, c.customer_name, c.owner_user_id as customer_owner_id,
                fu.real_name as followup_owner_name,
                cu.real_name as owner_user_name, cu.user_name')
            ->order('f.next_follow_date', 'asc');

        if ($userId > 0) {
            $query->where('f.owner_user_id', $userId);
        }

        $list = $query->select()->toArray();
        return ['list' => $list, 'total' => count($list)];
    }

    /** 展开自定义事件的重复规则，返回日期时间戳数组 */
    private static function expandEventDates(array $event, int $startTs, int $endTs): array
    {
        $dates = [];
        $eventDate = strtotime($event['event_date']);
        if (!$eventDate) return [];

        $endLimit = !empty($event['end_date'])
            ? min(strtotime($event['end_date']), $endTs)
            : $endTs;
        $rule = json_decode($event['repeat_rule'] ?? '{}', true) ?: [];
        $interval = max(1, intval($rule['interval'] ?? 1));
        $type = $event['repeat_type'] ?? 'none';

        switch ($type) {
            case 'none':
                if ($eventDate >= $startTs && $eventDate <= $endTs) $dates[] = $eventDate;
                break;

            case 'daily':
                $cursor = $eventDate;
                while ($cursor <= $endLimit) {
                    if ($cursor >= $startTs) $dates[] = $cursor;
                    $cursor = strtotime("+{$interval} days", $cursor);
                }
                break;

            case 'weekly':
                $days = $rule['days'] ?? [(int)date('N', $eventDate)];
                $cursor = $eventDate;
                $weekStep = $interval * 7;
                // 从 event_date 开始逐周推进
                while ($cursor <= $endLimit) {
                    // 检查本周内的每个指定星期
                    $weekStart = strtotime('Monday this week', $cursor);
                    if ($weekStart < $eventDate) $weekStart = strtotime('Monday this week', $eventDate);
                    foreach ($days as $d) {
                        $dayTs = strtotime("+" . ($d - 1) . " days", $weekStart);
                        if ($dayTs >= $startTs && $dayTs <= $endLimit && $dayTs >= $eventDate) {
                            $dates[] = $dayTs;
                        }
                    }
                    $cursor = strtotime("+{$weekStep} days", $weekStart);
                }
                $dates = array_unique($dates);
                break;

            case 'monthly':
                $day = $rule['day'] ?? (int)date('d', $eventDate);
                $ym = date('Y-m', $eventDate);
                $cursor = strtotime($ym . '-01');
                while ($cursor <= $endLimit) {
                    $testMonth = date('Y-m', $cursor);
                    $lastDay = (int)date('t', $cursor);
                    $testDay = min($day, $lastDay);
                    $testDate = strtotime($testMonth . '-' . str_pad($testDay, 2, '0', STR_PAD_LEFT));
                    if ($testDate >= $startTs && $testDate <= $endLimit && $testDate >= $eventDate) {
                        $dates[] = $testDate;
                    }
                    $cursor = strtotime("+{$interval} months", $cursor);
                }
                break;

            case 'yearly':
                $month = $rule['month'] ?? (int)date('m', $eventDate);
                $day = $rule['day'] ?? (int)date('d', $eventDate);
                $y = (int)date('Y', $eventDate);
                while (true) {
                    $testStr = "{$y}-" . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($day, 2, '0', STR_PAD_LEFT);
                    $testDate = strtotime($testStr);
                    if ($testDate === false || $testDate > $endLimit) break;
                    if ($testDate >= $startTs) $dates[] = $testDate;
                    $y += $interval;
                }
                break;
        }

        sort($dates);
        return $dates;
    }
}
