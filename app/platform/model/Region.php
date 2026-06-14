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
    private static $version = '1.0.3';

    // ── 公开方法 ──

    public static function getNameById(int $id = 0): string
    {
        if ($id == 0) return '其他';
        $data = self::getCacheAll();
        return isset($data[$id]) ? $data[$id]['name'] : '其他';
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
            ->field('id, pid, name, level')
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
