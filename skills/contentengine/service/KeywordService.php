<?php
namespace skills\contentengine\service;

use skills\contentengine\model\ContentKeyword;

class KeywordService
{
    public function getList(array $params): array
    {
        $model = new ContentKeyword();
        $list = $model->getList($params, $params);
        return ['success' => true, 'data' => compact('list'), 'error' => ''];
    }

    public function add(array $data): array
    {
        $model = new ContentKeyword();
        if ($model->add($data)) {
            return ['success' => true, 'data' => ['id' => $model->id], 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $model->getError() ?: '添加失败'];
    }

    public function delete(int $id): array
    {
        $model = ContentKeyword::where('id', $id)->find();
        if (!$model) return ['success' => false, 'data' => null, 'error' => '关键词不存在'];
        $model->save(['status' => 0, 'update_time' => time()]);
        return ['success' => true, 'data' => [], 'error' => ''];
    }

    public function batchImport(array $keywords, int $type = 2): array
    {
        $model = new ContentKeyword();
        $result = $model->batchImport($keywords, $type);
        return ['success' => true, 'data' => $result, 'error' => ''];
    }
}
