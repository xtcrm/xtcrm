<?php

declare (strict_types=1);

namespace app\platform\config;

use think\facade\Db;

/**
 * 平台配置管理
 *
 * 基于 yoshop_config 表，提供租户隔离的配置读写。
 * 字典类型的配置（行业、等级、来源等）建议走 ConfigService 的种子机制，
 * 简单 key-value 配置（API Key、开关、阈值等）走此类。
 */
class PlatformConfig
{
    const TABLE = 'config';

    /** @var int 平台级 storeId */
    const GLOBAL_STORE = 0;

    /**
     * 读取一条配置
     *
     * @param string $key        config_name
     * @param int    $storeId    租户 ID
     * @param mixed  $default    默认值
     * @return string|null
     */
    public static function get(string $key, int $storeId, $default = null): ?string
    {
        $value = Db::name(self::TABLE)
            ->where('config_name', $key)
            ->where('store_id', $storeId)
            ->where('status', 1)
            ->value('config_value');

        return ($value !== null && $value !== '') ? $value : $default;
    }

    /**
     * 读取平台级配置（store_id = 0，所有租户共享）
     */
    public static function getGlobal(string $key, $default = null): ?string
    {
        return self::get($key, self::GLOBAL_STORE, $default);
    }

    /**
     * 写入/更新一条配置
     *
     * @param string $key
     * @param string $value
     * @param int    $storeId
     * @param string $type  config_type（如 'crm_setting'、'fiscal_setting'）
     */
    public static function set(string $key, string $value, int $storeId, string $type = 'crm_setting'): void
    {
        $exist = Db::name(self::TABLE)
            ->where('config_name', $key)
            ->where('store_id', $storeId)
            ->find();

        if ($exist) {
            Db::name(self::TABLE)
                ->where('id', $exist['id'])
                ->update(['config_value' => $value, 'update_time' => time()]);
        } else {
            Db::name(self::TABLE)->insert([
                'store_id'     => $storeId,
                'config_type'  => $type,
                'config_name'  => $key,
                'config_value' => $value,
                'sort_order'   => 100,
                'is_system'    => 1,
                'status'       => 1,
                'create_time'  => time(),
                'update_time'  => time(),
            ]);
        }
    }

    /**
     * 获取某类型下所有配置
     *
     * @param string $type    config_type
     * @param int    $storeId
     * @return array  ['key' => 'value', ...]
     */
    public static function all(string $type, int $storeId): array
    {
        $rows = Db::name(self::TABLE)
            ->where('config_type', $type)
            ->where('store_id', $storeId)
            ->where('status', 1)
            ->order('sort_order', 'asc')
            ->select()
            ->toArray();

        $map = [];
        foreach ($rows as $r) {
            $map[$r['config_name']] = $r['config_value'];
        }
        return $map;
    }

    /**
     * 批量写入配置
     *
     * @param array  $keyValues ['key' => 'value', ...]
     * @param string $type      config_type
     * @param int    $storeId
     */
    public static function setBatch(array $keyValues, string $type, int $storeId): void
    {
        $existing = self::all($type, $storeId);
        $time = time();

        foreach ($keyValues as $key => $value) {
            if (array_key_exists($key, $existing)) {
                Db::name(self::TABLE)
                    ->where('config_name', $key)
                    ->where('store_id', $storeId)
                    ->update(['config_value' => (string)$value, 'update_time' => $time]);
            } else {
                Db::name(self::TABLE)->insert([
                    'store_id'     => $storeId,
                    'config_type'  => $type,
                    'config_name'  => $key,
                    'config_value' => (string)$value,
                    'sort_order'   => 100,
                    'is_system'    => 1,
                    'status'       => 1,
                    'create_time'  => $time,
                    'update_time'  => $time,
                ]);
            }
        }
    }

    /**
     * 删除一条配置
     */
    public static function delete(string $key, int $storeId): void
    {
        Db::name(self::TABLE)
            ->where('config_name', $key)
            ->where('store_id', $storeId)
            ->delete();
    }
}
