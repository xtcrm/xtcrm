<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use mpp\crm\model\Contact as ContactModel;
use mpp\crm\model\ContactCustomer;

class ContactService extends BaseService
{
    // 身份证前2位 → 省份映射
    private static $provinceMap = [
        '11' => '北京', '12' => '天津', '13' => '河北', '14' => '山西', '15' => '内蒙古',
        '21' => '辽宁', '22' => '吉林', '23' => '黑龙江',
        '31' => '上海', '32' => '江苏', '33' => '浙江', '34' => '安徽', '35' => '福建', '36' => '江西', '37' => '山东',
        '41' => '河南', '42' => '湖北', '43' => '湖南', '44' => '广东', '45' => '广西', '46' => '海南',
        '50' => '重庆', '51' => '四川', '52' => '贵州', '53' => '云南', '54' => '西藏',
        '61' => '陕西', '62' => '甘肃', '63' => '青海', '64' => '宁夏', '65' => '新疆',
        '71' => '台湾', '81' => '香港', '82' => '澳门',
    ];

    /** 解析身份证号 */
    public static function parseIdCard(string $idCard): array
    {
        $result = ['birthday' => '', 'gender' => 0, 'hometown' => ''];
        $idCard = trim($idCard);
        $len = strlen($idCard);

        if ($len === 18) {
            $birth = substr($idCard, 6, 8);
            if (checkdate(intval(substr($birth, 4, 2)), intval(substr($birth, 6, 2)), intval(substr($birth, 0, 4)))) {
                $result['birthday'] = substr($birth, 0, 4) . '-' . substr($birth, 4, 2) . '-' . substr($birth, 6, 2);
            }
            $genderCode = intval(substr($idCard, 16, 1));
            $result['gender'] = ($genderCode % 2 === 1) ? 1 : 2;
        } elseif ($len === 15) {
            $birth = '19' . substr($idCard, 6, 6);
            if (checkdate(intval(substr($birth, 4, 2)), intval(substr($birth, 6, 2)), intval(substr($birth, 0, 4)))) {
                $result['birthday'] = substr($birth, 0, 4) . '-' . substr($birth, 4, 2) . '-' . substr($birth, 6, 2);
            }
            $genderCode = intval(substr($idCard, 14, 1));
            $result['gender'] = ($genderCode % 2 === 1) ? 1 : 2;
        }

        if ($len >= 2) {
            $provinceCode = substr($idCard, 0, 2);
            $result['hometown'] = self::$provinceMap[$provinceCode] ?? '';
        }

        return $result;
    }

    /** 手机号查重，返回已存在的联系人 */
    public static function lookupByMobile(string $mobile, int $storeId): ?array
    {
        if (empty($mobile)) return null;
        ContactModel::$storeId = $storeId;
        $contact = (new ContactModel())->getByMobile($mobile, $storeId);
        if (!$contact) return null;

        // 附加关联公司列表
        ContactCustomer::$storeId = $storeId;
        $companies = (new ContactCustomer())->getByContactId($contact['id']);
        return [
            'contact'   => $contact,
            'companies' => $companies,
        ];
    }

    // ==================== 联系人管理（全局） ====================

    /** 联系人列表（带关联公司数） */
    public static function listAll(array $where, array $param, int $storeId): array
    {
        ContactModel::$storeId = $storeId;
        $where['store_id'] = $storeId;
        $list = (new ContactModel())->getList($where, $param);
        $items = $list->items();

        // 补充关联公司数
        if (!empty($items)) {
            $ids = array_column($items, 'id');
            $counts = (new ContactCustomer())->db()
                ->whereIn('contact_id', $ids)
                ->field('contact_id, COUNT(*) as company_count')
                ->group('contact_id')
                ->select()->toArray();
            $countMap = [];
            foreach ($counts as $c) {
                $countMap[$c['contact_id']] = $c['company_count'];
            }
            foreach ($items as &$row) {
                $row['company_count'] = $countMap[$row['id']] ?? 0;
            }
        }

        return [
            'list'  => $items,
            'total' => $list->total(),
        ];
    }

