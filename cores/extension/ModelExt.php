<?php

declare (strict_types=1);

namespace cores\extension;

use think\Model;
use think\Paginator;
use think\model\Collection;
use cores\BaseModel;

class ModelExt
{
    private static $instance;

    public static function getInstance()
    {
        if (!(self::$instance instanceof self)) {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function updateOne($model, $where, array $data) : bool
    {
        if (is_numeric($where)) {
            $where = [$model->getPk() => $where];
        }
        $D6bBP = $model->where($where)->limit(1)->save($data) !== false;
        return $D6bBP;
    }

    public static function updateBase($model, array $data, array $where, array $allowField = []) : bool
    {
        if (!empty($allowField)) {
            $model->allowField($allowField);
        }
        return $model->mySetUpdateWhere($where)->exists(true)->save($data);
    }

    public function reorganize(array $array1, array $array2, string $_ = 'cache', bool $__ = false) : array
    {
        $D6bBP = !in_array($_, ['cache', 'app']);
        if ($D6bBP) {
            return [];
        }
        $caching = [];
        foreach ($array1 + $array2 as $key => $val) {
            if (isset($array1[$key]) && is_array($array1[$key]) && isset($array2[$key]) && is_array($array2[$key])) {
                if (is_assoc($array1[$key])) {
                    $D6bBS = self::reorganize($array1[$key], $array2[$key], $_, $__);
                } else {
                    $D6bBS = $array2[$key];
                }
                $D6bBT = $D6bBS;
            } else {
                $D6bBT = $array2[$key] ?? $array1[$key];
            }
            $caching[$key] = $D6bBT;
        }
        if ($__ === true) {
            $D6bBQ = self::getValues($caching, false);
        } else {
            $D6bBQ = $caching;
        }
        return $D6bBQ;
    }

    public static function getValues(array $setting, bool $setKey = true) : array
    {
        $data = [];
        foreach ($setting as $k => $item) {
            if ($setKey) {
                $data[$item['key']] = $item['values'];
            } else {
                $data[$k] = $item;
            }
        }
        return $data;
    }

    public function preload(iterable $dataSet, array $with, bool $isToArray = false)
    {
        if (empty($dataSet)) {
            return $dataSet;
        }
        if ($dataSet instanceof Paginator || $dataSet instanceof Collection) {
            $D6bBP = !$dataSet->isEmpty();
            if ((bool) $D6bBP) {
                (bool) $dataSet->load($with);
            }
            if ($isToArray) {
                $D6bBP = $dataSet->toArray();
            } else {
                $D6bBP = $dataSet;
            }
            return $D6bBP;
        }
        if ($isToArray) {
            $D6bBP = [];
        } else {
            $D6bBP = $dataSet;
        }
        return $D6bBP;
    }

    public function related($model, array $with)
    {
        if ($model instanceof Model) {
            foreach ($with as $item) {
                $method = camelize($item);
                if ((bool) method_exists($model, $method)) {
                    (bool) $model->{$method};
                }
            }
        }
        return $model;
    }
}