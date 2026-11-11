// 开源版路由入口（由 sync/opensource/overrides 维护）
// 与私有版的差异：去掉 /app/preview/:appId（AI 应用构建器的预览路由，不在开源范围内）
import { createRouter, createWebHistory } from 'vue-router'

// 所有路由都直接注册，不走动态 addRoute
import { asyncRoutes } from './asyncRoutes'

const constantRoutes = [
  {
    path: '/passport',
    component: () => import('@/layouts/UserLayout.vue'),
    redirect: '/passport/login',
    children: [
      {
        path: 'login',
        name: 'login',
        component: () => import('@/views/passport/Login.vue'),
      },
    ],
  },
]

const router = createRouter({
  history: createWebHistory(),
  routes: [...constantRoutes, ...asyncRoutes, {
    path: '/:pathMatch(.*)*',
    name: '404',
    component: () => import('@/views/exception/404.vue'),
  }],
})

export default router
export { constantRoutes }
