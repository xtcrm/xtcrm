<?php

declare (strict_types=1);

namespace app\platform\helper;

/**
 * 数组工具类
 *
 * 自包含实现，不依赖 app\common\library\helper。
 */
class Arr
{
    /**
     * 获取二维数组中指定列的值
     */
    public static function getColumn(array $source, string $column): array
    {
        $result = [];
        foreach ($source as $item) {
            if (isset($item[$column])) {
                $result[] = $item[$column];
            }
        }
        return $result;
    }

    /**
     * 获取二维数组中指定多列的值
     */
    public static function getColumns(array $source, array $columns): array
    {
        $result = [];
        foreach ($source as $item) {
            $temp = [];
            foreach ($columns as $col) {
                $temp[$col] = $item[$col] ?? null;
            }
            $result[] = $temp;
        }
        return $result;
    }

    /**
     * 将二维数组按指定列设置为 key
     */
    public static function columnToKey(array $source, string $index): array
    {
        $data = [];
        foreach ($source as $item) {
            if (isset($item[$index])) {
                $data[$item[$index]] = $item;
            }
        }
        return $data;
    }

    /**
     * 过滤二维数组（保留指定列等于指定值的行）
     */
    public static function filterByValue(array $array, string $column, $value): array
    {
        $data = [];
        foreach ($array as $key => $item) {
            if (isset($item[$column]) && $item[$column] == $value) {
                $data[$key] = $item;
            }
        }
        return $data;
    }

    /**
     * 在二维数组中查找指定值所在的行
     * @return array|false
     */
    public static function search(array $array, string $column, $value)
    {
        foreach ($array as $item) {
            if (isset($item[$column]) && $item[$column] == $value) {
                return $item;
            }
        }
        return false;
    }

    /**
     * 设置默认查询参数
     */
    public static function setQueryDefaults(array $query, array $defaults = []): array
    {
        $data = array_merge($defaults, $query);
        foreach ($query as $field => $value) {
            if (!isset($defaults[$field])) continue;
            if (empty($value) && $value !== '0') {
                $data[$field] = $defaults[$field];
            }
        }
        return $data;
    }
}
