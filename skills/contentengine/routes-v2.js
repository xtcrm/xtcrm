export default [
  {
    path: '/crm/content/create',
    name: 'content-create',
    component: () => import('@skills/contentengine/views-v2/Create.vue'),
    meta: { title: '创作工坊', keepAlive: false, permission: ['/crm/content/create'] },
  },
  {
    path: '/crm/content/config',
    name: 'content-config',
    component: () => import('@skills/contentengine/views-v2/Config.vue'),
    meta: { title: '创作配置', keepAlive: false, permission: ['/crm/content/config'] },
  },
  {
    path: '/crm/content/profile',
    name: 'content-profile',
    component: () => import('@skills/contentengine/views-v2/Profile.vue'),
    meta: { title: '公司档案', keepAlive: false, permission: ['/crm/content/profile'] },
  },
  {
    path: '/crm/content/keywords',
    name: 'content-keywords',
    component: () => import('@skills/contentengine/views-v2/Keywords.vue'),
    meta: { title: '关键词管理', keepAlive: false, permission: ['/crm/content/keywords'] },
  },
  {
    path: '/crm/content/solutions',
    name: 'content-solutions',
    component: () => import('@skills/contentengine/views-v2/Solutions.vue'),
    meta: { title: '解决方案库', keepAlive: false, permission: ['/crm/content/solutions'] },
  },
  {
    path: '/crm/content/styles',
    name: 'content-styles',
    component: () => import('@skills/contentengine/views-v2/Styles.vue'),
    meta: { title: '写作风格', keepAlive: false, permission: ['/crm/content/styles'] },
  },
  {
    path: '/crm/content/agents',
    name: 'content-agents',
    component: () => import('@skills/contentengine/views-v2/Agents.vue'),
    meta: { title: '创作Agent', keepAlive: false, permission: ['/crm/content/agents'] },
  },
  {
    path: '/crm/content/calendar',
    name: 'content-calendar',
    component: () => import('@skills/contentengine/views-v2/Calendar.vue'),
    meta: { title: '内容日历', keepAlive: false, permission: ['/crm/content/calendar'] },
  },
  {
    path: '/crm/content/analytics',
    name: 'content-analytics',
    component: () => import('@skills/contentengine/views-v2/Analytics.vue'),
    meta: { title: '内容效果', keepAlive: false, permission: ['/crm/content/analytics'] },
  },
]
