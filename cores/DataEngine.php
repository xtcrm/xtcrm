<?php

namespace cores;

use think\facade\Db;

/**
 * AI 应用数据引擎
 *
 * 核心能力：AI 定义实体 Schema → 框架自动建 MySQL 表 → 提供通用 CRUD
 *
 * 表名规则：app_{app_id}_{entity_key}
 * 每个表自动包含：id, store_id, is_delete, create_time, update_time
 */
class DataEngine
{
    /** 类型映射：schema type → MySQL column */
    const TYPE_MAP = [
        'string'   => 'VARCHAR(200)',
        'text'     => 'TEXT',
        'number'   => 'DECIMAL(15,2)',
        'int'      => 'INT',
        'date'     => 'VARCHAR(30)',
        'enum'     => 'VARCHAR(50)',
        'file'     => 'INT',
        'bool'     => 'TINYINT(1)',
        'relation' => 'INT',
    ];

    /** 系统自动添加的列 */
    const SYSTEM_COLUMNS = [
        'id'          => 'int unsigned NOT NULL AUTO_INCREMENT',
        'store_id'    => 'int unsigned NOT NULL DEFAULT 0',
        'owner_user_id' => 'int unsigned NOT NULL DEFAULT 0 COMMENT \'所属人\'',
        'is_delete'   => 'tinyint unsigned NOT NULL DEFAULT 0',
        'create_time' => 'int unsigned NOT NULL DEFAULT 0',
        'update_time' => 'int unsigned DEFAULT NULL',
    ];

    /** 不能设默认值的 MySQL 类型 */
    const NO_DEFAULT_TYPES = ['TEXT', 'MEDIUMTEXT', 'LONGTEXT', 'BLOB', 'JSON', 'GEOMETRY'];

    /**
     * 生成表名（含前缀）
     */
    public static function tableName(string $appId, string $entityKey): string
    {
        $prefix = \think\facade\Db::connect()->getConfig('prefix');
        return $prefix . 'app_' . $appId . '_' . $entityKey;
    }

    /**
     * 根据实体 Schema 创建 MySQL 表
     *
     * @param string $appId      应用标识
     * @param string $entityKey  实体标识
     * @param array  $schema     字段定义 { fields: [{key,label,type,required,index,default,values}] }
     * @return string 表名
     */
    public static function createTable(string $appId, string $entityKey, array $schema): string
    {
        $tableName = self::tableName($appId, $entityKey);
        $fields = $schema['fields'] ?? [];

        $columns = [];
        $indexes = [];

        // 系统列
        foreach (self::SYSTEM_COLUMNS as $col => $def) {
            $columns[] = "`{$col}` {$def}";
        }

        // 业务字段
        foreach ($fields as $field) {
            $key = $field['key'] ?? '';
            $type = $field['type'] ?? 'string';
            $label = $field['label'] ?? $key;
            $required = !empty($field['required']);
            $index = !empty($field['index']);
            $mysqlType = self::TYPE_MAP[$type] ?? 'VARCHAR(200)';

            $colDef = "`{$key}` {$mysqlType}";

            // 默认值（TEXT/BLOB/JSON 类型不能设默认值）
            $noDefault = false;
            foreach (self::NO_DEFAULT_TYPES as $ndt) {
                if (stripos($mysqlType, $ndt) !== false) { $noDefault = true; break; }
            }
            if (!$noDefault) {
                if (isset($field['default'])) {
                    $default = $field['default'];
                    if (is_string($default)) {
                        $colDef .= " DEFAULT '{$default}'";
                    } else {
                        $colDef .= " DEFAULT {$default}";
                    }
                } elseif (in_array($type, ['number', 'int', 'relation', 'file', 'bool'])) {
                    $colDef .= " DEFAULT 0";
                } else {
                    $colDef .= " DEFAULT ''";
                }
            }

            // 注释
            $comment = $label ?: $key;
            $colDef .= " COMMENT '{$comment}'";

            $columns[] = $colDef;

            // 索引
            if ($index) {
                $indexes[] = "KEY `idx_{$key}` (`store_id`, `{$key}`)";
            }
        }

        // 主键
        $columns[] = "PRIMARY KEY (`id`)";

        $sql = "CREATE TABLE `{$tableName}` (\n  "
            . implode(",\n  ", $columns)
            . (empty($indexes) ? '' : ",\n  " . implode(",\n  ", $indexes))
            . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        Db::execute($sql);

        return $tableName;
    }

    /**
     * 删除表
     */
    public static function dropTable(string $tableName): void
    {
        Db::execute("DROP TABLE IF EXISTS `{$tableName}`");
    }

