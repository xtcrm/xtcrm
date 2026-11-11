<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use mpp\crm\model\CustomField as CustomFieldModel;

class CustomFieldService extends BaseService
{
    public function getList($param = []): array
    {
        $model = new CustomFieldModel();
        $list = $model->getList($param, $param);
        return ['success' => true, 'data' => compact('list'), 'error' => ''];
    }

    public function add($data): array
    {
        $model = new CustomFieldModel();
        if ($model->add($data)) {
            return ['success' => true, 'data' => ['id' => $model->id], 'error' => ''];
        }
        return ['success' => false, 'data' => [], 'error' => $model->getError() ?: '添加失败'];
    }

    public function edit($data): array
    {
        $model = new CustomFieldModel();
        if ($model->edit($data)) {
            return ['success' => true, 'data' => [], 'error' => ''];
        }
        return ['success' => false, 'data' => [], 'error' => '编辑失败'];
    }

    public function delete($id): array
    {
        $model = new CustomFieldModel();
        $row = $model->where('id', $id)->find();
        if (!$row) return ['success' => false, 'data' => [], 'error' => '字段不存在'];
        $row->delete();
        return ['success' => true, 'data' => [], 'error' => ''];
    }

    public function setStatus($id, $status): array
    {
        $model = new CustomFieldModel();
        $row = $model->where('id', $id)->find();
        if (!$row) return ['success' => false, 'data' => [], 'error' => '字段不存在'];
        $row->save(['status' => $status]);
        return ['success' => true, 'data' => [], 'error' => ''];
    }

    public function getActiveFields($entity = 'customer'): array
    {
        $model = new CustomFieldModel();
        $fields = $model->getActiveFields($entity);
        foreach ($fields as &$f) {
            if (!empty($f['options'])) {
                $f['options'] = json_decode($f['options'], true) ?: [];
            } else {
                $f['options'] = [];
            }
        }
        return ['success' => true, 'data' => ['fields' => $fields], 'error' => ''];
    }
}
