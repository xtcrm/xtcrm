---
name: invoice
version: 1.0.0
description: 代开发票服务 — 小程序用户上传收据/订单/小票，AI 识图预填，后台审核代开票
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

# 代开发票

## 功能

1. **销方公司建档**：小程序用户首次使用时绑定公司信息（名称/税号/银行），管理员后台可审核、绑定多会员
2. **AI 识图提交**：拍照上传收据/订单/小票 → 阿里云 OCR 提取文字 → DeepSeek 结构化 → 预填购方/商品/税率
3. **后台审核**：管理员审核通过/驳回，驳回需填原因
4. **代开发票**：管理员去易开开开票后回传票图/文字，短信通知销方
5. **发票查看与下载**：小程序端查看列表、详情、下载票图/PDF

## 状态机

```
待审核(0) → 审核通过(1) → 已开票(2)
     ↓                      ↓
  已驳回(4)              已作废(3)
```

## 菜单（CRM admin）

- 应用 → 代开发票 → 发票管理 / 开票公司 / 短信配置
- 菜单ID: 21000-21999

## 路由

- PC: `s=/platform/invoice.{controller}/{action}`
- API: `s=/api/invoice.{controller}/{action}`