    /**
     * 修改表——根据新旧 schema 差异增删字段
     */
    public static function alterTable(string $tableName, array $oldSchema, array $newSchema): array
    {
        $oldFields = [];
        foreach ($oldSchema['fields'] ?? [] as $f) { $oldFields[$f['key']] = $f; }
        $newFields = [];
        foreach ($newSchema['fields'] ?? [] as $f) { $newFields[$f['key']] = $f; }

        $changes = [];

        foreach ($newFields as $key => $field) {
            if (!isset($oldFields[$key])) {
                // 新增字段
                $type = $field['type'] ?? 'string';
                $mysqlType = self::TYPE_MAP[$type] ?? 'VARCHAR(200)';
                $label = $field['label'] ?? $key;

                $noDefault = false;
                foreach (self::NO_DEFAULT_TYPES as $ndt) {
                    if (stripos($mysqlType, $ndt) !== false) { $noDefault = true; break; }
                }
                $defaultClause = '';
                if (!$noDefault) {
                    if (in_array($type, ['number','int','relation','file','bool'])) {
                        $defaultClause = " DEFAULT 0";
                    } else {
                        $defaultClause = " DEFAULT ''";
                    }
                }

                Db::execute("ALTER TABLE `{$tableName}` ADD COLUMN `{$key}` {$mysqlType}{$defaultClause} COMMENT '{$label}'");
                $changes[] = "新增字段：{$label}({$key})";

                // 索引
                if (!empty($field['index'])) {
                    Db::execute("ALTER TABLE `{$tableName}` ADD INDEX `idx_{$key}` (`store_id`, `{$key}`)");
                }
            }
        }

        foreach ($oldFields as $key => $field) {
            if (!isset($newFields[$key])) {
                // 删除字段
                Db::execute("ALTER TABLE `{$tableName}` DROP COLUMN `{$key}`");
                $changes[] = "删除字段：{$key}";
            }
        }

        return $changes;
    }

    /**
     * 分页列表查询
     */
    public static function list(string $tableName, int $storeId, array $params = []): array
    {
        $page = intval($params['page'] ?? 1);
        $pageSize = intval($params['pageSize'] ?? 20);
        $search = $params['search'] ?? '';
        $sort = $params['sort'] ?? 'id';
        $order = $params['order'] ?? 'desc';

        // 读取 schema 知道哪些字段是 searchable
        $searchableFields = self::getSearchableFields($tableName);

        $query = Db::table($tableName)
            ->where('store_id', $storeId)
            ->where('is_delete', 0);

        // 关键词搜索
        if (!empty($search) && !empty($searchableFields)) {
            $query->where(function ($q) use ($search, $searchableFields) {
                foreach ($searchableFields as $i => $field) {
                    if ($i === 0) {
                        $q->where($field, 'like', "%{$search}%");
                    } else {
                        $q->whereOr($field, 'like', "%{$search}%");
                    }
                }
            });
        }

        // JSON filter
        if (!empty($params['filter'])) {
            $filters = is_string($params['filter']) ? json_decode(html_entity_decode($params['filter']), true) : $params['filter'];
            if (is_array($filters)) {
                foreach ($filters as $f) {
                    $field = $f['field'] ?? '';
                    $op = $f['op'] ?? 'eq';
                    $value = $f['value'] ?? '';
                    if ($field && $value !== '') {
                        if ($op === 'eq') $query->where($field, $value);
                        elseif ($op === 'like') $query->where($field, 'like', "%{$value}%");
                        elseif ($op === 'gt') $query->where($field, '>', $value);
                        elseif ($op === 'lt') $query->where($field, '<', $value);
                    }
                }
            }
        }

        // 排序 — 仅允许白名单字段
        $allowedSorts = ['id', 'create_time', 'update_time', 'sort_order'];
        $searchableFieldsAll = $searchableFields;
        // 也允许 schema 里所有字段排序
        $schemaFields = self::getAllFields($tableName);
        $allowedSorts = array_merge($allowedSorts, $schemaFields);
        if (!in_array($sort, $allowedSorts)) $sort = 'id';
        if (!in_array(strtolower($order), ['asc', 'desc'])) $order = 'desc';

        $total = $query->count();
        $rows = $query->order($sort, $order)
            ->page($page, $pageSize)
            ->select()
            ->toArray();

        return [
            'list'  => $rows,
            'total' => $total,
            'page'  => $page,
            'pageSize' => $pageSize,
        ];
    }

    /**
     * 单条查询
     */
    public static function get(string $tableName, int $storeId, int $id): ?array
    {
        return Db::table($tableName)
            ->where('store_id', $storeId)
            ->where('id', $id)
            ->where('is_delete', 0)
            ->find();
    }

