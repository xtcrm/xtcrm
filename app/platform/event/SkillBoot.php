<?php

namespace app\platform\event;

use cores\SkillLoader;

/**
 * 启动技能包加载器
 *
 * 框架 AppInit 事件监听器。
 * 注：ModuleLoader 已废弃（modules/ 目录迁移到 mpp/ + skills/），仅保留 SkillLoader。
 */
class SkillBoot
{
    public function handle(): void
    {
        SkillLoader::boot();
    }
}
