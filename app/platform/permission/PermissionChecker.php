<?php

declare (strict_types=1);

namespace app\platform\permission;

use app\platform\auth\TokenService;
use app\platform\model\User;
use app\platform\model\UserRole;
use app\platform\model\RoleMenu;
use app\platform\model\Menu;

/**
 * 平台权限检查器
 *
 * 基于 platform 自有 model，不依赖 app/store/ 或 app/common/。
 */
class PermissionChecker
{
    public static function can(string $menuPath, ?int $userId = null): bool
    {
        try {
            self::require($menuPath, '', $userId);
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @throws \cores\exception\BaseException
     */
    public static function require(string $menuPath, string $actionName = '', ?int $userId = null): void
    {
        if ($userId === null) {
            $user = TokenService::getUser();
        } else {
            $user = User::detail($userId);
        }
        $user = $user ?: [];

        // 超管放行
        if (!empty($user['is_super'])) return;

        $uid = $user['store_user_id'] ?? 0;
        if (empty($uid)) {
            $name = $actionName ?: basename($menuPath);
            throwError("无权限，当前角色未开启「{$name}」操作");
            return;
        }

        $roleIds = UserRole::getRoleIdsByUserId($uid);
        if (empty($roleIds) || in_array(1, $roleIds)) return;

        $menuIds = RoleMenu::getMenuIds($roleIds);
        $has = Menu::withoutGlobalScope()
            ->where('path', $menuPath)
            ->whereIn('menu_id', $menuIds)
            ->count() > 0;

        if (!$has) {
            $name = $actionName ?: basename($menuPath);
            throwError("无权限，当前角色未开启「{$name}」操作");
        }
    }

    public static function getAbilities(int $userId): array
    {
        $roleIds = UserRole::getRoleIdsByUserId($userId);
        if (empty($roleIds)) return [];

        $menuIds = RoleMenu::getMenuIds($roleIds);
        if (empty($menuIds)) return [];

        return Menu::withoutGlobalScope()
            ->whereIn('menu_id', $menuIds)
            ->column('path');
    }

    public static function getRoles(int $userId): array
    {
        return UserRole::getRoleIdsByUserId($userId);
    }
}
