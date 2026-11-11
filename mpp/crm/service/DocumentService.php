<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use mpp\crm\service\OperationLogService;
use mpp\crm\model\Document as DocumentModel;
use app\common\library\storage\Driver as StorageDriver;

class DocumentService extends BaseService
{
    /** 列表 */
    public function getList(array $filters = []): array
    {
        $model = new DocumentModel();
        $list = $model->getList($filters);
        return ['success' => true, 'data' => compact('list'), 'error' => ''];
    }

    /** 上传文件并创建文档记录 */
    public function upload(array $data, int $userId): array
    {
        $customerId = intval($data['customer_id'] ?? 0);
        if ($customerId <= 0) {
            return ['success' => false, 'data' => null, 'error' => '所属客户不能为空'];
        }

        $storeId = intval($data['store_id'] ?? $this->storeId ?? 0);

        // 读取存储配置
        $storageConfig = self::getStorageConfig($storeId);

        try {
            $storage = new StorageDriver($storageConfig);
            $storage->setUploadFile('iFile')->setRootDirName((string)$storeId);
            if (!$storage->upload()) {
                return ['success' => false, 'data' => null, 'error' => $storage->getError() ?: '文件上传失败'];
            }
            $fileInfo = $storage->getSaveFileInfo();
        } catch (\Throwable $e) {
            return ['success' => false, 'data' => null, 'error' => $e->getMessage() ?: '文件上传失败'];
        }

        // 写入 upload_file 表
        $ext = strtolower(pathinfo($fileInfo['file_name'], PATHINFO_EXTENSION));
        $fileType = in_array($ext, ['jpg','jpeg','png','gif','bmp','webp','svg','ico']) ? 10
            : (in_array($ext, ['mp4','avi','mov','wmv','flv','mkv','webm']) ? 30 : 20);

        $fileId = \think\facade\Db::name('upload_file')->insertGetId([
            'group_id'   => 0,
            'channel'    => 10,
            'storage'    => $storageConfig['default'] ?? 'local',
            'domain'     => '',
            'file_name'  => $fileInfo['file_name'],
            'file_path'  => $fileInfo['file_path'],
            'file_size'  => $fileInfo['file_size'] ?? 0,
            'file_ext'   => $ext,
            'file_type'  => $fileType,
            'store_id'   => $storeId,
            'is_recycle' => 0,
            'is_delete'  => 0,
            'create_time' => time(),
        ]);

        $model = new DocumentModel();
        $docData = [
            'customer_id' => $customerId,
            'project_id' => intval($data['project_id'] ?? 0),
            'quotation_id' => intval($data['quotation_id'] ?? 0),
            'order_id' => intval($data['order_id'] ?? 0),
            'contract_id' => intval($data['contract_id'] ?? 0),
            'file_id' => $fileId,
            'category' => $data['category'] ?? '其他',
            'title' => $data['title'] ?: $fileInfo['file_name'],
            'remark' => $data['remark'] ?? '',
            'owner_user_id' => $userId,
        ];

        if ($model->add($docData)) {
            return ['success' => true, 'data' => ['id' => $model->id, 'file_id' => $fileId], 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $model->getError() ?: '保存失败'];
    }

    /** 读取存储配置 */
    private static function getStorageConfig(int $storeId): array
    {
        $setting = \think\facade\Db::name('store_setting')
            ->where('store_id', $storeId)
            ->where('key', 'storage')
            ->find();

        if ($setting && !empty($setting['values'])) {
            $values = json_decode($setting['values'], true);
            if (is_array($values)) return $values;
        }

        return ['default' => 'local', 'engine' => ['local' => null]];
    }

    /** 编辑 */
    public function edit(int $id, array $data): array
    {
        $model = new DocumentModel();
        $detail = $model->where('id', $id)->find();
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '文件不存在'];
        if ($detail->edit($data)) {
            OperationLogService::logEdit('文件', $id, $data['title'] ?? ($detail['title'] ?? ''), $data);
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => $detail->getError() ?: '更新失败'];
    }

    /** 删除（软删除 document + upload_file） */
    public function delete(int $id): array
    {
        $model = new DocumentModel();
        $detail = $model->where('id', $id)->find();
        if (!$detail) return ['success' => false, 'data' => null, 'error' => '文件不存在'];

        // 被沟通记录引用的文件不直接删除（删除沟通记录后即可删）
        $refCount = (int)\think\facade\Db::name('crm_followup')
            ->where('store_id', (int)$this->storeId)
            ->where('attachment_doc_id', $id)
            ->where('is_delete', 0)
            ->count();
        if ($refCount > 0) {
            return ['success' => false, 'data' => null, 'error' => "该文件已被 {$refCount} 条沟通记录引用，请先删除对应沟通记录"];
        }

        $detail->is_delete = 1;
        $fileId = $detail->file_id;
        if ($detail->save()) {
            OperationLogService::logDelete('文件', $id, $detail['title'] ?? '');
            // 软删除 upload_file
            \think\facade\Db::name('upload_file')->where('file_id', $fileId)->update(['is_delete' => 1]);
            return ['success' => true, 'data' => null, 'error' => ''];
        }
        return ['success' => false, 'data' => null, 'error' => '删除失败'];
    }
}
