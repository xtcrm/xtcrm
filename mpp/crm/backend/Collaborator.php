<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use think\facade\Db;

class Collaborator extends BaseController
{
    protected $methodRules = ['lists'=>'GET','add'=>'POST','delete'=>'POST'];

    public function lists()
    {
        $customerId = $this->request->param('customer_id', 0);
        $list = Db::name('crm_customer_collaborator')->alias('cc')
            ->join('yoshop_store_user u', 'cc.user_id=u.store_user_id')
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
            ->where('customer_id', $data['customer_id'])->where('user_id', $data['user_id'])->find();
        if ($exists) return $this->renderError('该用户已是协作成员');
        Db::name('crm_customer_collaborator')->insert([
            'store_id' => $this->storeId, 'customer_id' => $data['customer_id'],
            'user_id' => $data['user_id'], 'permission' => intval($data['permission'] ?? 1),
            'remark' => $data['remark'] ?? '', 'create_time' => time(),
        ]);
        return $this->renderSuccess([], '添加成功');
    }

    public function delete()
    {
        $id = $this->request->param('id', 0);
        Db::name('crm_customer_collaborator')->where('id', $id)->delete();
        return $this->renderSuccess([], '移除成功');
    }
}
