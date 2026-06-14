<?php

declare (strict_types=1);

/**
 * 权限检查（app/store/ 迁移完成后，Auth 类已移除，默认放行）
 */
function checkPrivilege($url, bool $strict = true): bool
{
    return true;
}

function str2date(string $date, bool $isWithTime = false)
{
    if (!$isWithTime) {
        $date = date('Y-m-d', strtotime($date));
    }
    return strtotime($date);
}

function between_time(array $times, bool $isWithTime = false): array
{
    foreach ($times as &$time) {
        $time = trim($time, '&quot;');
        $time = str2date($time, $isWithTime);
    }
    return ['start_time' => current($times), 'end_time' => next($times)];
}
