export default [
  {
    path: '/crm/content/create',
    component: () => import('./views/Create'),
    meta: { title: '创作工坊', keepAlive: false, permission: ['/crm/content/create'] }
  },
  {
    path: '/crm/content/config',
    component: () => import('./views/Config'),
    meta: { title: '创作配置', keepAlive: false, permission: ['/crm/content/config'] }
  },
  {
    path: '/crm/content/profile',
    component: () => import('./views/Profile'),
    meta: { title: '公司档案', keepAlive: false, permission: ['/crm/content/profile'] }
  },
  {
    path: '/crm/content/keywords',
    component: () => import('./views/Keywords'),
    meta: { title: '关键词管理', keepAlive: false, permission: ['/crm/content/keywords'] }
  },
  {
    path: '/crm/content/solutions',
    component: () => import('./views/Solutions'),
    meta: { title: '解决方案库', keepAlive: false, permission: ['/crm/content/solutions'] }
  },
  {
    path: '/crm/content/styles',
    component: () => import('./views/Styles'),
    meta: { title: '写作风格', keepAlive: false, permission: ['/crm/content/styles'] }
  },
  {
    path: '/crm/content/agents',
    component: () => import('./views/Agents'),
    meta: { title: '创作Agent', keepAlive: false, permission: ['/crm/content/agents'] }
  },
  {
    path: '/crm/content/calendar',
    component: () => import('./views/Calendar'),
    meta: { title: '内容日历', keepAlive: false, permission: ['/crm/content/calendar'] }
  },
  {
    path: '/crm/content/analytics',
    component: () => import('./views/Analytics'),
    meta: { title: '内容效果', keepAlive: false, permission: ['/crm/content/analytics'] }
  }
]
