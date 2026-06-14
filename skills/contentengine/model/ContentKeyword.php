<?php
namespace skills\contentengine\model;

use cores\BaseModel;

class ContentKeyword extends BaseModel
{
    protected $name = 'crm_content_keyword';
    protected $autoWriteTimestamp = true;

    public function getList(array $where = [], array $param = [])
    {
        $query = $this->where('status', '>=', 0);
        if (!empty($where['type'])) $query->where('type', $where['type']);
        if (!empty($where['keyword'])) $query->where('keyword', 'like', '%' . $where['keyword'] . '%');
        if (!empty($where['group_tag'])) $query->where('group_tag', $where['group_tag']);
        return $query->order(['id' => 'desc'])->paginate($param);
    }

    public function add(array $data): bool
    {
        $data['store_id'] = static::$storeId;
        if (empty($data['keyword'])) { $this->error = '关键词不能为空'; return false; }
        $data['create_time'] = time();
        $data['update_time'] = time();
        return $this->save($data) !== false;
    }

    public function batchImport(array $keywords, int $type = 2): array
    {
        $added = 0; $skipped = 0;
        foreach ($keywords as $kw) {
            $kw = trim($kw);
            if (empty($kw)) continue;
            $exists = $this->where('keyword', $kw)->find();
            if ($exists) { $skipped++; continue; }
            $this->add(['keyword' => $kw, 'type' => $type]);
            $added++;
        }
        return ['added' => $added, 'skipped' => $skipped];
    }
}
