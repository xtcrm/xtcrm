<?php

declare(strict_types=1);

namespace app\platform\flow\model;

use cores\BaseModel;

/**
 * 流程模板（一个版本一行）
 */
class Template extends BaseModel
{
    protected $name = 'flow_template';
    protected $autoWriteTimestamp = true;
    protected $dateFormat = false;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';

    public function nodes()
    {
        return $this->hasMany(Node::class, 'template_id', 'id');
    }

    public function edges()
    {
        return $this->hasMany(Edge::class, 'template_id', 'id');
    }

    public static function statusMap(): array
    {
        return [
            self::STATUS_DRAFT => '草稿',
            self::STATUS_PUBLISHED => '已发布',
            self::STATUS_ARCHIVED => '已归档',
        ];
    }

    public function detail(int $id)
    {
        return $this->where('id', $id)->where('is_delete', 0)->find();
    }
}
