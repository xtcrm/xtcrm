# CRM 模块数据库迁移

命名约定：`{序号}_{版本号}_{描述}.sql`

ModuleLoader 启动时按序号顺序自动执行未跑过的 SQL，执行记录写入 `yoshop_skill_migration` 表。
