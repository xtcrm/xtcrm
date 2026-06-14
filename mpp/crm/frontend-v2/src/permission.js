import NProgress from 'nprogress'
import 'nprogress/nprogress.css'
import router from '@/router'
import { useUserStore } from '@/stores/user'
import { usePermissionStore } from '@/stores/permission'

NProgress.configure({ showSpinner: false })

router.beforeEach(async (to) => {
  NProgress.start()

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
  console.log('[guard] done ->', to.path)
  NProgress.done()
})
