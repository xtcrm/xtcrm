export default [
  {
    path: '/crm/quotation/cn-preview',
    name: 'quote-cn-preview',
    component: () => import('@skills/quotecn/views-v2/QuoteCnPreview.vue'),
    meta: { title: '中文报价预览', keepAlive: false, permission: ['/crm/quotation/cn-preview'] },
    hidden: true,
  },
]