    /** 联系人详情（个人信息 + 关联公司列表 + 跟进记录） */
    public function detail(int $id, int $storeId): array
    {
        ContactModel::$storeId = $storeId;
        ContactCustomer::$storeId = $storeId;
        $contact = (new ContactModel())->detail($id);
        if (!$contact) return ['success' => false, 'data' => null, 'error' => '联系人不存在'];

        // 关联公司
        $companies = (new ContactCustomer())->getByContactId($id);

        // 各公司的跟进记录
        if (!empty($companies)) {
            $customerIds = array_column($companies, 'customer_id');
            $followUps = \think\facade\Db::name('crm_followup')
                ->whereIn('customer_id', $customerIds)
                ->where('is_delete', 0)
                ->field('id, customer_id, follow_type, follow_content, create_time')
                ->order('create_time', 'desc')
                ->limit(50)
                ->select()->toArray();

            $followByCustomer = [];
            foreach ($followUps as $f) {
                $followByCustomer[$f['customer_id']][] = $f;
            }
            foreach ($companies as &$comp) {
                $comp['followups'] = $followByCustomer[$comp['customer_id']] ?? [];
            }
        }

        return ['success' => true, 'data' => [
            'contact'   => $contact,
            'companies' => $companies,
        ], 'error' => ''];
    }

    // ==================== 客户下的联系人操作 ====================

    /** 获取某客户的联系人列表 */
    public function getByCustomer(int $customerId, int $storeId): array
    {
        ContactCustomer::$storeId = $storeId;
        $list = (new ContactCustomer())->getByCustomerId($customerId);
        return ['success' => true, 'data' => ['list' => $list], 'error' => ''];
    }

    /**
     * 新增/绑定联系人（手机号唯一）
     * 1. 如果手机号已存在 → 直接绑定到当前客户
     * 2. 如果手机号不存在 → 新建联系人 + 绑定
     */
    public function addOrBind(array $data, int $storeId, int $actorUserId): array
    {
        $mobile = trim($data['mobile'] ?? '');
        $customerId = intval($data['customer_id'] ?? 0);
        if (empty($mobile)) return ['success' => false, 'data' => null, 'error' => '手机号不能为空'];
        if ($customerId <= 0) return ['success' => false, 'data' => null, 'error' => '所属客户不能为空'];

        // 身份证自动解析
        if (!empty($data['id_card'])) {
            $parsed = self::parseIdCard($data['id_card']);
            if (empty($data['birthday']) && !empty($parsed['birthday'])) $data['birthday'] = $parsed['birthday'];
            if (empty($data['gender']) && !empty($parsed['gender'])) $data['gender'] = $parsed['gender'];
            if (empty($data['hometown']) && !empty($parsed['hometown'])) $data['hometown'] = $parsed['hometown'];
        }

        ContactModel::$storeId = $storeId;
        ContactCustomer::$storeId = $storeId;
        $contactModel = new ContactModel();

        // 按手机号查找是否已存在
        $existing = $contactModel->getByMobile($mobile, $storeId);

        if ($existing) {
            // 已存在：更新个人信息，绑定到客户
            $contactId = $existing['id'];
            $contactModel->where('id', $contactId)->save([
                'contact_name' => $data['contact_name'] ?? $existing['contact_name'],
                'gender'       => $data['gender'] ?? $existing['gender'],
                'birthday'     => $data['birthday'] ?? $existing['birthday'],
                'id_card'      => $data['id_card'] ?? $existing['id_card'],
                'hometown'     => $data['hometown'] ?? $existing['hometown'],
                'address'      => $data['address'] ?? $existing['address'],
                'email'        => $data['email'] ?? $existing['email'],
                'wechat'       => $data['wechat'] ?? $existing['wechat'],
                'telephone'    => $data['telephone'] ?? $existing['telephone'],
                'update_time'  => time(),
            ]);
        } else {
            // 新建
            $contactModel->add([
                'contact_name' => $data['contact_name'] ?? '',
                'mobile'       => $mobile,
                'gender'       => $data['gender'] ?? 0,
                'birthday'     => $data['birthday'] ?? '',
                'id_card'      => $data['id_card'] ?? '',
                'hometown'     => $data['hometown'] ?? '',
                'address'      => $data['address'] ?? '',
                'email'        => $data['email'] ?? '',
                'wechat'       => $data['wechat'] ?? '',
                'telephone'    => $data['telephone'] ?? '',
                'remark'       => $data['remark'] ?? '',
            ]);
            $contactId = $contactModel->id;
        }

        // 绑定到客户
        $bindData = [
            'position'   => $data['position'] ?? '',
            'department' => $data['department'] ?? '',
            'is_primary' => $data['is_primary'] ?? 0,
        ];
        (new ContactCustomer())->bind($contactId, $customerId, $bindData);

        $verb = $existing ? '已绑定' : '已添加';
        EventService::record('customer_updated', 'customer', $customerId, [
            'contact_bound' => true, 'contact_id' => $contactId, 'mobile_matched' => !!$existing,
        ], $actorUserId);

        return ['success' => true, 'data' => [
            'id' => $contactId,
            'is_existing' => !!$existing,
            'msg' => $existing ? "该手机号已存在，直接绑定到当前客户" : "{$verb}",
        ], 'error' => ''];
    }

