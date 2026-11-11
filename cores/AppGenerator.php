<?php
namespace cores;

/**
 * App Generator v2 — DSL → 建表 + JSON 页面定义（配置驱动）
 *
 * 不再生成 .vue 源码文件。
 * 生成结果：建表 + 每个页面存 JSON 配置到 app_page.definition。
 * 前端 AppRender 读 JSON 渲染 → 部署后即生成即用，无需重新构建。
 */
class AppGenerator
{
    private string $appId;
    private array $dsl;

    public function __construct(string $appId, array $dsl)
    {
        $this->appId = $appId;
        $this->dsl = $dsl;
    }

    /** 生成建表 + 页面 JSON 定义 */
    public function generate(): array
    {
        $files = [];
        foreach ($this->dsl['entities'] ?? [] as $ek => $e) {
            $sql = $this->buildTableSQL($ek, $e);
            \think\facade\Db::execute($sql);
            $this->saveEntity($ek, $e);
            $files[] = "TABLE {$ek}";
        }
        foreach ($this->dsl['pages'] ?? [] as $p) {
            $ek = $p['entity'] ?? '';
            if (!isset($this->dsl['entities'][$ek])) continue;
            $type = $p['type'] ?? 'table';
            $pageJson = $this->buildPageDSL($ek, $p, $type);
            $this->writePageFile($p, $pageJson);
            $files[] = "PAGE {$p['key']}";
        }
        $this->saveApp();
        return $files;
    }

