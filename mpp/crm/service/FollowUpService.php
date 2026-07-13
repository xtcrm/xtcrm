<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use mpp\crm\model\FollowUp as FollowUpModel;

class FollowUpService extends BaseService
{
    public function getList(int $customerId = 0): array
    {
        $model = new FollowUpModel();
        $list = $model->getList($customerId);
        return ['success' => true, 'data' => compact('list'), 'error' => ''];
    }

    public function add(array $data, int $actorUserId): array
    {
        $data['owner_user_id'] = $actorUserId;
        $model = new FollowUpModel();
        if ($model->add($data)) {
            EventService::record('follow_up_completed', 'customer', $data['customer_id'] ?? 0, [
                'followup_id' => $model->id,
                'follow_type' => $data['follow_type'] ?? '',
                'result' => $data['result'] ?? '',
            ], $actorUserId);
            try { InsightService::handleEvent('follow_up_completed', $data['customer_id'] ?? 0); } catch (\Throwable $e) {}
            return ['success' => true, 'data' => ['id' => $model->id], 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $model->getError() ?: '添加失败'];
    }

    public function edit(int $id, array $data, int $actorUserId): array
    {
        $model = new FollowUpModel();
        $detail = $model->where('id', $id)->find();
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '跟进记录不存在'];
        if ($detail->edit($data)) {
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
            EventService::record('customer_updated', 'customer', $customerId, [
                'followup_deleted' => true, 'followup_id' => $id,
            ], $actorUserId);
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => '删除失败'];
    }

    /** 日历事件：拜访 + 联系人生日 + 公司成立日 */
    public static function calendarEvents(int $storeId, string $start, string $end, int $userId = 0): array
    {
        $startTs = strtotime($start);
        $endTs = strtotime($end) + 86399;
        $events = [];

        // 1. 拜访事件
        $query = \think\facade\Db::name('crm_followup')->alias('f')
            ->join('yoshop_crm_customer c', 'c.id = f.customer_id AND c.is_delete = 0')
            ->leftJoin('yoshop_store_user cu', 'cu.store_user_id = c.owner_user_id')
            ->where('f.store_id', $storeId)->where('f.is_delete', 0)
            ->where('f.next_follow_date', '>=', $startTs)->where('f.next_follow_date', '<=', $endTs)
            ->field("f.id, f.customer_id, f.follow_type, f.next_follow_content, f.next_follow_date, f.follow_content, c.customer_name, cu.real_name as owner_user_name, cu.user_name, 'visit' as event_type")
            ->order('f.next_follow_date', 'asc');
        if ($userId > 0) $query->where('f.owner_user_id', $userId);
        $events = array_merge($events, $query->select()->toArray());

        // 2. 联系人生日（本月同一天都算，跨年取日期部分）
        $birthdayContacts = \think\facade\Db::name('crm_contact')
            ->where('store_id', $storeId)->where('is_delete', 0)->where('birthday', '<>', '')
            ->field("id, contact_name, mobile, birthday, 'birthday' as event_type")
            ->select()->toArray();

        foreach ($birthdayContacts as $c) {
            // 生日映射到今年的日期
            $mmdd = substr($c['birthday'], 5); // MM-DD
            $yearStart = date('Y', $startTs);
            $yearEnd = date('Y', $endTs);
            for ($y = $yearStart; $y <= $yearEnd; $y++) {
                $ts = strtotime("{$y}-{$mmdd}");
                if ($ts >= $startTs && $ts <= $endTs) {
                    $events[] = [
                        'id' => 'bday_' . $c['id'] . '_' . $y,
                        'customer_id' => 0,
                        'contact_name' => $c['contact_name'],
                        'mobile' => $c['mobile'],
                        'birthday' => $c['birthday'],
                        'follow_type' => '生日',
                        'next_follow_content' => $c['contact_name'] . ' 生日',
                        'next_follow_date' => $ts,
                        'event_type' => 'birthday',
                        'owner_user_name' => '',
                        'user_name' => '',
                        'customer_name' => '',
                    ];
                }
            }
        }

        // 3. 公司成立日
        $companies = \think\facade\Db::name('crm_customer')
            ->where('store_id', $storeId)->where('is_delete', 0)->where('established_date', '<>', '')
            ->field("id, customer_name, established_date, 'established' as event_type")
            ->select()->toArray();

        foreach ($companies as $co) {
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
                        'owner_user_name' => '',
                        'user_name' => '',
                    ];
                }
            }
        }

        // 4. 加载已有祝福语
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

        // 按时间排序
        usort($events, function ($a, $b) {
            return ($a['next_follow_date'] ?? 0) - ($b['next_follow_date'] ?? 0);
        });

        return ['list' => $events, 'total' => count($events)];
    }

    /** 周拜访：按日期范围查询 */
    public static function weekVisits(int $storeId, string $start, string $end, int $userId = 0): array
    {
        $startTs = strtotime($start);
        $endTs = strtotime($end) + 86399;
        $query = \think\facade\Db::name('crm_followup')->alias('f')
            ->join('yoshop_crm_customer c', 'c.id = f.customer_id AND c.is_delete = 0')
            ->leftJoin('yoshop_store_user cu', 'cu.store_user_id = c.owner_user_id')
            ->leftJoin('yoshop_store_user fu', 'fu.store_user_id = f.owner_user_id')
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
            ->join('yoshop_crm_customer c', 'c.id = f.customer_id AND c.is_delete = 0')
            ->leftJoin('yoshop_store_user fu', 'fu.store_user_id = f.owner_user_id')
            ->leftJoin('yoshop_store_user cu', 'cu.store_user_id = c.owner_user_id')
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
}
