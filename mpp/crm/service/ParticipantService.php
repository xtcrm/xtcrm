<?php
namespace mpp\crm\service;

use app\platform\BaseService;
use mpp\crm\model\Project as ProjectModel;
use mpp\crm\model\ProjectParticipant as ParticipantModel;
use think\facade\Db;

/**
 * 项目参与人 / 数据权限
 *
 * 功能权限由角色菜单控制；本服务只负责"能看到哪些项目"。
 */
class ParticipantService extends BaseService
{
    /** 项目参与人列表 */
    public function listByProject(int $projectId, int $storeId): array
    {
        $this->bindStore($storeId);
        return (new ParticipantModel())->listByProject($projectId);
    }

    /** 批量加人（部门/角色批量时由调用方展开成 user_ids） */
    public function addMany(int $projectId, array $userIds, int $permission, int $source, int $invitedBy, int $storeId, string $remark = ''): array
    {
        $this->bindStore($storeId);
        $project = (new ProjectModel())->detail($projectId);
        if (!$project) return ['success' => false, 'error' => '项目不存在'];

        $count = (new ParticipantModel())->addMany($projectId, $userIds, $permission, $source, $invitedBy, $remark);
        if ($count > 0) {
            EventService::record('project_participant_added', 'project', $projectId, [
                'user_ids' => array_values(array_unique(array_map('intval', $userIds))),
                'permission' => $permission,
                'source' => $source,
            ], $invitedBy, $projectId);
        }
        return ['success' => true, 'data' => ['count' => $count]];
    }

    /** 修改参与人权限（只读 ↔ 可编辑） */
    public function updatePermission(int $projectId, int $userId, int $permission, int $actorId, int $storeId): array
    {
        $this->bindStore($storeId);
        $project = (new ProjectModel())->detail($projectId);
        if (!$project) return ['success' => false, 'error' => '项目不存在'];
        if ((int)$project['owner_user_id'] === $userId) {
            return ['success' => false, 'error' => '项目负责人权限固定，不能修改'];
        }
        $permission = $permission === 2 ? 2 : 1;
        $affected = Db::name('crm_project_participant')
            ->where('store_id', $storeId)->where('project_id', $projectId)
            ->where('user_id', $userId)->where('status', 1)
            ->update(['permission' => $permission, 'update_time' => time()]);
        if ($affected === false) return ['success' => false, 'error' => '操作失败'];

        EventService::record('project_participant_updated', 'project', $projectId, [
            'user_id' => $userId, 'permission' => $permission,
        ], $actorId, $projectId);
        return ['success' => true, 'data' => null];
    }

    /** 移出参与人（不删行） */
    public function remove(int $projectId, int $userId, int $actorId, int $storeId, string $reason = ''): array
    {
        $this->bindStore($storeId);
        $project = (new ProjectModel())->detail($projectId);
        if (!$project) return ['success' => false, 'error' => '项目不存在'];
        if ((int)$project['owner_user_id'] === $userId) return ['success' => false, 'error' => '项目负责人不能被移出，请先转派'];
        if (!(new ParticipantModel())->remove($projectId, $userId)) {
            return ['success' => false, 'error' => '操作失败'];
        }
        EventService::record('project_participant_removed', 'project', $projectId, [
            'user_id' => $userId, 'reason' => $reason,
        ], $actorId, $projectId);
        return ['success' => true, 'data' => null];
    }

    /**
     * 当前用户可见的项目 ID 集合
     * @return array|null null 表示不限制（超管）
     */
    public function visibleProjectIds(int $viewerId, int $storeId): ?array
    {
        if ($this->isSuper($viewerId, $storeId)) return null;

        $participantIds = Db::name('crm_project_participant')
            ->where('store_id', $storeId)->where('user_id', $viewerId)->where('status', 1)
            ->column('project_id');

        $collabCustomerIds = Db::name('crm_customer_collaborator')
            ->where('store_id', $storeId)->where('user_id', $viewerId)
            ->column('customer_id');

        $ownerCustomerIds = Db::name('crm_customer')
            ->where('store_id', $storeId)->where('owner_user_id', $viewerId)->where('is_delete', 0)
            ->column('id');

        $query = Db::name('crm_project')
            ->where('store_id', $storeId)->where('is_delete', 0)
            ->where(function ($q) use ($viewerId, $participantIds, $collabCustomerIds, $ownerCustomerIds) {
                $q->where('owner_user_id', $viewerId)->whereOr('sales_user_id', $viewerId);
                if (!empty($participantIds)) $q->whereOr('id', 'in', $participantIds);
                if (!empty($collabCustomerIds)) $q->whereOr('customer_id', 'in', $collabCustomerIds);
                if (!empty($ownerCustomerIds)) $q->whereOr('customer_id', 'in', $ownerCustomerIds);
            });

        return $query->column('id');
    }

    public function canView(int $projectId, int $viewerId, int $storeId): bool
    {
        if ($this->isSuper($viewerId, $storeId)) return true;
        $ids = $this->visibleProjectIds($viewerId, $storeId);
        return $ids === null || in_array($projectId, array_map('intval', $ids), true);
    }

    /** 能否编辑项目内容（不含取消/归档等高风险动作，那些由负责人判定） */
    public function canEdit(int $projectId, int $viewerId, int $storeId): bool
    {
        if ($this->isSuper($viewerId, $storeId)) return true;

        $project = Db::name('crm_project')
            ->where('store_id', $storeId)->where('id', $projectId)->where('is_delete', 0)->find();
        if (!$project) return false;

        if (in_array($viewerId, [(int)$project['owner_user_id'], (int)$project['sales_user_id']], true)) return true;

        $participant = Db::name('crm_project_participant')
            ->where('store_id', $storeId)->where('project_id', $projectId)
            ->where('user_id', $viewerId)->where('status', 1)->find();
        if ($participant && (int)$participant['permission'] === 2) return true;

        // 客户级协作人：可编辑等价于参与人可编辑
        $collab = Db::name('crm_customer_collaborator')
            ->where('store_id', $storeId)->where('customer_id', $project['customer_id'])
            ->where('user_id', $viewerId)->find();
        if ($collab && (int)$collab['permission'] === 2) return true;

        $customerOwner = Db::name('crm_customer')
            ->where('store_id', $storeId)->where('id', $project['customer_id'])->value('owner_user_id');
        return (int)$customerOwner === $viewerId;
    }

    private function isSuper(int $userId, int $storeId): bool
    {
        if ($userId <= 0) return false;
        $isSuper = Db::name('store_user')
            ->where('store_id', $storeId)->where('store_user_id', $userId)
            ->value('is_super');
        return (int)$isSuper === 1;
    }

    private function bindStore(int $storeId): void
    {
        ProjectModel::$storeId = $storeId;
        ParticipantModel::$storeId = $storeId;
    }
}
