<?php
namespace skills\contentengine\service;

use skills\contentengine\model\Solution;

class SolutionService
{
    public function getList(array $params): array
    {
        $model = new Solution();
        $list = $model->getList($params, $params);
        return ['success' => true, 'data' => compact('list'), 'error' => ''];
    }

    public function detail(int $id): array
    {
        $model = Solution::where('id', $id)->find();
        if (!$model) return ['success' => false, 'data' => null, 'error' => '方案不存在'];
        return ['success' => true, 'data' => ['detail' => $model], 'error' => ''];
    }

    public function add(array $data): array
    {
        $model = new Solution();
        if ($model->add($data)) {
            return ['success' => true, 'data' => ['id' => $model->id], 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $model->getError() ?: '添加失败'];
    }

    public function edit(array $data): array
    {
        $model = Solution::where('id', $data['id'])->find();
        if (!$model) return ['success' => false, 'data' => null, 'error' => '方案不存在'];
        if ($model->edit($data)) {
            return ['success' => true, 'data' => [], 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $model->getError() ?: '编辑失败'];
    }

    public function delete(int $id): array
    {
        $model = Solution::where('id', $id)->find();
        if (!$model) return ['success' => false, 'data' => null, 'error' => '方案不存在'];
        $model->save(['status' => 0, 'update_time' => time()]);
        return ['success' => true, 'data' => [], 'error' => ''];
    }
}
