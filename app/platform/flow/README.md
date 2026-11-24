# 平台流程引擎（app/platform/flow）

平台级审批流底座。**引擎不认业务**：只存 `biz_type` + `biz_id`，业务状态由各模块的 Hook 回写。

## 目录

```
app/platform/controller/flow/   Controller（Template / Task / Instance / Base）
app/platform/flow/
├── FlowBizInterface.php        业务接入契约
├── FlowRegistry.php            业务注册表（读 config/flow.php）
├── FlowException.php
├── model/                      Template / Node / Edge / Instance / Token / Task / Log
├── service/
│   ├── FlowEngine.php          启动、推进、会签/或签/比例/顺序、网关、驳回、转交、加签、终止
│   ├── TemplateService.php     模板 CRUD、版本、发布、归档、默认
│   ├── TemplateValidator.php   发布前校验
│   ├── ConditionEvaluator.php  结构化条件求值
│   ├── OrgResolver.php         角色/部门/用户/字段/发起人/上级解析
│   ├── TaskService.php         待办与流程步骤
│   ├── InstanceService.php     实例查询与终止
│   └── NotificationService.php 站内通知
├── timer/Sla.php               临期提醒 + 超时升级（每 5 分钟）
├── views-v2/                   Vue 3 页面（CRM v2 通过 @platform 引用）
└── 建表/菜单：app/platform/migrations/003_*.sql
```

## 接入一个新业务（4 步）

1. 实现 `app\platform\flow\FlowBizInterface`：
   - `snapshot()` 业务摘要（标题、金额、业务状态）
   - `detailUrl()` 详情页地址
   - `variables()` 网关条件变量
   - `onInstanceStart / onNodeEnter / onNodeComplete / onInstanceEnd`
2. 在 `config/flow.php` 的 `biz_types` 加一段：`name`、`hook`、`detail_route`、`title_field`、`variables`。
3. 首次启动前，业务方可用 `template_seeder` 生成默认模板（可选）。
4. 后台「流程中心 → 流程模板」拖拽设计、校验、发布、设为默认。

业务提交时：

```php
(new \app\platform\flow\service\FlowEngine())->start(
    'your_biz_type', $bizId, $storeId, $actor, '', [], false
);
```

业务详情页嵌入：

```vue
<FlowSteps biz-type="your_biz_type" :biz-id="id" />
```

**不需要改引擎、表结构、设计器。**

## 执行语义

- 节点类型：`start` / `task` / `approval` / `sign` / `payment` / `gateway` / `end`
- 网关：`exclusive`（排他，需默认边）/ `parallel`（并行）/ `inclusive`（包容）
- 完成规则：`any` 或签 / `all` 会签 / `ratio` 比例 / `sequential` 顺序
- 指派：角色（ID 或运行时角色名）、指定人、部门（含子部门）、业务字段、发起人、上级
- 兜底：节点可配 `config.fallback_rules`，主规则无人时依次尝试；仍无人则回落发起人，不会产生死单
- 驳回：`to_start` / `back_to` / `back_prev` / `terminate`
- 并行用 `flow_token` 表达，合并网关 `join_mode=all|any`
- 实例锁在启动时的 `template_version`，发布新版本不影响在途单

## 权限

- 菜单：`/app/flow`、`/app/flow/task`、`/app/flow/template`、`/app/flow/instance`
- 动作：`/flow.template/*`、`/flow.task/*`、`/flow.instance/*`
- 待办详情仅本人/发起人/已完成人/超管可看；终止仅发起人/超管
- `meta` 与 `steps` 对已登录用户开放（供设计器和业务详情使用）
- 非超管角色需在「角色管理」勾选「流程中心」菜单及动作权限，否则看不到待办页；超管默认全开

## 定时任务

`app/event.php` 已注册 `FlowSla`，由 `app/timer/controller/Store.php` 按租户触发：

```bash
php think timer start -i 300
```

扫描 `flow_task.remind_at` / `due_at`，发站内通知并按节点 `escalate_to`（未配置则部门负责人）升级。

## 测试

- `runtime/flow_selftest.php`：并行/条件/排他默认分支
- `runtime/flow_selftest2.php`：会签/或签/比例/顺序/驳回/转交/加签/租户隔离 19 项
- `runtime/fiscal_flow_selftest.php`：财税业务单端到端（事务内回滚）

自测脚本在 `runtime/`，不入库。
