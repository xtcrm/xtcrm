<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use mpp\crm\model\Knowledge as KnowledgeModel;

class KnowledgeService extends BaseService
{
    public function getList(array $params): array
    {
        $model = new KnowledgeModel();
        $list = $model->getList($params);
        return ['success' => true, 'data' => compact('list'), 'error' => ''];
    }

    public function categories(): array
    {
        $cats = ['产品知识', '销售话术', '竞品对比', '成功案例', 'FAQ'];
        return ['success' => true, 'data' => compact('cats'), 'error' => ''];
    }

    public function add(array $data, int $actorUserId): array
    {
        $model = new KnowledgeModel();
        if ($model->add($data)) {
            return ['success' => true, 'data' => ['id' => $model->id], 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $model->getError() ?: '添加失败'];
    }

    public function edit(int $id, array $data, int $actorUserId): array
    {
        $model = new KnowledgeModel();
        $row = $model->where('id', $id)->find();
        if (!$row) return ['success' => false, 'data' => null, 'error' => '记录不存在'];
        if ($row->edit($data)) {
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $row->getError() ?: '更新失败'];
    }

    public function delete(int $id, int $actorUserId): array
    {
        $model = new KnowledgeModel();
        $row = $model->where('id', $id)->find();
        if (!$row) return ['success' => false, 'data' => null, 'error' => '记录不存在'];
        $row->is_delete = 1;
        if ($row->save()) {
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => '删除失败'];
    }
}
