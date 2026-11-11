---
name: invoice
version: 1.1.0
description: 票无忧 — 小微企业票税防火墙。扫码识票、自动归档、税负预警，专治"缺票"和"稽查焦虑"
metadata:
  skill:
    type: business
    tables:
      - invoice_company
      - invoice_company_member
      - invoice_record
    channels:
      - web
      - wechat
---

# 票无忧

> 不做账，只管风险。金蝶用友做"财务管控"，票无忧做"税务避险"。

## 功能

1. **扫码识票**：微信小程序扫发票二维码 → 解析结构化数据 → 自动入库（准确率 100%，< 1 秒）
2. **验真去重**：同票号自动去重，对接国税查验平台验真
3. **自动归档**：进项/销项自动分类，按月/公司/类型筛选
4. **税负仪表盘**：进销项实时对比 + 税负率计算 + 6 个月趋势 + 年度累计
5. **预警推送**：税负率低于安全线 → 微信提醒 "本月票不够了"
6. **申报就绪**：按月导出申报数据表，会计直接可用

## 状态机（保留旧流程兼容）

```
扫码直入(approved)             待审核(0) → 审核通过(1) → 已开票(2)
                                            ↓              ↓
                                         已驳回(4)       已作废(3)
```

## 菜单（CRM admin）

- 应用 → 票无忧 → 税负仪表盘 / 发票管理 / 开票公司 / 短信配置 / 预警配置 / OCR 配置 / 会员列表
- 菜单ID: 21000-21010

## 路由

- PC: `s=/platform/invoice.{controller}/{action}`
- API: `s=/api/invoice.{controller}/{action}`

## 新增接口（Phase 1）

| 端 | 路由 | 说明 |
|----|------|------|
| API | `/api/invoice/qrcode` | 扫码识票入库 |
| API | `/api/invoice/dashboard` | 仪表盘数据 |
| API | `/api/invoice/exportReport` | 申报导出 |
| PC | `/invoice.invoice/dashboard` | PC 仪表盘 |
| PC | `/invoice.config/alertConfig` | 预警配置读 |
| PC | `/invoice.config/saveAlertConfig` | 预警配置写 |
