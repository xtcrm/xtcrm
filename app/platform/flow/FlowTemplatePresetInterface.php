<?php

declare(strict_types=1);

namespace app\platform\flow;

/**
 * 系统预设流程模板
 *
 * 业务方实现此接口后，在 config/flow.php 注册 template_presets，
 * 用户新建模板时即使库里一条记录都没有，也能直接选系统预置流程。
 */
interface FlowTemplatePresetInterface
{
    /**
     * @return array<int, array{
     *   key:string,
     *   name:string,
     *   description:string,
     *   nodes:array,
     *   edges:array
     * }>
     */
    public function presets(int $storeId): array;
}
