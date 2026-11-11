<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use mpp\crm\model\TagGroup as TagGroupModel;
use mpp\crm\model\Tag as TagModel;
use mpp\crm\model\CustomerTag as CustomerTagModel;
use mpp\crm\model\ContactTag as ContactTagModel;
use mpp\crm\service\OperationLogService;

class TagService extends BaseService
{
    // ==================== 分组 CRUD ====================

    public function groupList(array $params): array
    {
        $model = new TagGroupModel();
        $model::$storeId = $this->storeId;
        $list = $model->getList(['target_type' => $params['target_type'] ?? '']);
        return ['success' => true, 'data' => ['list' => $list], 'error' => ''];
    }

    public function groupAdd(array $data): array
    {
        $model = new TagGroupModel();
        $model::$storeId = $this->storeId;
        if ($model->add($data)) {
            return ['success' => true, 'data' => ['id' => $model->id], 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $model->getError() ?: '添加失败'];
    }

    public function groupEdit(int $id, array $data): array
    {
        $model = new TagGroupModel();
        $model::$storeId = $this->storeId;
        $detail = $model->detail($id);
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '分组不存在'];
        $detail->save($data);
        return ['success' => true, 'data' => null, 'error' => ''];
    }

    public function groupDelete(int $id): array
    {
        $model = new TagGroupModel();
        $model::$storeId = $this->storeId;
        if ($model->deleteGroup($id)) {
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $model->getError() ?: '删除失败'];
    }

    // ==================== 标签 CRUD ====================

    public function getList(array $params): array
    {
        $model = new TagModel();
        $model::$storeId = $this->storeId;
        $list = $model->getList($params);
        return ['success' => true, 'data' => ['list' => $list], 'error' => ''];
    }

    public function add(array $data): array
    {
        $model = new TagModel();
        $model::$storeId = $this->storeId;
        if ($model->add($data)) {
            OperationLogService::logAdd('标签', $model->id, $data['name'] ?? '');
            return ['success' => true, 'data' => ['id' => $model->id], 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $model->getError() ?: '添加失败'];
    }

    public function edit(int $id, array $data): array
    {
        $model = new TagModel();
        $model::$storeId = $this->storeId;
        $detail = $model->detail($id);
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '标签不存在'];
        $detail->save($data);
        OperationLogService::logEdit('标签', $id, $data['name'] ?? ($detail['name'] ?? ''), $data);
        return ['success' => true, 'data' => null, 'error' => ''];
    }

    public function delete(int $id): array
    {
        $model = new TagModel();
        $model::$storeId = $this->storeId;
        $tag = $model->detail($id);
        if ($model->deleteTag($id)) {
            OperationLogService::logDelete('标签', $id, $tag['name'] ?? '');
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $model->getError() ?: '删除失败'];
    }

    // ==================== 分组树（下拉选择器用） ====================

    public function allByTarget(string $targetType): array
    {
        $model = new TagModel();
        $model::$storeId = $this->storeId;
        $list = $model->getGroupedByTarget($targetType);
        return ['success' => true, 'data' => ['list' => $list], 'error' => ''];
    }

    // ==================== 客户标签 ====================

    public function getCustomerTags(int $customerId): array
    {
        $model = new CustomerTagModel();
        $model::$storeId = $this->storeId;
        $tags = $model->getByCustomer($customerId);
        $names = array_column($tags, 'name');
        return ['success' => true, 'data' => ['tags' => $tags, 'names' => $names], 'error' => ''];
    }

    /**
     * 设置客户标签，接受标签名数组。已有标签按名匹配，不存在的自动创建（未分组）
     */
    public function setCustomerTags(int $customerId, array $names): array
    {
        $names = array_map('trim', $names);
        $names = array_filter($names, function($n) { return $n !== ''; });
        $tagIds = $this->resolveOrCreateTags(array_values($names), 'customer');

        $model = new CustomerTagModel();
        $model::$storeId = $this->storeId;
        if ($model->setTags($customerId, $tagIds)) {
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $model->getError() ?: '设置失败'];
    }

    // ==================== 联系人标签 ====================

    public function getContactTags(int $contactId): array
    {
        $model = new ContactTagModel();
        $model::$storeId = $this->storeId;
        $tags = $model->getByContact($contactId);
        $names = array_column($tags, 'name');
        return ['success' => true, 'data' => ['tags' => $tags, 'names' => $names], 'error' => ''];
    }

    public function setContactTags(int $contactId, array $names): array
    {
        $names = array_map('trim', $names);
        $names = array_filter($names, function($n) { return $n !== ''; });
        $tagIds = $this->resolveOrCreateTags(array_values($names), 'contact');

        $model = new ContactTagModel();
        $model::$storeId = $this->storeId;
        if ($model->setTags($contactId, $tagIds)) {
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $model->getError() ?: '设置失败'];
    }

    // ==================== 工具方法 ====================

    /**
     * 按名称解析标签：已有的返回 ID，不存在的自动创建（未分组）
     * @param string[] $names
     * @param string $targetType 'customer' | 'contact'
     * @return int[]
     */
    private function resolveOrCreateTags(array $names, string $targetType): array
    {
        if (empty($names)) return [];

        $tagModel = new TagModel();
        $tagModel::$storeId = $this->storeId;

        // 查已有标签
        $existing = $tagModel->db()
            ->where('store_id', $this->storeId)
            ->where('target_type', $targetType)
            ->whereIn('name', $names)
            ->column('id', 'name');

        // MySQL utf8mb4_unicode_ci 大小写不敏感，而 PHP 数组键大小写敏感。
        // 统一转小写做键，避免库里已存在 "test"、输入 "Test" 时查不到而走重复创建。
        $existingByLower = [];
        foreach ($existing as $dbName => $id) {
            $existingByLower[strtolower($dbName)] = $id;
        }

        $ids = [];
        foreach ($names as $name) {
            $key = strtolower($name);
            if (isset($existingByLower[$key])) {
                $ids[] = $existingByLower[$key];
                continue;
            }
            // 自动创建，放入未分组
            $newTag = new TagModel();
            $newTag::$storeId = $this->storeId;
            if ($newTag->add([
                'name'        => $name,
                'target_type' => $targetType,
                'group_id'    => 0,
                'color'       => 'blue',
                'sort_order'  => 100,
            ])) {
                $ids[] = $newTag->id;
            } else {
                // add 失败：多为并发创建、输入重名或大小写/空白差异被唯一键拦截，
                // 重新按名查一次拿真实 ID，避免把 null 传进 pivot 插入
                $again = $tagModel->db()
                    ->where('store_id', $this->storeId)
                    ->where('target_type', $targetType)
                    ->where('name', $name)
                    ->find();
                if ($again) $ids[] = $again['id'];
            }
        }
        // 兜底过滤，保证返回的必定是合法 ID（>0）
        return array_values(array_filter($ids, function ($id) { return $id > 0; }));
    }
}
