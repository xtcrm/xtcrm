<?php
namespace mpp\crm\model;

use cores\BaseModel;
use think\facade\Db;

/**
 * 项目参与人（项目级常驻角色）
 *
 * 项目负责人 / 销售负责人存在 crm_project 上，本表只存额外参与人。
 * 移出不删行，status=0，便于审计与重新加入。
 */
class ProjectParticipant extends BaseModel
{
    protected $name = 'crm_project_participant';
    protected $autoWriteTimestamp = true;
    protected $dateFormat = false; // 时间字段保持 int 时间戳（前端按秒处理）

    /**
     * 批量加人（幂等）：已存在（含已移出）则恢复并更新权限
     */
    public function addMany(int $projectId, array $userIds, int $permission, int $source, int $invitedBy, string $remark = ''): int
    {
        $count = 0;
        foreach (array_unique(array_map('intval', $userIds)) as $userId) {
            if ($userId <= 0) continue;
            $row = Db::name($this->name)
                ->where('store_id', static::$storeId)
                ->where('project_id', $projectId)
                ->where('user_id', $userId)
                ->find();
            $now = time();
            if ($row) {
                Db::name($this->name)->where('id', $row['id'])->update([
                    'permission' => $permission,
                    'source' => $source,
                    'status' => 1,
                    'remark' => $remark !== '' ? $remark : ($row['remark'] ?? ''),
                    'update_time' => $now,
                ]);
            } else {
                Db::name($this->name)->insert([
                    'store_id' => static::$storeId,
                    'project_id' => $projectId,
                    'user_id' => $userId,
                    'permission' => $permission,
                    'source' => $source,
                    'invited_by_user_id' => $invitedBy,
                    'status' => 1,
                    'remark' => $remark,
                    'create_time' => $now,
                    'update_time' => $now,
                ]);
            }
            $count++;
        }
        return $count;
    }

    /** 移出（不删行） */
    public function remove(int $projectId, int $userId): bool
    {
        $affected = Db::name($this->name)
            ->where('store_id', static::$storeId)
            ->where('project_id', $projectId)
            ->where('user_id', $userId)
            ->update(['status' => 0, 'update_time' => time()]);
        return $affected !== false;
    }

    /** 项目参与人列表（带姓名/部门） */
    public function listByProject(int $projectId): array
    {
        return Db::name($this->name)->alias('p')
            ->leftJoin('store_user u', 'u.store_user_id = p.user_id AND u.store_id = p.store_id')
            ->where('p.store_id', static::$storeId)
            ->where('p.project_id', $projectId)
            ->where('p.status', 1)
            ->field([
                'p.id', 'p.user_id', 'p.permission', 'p.source', 'p.remark',
                'u.real_name', 'u.user_name', 'u.department_id', 'u.mobile',
            ])
            ->order(['p.id' => 'asc'])
            ->select()->toArray();
    }
}
