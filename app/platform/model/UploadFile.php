<?php

declare (strict_types=1);

namespace app\platform\model;

use app\common\model\UploadFile as UploadFileModel;
use app\common\library\storage\Driver as StorageDriver;
use app\common\enum\file\Storage as StorageEnum;

/**
 * 文件库模型
 * Class UploadFile
 * @package app\platform\model
 */
class UploadFile extends UploadFileModel
{
    /**
     * 获取列表记录
     * @param array $param
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getList(array $param = [])
    {
        // 商品列表获取条件
        $params = $this->setQueryDefaultValue($param, [
            'fileType' => -1,                   // 文件类型(-1全部 10图片 20附件 30视频)
            'groupId' => -1,                    // 分组ID(-1全部 0未分组)
            'fileName' => '',                   // 文件名称
            'storage' => '',                    // 存储方式(StorageEnum)
            'channel' => -1,                    // 上传来源(-1全部 10商户后台 20用户端)
            'isRecycle' => false                // 是否在回收站
        ]);
        // 查询对象
        $query = $this->getNewQuery();
        // 文件分组
        $params['groupId'] > -1 && $query->where('group_id', '=', (int)$params['groupId']);
        // 文件类型
        $params['fileType'] > -1 && $query->where('file_type', '=', (int)$params['fileType']);
        // 存储方式
        !empty($params['storage']) && $query->where('storage', '=', $params['storage']);
        // 上传来源
        $params['channel'] > -1 && $query->where('channel', '=', (int)$params['channel']);
        // 文件名称
        !empty($params['fileName']) && $query->where('file_name', 'like', "%{$params['fileName']}%");
        // 是否在回收站
        $query->where('is_recycle', '=', (int)$params['isRecycle']);
        // 查询列表数据
        return $query->where('channel', '=', 10)
            ->where('is_delete', '=', 0)
            ->order(['file_id' => 'desc'])
            ->paginate(15);
    }

    /**
     * 移入|移出回收站
     * @param bool $isRecycle
     * @return false|int
     */
    public function setRecycle(bool $isRecycle = true)
    {
        return $this->save(['is_recycle' => (int)$isRecycle]);
    }

    /**
     * 删除文件(批量)
     * @param array $fileIds 文件ID集
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setDelete(array $fileIds): bool
    {
        // 验证文件数量
        if (count($fileIds) > 15) {
            $this->error = '一次性最多删除15个文件';
            return false;
        }
        // 存储配置信息（默认本地存储）
        $config = ['default' => 'local', 'engine' => ['local' => null]];
        foreach ($fileIds as $fileId) {
            // 获取文件详情
            $fileInfo = static::detail($fileId);
            // 实例化存储驱动
            $storage = new StorageDriver($config, $fileInfo['storage']);
            // 删除文件
            if (!$storage->delete($fileInfo['file_path'])) {
                $this->error = '文件删除失败：' . $storage->getError();
                return false;
            }
            // 标记为已删除
            $fileInfo->save(['is_delete' => 1]);
        }
        return true;
    }

    /**
     * 批量移动文件分组
     * @param int $groupId
     * @param array $fileIds
     * @return bool
     */
    public function moveGroup(int $groupId, array $fileIds): bool
    {
        return static::updateBase(['group_id' => $groupId], [['file_id', 'in', $fileIds]]);
    }

    /**
     * 添加文件库记录
     * @param array $data
     * @param int $fileType
     * @param int $groupId
     * @return bool|false
     */
    public function add(array $data, int $fileType, int $groupId = 0): bool
    {
        return $this->save([
            'group_id' => $groupId > 0 ? (int)$groupId : 0,
            'channel' => 10,
            'storage' => $data['storage'],
            'domain' => $data['domain'],
            'file_name' => $data['file_name'],
            'file_path' => $data['file_path'],
            'file_size' => $data['file_size'],
            'file_ext' => $data['file_ext'],
            'file_type' => $fileType,
            'store_id' => self::$storeId
        ]);
    }

    /**
     * 编辑记录
     * @param array $data
     * @return bool
     */
    public function edit(array $data): bool
    {
        return $this->allowField(['file_name', 'group_id'])->save($data) !== false;
    }
}
