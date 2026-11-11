<?php
namespace skills\entquery\service;

/**
 * 开源版占位：entquery skill 未开源
 *
 * CRM 的 AI Agent 有 lookup_company 工具会调用这里。
 * 开源版不接工商数据源，直接返回明确错误。
 */
class CompanyQuery
{
    public function __construct(int $storeId, int $userId = 0)
    {
    }

    public function search(string $keyword): array
    {
        return ['success' => false, 'error' => '企业查询功能未包含在开源版中'];
    }

    public function detail(string $keyword, bool $force = false): array
    {
        return ['success' => false, 'error' => '企业查询功能未包含在开源版中'];
    }
}
