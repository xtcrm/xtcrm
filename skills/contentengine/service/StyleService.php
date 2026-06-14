<?php
namespace skills\contentengine\service;

use think\facade\Db;

class StyleService
{
    /** 获取预设 + 租户自定义 */
    public function listAll(int $storeId): array
    {
        $presets = PresetLoader::getStyles();
        $custom = Db::name('crm_content_style')
            ->where('store_id', $storeId)
            ->order('id', 'asc')->select()->toArray();
        return ['success' => true, 'data' => ['presets' => $presets, 'custom' => $custom], 'error' => ''];
    }

    /** 从预设复制为租户自己的风格 */
    public function copyFromPreset(string $presetKey, int $storeId): array
    {
        $preset = PresetLoader::getStyle($presetKey);
        if (!$preset) return ['success' => false, 'data' => null, 'error' => '预设不存在'];
        $id = Db::name('crm_content_style')->insertGetId([
            'store_id' => $storeId,
            'name' => $preset['name'] . '(我的)',
            'role_desc' => $preset['role_desc'] ?? '',
            'system_prompt' => $preset['system_prompt'] ?? '',
            'forbidden_words' => $preset['forbidden_words'] ?? '',
            'tone' => $preset['tone'] ?? '专业',
            'create_time' => time(),
            'update_time' => time(),
        ]);
        $row = Db::name('crm_content_style')->where('id', $id)->find();
        return ['success' => true, 'data' => $row, 'error' => ''];
    }

    public function add(array $data, int $storeId): array
    {
        $data['store_id'] = $storeId;
        if (empty($data['name'])) return ['success' => false, 'data' => null, 'error' => '名称不能为空'];
        $data['create_time'] = time();
        $data['update_time'] = time();
        $id = Db::name('crm_content_style')->insertGetId($data);
        $row = Db::name('crm_content_style')->where('id', $id)->find();
        return ['success' => true, 'data' => $row, 'error' => ''];
    }

    public function edit(array $data, int $storeId): array
    {
        Db::name('crm_content_style')->where('id', $data['id'])->where('store_id', $storeId)
            ->update(array_merge($data, ['update_time' => time()]));
        return ['success' => true, 'data' => [], 'error' => ''];
    }

    public function delete(int $id, int $storeId): array
    {
        Db::name('crm_content_style')->where('id', $id)->where('store_id', $storeId)->delete();
        return ['success' => true, 'data' => [], 'error' => ''];
    }
}