    /**
     * 新增记录
     */
    public static function create(string $tableName, int $storeId, array $data, int $userId = 0): int
    {
        $data['store_id'] = $storeId;
        $data['is_delete'] = 0;
        $data['create_time'] = time();
        if (empty($data['owner_user_id'])) $data['owner_user_id'] = $userId;

        // 只保留表中存在的字段
        $allowed = self::getAllFields($tableName);
        $filtered = ['store_id' => $storeId, 'is_delete' => 0, 'create_time' => time(), 'owner_user_id' => $userId];
        foreach ($data as $k => $v) {
            if (in_array($k, $allowed)) {
                // 截断 ISO 日期字符串为 YYYY-MM-DD
                if (is_string($v) && strlen($v) > 10 && preg_match('/^\d{4}-\d{2}-\d{2}T/', $v)) {
                    $v = substr($v, 0, 10);
                }
                $filtered[$k] = $v;
            }
        }

        return Db::table($tableName)->insertGetId($filtered);
    }

    /**
     * 更新记录
     */
    public static function update(string $tableName, int $storeId, int $id, array $data): bool
    {
        $data['update_time'] = time();
        $allowed = self::getAllFields($tableName);
        $filtered = ['update_time' => time()];
        foreach ($data as $k => $v) {
            if (in_array($k, $allowed)) {
                if (is_string($v) && strlen($v) > 10 && preg_match('/^\d{4}-\d{2}-\d{2}T/', $v)) {
                    $v = substr($v, 0, 10);
                }
                $filtered[$k] = $v;
            }
        }

        return Db::table($tableName)
            ->where('store_id', $storeId)
            ->where('id', $id)
            ->update($filtered) !== false;
    }

    /**
     * 软删除
     */
    public static function delete(string $tableName, int $storeId, int $id): bool
    {
        return Db::table($tableName)
            ->where('store_id', $storeId)
            ->where('id', $id)
            ->update(['is_delete' => 1, 'update_time' => time()]) !== false;
    }

    /**
     * 读取实体 Schema（从 app_entity 表）
     */
    public static function getSchema(string $appId, string $entityKey): ?array
    {
        $row = Db::name('app_entity')
            ->where('app_id', $appId)
            ->where('entity_key', $entityKey)
            ->find();

        if (!$row) return null;

        $schema = $row['entity_schema'];
        if (is_string($schema)) {
            $schema = json_decode($schema, true);
        }

        return [
            'app_id'      => $row['app_id'],
            'entity_key'  => $row['entity_key'],
            'entity_name' => $row['entity_name'],
            'table_name'  => $row['table_name'],
            'schema'      => $schema,
            'app_name'    => $row['app_name'] ?? '',
        ];
    }

    /**
     * 获取表的所有业务字段名（优先 app_entity，fallback 读数据库）
     */
    private static function getAllFields(string $tableName): array
    {
        $prefix = Db::connect()->getConfig('prefix');
        $baseName = str_replace($prefix, '', $tableName);

        $row = Db::name('app_entity')
            ->where('table_name', $tableName)
            ->find();

        if (!$row) {
            $row = Db::name('app_entity')
                ->where('table_name', $prefix . $baseName)
                ->find();
        }

        if ($row && !empty($row['entity_schema'])) {
            $schema = is_string($row['entity_schema']) ? json_decode($row['entity_schema'], true) : $row['entity_schema'];
            $fields = $schema['fields'] ?? [];
            return array_column($fields, 'key');
        }

        // Fallback: 从 INFORMATION_SCHEMA 读取实际列名，排除系统列
        try {
            $dbName = Db::connect()->getConfig('database');
            $cols = Db::query(
                "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?",
                [$dbName, $tableName]
            );
            $systemCols = ['id', 'store_id', 'is_delete', 'create_time', 'update_time', 'sort_order'];
            $fields = [];
            foreach ($cols as $col) {
                $name = $col['COLUMN_NAME'];
                if (!in_array($name, $systemCols)) {
                    $fields[] = $name;
                }
            }
            return $fields;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 获取可搜索的字段列表（优先 app_entity，fallback 读数据库 string 列）
     */
    private static function getSearchableFields(string $tableName): array
    {
        $prefix = Db::connect()->getConfig('prefix');
        $baseName = str_replace($prefix, '', $tableName);

        $row = Db::name('app_entity')
            ->where('table_name', $tableName)
            ->find();

        if (!$row) {
            $row = Db::name('app_entity')
                ->where('table_name', $prefix . $baseName)
                ->find();
        }

        if ($row && !empty($row['entity_schema'])) {
            $schema = is_string($row['entity_schema']) ? json_decode($row['entity_schema'], true) : $row['entity_schema'];
            $fields = $schema['fields'] ?? [];
            $searchable = array_filter($fields, fn($f) => !empty($f['index']) || $f['type'] === 'string');
            return array_column($searchable, 'key');
        }

        // Fallback: 读实际表结构，取 string/varchar/text 类型字段
        $all = self::getAllFields($tableName);
        // 返回所有字段作为搜索字段（保守策略）
        return $all;
    }
}
