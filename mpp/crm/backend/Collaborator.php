<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use mpp\crm\service\CustomerLogService;
use think\facade\Db;

class Collaborator extends BaseController
{
    protected $methodRules = ['lists'=>'GET','add'=>'POST','delete'=>'POST'];

    public function lists()
    {
        $customerId = $this->request->param('customer_id', 0);
        $list = Db::name('crm_customer_collaborator')->alias('cc')
            ->join('yoshop_store_user u', 'cc.user_id=u.store_user_id AND u.store_id=cc.store_id')
            ->where('cc.store_id', $this->storeId)
            ->where('cc.customer_id', $customerId)
            ->field('cc.*, u.real_name, u.user_name')
            ->order('cc.id desc')->select();
        return $this->renderSuccess(compact('list'));
    }

    public function add()
    {
        $data = $this->postData();
        if (empty($data['customer_id']) || empty($data['user_id'])) {
            return $this->renderError('参数不完整');
        }
        $exists = Db::name('crm_customer_collaborator')
            ->where('store_id', $this->storeId)
            ->where('customer_id', $data['customer_id'])->where('user_id', $data['user_id'])->find();
        if ($exists) return $this->renderError('该用户已是协作成员');
        $customerId = (int)$data['customer_id'];
        $userId = (int)$data['user_id'];
        $permission = intval($data['permission'] ?? 1);
        Db::name('crm_customer_collaborator')->insert([
            'store_id' => $this->storeId, 'customer_id' => $customerId,
            'user_id' => $userId, 'permission' => $permission,
            'remark' => $data['remark'] ?? '', 'create_time' => time(),
        ]);
        $user = Db::name('store_user')->where('store_id', $this->storeId)
            ->where('store_user_id', $userId)->field('real_name,user_name')->find();
        $name = (string)(($user['real_name'] ?? '') ?: ($user['user_name'] ?? ''));
        CustomerLogService::record(
            $customerId,
            'collaborator_added',
            '添加协作成员' . ($name !== '' ? "：{$name}" : '') . '（' . ($permission === 2 ? '可编辑' : '只读') . '）',
            ['user_id' => $userId, 'permission' => $permission, 'remark' => $data['remark'] ?? ''],
            (int)($this->store['store_user_id'] ?? ($this->store['uid'] ?? 0)),
            $this->storeId
        );
        return $this->renderSuccess([], '添加成功');
    }

    public function delete()
    {
        $id = (int)$this->request->param('id', 0);
        $row = Db::name('crm_customer_collaborator')
            ->where('store_id', $this->storeId)->where('id', $id)->find();
        if (!$row) return $this->renderError('协作成员不存在');
        Db::name('crm_customer_collaborator')
            ->where('store_id', $this->storeId)->where('id', $id)->delete();
        $user = Db::name('store_user')->where('store_id', $this->storeId)
            ->where('store_user_id', (int)$row['user_id'])->field('real_name,user_name')->find();
        $name = (string)(($user['real_name'] ?? '') ?: ($user['user_name'] ?? ''));
        CustomerLogService::record(
            (int)$row['customer_id'],
            'collaborator_removed',
            '移除协作成员' . ($name !== '' ? "：{$name}" : ''),
            ['user_id' => (int)$row['user_id']],
            (int)($this->store['store_user_id'] ?? ($this->store['uid'] ?? 0)),
            $this->storeId
        );
        return $this->renderSuccess([], '移除成功');
    }
}
