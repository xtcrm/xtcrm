<?php
namespace skills\entquery\service;

/**
 * 开源版占位：entquery skill 未开源
 *
 * 客户批量导入时会用本地企业档案做「名称 / 信用代码」核验。
 * 开源版没有 entquery 的档案表，这里统一返回「未命中」，
 * 导入流程会照常继续，只是不做档案回填。
 */
class CompanyArchive
{
    public function verifyAgainst(string $name, string $taxNumber): array
    {
        return ['found' => false, 'match' => false, 'archive_name' => '', 'fields' => []];
    }
}
