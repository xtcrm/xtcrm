<?php
namespace mpp\crm\service;

use think\facade\Db;

/**
 * AI 助手上下文构建（PC + 移动端共用）
 */
class AssistantContextService
{
    /**
     * 构建 AI 对话上下文
     * @param string $message 用户消息
     * @param int $customerId 当前客户ID（可选）
     * @param int $userId 当前用户ID
     * @param int $storeId 租户ID
     * @return string
     */
    public function build(string $message, int $customerId, int $userId, int $storeId): string
    {
        $ctx = [];

        // ① 当前客户详情 + 最近跟进
        if ($customerId > 0) {
            $ctx = array_merge($ctx, $this->customerContext($customerId));
        }

        // ② 团队概览
        $total = Db::name('crm_customer')->where('is_delete', 0)->where('store_id', $storeId)->count();
        $needFollow = Db::name('crm_customer')
            ->where('is_delete', 0)->where('store_id', $storeId)
            ->where(function ($q) {
                $q->where('last_followup_time', '<', time() - 7 * 86400)->whereOr('last_followup_time', null);
            })->count();
        $ctx[] = "团队概览：共{$total}个客户，其中{$needFollow}个超过7天未跟进";

        // ③ 本月新增客户
        $monthStart = strtotime(date('Y-m-01'));
        $monthNew = Db::name('crm_customer')->where('is_delete', 0)->where('store_id', $storeId)
            ->where('create_time', '>=', $monthStart)->count();
        if ($monthNew > 0) {
            $newNames = Db::name('crm_customer')->where('is_delete', 0)->where('store_id', $storeId)
                ->where('create_time', '>=', $monthStart)
                ->order(['create_time' => 'desc'])->limit(10)->column('customer_name');
            $ctx[] = "本月新增：{$monthNew}个客户（" . implode('、', $newNames) . "）";
        } else {
            $ctx[] = "本月新增：0个客户";
        }

        // ④ 本月业绩
        $monthQuotation = Db::name('crm_quotation')->where('store_id', $storeId)
            ->where('create_time', '>=', $monthStart)->sum('final_amount');
        $monthOrder = Db::name('crm_order')->where('store_id', $storeId)
            ->where('create_time', '>=', $monthStart)->sum('final_amount');
        $ctx[] = "本月业绩：报价总额" . ($monthQuotation ? number_format($monthQuotation / 10000, 1) . '万' : '0')
            . "，订单总额" . ($monthOrder ? number_format($monthOrder / 10000, 1) . '万' : '0');

        // ⑤ 我的客户
        if ($userId > 0) {
            $myTotal = Db::name('crm_customer')->where('is_delete', 0)
                ->where('owner_user_id', $userId)->where('store_id', $storeId)->count();
            if ($myTotal > 0) {
                $myNeed = Db::name('crm_customer')->where('is_delete', 0)
                    ->where('owner_user_id', $userId)->where('store_id', $storeId)
                    ->where(function ($q) {
                        $q->where('last_followup_time', '<', time() - 7 * 86400)->whereOr('last_followup_time', null);
                    })->count();
                $needNames = Db::name('crm_customer')->where('is_delete', 0)
                    ->where('owner_user_id', $userId)->where('store_id', $storeId)
                    ->where(function ($q) {
                        $q->where('last_followup_time', '<', time() - 7 * 86400)->whereOr('last_followup_time', null);
                    })->order(['last_followup_time' => 'asc'])->limit(8)->column('customer_name');
                $ctx[] = "我的客户：{$myTotal}个，其中{$myNeed}个需跟进"
                    . ($needNames ? '（' . implode('、', $needNames) . '）' : '');
            } else {
                $ctx[] = "我的客户：0个";
            }
        }

        // ⑥ 知识库匹配
        $kb = $this->matchKnowledge($message, $customerId, $storeId);
        if ($kb) {
            $ctx[] = "相关知识库资料：\n{$kb}";
        }

        return implode("\n", $ctx);
    }

    /** 当前客户上下文 */
    private function customerContext(int $customerId): array
    {
        $ctx = [];
        $c = Db::name('crm_customer')->where('id', $customerId)->find();
        if (!$c) return $ctx;

        $levelName = Db::name('config')->where('config_type', 'customer_level')
            ->where('config_value', $c['level_id'])->value('config_name');
        $ctx[] = "当前客户：{$c['customer_name']}，行业{$c['industry']}，等级{$levelName}"
            . "，漏斗阶段" . (['', '初步接触', '需求确认', '报价', '谈判', '成交'][$c['funnel_stage']] ?? '')
            . "，上次跟进" . ($c['last_followup_time'] ? date('Y-m-d', $c['last_followup_time']) : '无');

        $fus = Db::name('crm_followup')->where('customer_id', $customerId)->where('is_delete', 0)
            ->order(['follow_date' => 'desc'])->limit(5)
            ->field(['follow_type', 'follow_content', 'result', 'follow_date'])->select()->toArray();
        if ($fus) {
            $ctx[] = "最近跟进：";
            foreach ($fus as $f) {
                $ctx[] = date('Y-m-d', $f['follow_date'])
                    . " [{$f['follow_type']}/{$f['result']}] " . mb_substr($f['follow_content'], 0, 100);
            }
        }
        return $ctx;
    }

    /** 知识库匹配 */
    private function matchKnowledge(string $message, int $customerId, int $storeId): string
    {
        $keywords = [];
        if ($customerId > 0) {
            $c = Db::name('crm_customer')->where('id', $customerId)->find();
            if ($c) {
                if (!empty($c['industry'])) $keywords[] = $c['industry'];
                if (!empty($c['customer_group'])) $keywords[] = $c['customer_group'];
            }
        }
        $terms = ['报价', '合同', '油墨', 'PCB', 'FPC', 'UV', '金属', '包装', '户外', '玩具', '体育用品', '高遮盖', '助剂', '辅料', '税率', '发票', '付款', '跟进', '催款', '招投标'];
        foreach ($terms as $t) {
            if (mb_strpos($message, $t) !== false || mb_strpos(implode('', $keywords), $t) !== false) {
                $keywords[] = $t;
            }
        }
        $keywords = array_unique($keywords);
        if (empty($keywords)) return '';

        $query = Db::name('crm_knowledge')->where('is_delete', 0)->where('store_id', $storeId);
        $query->where(function ($q) use ($keywords) {
            foreach ($keywords as $kw) {
                $q->whereOr('title|content|tags', 'like', "%{$kw}%");
            }
        });
        $rows = $query->order(['sort_order' => 'asc'])->limit(3)
            ->field(['title', 'content', 'category'])->select()->toArray();
        if (empty($rows)) return '';

        $text = '';
        foreach ($rows as $r) {
            $text .= "[{$r['category']}] {$r['title']}：{$r['content']}\n";
        }
        return $text;
    }
}
