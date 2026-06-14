<?php
namespace app\common\enum;

/**
 * 全局字典类型常量
 * 数据来源: yoshop_config 表（store_id=0 系统预置, store_id>0 租户自定义）
 *
 * ===== 后端用法 =====
 *
 *   use app\common\service\ConfigService;
 *   use app\common\enum\ConfigType;
 *
 *   // 获取系统默认
 *   $industries = ConfigService::get(ConfigType::INDUSTRY);
 *
 *   // 获取含租户自定义
 *   $industries = ConfigService::getWithStore(ConfigType::INDUSTRY, $storeId);
 *
 *   // 快捷方法（自动含租户）
 *   $industries = ConfigService::industries();
 *   $banks      = ConfigService::banks();
 *   $services   = ConfigService::serviceTypes();
 *
 *   // 清除缓存（增删改后调用）
 *   ConfigService::clearCache(ConfigType::INDUSTRY, $storeId);
 *
 * ===== 前端用法 =====
 *
 *   import { getAll as getConfigs } from '@/api/fiscal/config'
 *   getConfigs().then(res => {
 *     this.industries = res.data.industry
 *     this.banks = res.data.bank
 *   })
 *
 * ===== 新增类型 =====
 *
 *   1. 在下面加常量
 *   2. INSERT INTO yoshop_config 预置数据
 *   3. ConfigService 加快捷方法（可选）
 *   4. 前端即可通过 /fiscal.config/all 获取
 */
class ConfigType
{
    /** 行业 */
    const INDUSTRY = 'industry';
    /** 银行 */
    const BANK = 'bank';
    /** 服务类型 */
    const SERVICE_TYPE = 'service_type';
    /** 发票类型 */
    const INVOICE_TYPE = 'invoice_type';
    /** 文档分类 */
    const DOC_CATEGORY = 'doc_category';
    /** 商机类型 */
    const OPPORTUNITY_TYPE = 'opportunity_type';
    /** 企业类型 */
    const ECON_KIND = 'econ_kind';
    /** 收费周期 */
    const PAYMENT_CYCLE = 'payment_cycle';

    // === CRM 预留 ===
    const CUSTOMER_LEVEL  = 'customer_level';
    const CUSTOMER_GROUP  = 'customer_group';
    const CUSTOMER_SOURCE = 'customer_source';
}
