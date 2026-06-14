import { createRouter, createWebHashHistory } from 'vue-router'

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
  history: createWebHashHistory('/crm/'),
  routes: [...constantRoutes, ...asyncRoutes, {
    path: '/:pathMatch(.*)*',
    name: '404',
    component: () => import('@/views/exception/404.vue'),
  }],
})

export default router
export { constantRoutes }