    /** 编辑联系人个人信息 */
    public function edit(int $id, array $data, int $storeId, int $actorUserId): array
    {
        if (!empty($data['id_card'])) {
            $parsed = self::parseIdCard($data['id_card']);
            if (empty($data['birthday']) && !empty($parsed['birthday'])) $data['birthday'] = $parsed['birthday'];
            if (empty($data['gender']) && !empty($parsed['gender'])) $data['gender'] = $parsed['gender'];
            if (empty($data['hometown']) && !empty($parsed['hometown'])) $data['hometown'] = $parsed['hometown'];
        }

        ContactModel::$storeId = $storeId;
        ContactCustomer::$storeId = $storeId;
        $model = new ContactModel();
        $detail = $model->detail($id);
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '联系人不存在'];

        // 更新关联表中的职位/部门/是否首要
        if (isset($data['customer_id']) && $data['customer_id'] > 0) {
            $bindData = [];
            if (isset($data['position'])) $bindData['position'] = $data['position'];
            if (isset($data['department'])) $bindData['department'] = $data['department'];
            if (isset($data['is_primary'])) $bindData['is_primary'] = $data['is_primary'];
            if (!empty($bindData)) {
                (new ContactCustomer())->bind($id, intval($data['customer_id']), $bindData);
            }
            unset($data['customer_id'], $data['position'], $data['department'], $data['is_primary']);
        }

        if ($detail->save($data)) {
            EventService::record('customer_updated', 'customer', $detail['customer_id'] ?? 0, [
                'contact_edited' => true, 'contact_id' => $id,
            ], $actorUserId);
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $detail->getError() ?: '更新失败'];
    }

    /** 解除与某客户的绑定 */
    public function unbind(int $contactId, int $customerId, int $storeId, int $actorUserId): array
    {
        ContactCustomer::$storeId = $storeId;
        (new ContactCustomer())->unbind($contactId, $customerId);
        EventService::record('customer_updated', 'customer', $customerId, [
            'contact_unbound' => true, 'contact_id' => $contactId,
        ], $actorUserId);
        return ['success' => true, 'data' => null, 'error' => ''];
    }

    /** 删除联系人（软删除） */
    public function delete(int $id, int $storeId, int $actorUserId): array
    {
        ContactModel::$storeId = $storeId;
        $model = new ContactModel();
        $detail = $model->detail($id);
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '联系人不存在'];
        $detail->is_delete = 1;
        if ($detail->save()) {
            EventService::record('customer_updated', 'customer', 0, [
                'contact_deleted' => true, 'contact_id' => $id,
            ], $actorUserId);
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => '删除失败'];
    }
}
