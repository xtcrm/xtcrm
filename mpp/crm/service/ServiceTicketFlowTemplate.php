<?php

namespace mpp\crm\service;

use app\platform\flow\FlowTemplatePresetInterface;
use app\platform\flow\model\Node;
use app\platform\flow\service\TemplateService;
use think\facade\Db;

/**
 * 客服单默认流程（系统预置）
 *
 * 客服升级处理：技术处理 → 客服确认 → 完成
 * 只有客服主动发起流程时才用；默认直通不走这里。
 */
class ServiceTicketFlowTemplate implements FlowTemplatePresetInterface
{
    public const CODE = 'crm_service_ticket_default';

    public function presets(int $storeId): array
    {
        return [[
            'key'         => 'crm_service_ticket_escalate',
            'name'        => '客服升级处理',
            'description' => '技术处理 → 客服确认 → 完成（系统预置）',
            'nodes'       => $this->nodes(),
            'edges'       => $this->edges(),
        ]];
    }

    public function ensure(int $storeId, string $bizType): void
    {
        if ($bizType !== 'crm_service_ticket') return;

        $exists = Db::name('flow_template')
            ->where('store_id', $storeId)
            ->where('biz_type', 'crm_service_ticket')
            ->where('status', 'published')
            ->where('is_delete', 0)
            ->count();
        if ($exists > 0) return;

        $this->createDefault($storeId);
    }

    public function createDefault(int $storeId): void
    {
        $service = new TemplateService($storeId);
        $created = $service->create([
            'biz_type'    => 'crm_service_ticket',
            'code'        => self::CODE,
            'name'        => '客服升级处理',
            'description' => '技术处理 → 客服确认 → 完成',
        ], 0);

        $templateId = (int)($created['template']['id'] ?? 0);
        if ($templateId <= 0) return;

        $service->saveDraft($templateId, ['nodes' => $this->nodes(), 'edges' => $this->edges()], 0);
        $service->publish($templateId, true, 0, true, true);
    }

    private function nodes(): array
    {
        return [
            $this->node('start', '开始', Node::TYPE_START, 80, 300, ['sort' => 10]),

            $this->node('tech_handle', '技术处理', Node::TYPE_TASK, 300, 300, [
                'assignee_rules' => [['type' => 'role_name', 'name' => '技术']],
                'complete_mode'  => 'any',
                'actions'        => ['complete', 'reject'],
                'reject_policy'  => ['type' => 'to_start'],
                'sla_hours'      => 24,
                'form_schema'    => [
                    ['key' => 'handle_note', 'label' => '处理说明', 'type' => 'textarea', 'required' => true],
                ],
                'config' => [
                    'fallback_rules' => [
                        ['type' => 'field', 'field' => 'assignee_user_id'],
                        ['type' => 'role_name', 'name' => '客服'],
                    ],
                    'allow_transfer' => true,
                    'allow_add_sign' => true,
                ],
                'sort' => 20,
            ]),

            $this->node('confirm', '客服确认', Node::TYPE_APPROVAL, 540, 300, [
                'assignee_rules' => [['type' => 'field', 'field' => 'created_by']],
                'complete_mode'  => 'any',
                'actions'        => ['approve', 'reject'],
                'reject_policy'  => ['type' => 'back_to', 'target' => 'tech_handle'],
                'sla_hours'      => 24,
                'form_schema'    => [
                    ['key' => 'remark', 'label' => '确认结果', 'type' => 'textarea', 'required' => false],
                ],
                'config' => [
                    'fallback_rules' => [
                        ['type' => 'role_name', 'name' => '客服'],
                    ],
                    'allow_transfer' => true,
                    'allow_add_sign' => true,
                ],
                'sort' => 30,
            ]),

            $this->node('end', '结束', Node::TYPE_END, 780, 300, ['sort' => 999]),
        ];
    }

    private function edges(): array
    {
        return [
            ['from_key' => 'start',       'to_key' => 'tech_handle', 'sort' => 10],
            ['from_key' => 'tech_handle', 'to_key' => 'confirm',     'sort' => 20],
            ['from_key' => 'confirm',     'to_key' => 'end',         'sort' => 30],
        ];
    }

    private function node(string $key, string $name, string $type, int $x, int $y, array $extra = []): array
    {
        return array_merge([
            'node_key'        => $key,
            'name'            => $name,
            'type'            => $type,
            'gateway_type'    => '',
            'join_mode'       => 'all',
            'assignee_mode'   => 'any',
            'assignee_rules'  => [],
            'complete_mode'   => 'any',
            'complete_ratio'  => 0,
            'actions'         => [],
            'form_schema'     => [],
            'condition_json'  => null,
            'reject_policy'   => null,
            'sync_status'     => '',
            'sync_fields'     => null,
            'sla_hours'       => 0,
            'remind_before_hours' => 4,
            'escalate_to'     => null,
            'position_x'      => $x,
            'position_y'      => $y,
            'config'          => [],
            'sort'            => 100,
        ], $extra);
    }
}
