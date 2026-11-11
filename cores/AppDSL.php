<?php
namespace cores;

/**
 * App DSL 验证器 + 辅助方法
 */
class AppDSL
{
    /** 校验 DSL 合法性，返回 [valid, errors] */
    public static function validate(array $dsl): array
    {
        $errors = [];
        if (empty($dsl['name'])) $errors[] = '缺少 name';
        if (empty($dsl['entities'])) $errors[] = '缺少 entities';

        foreach ($dsl['entities'] ?? [] as $ek => $entity) {
            if (empty($entity['fields'])) { $errors[] = "实体 {$ek} 缺少 fields"; continue; }
            foreach ($entity['fields'] as $i => $f) {
                if (empty($f['key'])) $errors[] = "实体 {$ek} 字段[{$i}] 缺少 key";
                if (empty($f['label'])) $errors[] = "实体 {$ek} 字段[{$i}] 缺少 label";
                $type = $f['type'] ?? 'string';
                $validTypes = ['string','text','int','decimal','date','datetime','enum','bool','file','ref'];
                if (!in_array($type, $validTypes)) $errors[] = "实体 {$ek} 字段 {$f['key']} 类型 {$type} 无效";
                if ($type === 'enum' && empty($f['values'])) $errors[] = "实体 {$ek} 字段 {$f['key']} 枚举缺少 values";
                if ($type === 'ref' && empty($f['ref']['source'])) $errors[] = "实体 {$ek} 字段 {$f['key']} ref 缺少 source";
            }
        }

        foreach ($dsl['pages'] ?? [] as $i => $p) {
            if (empty($p['key'])) $errors[] = "页面[{$i}] 缺少 key";
            $type = $p['type'] ?? '';
            if (!in_array($type, ['table','form','detail','dashboard'])) $errors[] = "页面 {$p['key']} 类型无效";
            if (in_array($type, ['table','form','detail']) && empty($p['entity'])) $errors[] = "页面 {$p['key']} 缺少 entity";
        }

        return [empty($errors), $errors];
    }

    /** 获取 ref 字段的选择器组件名 */
    public static function selectorComponent(array $field): string
    {
        $src = $field['ref']['source'] ?? '';
        if ($src === 'crm_customer') return 'CustomerSelector';
        if ($src === 'crm_contact') return 'ContactSelector';
        if ($src === 'crm_product') return 'ProductSelector';
        if ($src === 'user' || $src === 'store_user') return 'UserSelector';
        return 'CustomerSelector'; // fallback
    }

    /** DSL type → MySQL column */
    public static function toMySQLType(string $type): string
    {
        return [
            'string' => 'VARCHAR(200)', 'text' => 'TEXT', 'int' => 'INT',
            'decimal' => 'DECIMAL(15,2)', 'date' => 'VARCHAR(30)',
            'datetime' => 'INT', 'enum' => 'VARCHAR(50)', 'bool' => 'TINYINT(1)',
            'file' => 'INT', 'ref' => 'INT',
        ][$type] ?? 'VARCHAR(200)';
    }

    /** DSL type → Vue form input */
    public static function toFormInput(array $field): string
    {
        $k = $field['key']; $l = $field['label']; $t = $field['type'] ?? 'string';
        if ($t === 'ref') {
            $sel = self::selectorComponent($field);
            return "<a-form-item label=\"{$l}\"><{$sel} v-model=\"form.{$k}\" /></a-form-item>";
        }
        if ($t === 'text') return "<a-form-item label=\"{$l}\"><a-textarea v-model:value=\"form.{$k}\" :rows=\"3\" /></a-form-item>";
        if ($t === 'enum') {
            $opts = '';
            foreach (($field['values'] ?? []) as $v) $opts .= "<a-select-option value=\"{$v}\">{$v}</a-select-option>";
            return "<a-form-item label=\"{$l}\"><a-select v-model:value=\"form.{$k}\" allow-clear>{$opts}</a-select></a-form-item>";
        }
        if ($t === 'decimal' || $t === 'int') return "<a-form-item label=\"{$l}\"><a-input-number v-model:value=\"form.{$k}\" style=\"width:100%\" /></a-form-item>";
        if ($t === 'date') return "<a-form-item label=\"{$l}\"><a-date-picker v-model:value=\"form.{$k}\" style=\"width:100%\" /></a-form-item>";
        if ($t === 'bool') return "<a-form-item label=\"{$l}\"><a-switch v-model:checked=\"form.{$k}\" /></a-form-item>";
        return "<a-form-item label=\"{$l}\"><a-input v-model:value=\"form.{$k}\" /></a-form-item>";
    }

    /** 用于表格 bodyCell 的 column 渲染 */
    public static function toColumnDef(array $field): array
    {
        $k = $field['key']; $l = $field['label']; $t = $field['type'] ?? 'string';
        $col = ['title' => $l, 'key' => $k, 'dataIndex' => $k];
        if ($t === 'enum' || $t === 'bool') $col['width'] = 100;
        if ($t === 'decimal' || $t === 'int') $col['width'] = 120;
        if ($t === 'ref') $col['width'] = 180;
        return $col;
    }

    /** 用于 bodyCell slot 的模板片段 */
    public static function toCellTemplate(array $field): string
    {
        $k = $field['key']; $t = $field['type'] ?? 'string';
        if ($t === 'ref') {
            $sel = self::selectorComponent($field);
            return "<template v-if=\"column.key === '{$k}'\">\n          <{$sel} :model-value=\"record.{$k}\" disabled />\n        </template>";
        }
        if ($t === 'enum') {
            $cases = '';
            foreach (($field['values'] ?? []) as $v) $cases .= "record.{$k} === '{$v}' ? 'processing' : ";
            return "<template v-if=\"column.key === '{$k}'\">\n          <a-tag :color=\"record.{$k} === '已完成' ? 'green' : record.{$k} === '待处理' ? 'orange' : 'default'\">{{ record.{$k} }}</a-tag>\n        </template>";
        }
        return '';
    }
}
