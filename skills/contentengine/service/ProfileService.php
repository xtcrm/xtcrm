<?php
namespace skills\contentengine\service;

use skills\contentengine\model\CompanyProfile;

class ProfileService
{
    public function detail(int $storeId): array
    {
        $model = new CompanyProfile();
        $profile = $model->getByStoreId($storeId);
        return ['success' => true, 'data' => ['detail' => $profile], 'error' => ''];
    }

    public function save(array $data, int $storeId): array
    {
        $model = new CompanyProfile();
        $ok = $model->saveProfile($data, $storeId);
        if ($ok) return ['success' => true, 'data' => [], 'error' => ''];
        return ['success' => false, 'data' => null, 'error' => $model->getError() ?: '保存失败'];
    }
}
