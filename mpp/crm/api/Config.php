<?php
declare(strict_types=1);

namespace mpp\crm\api;

use app\common\service\ConfigService;

/**
 * 移动端字典/配置
 * URL: /api/crm.config/all
 */
class Config extends Base
{
    protected $methodRules = ['all' => 'GET'];

    /** GET /api/crm.config/all */
    public function all()
    {
        $sid = $this->storeId;
        $result = [
            'industry'         => $this->format(ConfigService::industries($sid)),
            'customer_level'   => $this->format(ConfigService::customerLevels($sid)),
            'customer_source'  => $this->format(ConfigService::customerSources($sid)),
            'customer_group'   => $this->format(ConfigService::customerGroups($sid)),
            'follow_type'      => $this->format(ConfigService::followTypes($sid)),
            'follow_result'    => $this->format(ConfigService::followResults($sid)),
            'product_category' => $this->format(ConfigService::productCategories($sid)),
            'currency'         => $this->format(ConfigService::currencies($sid)),
        ];
        return $this->renderSuccess($result);
    }

    private function format(array $items): array
    {
        $options = [];
        foreach ($items as $item) {
            if (!empty($item['config_value'])) {
                $options[] = [
                    'value' => (int)$item['config_value'],
                    'name'  => $item['config_name'] ?? '',
                ];
            }
        }
        return $options;
    }
}