    /** 页面配置写服务器磁盘 JSON 文件 */
    private function writePageFile(array $p, array $pageJson): void
    {
        $key = $p['key'] ?? '';
        $dir = self::pageConfigDir($this->appId);
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        file_put_contents(
            $dir . '/' . $key . '.json',
            json_encode($pageJson, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
        // 数据库 app_page 只存元数据 + 指向文件
        $r = \think\facade\Db::name('app_page')->where('app_id', $this->appId)->where('page_key', $key)->find();
        $d = ['app_id' => $this->appId, 'page_key' => $key, 'page_name' => $p['title'] ?? $key, 'page_type' => $p['type'] ?? 'table', 'definition' => json_encode($pageJson, JSON_UNESCAPED_UNICODE), 'source_code' => '', 'create_time' => time()];
        if ($r) \think\facade\Db::name('app_page')->where('id', $r['id'])->update($d);
        else \think\facade\Db::name('app_page')->insert($d);
    }

    /** 页面配置文件目录（mpp/crm/apps，项目代码内可追踪） */
    public static function pageConfigDir(string $appId): string
    {
        return app()->getRootPath() . 'mpp/crm/apps/' . $appId . '/pages';
    }

    /**
     * 生成页面的 JSON DSL（前端 AppRender 渲染）
     */
    private function buildPageDSL(string $ek, array $page, string $type): array
    {
        $entity = $this->dsl['entities'][$ek] ?? ['fields' => []];
        $fields = $entity['fields'] ?? [];

        if ($type === 'stats' || $type === 'dashboard') {
            // 统计看板
            $metrics = $page['features']['statsBar']['metrics'] ?? [];
            return [[
                '_key' => 'stats_' . $ek,
                'type' => 'stats',
                'props' => ['app_id' => $this->appId, 'entity' => $ek, 'items' => $metrics],
            ]];
        }

        if ($type === 'form') {
            // 表单页 → 完整字段配置
            $fieldsConfig = $this->buildFormFields($fields);
            return [[
                '_key' => 'form_' . $ek,
                'type' => 'form',
                'props' => [
                    'app_id' => $this->appId,
                    'entity' => $ek,
                    'title' => $page['title'] ?? '表单',
                    'fields' => $fieldsConfig,
                ],
            ]];
        }

        // 默认表格页
        $columns = $this->buildColumns($fields);
        $filters = $this->buildFilters($page, $fields);
        return [[
            '_key' => 'table_' . $ek,
            'type' => 'table',
            'props' => [
                'app_id' => $this->appId,
                'entity' => $ek,
                'title' => $page['title'] ?? ($entity['label'] ?? $ek) . '列表',
                'columns' => $columns,
                'fields' => $this->buildFormFields($fields),
                'filters' => $filters,
                'searchPlaceholder' => '搜索...',
                'showCreate' => true,
                'createLabel' => '新增' . ($entity['label'] ?? ''),
            ],
        ]];
    }

    /** 构建筛选配置：DSL 指定 + 自动补枚举字段 */
    private function buildFilters(array $page, array $fields): array
    {
        $filters = $page['filters'] ?? [];

        // 自动把 enum 字段加为筛选（去重）
        $existing = array_column($filters, 'field');
        foreach ($fields as $f) {
            if (($f['type'] ?? '') === 'enum' && !in_array($f['key'], $existing)) {
                $filters[] = ['field' => $f['key'], 'type' => 'enum'];
            }
        }
        // 给每个 filter 补 values（从字段的 enum 值）和 dict 引用
        foreach ($filters as &$flt) {
            foreach ($fields as $f) {
                if ($f['key'] === $flt['field']) {
                    if (!empty($f['values'])) $flt['values'] = $f['values'];
                    if (!empty($f['dict'])) $flt['dict'] = $f['dict'];
                    break;
                }
            }
        }
        return $filters;
    }

    /** 字段 → 完整表单配置（含类型、枚举、ref） */
    private function buildFormFields(array $fields): array
    {
        $flds = [];
        foreach ($fields as $f) {
            $item = [
                'key' => $f['key'],
                'label' => $f['label'] ?? $f['key'],
                'type' => $f['type'] ?? 'string',
            ];
            if (!empty($f['required'])) $item['required'] = true;
            if (!empty($f['values'])) $item['values'] = $f['values'];
            if (!empty($f['dict'])) $item['dict'] = $f['dict'];
            if (!empty($f['ref'])) $item['ref'] = $f['ref'];
            $flds[] = $item;
        }
        return $flds;
    }

    /** 字段 → 表格列定义 */
    private function buildColumns(array $fields): array
    {
        $cols = [];
        foreach ($fields as $f) {
            $k = $f['key']; $t = $f['type'] ?? 'string';
            $col = ['title' => $f['label'] ?? $k, 'dataIndex' => $k, 'key' => $k, 'type' => $t];
            if ($t === 'ref') {
                $col['width'] = 180;
                $col['ref'] = $f['ref'] ?? [];
            } elseif ($t === 'enum' || $t === 'bool') {
                $col['width'] = 100;
                $col['values'] = $f['values'] ?? [];
                if (!empty($f['dict'])) $col['dict'] = $f['dict'];
            } elseif (in_array($t, ['decimal', 'int'])) {
                $col['width'] = 120;
            }
            $cols[] = $col;
        }
        return $cols;
    }

    private function buildTableSQL(string $ek, array $entity): string
    {
        $tn = DataEngine::tableName($this->appId, $ek);
        $cs = ["`id` int unsigned NOT NULL AUTO_INCREMENT", "`store_id` int unsigned NOT NULL DEFAULT 0", "`owner_user_id` int unsigned NOT NULL DEFAULT 0 COMMENT '所属人'", "`is_delete` tinyint unsigned NOT NULL DEFAULT 0", "`create_time` int unsigned NOT NULL DEFAULT 0", "`update_time` int unsigned DEFAULT NULL"];
        foreach ($entity['fields'] ?? [] as $f) {
            $t = AppDSL::toMySQLType($f['type'] ?? 'string');
            $d = "`{$f['key']}` {$t}";
            if (!in_array($f['type'] ?? '', ['text'])) $d .= in_array($f['type'] ?? '', ['int', 'decimal', 'bool', 'file', 'ref']) ? ' DEFAULT 0' : " DEFAULT ''";
            if (!empty($f['label'])) $d .= " COMMENT '{$f['label']}'";
            $cs[] = $d;
        }
        $cs[] = "PRIMARY KEY (`id`)";
        return "CREATE TABLE IF NOT EXISTS `{$tn}` (\n  " . implode(",\n  ", $cs) . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    }

    private function saveEntity(string $ek, array $e): void
    {
        $tn = DataEngine::tableName($this->appId, $ek);
        $r = \think\facade\Db::name('app_entity')->where('app_id', $this->appId)->where('entity_key', $ek)->find();
        $d = ['app_id' => $this->appId, 'entity_key' => $ek, 'entity_name' => $e['label'] ?? $ek, 'table_name' => $tn, 'entity_schema' => json_encode($e, JSON_UNESCAPED_UNICODE), 'create_time' => time()];
        if ($r) \think\facade\Db::name('app_entity')->where('id', $r['id'])->update($d);
        else \think\facade\Db::name('app_entity')->insert($d);
    }

    private function saveApp(): void
    {
        $d = ['name' => $this->dsl['name'] ?? $this->appId, 'spec' => json_encode($this->dsl, JSON_UNESCAPED_UNICODE), 'status' => 1, 'update_time' => time()];
        $x = \think\facade\Db::name('app_definition')->where('app_id', $this->appId)->find();
        if ($x) \think\facade\Db::name('app_definition')->where('app_id', $this->appId)->update($d);
        else \think\facade\Db::name('app_definition')->insert(array_merge($d, ['app_id' => $this->appId, 'store_id' => 0, 'create_time' => time()]));
    }
}
