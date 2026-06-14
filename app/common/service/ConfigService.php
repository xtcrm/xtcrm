<?php
namespace app\common\service;

use think\facade\Cache;
use think\facade\Db;

/**
 * 全局字典服务
 * 每个租户独立数据，首次访问时自动播种默认值
 *
 * 用法:
 *   $list = ConfigService::get(ConfigType::INDUSTRY);
 *   $list = ConfigService::industries();
 */
class ConfigService
{
    const CACHE_KEY_PREFIX = 'config:';
    const CACHE_TTL = 86400;

    /**
     * 默认种子数据（按类型）
     */
    private static array $seeds = [
        'industry' => [
            '科技型','餐饮连锁','现代服务','跨境电商','建筑','制造','贸易',
            '教育培训','医疗健康','物流运输','房地产','农林牧渔','文化传媒','其他'
        ],
        'bank' => [
            '中国工商银行','中国建设银行','中国农业银行','中国银行','交通银行',
            '招商银行','中信银行','中国民生银行','兴业银行','上海浦东发展银行',
            '平安银行','中国邮政储蓄银行','广发银行','北京银行','华夏银行'
        ],
        'service_type' => [
            '基础代账','工商变更','高端税筹','合规治理','工商注册','资质代办','其他'
        ],
        'invoice_type' => [
            '增值税专用发票','增值税普通发票','电子发票','数电票'
        ],
        'econ_kind' => [
            '有限责任公司','股份有限公司','全民所有制','集体所有制','合伙企业',
            '个人独资企业','个体工商户','农民专业合作社','外资企业','其他'
        ],
        'doc_category' => [
            '工商证照','财务报表','纳税回执','合同协议','其他'
        ],
        'opportunity_type' => [
            '个独/个体户税筹分流','高企申报','残保金减免','合规升级','其他'
        ],
        'payment_cycle' => [
            ['name' => '月度', 'value' => '1'],
            ['name' => '季度', 'value' => '2'],
            ['name' => '年度', 'value' => '3'],
        ],
        // === CRM 模块 ===
        'customer_level' => ['A级-战略','B级-重要','C级-普通','D级-潜在'],
        'customer_source' => ['展会','客户介绍','网络搜索','电话来访','广告投放','协会推荐','陌拜','其他'],
        'customer_group' => ['塑料油墨','金属油墨','UV油墨','PCB油墨','FPC油墨','包装油墨','户外广告','玩具油墨','体育用品','高遮盖','其他'],
        'follow_type' => ['电话','拜访','微信','邮件','展会见面','线上会议','其他'],
        'follow_result' => ['有效','无效','待定'],
        'product_category' => ['塑料油墨','金属油墨','UV油墨','PCB油墨','FPC油墨','包装油墨','户外广告','玩具','体育用品','高遮盖','特殊油墨','助剂/辅料'],
        'currency' => ['CNY','USD','EUR','JPY'],
    ];

    /**
     * 获取指定类型的配置（自动播种）
     */
    public static function get(string $type, ?int $storeId = null): array
    {
        $storeId = $storeId ?: app()->request->storeId();

        $cacheKey = self::CACHE_KEY_PREFIX . $type . ':' . $storeId;
        $cached = Cache::get($cacheKey);
        if ($cached !== null) return $cached;

        $list = Db::name('config')
            ->where('config_type', $type)
            ->where('store_id', $storeId)
            ->where('status', 1)
            ->order('sort_order', 'asc')
            ->select()
            ->toArray();

        // 首次无数据 或 config_value 全为空，自动播种
        $allEmpty = !empty($list) && count(array_filter(array_column($list, 'config_value'), function ($v) { return $v !== null && $v !== ''; })) === 0;
        if (empty($list) || $allEmpty) {
            // 删掉空值行，重新播种
            if ($allEmpty) {
                Db::name('config')
                    ->where('config_type', $type)->where('store_id', $storeId)->delete();
            }
            self::seed($type, $storeId);
            $list = Db::name('config')
                ->where('config_type', $type)
                ->where('store_id', $storeId)
                ->where('status', 1)
                ->order('sort_order', 'asc')
                ->select()
                ->toArray();
        }

        Cache::set($cacheKey, $list, self::CACHE_TTL);
        return $list;
    }

    /**
     * 播种默认数据到指定租户
     */
    public static function seed(string $type, int $storeId): void
    {
        if (!isset(self::$seeds[$type])) return;

        $time = time();
        $rows = [];
        $sort = 100;
        $autoId = 1;
        foreach (self::$seeds[$type] as $item) {
            $name = is_array($item) ? $item['name'] : $item;
            // 字符串种子：自动生成递增 ID 作为 config_value
            // 数组种子：使用显式 value，无则也用递增 ID
            if (is_array($item) && isset($item['value'])) {
                $value = $item['value'];
            } else {
                $value = strval($autoId++);
            }
            $rows[] = [
                'store_id' => $storeId,
                'config_type' => $type,
                'config_name' => $name,
                'config_value' => $value,
                'sort_order' => $sort,
                'is_system' => 0,
                'status' => 1,
                'create_time' => $time,
                'update_time' => $time,
            ];
            $sort += 10;
        }
        Db::name('config')->insertAll($rows);
    }

    /**
     * 清除缓存
     */
    public static function clearCache(string $type, ?int $storeId = null): void
    {
        $storeId = $storeId ?: app()->request->storeId();
        Cache::delete(self::CACHE_KEY_PREFIX . $type . ':' . $storeId);
    }

    // === 快捷方法 ===
    public static function industries(?int $storeId = null): array    { return self::get(\app\common\enum\ConfigType::INDUSTRY, $storeId); }
    public static function banks(?int $storeId = null): array         { return self::get(\app\common\enum\ConfigType::BANK, $storeId); }
    public static function serviceTypes(?int $storeId = null): array  { return self::get(\app\common\enum\ConfigType::SERVICE_TYPE, $storeId); }
    public static function invoiceTypes(?int $storeId = null): array  { return self::get(\app\common\enum\ConfigType::INVOICE_TYPE, $storeId); }
    public static function docCategories(?int $storeId = null): array { return self::get(\app\common\enum\ConfigType::DOC_CATEGORY, $storeId); }
    public static function opportunityTypes(?int $storeId = null): array { return self::get(\app\common\enum\ConfigType::OPPORTUNITY_TYPE, $storeId); }
    public static function econKinds(?int $storeId = null): array    { return self::get(\app\common\enum\ConfigType::ECON_KIND, $storeId); }
    public static function paymentCycles(?int $storeId = null): array { return self::get(\app\common\enum\ConfigType::PAYMENT_CYCLE, $storeId); }
    // CRM
    public static function customerLevels(?int $storeId = null): array  { return self::get(\app\common\enum\ConfigType::CUSTOMER_LEVEL, $storeId); }
    public static function customerSources(?int $storeId = null): array { return self::get(\app\common\enum\ConfigType::CUSTOMER_SOURCE, $storeId); }
    public static function customerGroups(?int $storeId = null): array  { return self::get(\app\common\enum\ConfigType::CUSTOMER_GROUP, $storeId); }
    public static function followTypes(?int $storeId = null): array     { return self::get('follow_type', $storeId); }
    public static function followResults(?int $storeId = null): array   { return self::get('follow_result', $storeId); }
    public static function productCategories(?int $storeId = null): array { return self::get('product_category', $storeId); }
    public static function currencies(?int $storeId = null): array      { return self::get('currency', $storeId); }
}
