// 平台流程引擎路由
//
// 拆成两组：
//   flowWorkbenchRoutes → 工作台：我的待办、我的流程（日常处理）
//   flowAdminRoutes     → 应用：流程模板、设计器（配置）
//
// 宿主 router 分别 spread 到 /index 和 /app 的 children 里。

const TaskPage = () => import('./task/index.vue')
const InstancePage = () => import('./instance/index.vue')
const TemplatePage = () => import('./template/index.vue')
const DesignerPage = () => import('./designer/index.vue')

export const flowWorkbenchRoutes = [
  {
    path: '/index/flow/task',
    name: 'flow-task',
    component: TaskPage,
    meta: { title: '我的待办' },
  },
  {
    path: '/index/flow/instance',
    name: 'flow-instance',
    component: InstancePage,
    meta: { title: '我的流程' },
  },
]

export const flowAdminRoutes = [
  {
    path: '/app/flow',
    name: 'app-flow',
    component: () => import('@/router/RouteView.vue'),
    redirect: '/app/flow/template',
    meta: { title: '流程中心' },
    children: [
      {
        path: '/app/flow/template',
        name: 'flow-template',
        component: TemplatePage,
        meta: { title: '流程模板', permission: ['/app/flow/template'] },
      },
      {
        path: '/app/flow/designer',
        name: 'flow-designer',
        component: DesignerPage,
        meta: { title: '流程设计器', hidden: true, permission: ['/app/flow/designer'] },
      },
    ],
  },
]

export default [...flowWorkbenchRoutes, ...flowAdminRoutes]
