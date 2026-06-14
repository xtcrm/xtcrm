export default [
  { path: '/crm/quotation/cn-preview', name: 'quote-cn-preview', component: () => import('./QuoteCnPreview.vue'), meta: { title: '中文报价预览', keepAlive: false, permission: ['/crm/quotation/cn-preview', '/crm/quotation_cn/preview'] }, hidden: true },
]
