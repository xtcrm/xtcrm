<?php
namespace mpp\crm\backend;

use app\platform\backend\BaseController;
use cores\AppDSL;
use cores\AppGenerator;

class AppBuilder extends BaseController
{
    protected $methodRules = ['build' => 'POST', 'aiDsl' => 'POST', 'listApps' => 'GET', 'page' => 'GET', 'dicts' => 'GET', 'dictAdd' => 'POST', 'dictEdit' => 'POST', 'dictDel' => 'POST', 'listConfigs' => 'GET', 'saveConfig' => 'POST', 'delete' => 'POST'];

    /** 列出应用的所有 JSON 配置文件 */
    public function listConfigs()
    {
        $appsDir = app()->getRootPath() . 'mpp/crm/apps';
        if (!is_dir($appsDir)) return $this->renderSuccess(['apps' => []]);

        $apps = [];
        foreach (glob($appsDir . '/*', GLOB_ONLYDIR) as $appDir) {
            $appId = basename($appDir);
            $pagesDir = $appDir . '/pages';
            $files = is_dir($pagesDir) ? glob($pagesDir . '/*.json') : [];
            $pages = [];
            foreach ($files as $f) {
                $name = basename($f, '.json');
                $content = file_get_contents($f);
                $pages[] = [
                    'key' => $name,
                    'content' => json_decode($content, true) ?: [],
                    'raw' => $content,
                    'updated' => filemtime($f),
                ];
            }
            $apps[] = ['app_id' => $appId, 'pages' => $pages];
        }
        return $this->renderSuccess(['apps' => $apps]);
    }

