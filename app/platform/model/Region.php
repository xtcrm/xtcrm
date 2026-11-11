<?php

declare (strict_types=1);

namespace app\platform\model;

use cores\BaseModel;
use think\facade\Cache;
use app\platform\helper\Arr;

/**
 * 平台地区模型（region 表）
 */
class Region extends BaseModel
{
    protected $name = 'region';
    protected $pk   = 'id';
    protected $createTime = false;
    protected $updateTime = false;

    protected $type = [
        'id'    => 'integer',
        'pid'   => 'integer',
        'level' => 'integer',
    ];

    /** @var string 缓存版本号 */
    private static $version = '1.0.4';

    // ── 公开方法 ──

    public static function getNameById(int $id = 0): string
    {
        if ($id == 0) return '其他';
        $data = self::getCacheAll();
        return isset($data[$id]) ? $data[$id]['name'] : '其他';
    }

    /**
     * 按行政区划代码（身份证前6位）解析省/市/区县名称
     *
     * @param string $code6 6位行政区划代码，如 110101
     * @return array{province:string,city:string,district:string}
     */
    public static function getNamesByCode(string $code6): array
    {
        $result = ['province' => '', 'city' => '', 'district' => ''];
        $code6 = substr(trim($code6), 0, 6);
        if (strlen($code6) < 6) return $result;
        // 旧行政区划代码 → 现行代码（身份证老号码的市/县一级对不上，先做一次映射）
        $current      = self::resolveToCurrentCode($code6);
        $provinceCode = substr($current, 0, 2) . '0000';
        $cityCode     = substr($current, 0, 4) . '00';
        foreach (self::getCacheAll() as $item) {
            if ($item['code'] == $provinceCode) {
                $result['province'] = $item['name'];
            } elseif ($item['code'] == $cityCode) {
                $result['city'] = $item['name'];
            } elseif ($item['code'] == $current) {
                $result['district'] = $item['name'];
            }
        }
        return $result;
    }

    /**
     * 旧行政区划代码 → 现行代码（递归跟随后继，防环）
     */
    private static function resolveToCurrentCode(string $code6): string
    {
        $map  = self::getOldCodeMap();
        $seen = [];
        while (isset($map[$code6]) && !isset($seen[$code6])) {
            $seen[$code6] = true;
            $code6        = $map[$code6];
        }
        return $code6;
    }

    /**
     * 旧→新行政区划代码映射（历史沿革数据，来源 GB/T 2260 代码变更）
     */
    private static function getOldCodeMap(): array
    {
        static $map = null;
        if ($map === null) {
            $file = __DIR__ . '/../data/region_old_codes.json';
            $map  = is_file($file) ? json_decode((string)file_get_contents($file), true) : [];
            if (!is_array($map)) $map = [];
        }
        return $map;
    }

    public static function getCacheTree(): array
    {
        return self::getCacheData('tree');
    }

    public static function getCacheAll(): array
    {
        return self::getCacheData('all');
    }

    public static function getCacheCounts(): array
    {
        return self::getCacheData('counts');
    }

    // ── 缓存逻辑 ──

    private static function getCacheData(string $item = null): array
    {
        static $cacheData = [];
        if (empty($cacheData)) {
            $instance = new static;
            $cacheData = $instance->buildCache();
        }
        return $item ? $cacheData[$item] : $cacheData;
    }

    private function buildCache(): array
    {
        $complete = Cache::get('region');
        if (!empty($complete) && isset($complete['version']) && $complete['version'] == self::$version) {
            return $complete;
        }

        $allList = $this->loadAll();
        $complete = [
            'all'     => $allList,
            'tree'    => $this->buildTree($allList),
            'counts'  => $this->buildCounts($allList),
            'version' => self::$version,
        ];
        Cache::tag('cache')->set('region', $complete);
        return $complete;
    }

    private function loadAll(): array
    {
        $list = self::withoutGlobalScope()
            ->field('id, pid, name, code, level')
            ->select()
            ->toArray();
        return Arr::columnToKey($list, 'id');
    }

    private function buildCounts(array $allList): array
    {
        $counts = ['total' => count($allList), 'province' => 0, 'city' => 0, 'region' => 0];
        $level = [1 => 'province', 2 => 'city', 3 => 'region'];
        foreach ($allList as $item) {
            if (isset($level[$item['level']])) $counts[$level[$item['level']]]++;
        }
        return $counts;
    }

    private function buildTree(array $allList): array
    {
        $treeList = [];
        foreach ($allList as $pKey => $province) {
            if ($province['level'] != 1) continue;
            $treeList[$province['id']] = $province;
            unset($allList[$pKey]);
            foreach ($allList as $cKey => $city) {
                if ($city['level'] == 2 && $city['pid'] == $province['id']) {
                    $treeList[$province['id']]['city'][$city['id']] = $city;
                    unset($allList[$cKey]);
                    foreach ($allList as $rKey => $region) {
                        if ($region['level'] == 3 && $region['pid'] == $city['id']) {
                            $treeList[$province['id']]['city'][$city['id']]['region'][$region['id']] = $region;
                            unset($allList[$rKey]);
                        }
                    }
                }
            }
        }
        return $treeList;
    }
}
