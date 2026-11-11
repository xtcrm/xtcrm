<?php
namespace mpp\crm\model;

use cores\BaseModel;

class Document extends BaseModel
{
    protected $name = 'crm_document';
    protected $autoWriteTimestamp = true;
    protected $dateFormat = false;
    protected $append = ['owner_user_name', 'file_info'];

    /** 列表查询 */
    public function getList(array $filters = [])
    {
        $query = $this->where('is_delete', 0);

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', intval($filters['customer_id']));
        }
        if (!empty($filters['project_id'])) {
            $query->where('project_id', intval($filters['project_id']));
        }
        if (!empty($filters['quotation_id'])) {
            $query->where('quotation_id', intval($filters['quotation_id']));
        }
        if (!empty($filters['order_id'])) {
            $query->where('order_id', intval($filters['order_id']));
        }
        if (!empty($filters['contract_id'])) {
            $query->where('contract_id', intval($filters['contract_id']));
        }
        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        $list = $query->order(['id' => 'desc'])->paginate($filters['list_rows'] ?? 15);

        // 批量获取文件信息
        $fileIds = [];
        foreach ($list as $row) {
            if (!empty($row['file_id'])) $fileIds[] = $row['file_id'];
        }
        $files = [];
        if (!empty($fileIds)) {
            $files = \think\facade\Db::name('upload_file')
                ->whereIn('file_id', $fileIds)
                ->where('is_delete', 0)
                ->column('file_name,file_path,file_size,file_ext,domain,storage', 'file_id');
        }
        // 合并文件信息到每行
        foreach ($list as &$row) {
            $fi = $files[$row['file_id']] ?? null;
            if ($fi) {
                $row['file_name'] = $fi['file_name'];
                $row['file_path'] = $fi['file_path'];
                $row['file_size'] = $fi['file_size'];
                $row['file_ext']  = $fi['file_ext'];
                $row['domain']    = $fi['domain'];
                $row['storage']   = $fi['storage'];
            }
        }

        return $list;
    }

    /** 新增 */
    public function add(array $data)
    {
        $data['store_id'] = static::$storeId;
        if (empty($data['customer_id'])) {
            $this->error = '所属客户不能为空';
            return false;
        }
        if (empty($data['file_id'])) {
            $this->error = '文件不能为空';
            return false;
        }
        return $this->save($data);
    }

    /** 编辑（只允许改标题/分类/备注） */
    public function edit(array $data)
    {
        return $this->allowField(['title', 'category', 'remark', 'quotation_id', 'order_id', 'contract_id'])->save($data);
    }

    /** owner_user_name 访问器 */
    public function getOwnerUserNameAttr($value, $data)
    {
        if (!empty($data['owner_user_id'])) {
            $u = \think\facade\Db::name('store_user')
                ->where('store_user_id', $data['owner_user_id'])
                ->field('real_name, user_name')->find();
            return $u ? ($u['real_name'] ?: $u['user_name']) : '';
        }
        return '';
    }

    /** file_info 访问器 — 构建完整文件 URL */
    public function getFileInfoAttr($value, $data)
    {
        $path = $data['file_path'] ?? '';
        if (empty($path)) return null;
        $domain = $data['domain'] ?? '';
        if (!empty($domain)) {
            return ['url' => rtrim($domain, '/') . '/' . ltrim($path, '/'), 'path' => $path];
        }
        return ['url' => '/uploads/' . ltrim($path, '/'), 'path' => $path];
    }
}
