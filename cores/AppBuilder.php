<?php
namespace cores;

use think\facade\Db;
use app\common\service\AiService;

class AppBuilder
{
    const GEN_DIR = 'views/app/generated';

    public static function genPath(string $appId = ''): string
    {
        $base = app()->getRootPath() . 'mpp/crm/frontend-v2/src/' . self::GEN_DIR;
        return $appId ? $base . '/' . $appId : $base;
    }

    public static function readExistingCode(string $appId): array
    {
        $dir = self::genPath($appId);
        if (!is_dir($dir)) return [];
        $files = [];
        foreach (glob($dir . '/*.vue') as $f) $files[basename($f)] = file_get_contents($f);
        return $files;
    }

    public static function build(string $prompt, string $appId = '', array $spec = null): array
    {
        $appId = $appId ?: 'app_' . substr(md5($prompt . time()), 0, 8);
        $isNew = !is_dir(self::genPath($appId));
        $existingFiles = self::readExistingCode($appId);

        // System prompt — 用单引号字符串，不用 Heredoc（避免PHP解析${}破坏Vue模板）
        if ($spec) {
            $systemPrompt = '你是Vue3前端专家。用以下领域组件组装页面，不要裸写代码。

可用组件：
- CustomerSelector: <CustomerSelector v-model="customerId" /> 搜索选择CRM客户
- ContactSelector: <ContactSelector v-model="contactId" /> 搜索选择联系人
- UserSelector: <UserSelector v-model="userId" /> 搜索选择用户
- SearchableTable: <SearchableTable app-id="' . $appId . '" entity="entityKey" :columns="cols" /> 表格+搜索+分页。支持#actions和#bodyCell插槽。cols里不用加操作列
- EntityForm: <EntityForm app-id="' . $appId . '" entity="entityKey" ref="formRef" :record="editing" @submit="onOk" /> 自动表单
- StatCards: <StatCards app-id="' . $appId . '" entity="entityKey" :items="[{label:\'待处理\',filter:\'status=待处理\',color:\'#faad14\'}]" /> 统计卡片

导入: import Xxx from \'@/components/app/Xxx.vue\'

数据API: GET/POST /crm.appEngine/dataList|Create|Update|Delete?app_id=' . $appId . '&entity_key=ENTITY

输出格式: /// FILE: Name.vue 开头，然后完整template+script+style。输出1-2个文件。';
        } else {
            $systemPrompt = '你是企业应用构建专家。先确认关键需求（2-3个问题），再规划页面，最后逐文件生成Vue3+AntDesign组件。技术：Vue3 script setup + AntDesignVue4。数据API：GET/POST /crm.appEngine/dataList|Create|Update|Delete。输出：/// FILE: Name.vue + 完整源码。';
        }

        // 拼接 spec 和已有代码
        $userPrompt = $prompt;
        if ($spec) $userPrompt .= "\n\nSpec:\n" . json_encode($spec, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        if (!$isNew && $existingFiles) {
            $userPrompt .= "\n\n当前代码:\n";
            foreach ($existingFiles as $n => $c) $userPrompt .= "--- {$n} ---\n{$c}\n";
            $userPrompt .= "\n请修改以上组件。";
        }

        $files = [];

        // 尝试 AI 生成
        try {
            $result = AiService::quickAsk($systemPrompt . "\n\n" . $userPrompt);
            if ($result['success'] && !empty($result['data'])) {
                $files = self::parseFiles($result['data']);
            }
        } catch (\Throwable $e) {}

        // AI失败 → 模板 fallback
        if (empty($files)) {
            $files = self::templateCode($appId, $spec);
        }

        if (empty($files)) {
            return ['success' => false, 'files' => [], 'error' => '生成失败'];
        }

        // 写入磁盘
        $dir = self::genPath($appId);
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $written = [];
        foreach ($files as $name => $code) {
            file_put_contents($dir . '/' . $name, $code);
            $written[] = $name;
            // 存 app_page
            $exist = Db::name('app_page')->where('app_id', $appId)->where('page_key', $name)->find();
            if ($exist) Db::name('app_page')->where('id', $exist['id'])->update(['source_code' => $code, 'update_time' => time()]);
            else Db::name('app_page')->insert(['app_id' => $appId, 'page_key' => $name, 'page_name' => str_replace('.vue','',$name), 'page_type' => 'generated', 'source_code' => $code, 'create_time' => time()]);
        }
        // 存 app_definition
        $existApp = Db::name('app_definition')->where('app_id', $appId)->find();
        if ($existApp) Db::name('app_definition')->where('app_id', $appId)->update(['update_time' => time()]);
        else Db::name('app_definition')->insert(['app_id' => $appId, 'name' => $appId, 'store_id' => 0, 'status' => 0, 'create_time' => time()]);

        return ['success' => true, 'files' => $written, 'app_id' => $appId, 'error' => ''];
    }

    private static function parseFiles(string $content): array
    {
        $files = [];
        // /// FILE: Name.vue
        $parts = preg_split('/\/\/\/\s*FILE:\s*(.+\.vue)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        for ($i = 1; $i < count($parts); $i += 2) {
            $n = trim($parts[$i]); $c = trim($parts[$i+1] ?? '');
            $c = preg_replace('/^```(?:vue|html)?\s*/', '', $c);
            $c = preg_replace('/\s*```$/', '', $c);
            if ($n && strlen($c) > 50) $files[$n] = $c;
        }
        // ```vue ... ```
        if (empty($files) && preg_match_all('/```vue\s*\n(.*?)```/s', $content, $m)) {
            foreach ($m[1] as $i => $c) {
                if (strlen(trim($c)) > 50) $files['Page' . ($i ?: '') . '.vue'] = trim($c);
            }
        }
        // 整个内容
        if (empty($files) && strlen(trim($content)) > 50) {
            $c = preg_replace('/^```(?:vue|html)?\s*/', '', trim($content));
            $c = preg_replace('/\s*```$/', '', $c);
            if (strlen($c) > 50) $files['IndexPage.vue'] = $c;
        }
        return $files;
    }

    public static function templateCode(string $appId, ?array $spec): array
    {
        $entities = $spec['entities'] ?? [];
        if (empty($entities)) return [];
        $e = $entities[0];
        $ek = $e['key'] ?? 'record';
        $en = $e['name'] ?? '记录';
        $fs = $e['fields'] ?? [];

        // 列表列定义
        $cols = '';
        foreach (array_slice($fs, 0, 7) as $f) {
            $k = $f['key']; $l = $f['label'];
            if (($f['type']??'') === 'relation' || $k === 'customer_id') {
                $cols .= "  { title: '{$l}', key: '{$k}', width: 180 },\n";
            } else {
                $cols .= "  { title: '{$l}', dataIndex: '{$k}', key: '{$k}' },\n";
            }
        }

        // 表单字段
        $formItems = '';
        foreach (array_slice($fs, 0, 8) as $f) {
            $k = $f['key']; $l = $f['label'];
            if (($f['type']??'') === 'relation' || $k === 'customer_id') {
                $formItems .= "        <a-form-item label=\"{$l}\"><CustomerSelector v-model=\"form.{$k}\" /></a-form-item>\n";
            } elseif (($f['type']??'') === 'enum') {
                $formItems .= "        <a-form-item label=\"{$l}\"><a-select v-model:value=\"form.{$k}\" allow-clear>\n";
                foreach (($f['values']??[]) as $v) $formItems .= "          <a-select-option value=\"{$v}\">{$v}</a-select-option>\n";
                $formItems .= "        </a-select></a-form-item>\n";
            } elseif (($f['type']??'') === 'text') {
                $formItems .= "        <a-form-item label=\"{$l}\"><a-textarea v-model:value=\"form.{$k}\" :rows=\"3\" /></a-form-item>\n";
            } elseif (($f['type']??'') === 'number') {
                $formItems .= "        <a-form-item label=\"{$l}\"><a-input-number v-model:value=\"form.{$k}\" style=\"width:100%\" /></a-form-item>\n";
            } elseif (($f['type']??'') === 'date') {
                $formItems .= "        <a-form-item label=\"{$l}\"><a-date-picker v-model:value=\"form.{$k}\" style=\"width:100%\" /></a-form-item>\n";
            } else {
                $formItems .= "        <a-form-item label=\"{$l}\"><a-input v-model:value=\"form.{$k}\" /></a-form-item>\n";
            }
        }

        // bodyCell 模板（处理关联字段）
        $bodyCellRel = '';
        foreach ($fs as $f) {
            $k = $f['key'];
            if (($f['type']??'') === 'relation' || $k === 'customer_id') {
                $bodyCellRel .= "        <template v-if=\"column.key === '{$k}'\">
          <CustomerSelector :model-value=\"record.{$k}\" disabled />
        </template>\n";
            }
        }

        $code = <<<VUE
<template>
  <a-card title="{$en}管理">
    <SearchableTable app-id="{$appId}" entity="{$ek}" :columns="columns" search-placeholder="搜索...">
      <template #actions>
        <a-button type="primary" @click="openForm()" style="margin-left:8px">新增</a-button>
      </template>
      <template #bodyCell="{ column, record }">
{$bodyCellRel}        <template v-if="column.key === 'action'">
          <a @click="openForm(record)">编辑</a>
          <a-divider type="vertical" />
          <a-popconfirm title="确认删除?" @confirm="handleDelete(record)">
            <a style="color:#ff4d4f">删除</a>
          </a-popconfirm>
        </template>
      </template>
    </SearchableTable>

    <a-modal v-model:open="visible" :title="editing ? '编辑' : '新增'" width="560px" @ok="handleSubmit" :confirm-loading="saving">
      <a-form layout="vertical">
{$formItems}      </a-form>
    </a-modal>
  </a-card>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { message } from 'ant-design-vue'
import request from '@/utils/request'
import SearchableTable from '@/components/app/SearchableTable.vue'
import CustomerSelector from '@/components/app/CustomerSelector.vue'

const APP_ID = '{$appId}', ENTITY = '{$ek}'
const tableRef = ref(null)
const visible = ref(false), saving = ref(false), editing = ref(null)
const form = reactive({})

const columns = [
{$cols}]

function openForm(record) {
  editing.value = record || null
  columns.forEach(c => { form[c.key || c.dataIndex] = record ? (record[c.dataIndex || c.key] ?? '') : '' })
  visible.value = true
}

async function handleSubmit() {
  saving.value = true
  try {
    if (editing.value) {
      await request({ url: '/crm.appEngine/dataUpdate', method: 'post', data: { app_id: APP_ID, entity_key: ENTITY, id: editing.value.id, ...form } })
      message.success('已更新')
    } else {
      await request({ url: '/crm.appEngine/dataCreate', method: 'post', data: { app_id: APP_ID, entity_key: ENTITY, ...form } })
      message.success('已创建')
    }
    visible.value = false
    tableRef.value?.loadData()
  } catch { message.error('操作失败') }
  saving.value = false
}

async function handleDelete(r) {
  await request({ url: '/crm.appEngine/dataDelete', method: 'post', data: { app_id: APP_ID, entity_key: ENTITY, id: r.id } })
  message.success('已删除')
  tableRef.value?.loadData()
}
</script>
VUE;
        return ['ListPage.vue' => $code];
    }
}
