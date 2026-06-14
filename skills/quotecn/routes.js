export default [
  {
    path: '/crm/quotation/cn-preview',
    component: () => import('./views/quoteCnPreview'),
    meta: { title: '中文报价预览', keepAlive: false, permission: ['/crm/quotation/cn-preview'] },
    hidden: true
  }
]
