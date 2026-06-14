<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use app\common\service\ConfigService;
use app\common\enum\ConfigType;
use think\facade\Cache;
use think\facade\Db;

class Config extends BaseController
{
    protected $methodRules = [
        'all' => 'GET',
        'lists' => 'GET',
        'add' => 'POST',
        'edit' => 'POST',
        'delete' => 'POST',
        'init' => 'POST',
    ];

    /** 获取所有CRM字典 */
    public function all()
    {
        return $this->renderSuccess([
            'industry'        => ConfigService::industries(),
            'customer_level'  => ConfigService::customerLevels(),
            'customer_source' => ConfigService::customerSources(),
            'customer_group'  => ConfigService::customerGroups(),
            'follow_type'     => ConfigService::followTypes(),
            'follow_result'   => ConfigService::followResults(),
            'product_category'=> ConfigService::productCategories(),
            'currency'        => ConfigService::currencies(),
        ]);
    }

    /** 字典管理列表 */
    public function lists()
    {
        $type = $this->request->param('config_type', '');
        $query = Db::name('config')
            ->where('store_id', $this->storeId)
            ->where('status', 1)
            ->order('sort_order', 'asc');
        if ($type) $query->where('config_type', $type);
        $list = $query->paginate(30);
        return $this->renderSuccess(compact('list'));
    }

    /** 新增字典项 */
    public function add()
    {
        $data = $this->postData('form');
        if (empty($data['config_type']) || empty($data['config_name'])) {
            return $this->renderError('类型和名称不能为空');
        }
        if (empty($data['config_value']) || !is_numeric($data['config_value'])) {
            return $this->renderError('编号必须为数字');
        }
        $exists = Db::name('config')
            ->where('store_id', $this->storeId)
            ->where('config_type', $data['config_type'])
            ->where('config_name', $data['config_name'])
            ->find();
        if ($exists) return $this->renderError('该名称已存在');
        $dupVal = Db::name('config')
            ->where('store_id', $this->storeId)
            ->where('config_type', $data['config_type'])
            ->where('config_value', $data['config_value'])
            ->find();
        if ($dupVal) return $this->renderError('该编号已被使用');
        $time = time();
        Db::name('config')->insert([
            'store_id' => $this->storeId, 'config_type' => $data['config_type'],
            'config_name' => $data['config_name'], 'config_value' => $data['config_value'] ?? null,
            'sort_order' => intval($data['sort_order'] ?? 100), 'is_system' => 0, 'status' => 1,
            'create_time' => $time, 'update_time' => $time,
        ]);
        ConfigService::clearCache($data['config_type'], $this->storeId);
        return $this->renderSuccess([], '添加成功');
    }

    /** 编辑字典项 */
    public function edit()
    {
        $data = $this->postData('form');
        if (empty($data['id']) || empty($data['config_name'])) {
            return $this->renderError('参数错误');
        }
        if (empty($data['config_value']) || !is_numeric($data['config_value'])) {
            return $this->renderError('编号必须为数字');
        }
        $row = Db::name('config')->where('id', $data['id'])->where('store_id', $this->storeId)->find();
        if (!$row) return $this->renderError('记录不存在');
        $dup = Db::name('config')
            ->where('store_id', $this->storeId)->where('config_type', $row['config_type'])
            ->where('config_value', $data['config_value'])->where('id', '<>', $data['id'])
            ->find();
        if ($dup) return $this->renderError('该编号已被使用');
        Db::name('config')->where('id', $data['id'])->update([
            'config_name' => $data['config_name'], 'config_value' => $data['config_value'] ?? null,
            'sort_order' => intval($data['sort_order'] ?? 100), 'update_time' => time(),
        ]);
        ConfigService::clearCache($row['config_type'], $this->storeId);
        return $this->renderSuccess([], '更新成功');
    }

    /** 删除字典项 */
    public function delete()
    {
        $id = $this->request->param('id', 0);
        $row = Db::name('config')->where('id', $id)->where('store_id', $this->storeId)->find();
        if (!$row) return $this->renderError('记录不存在');
        Db::name('config')->where('id', $id)->delete();
        ConfigService::clearCache($row['config_type'], $this->storeId);
        return $this->renderSuccess([], '删除成功');
    }

    /** 初始化默认数据 */
    public function init()
    {
        $type = $this->request->param('config_type', '');
        if (empty($type)) return $this->renderError('请指定字典类型');

        $count = Db::name('config')->where('store_id', $this->storeId)->where('config_type', $type)->where('status', 1)->count();
        if ($count == 0) {
            ConfigService::seed($type, $this->storeId);
        }

        // 补 config_value 缺值
        $rows = Db::name('config')->where('store_id', $this->storeId)
            ->where('config_type', $type)->where('status', 1)
            ->where(function ($q) { $q->whereNull('config_value')->whereOr('config_value', ''); })
            ->order('sort_order', 'asc')->select()->toArray();

        if (empty($rows)) {
            ConfigService::clearCache($type, $this->storeId);
            return $this->renderSuccess([], '键值完整');
        }

        $idx = 1;
        foreach ($rows as $row) {
            Db::name('config')->where('id', $row['id'])->update(['config_value' => strval($idx), 'update_time' => time()]);
            $idx++;
        }
        ConfigService::clearCache($type, $this->storeId);
        return $this->renderSuccess([], '初始化成功');
    }
}
