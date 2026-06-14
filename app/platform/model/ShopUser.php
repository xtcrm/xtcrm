<?php

declare (strict_types=1);

namespace app\platform\model;

use cores\BaseModel;

/**
 * 店铺用户模型 — 封装 yoshop_user 表
 *
 * 只读/轻量写入，用于平台会员服务。
 */
class ShopUser extends BaseModel
{
    protected $name = 'user';
    protected $pk   = 'user_id';

    /**
     * 获取单个用户（基础信息）
     */
    public static function profile(int $userId): ?array
    {
        $row = static::where('user_id', $userId)->where('is_delete', 0)->find();
        if (!$row) return null;

        return [
            'user_id'    => (int)$row['user_id'],
            'mobile'     => $row['mobile'] ?? '',
            'nick_name'  => $row['nick_name'] ?? '',
            'avatar_id'  => (int)($row['avatar_id'] ?? 0),
            'gender'     => (int)($row['gender'] ?? 0),
            'grade_id'   => (int)($row['grade_id'] ?? 0),
            'platform'   => $row['platform'] ?? '',
        ];
    }

    /**
     * 获取钱包字段
     */
    public static function wallet(int $userId): array
    {
        $row = static::where('user_id', $userId)
            ->field('balance, points, subsidy_balance, pay_money, expend_money')
            ->find();

        if (!$row) {
            return ['balance' => 0, 'points' => 0, 'subsidy_balance' => 0, 'pay_money' => 0, 'expend_money' => 0];
        }

        return [
            'balance'         => (float)$row['balance'],
            'points'          => (int)$row['points'],
            'subsidy_balance' => (float)$row['subsidy_balance'],
            'pay_money'       => (float)$row['pay_money'],
            'expend_money'    => (float)$row['expend_money'],
        ];
    }

    /**
     * 跨店铺钱包汇总
     */
    public static function walletTotal(array $storeUserIds): array
    {
        if (empty($storeUserIds)) {
            return ['balance' => 0, 'points' => 0, 'subsidy_balance' => 0, 'pay_money' => 0, 'expend_money' => 0];
        }

        $totals = static::whereIn('user_id', $storeUserIds)
            ->field('SUM(balance) as b, SUM(points) as p, SUM(subsidy_balance) as s, SUM(pay_money) as pm, SUM(expend_money) as em')
            ->find();

        return [
            'balance'         => (float)($totals['b'] ?? 0),
            'points'          => (int)($totals['p'] ?? 0),
            'subsidy_balance' => (float)($totals['s'] ?? 0),
            'pay_money'       => (float)($totals['pm'] ?? 0),
            'expend_money'    => (float)($totals['em'] ?? 0),
        ];
    }

    /**
     * 更新用户字段
     */
    public static function updateField(int $userId, array $data): bool
    {
        $data['update_time'] = time();
        return static::where('user_id', $userId)->update($data) > 0;
    }

    /**
     * 余额变动
     */
    public static function incBalance(int $userId, float $amount): bool
    {
        return static::where('user_id', $userId)
            ->inc('balance', $amount)
            ->update(['update_time' => time()]) > 0;
    }

    /**
     * 积分变动
     */
    public static function incPoints(int $userId, int $points): bool
    {
        return static::where('user_id', $userId)
            ->inc('points', $points)
            ->update(['update_time' => time()]) > 0;
    }
}
