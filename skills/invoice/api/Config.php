<?php
namespace skills\invoice\api;

use app\api\controller\Controller;

class Config extends Controller
{
    /**
     * 获取字典（发票类型等）
     */
    public function all()
    {
        $storeId = $this->storeId;

        // 从 yoshop_config 读取发票类型字典
        $invoiceTypes = \think\facade\Db::name('config')
            ->where('store_id', $storeId)
            ->where('config_type', 'invoice_type')
            ->field('config_name, config_value')
            ->select()
            ->toArray();

        $types = [];
        foreach ($invoiceTypes as $r) {
            $types[] = ['value' => $r['config_name'], 'label' => $r['config_value']];
        }
        if (empty($types)) {
            $types = [
                ['value' => '1', 'label' => '增值税专用发票'],
                ['value' => '2', 'label' => '增值税普通发票'],
                ['value' => '3', 'label' => '电子发票'],
                ['value' => '4', 'label' => '数电票'],
            ];
        }

        return $this->renderSuccess(compact('types'));
    }
}
