<?php
namespace skills\workorder\service;

/**
 * 开源版占位：工单 skill 未开源
 *
 * CRM 的项目 / 交付模块会读写 yoshop_workorder_* 表，并调用本类的少量方法。
 * 开源发行版只提供表结构（见 mpp/crm/migrations/000_0.0.1_public_closed_skill_schema.sql）
 * 和这层最小占位，避免 CRM 代码因为类不存在而 fatal。
 *
 * 这里不包含任何工单业务逻辑：
 *   - 读方法返回空结果
 *   - 写方法返回明确的「未安装」错误，由调用方自行提示
 *
 * 如需完整工单能力，请使用闭源 skill：skills/workorder。
 */
class TicketService
{
    /** @var int */
    private $storeId = 0;

    public function __construct(int $storeId = 0)
    {
        $this->storeId = $storeId;
    }

    /** 绑定租户（CRM 里用静态方式调用） */
    public static function forStore(int $storeId)
    {
        return new self($storeId);
    }

    private function notAvailable(): array
    {
        return ['success' => false, 'error' => '工单功能未包含在开源版中'];
    }

    public function getMaterialList(array $params = []): array
    {
        return ['success' => true, 'data' => ['list' => ['data' => [], 'total' => 0]]];
    }

    public function getTypeManageList(): array
    {
        return ['success' => true, 'data' => ['list' => [], 'total' => 0]];
    }

    public function add(array $payload, array $actor = []): array
    {
        return $this->notAvailable();
    }

    public function changeStatus(int $id, int $status, string $remark = '', array $actor = []): array
    {
        return $this->notAvailable();
    }

    public function addType(array $data): array
    {
        return $this->notAvailable();
    }

    public function editType(array $data): array
    {
        return $this->notAvailable();
    }

    public function deleteType(int $id): array
    {
        return $this->notAvailable();
    }

    public function setTypeStatus(int $id, int $status): array
    {
        return $this->notAvailable();
    }
}
