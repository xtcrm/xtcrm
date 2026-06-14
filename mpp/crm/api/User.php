<?php
namespace mpp\crm\api;

use think\facade\Db;

class User extends Base
{
    protected $methodRules = ['list'=>'GET'];

    public function list()
    {
        $keyword = $this->request->param('keyword', '');
        $query = Db::name('store_user')
            ->where('store_id', $this->storeId)
            ->where('is_delete', 0);
        if ($keyword) {
            $query->where(function($q) use ($keyword) {
                $q->whereLike('user_name', "%{$keyword}%")
                  ->whereOr('real_name', "%{$keyword}%");
            });
        }
        $list = $query->field('store_user_id, user_name, real_name, department_id')
            ->limit(50)->select();
        return $this->renderSuccess(compact('list'));
    }
}
