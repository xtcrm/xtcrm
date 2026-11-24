import NProgress from 'nprogress'
import 'nprogress/nprogress.css'
import router from '@/router'
import { useUserStore } from '@/stores/user'
import { usePermissionStore } from '@/stores/permission'

NProgress.configure({ showSpinner: false })

let progressTimer = null
let progressStarted = false

// 旧岗位工作台入口已下线，老书签/深链跳到对应新列表，避免 404
const LEGACY_WORKBENCH_REDIRECTS = {
  '/crm/workbench/cs': '/crm/customer/index',
  '/crm/workbench/delivery': '/crm/project/index',
  '/crm/workbench/review': '/crm/project/review',
  // AI 应用构建器已下线
  '/ai': '/index/dashboard',
  '/ai/app-builder': '/index/dashboard',
  '/ai/app-list': '/index/dashboard',
  '/ai/config-manager': '/index/dashboard',
  '/ai/dict-manager': '/index/dashboard',
}

router.beforeEach((to) => {
  const target = LEGACY_WORKBENCH_REDIRECTS[to.path]
  if (target) return { path: target, query: to.query, replace: true }
})

router.beforeEach(async (to) => {
  clearTimeout(progressTimer)
  progressTimer = setTimeout(() => {
    NProgress.start()
    progressStarted = true
  }, 300)

  const userStore = useUserStore()
  const permissionStore = usePermissionStore()

  console.log('[guard]', to.path, '| token:', !!userStore.token, '| roles:', !!userStore.roles)

  // 已登录
  if (userStore.token) {
    // 在登录页：跳首页
    if (to.path === '/passport/login') {
      return '/'
    }

    // 首次：加载信息
    if (!userStore.roles) {
      try {
        await userStore.getInfo()
        await userStore.getStoreInfoAction()
        permissionStore.buildMenus(userStore.roles)
        return to.fullPath  // 重定向触发组件解析
      } catch (e) {
        console.error('[guard] roles failed:', e)
        userStore.logout()
        return '/passport/login'
      }
    }

    // 已加载：放行
    return true
  }

  // 未登录：只放行登录页
  if (to.path === '/passport/login') {
    return true
  }
  return `/passport/login?redirect=${to.path}`
})

router.afterEach((to) => {
  clearTimeout(progressTimer)
  if (progressStarted) {
    NProgress.done()
    progressStarted = false
  }
  console.log('[guard] done ->', to.path)
})