    /** 保存单个 JSON 配置文件 */
    public function saveConfig()
    {
        $appId = $this->request->post('app_id');
        $pageKey = $this->request->post('page_key');
        $content = $this->request->post('content');
        if (empty($appId) || empty($pageKey)) return $this->renderError('参数缺失');

        $dir = \cores\AppGenerator::pageConfigDir($appId);
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $path = $dir . '/' . $pageKey . '.json';

        if (is_string($content)) {
            $data = json_decode($content, true);
        } else {
            $data = $content;
        }
        if (!is_array($data)) return $this->renderError('配置格式错误');

        file_put_contents($path, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        // 同步数据库字段：对比配置里的实体字段，新增列到表
        $this->syncEntityFields($appId, $data);

        return $this->renderSuccess([], '配置已保存');
    }

    /** 对比配置字段与数据库表，自动加列 */
    private function syncEntityFields(string $appId, array $pageConfig): void
    {
        try {
            $node = $pageConfig[0] ?? [];
            $props = $node['props'] ?? [];
            $entityKey = $props['entity'] ?? '';
            $fields = $props['fields'] ?? [];
            if (empty($entityKey) || empty($fields)) return;

            // 读当前实体 schema
            $row = \think\facade\Db::name('app_entity')
                ->where('app_id', $appId)->where('entity_key', $entityKey)->find();
            if (!$row) return;

            $oldSchema = json_decode($row['entity_schema'], true) ?: [];
            $newSchema = ['fields' => $fields];

            // ALTER TABLE 加新字段
            $changes = \cores\DataEngine::alterTable($row['table_name'], $oldSchema, $newSchema);

            // 更新实体 schema
            \think\facade\Db::name('app_entity')->where('id', $row['id'])->update([
                'entity_schema' => json_encode($newSchema, JSON_UNESCAPED_UNICODE),
                'update_time' => time(),
            ]);
        } catch (\Throwable $e) {}
    }

    /** 读取应用的字典项（按 app_id + dict_key 分组） */
    public function dicts()
    {
        $appId = $this->request->param('app_id');
        if (empty($appId)) return $this->renderError('参数缺失');
        $rows = \think\facade\Db::name('app_dict')
            ->where('app_id', $appId)
            ->order('sort asc, id asc')
            ->select()->toArray();
        $grouped = [];
        foreach ($rows as $r) {
            $grouped[$r['dict_key']][] = ['id' => $r['id'], 'label' => $r['label'], 'sort' => $r['sort']];
        }
        return $this->renderSuccess(['dicts' => $grouped]);
    }

    /** 新增字典项 */
    public function dictAdd()
    {
        $appId = $this->request->post('app_id');
        $dictKey = $this->request->post('dict_key');
        $label = $this->request->post('label');
        if (empty($appId) || empty($dictKey) || empty($label)) return $this->renderError('参数缺失');
        $maxSort = \think\facade\Db::name('app_dict')
            ->where('app_id', $appId)->where('dict_key', $dictKey)
            ->max('sort');
        \think\facade\Db::name('app_dict')->insert([
            'app_id' => $appId, 'dict_key' => $dictKey, 'label' => $label,
            'sort' => ($maxSort ?? 0) + 1, 'create_time' => time(),
        ]);
        return $this->renderSuccess([], '字典项已添加');
    }

    /** 删除字典项 */
    public function dictDel()
    {
        $id = (int)$this->request->post('id');
        if ($id <= 0) return $this->renderError('参数缺失');
        \think\facade\Db::name('app_dict')->where('id', $id)->delete();
        return $this->renderSuccess([], '字典项已删除');
    }

    /** 编辑字典项 */
    public function dictEdit()
    {
        $id = (int)$this->request->post('id');
        $label = $this->request->post('label');
        $sort = (int)$this->request->post('sort', 0);
        if ($id <= 0 || empty($label)) return $this->renderError('参数缺失');
        $data = ['label' => $label];
        if ($sort > 0) $data['sort'] = $sort;
        \think\facade\Db::name('app_dict')->where('id', $id)->update($data);
        return $this->renderSuccess([], '字典项已更新');
    }

    /** DSL → 生成应用 */
    public function build()
    {
        $dsl = $this->request->post('dsl');
        if (empty($dsl)) return $this->renderError('DSL 不能为空');
        if (is_string($dsl)) $dsl = json_decode($dsl, true);

        list($valid, $errors) = AppDSL::validate($dsl);
        if (!$valid) return $this->renderError('DSL 校验失败：' . implode('；', $errors));

        $appId = $this->request->post('app_id', '');
        if (empty($appId)) $appId = 'app_' . date('YmdHis');
        $generator = new AppGenerator($appId, $dsl);
        $files = $generator->generate();

        return $this->renderSuccess(['app_id' => $appId, 'files' => $files], '生成完成：' . implode('、', $files));
    }

    public function listApps()
    {
        $rows = \think\facade\Db::name('app_definition')->order('update_time desc')->select()->toArray();
        $list = [];
        foreach ($rows as $row) {
            $pages = \think\facade\Db::name('app_page')->where('app_id', $row['app_id'])->field('page_key,page_name,definition')->select()->toArray();
            foreach ($pages as &$p) {
                if (!empty($p['definition'])) $p['definition'] = json_decode($p['definition'], true);
            }
            $spec = null;
            if (!empty($row['spec'])) {
                $spec = is_string($row['spec']) ? json_decode($row['spec'], true) : $row['spec'];
            }
            $list[] = ['app_id' => $row['app_id'], 'name' => $row['name'], 'pages' => $pages, 'spec' => $spec, 'update_time' => $row['update_time']];
        }
        return $this->renderSuccess(['apps' => $list]);
    }

    /** 读取单个页面的 JSON 配置（优先读磁盘文件，回退数据库） */
    public function page()
    {
        $appId = $this->request->param('app_id');
        $pageKey = $this->request->param('page_key');
        if (empty($appId) || empty($pageKey)) return $this->renderError('参数缺失');

        // 1. 优先读服务器磁盘 JSON 文件
        $file = \cores\AppGenerator::pageConfigDir($appId) . '/' . $pageKey . '.json';
        if (is_file($file)) {
            $def = json_decode(file_get_contents($file), true);
            if ($def) return $this->renderSuccess(['definition' => $def]);
        }

        // 2. 回退数据库
        $row = \think\facade\Db::name('app_page')
            ->where('app_id', $appId)->where('page_key', $pageKey)->find();
        if (!$row) return $this->renderError('页面不存在');
        $def = $row['definition'] ?? '';
        if ($def) $def = json_decode($def, true);
        return $this->renderSuccess(['definition' => $def ?: []]);
    }

    /** AI 生成 DSL */
    public function aiDsl()
    {
        $prompt = $this->request->post('prompt');
        if (empty($prompt)) return $this->renderError('请输入需求描述');

        $sysPrompt = '你是企业应用规划专家。根据用户需求输出 App DSL JSON。格式：{"name":"应用名","entities":{"entity_key":{"label":"中文名","fields":[{"key":"field_key","label":"中文标签","type":"string|text|int|decimal|date|enum|bool|ref","required":true/false,"index":true/false,"values":["枚举值"],"ref":{"source":"crm_customer|crm_contact|user"}}]}},"pages":[{"key":"page-key","title":"页面标题","type":"table|form|dashboard","entity":"entity_key","filters":[{"field":"status","type":"select"}],"features":{"statsBar":{"metrics":[{"label":"统计","value":"count"}]}}}]}。规则：字段key用英文下划线，关联CRM用ref:{source:"crm_customer"}，pages至少含1个table和1个form。输出纯JSON，不要markdown包裹。';

        // 统一走 AI 网关（平台配置/自定义配置/历史 crm_setting 都覆盖，且按租户隔离）
        if (!\app\platform\ai\AiConfig::isConfigured()) return $this->renderError('未配置AI API Key');

        $ai = \app\platform\ai\AiGateway::request($sysPrompt, $prompt, [
            'temperature' => 0.3,
            'max_tokens'  => 3000,
            'timeout'     => 60,
        ]);
        if (!$ai->success) return $this->renderError('AI调用失败：' . $ai->error);

        $content = (string)$ai->content;
        if ($content === '') return $this->renderError('AI返回为空');

        $json = preg_replace('/^```json\s*/', '', trim($content));
        $json = preg_replace('/\s*```$/', '', $json);
        if (preg_match('/\{[\s\S]*\}/', $json, $m)) $json = $m[0];
        $dsl = json_decode($json, true);
        if (empty($dsl['entities'])) return $this->renderError('AI生成格式异常');

        return $this->renderSuccess(['dsl' => $dsl], 'DSL已生成');
    }

    /** 删除应用 */
    public function delete()
    {
        $appId = $this->request->post('app_id');
        if (empty($appId)) return $this->renderError('参数缺失');
        $entities = \think\facade\Db::name('app_entity')->where('app_id', $appId)->select();
        foreach ($entities as $e) try { \think\facade\Db::execute("DROP TABLE IF EXISTS `{$e['table_name']}`"); } catch (\Throwable $e) {}
        \think\facade\Db::name('app_entity')->where('app_id', $appId)->delete();
        \think\facade\Db::name('app_page')->where('app_id', $appId)->delete();
        \think\facade\Db::name('app_definition')->where('app_id', $appId)->delete();
        $dir = \cores\AppBuilder::genPath($appId);
        if (is_dir($dir)) { array_map('unlink', glob("$dir/*")); rmdir($dir); }
        return $this->renderSuccess([], '已删除');
    }
}
