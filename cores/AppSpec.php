<?php
namespace cores;

use think\facade\Db;

/**
 * App Spec 引擎
 * spec JSON → 建表 + 实体注册
 */
class AppSpec
{
    /**
     * 解析 Spec 并建表
     * @return array [entities_created, errors]
     */
    public static function apply(string $appId, array $spec): array
    {
        $entities = $spec['entities'] ?? [];
        $created = [];
        $errors = [];

        foreach ($entities as $entity) {
            $key = $entity['key'] ?? '';
            $name = $entity['name'] ?? $key;
            $fields = $entity['fields'] ?? [];

            if (empty($key) || empty($fields)) {
                $errors[] = "实体 '{$name}' 缺少 key 或 fields";
                continue;
            }

            // 检查是否已存在
            $exist = Db::name('app_entity')
                ->where('app_id', $appId)->where('entity_key', $key)->find();

            if ($exist) {
                // 已有实体，ALTER TABLE 更新字段
                $oldSchema = is_string($exist['entity_schema']) ? json_decode($exist['entity_schema'], true) : $exist['entity_schema'];
                try {
                    DataEngine::alterTable($exist['table_name'], $oldSchema, ['fields' => $fields]);
                    Db::name('app_entity')->where('id', $exist['id'])->update([
                        'entity_schema' => json_encode(['fields' => $fields], JSON_UNESCAPED_UNICODE),
                        'update_time' => time(),
                    ]);
                    $created[] = ['key' => $key, 'name' => $name, 'table' => $exist['table_name'], 'action' => 'updated'];
                } catch (\Throwable $e) {
                    $errors[] = "更新实体 '{$name}' 失败：{$e->getMessage()}";
                }
            } else {
                // 新实体，建表
                try {
                    $tableName = DataEngine::createTable($appId, $key, ['fields' => $fields]);
                    Db::name('app_entity')->insert([
                        'app_id' => $appId, 'entity_key' => $key,
                        'entity_name' => $name, 'table_name' => $tableName,
                        'entity_schema' => json_encode(['fields' => $fields], JSON_UNESCAPED_UNICODE),
                        'create_time' => time(),
                    ]);
                    $created[] = ['key' => $key, 'name' => $name, 'table' => $tableName, 'action' => 'created'];
                } catch (\Throwable $e) {
                    $errors[] = "创建实体 '{$name}' 失败：{$e->getMessage()}";
                }
            }
        }

        // 更新应用定义
        $existApp = Db::name('app_definition')->where('app_id', $appId)->find();
        $appData = [
            'name' => $spec['name'] ?? $appId,
            'spec' => json_encode($spec, JSON_UNESCAPED_UNICODE),
            'status' => 1,
            'update_time' => time(),
        ];
        if ($existApp) {
            Db::name('app_definition')->where('app_id', $appId)->update($appData);
        } else {
            $appData['app_id'] = $appId;
            $appData['store_id'] = 0;
            $appData['create_time'] = time();
            Db::name('app_definition')->insert($appData);
        }

        return [$created, $errors];
    }

    /**
     * 读取应用 Spec
     */
    public static function get(string $appId): ?array
    {
        $row = Db::name('app_definition')->where('app_id', $appId)->find();
        if (!$row || empty($row['spec'])) return null;
        return json_decode($row['spec'], true);
    }
}
