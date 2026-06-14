export default [
  { path: '/crm/content/create', name: 'content-create', component: () => import('./Create.vue'), meta: { title: '创作工坊', keepAlive: false, permission: ['/crm/content/create'] } },
  { path: '/crm/content/config', name: 'content-config', component: () => import('./Config.vue'), meta: { title: '创作配置', keepAlive: false, permission: ['/crm/content/config'] } },
  { path: '/crm/content/profile', name: 'content-profile', component: () => import('./Profile.vue'), meta: { title: '公司档案', keepAlive: false, permission: ['/crm/content/profile'] } },
  { path: '/crm/content/keywords', name: 'content-keywords', component: () => import('./Keywords.vue'), meta: { title: '关键词管理', keepAlive: false, permission: ['/crm/content/keywords'] } },
  { path: '/crm/content/solutions', name: 'content-solutions', component: () => import('./Solutions.vue'), meta: { title: '解决方案库', keepAlive: false, permission: ['/crm/content/solutions'] } },
  { path: '/crm/content/styles', name: 'content-styles', component: () => import('./Styles.vue'), meta: { title: '写作风格', keepAlive: false, permission: ['/crm/content/styles'] } },
  { path: '/crm/content/agents', name: 'content-agents', component: () => import('./Agents.vue'), meta: { title: '创作Agent', keepAlive: false, permission: ['/crm/content/agents'] } },
  { path: '/crm/content/calendar', name: 'content-calendar', component: () => import('./Calendar.vue'), meta: { title: '内容日历', keepAlive: false, permission: ['/crm/content/calendar'] } },
  { path: '/crm/content/analytics', name: 'content-analytics', component: () => import('./Analytics.vue'), meta: { title: '内容效果', keepAlive: false, permission: ['/crm/content/analytics'] } },
]
