import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { login as loginApi, getUserInfo, getStoreInfo } from '@/api/login'
import { storage } from '@/utils/request'
import { useTabsStore } from '@/stores/tabs'
import { useAiStore } from '@/stores/ai'

export const useUserStore = defineStore('user', () => {
  const token = ref(storage.get('ACCESS_TOKEN') || '')
  const userInfo = ref(null)
  const roles = ref(null)
  const storeInfo = ref(null)

  const isLoggedIn = computed(() => !!token.value)
  const isSuper = computed(() => roles.value?.isSuper || false)
  const permissionList = computed(() => {
    if (!roles.value?.permissions) return []
    return roles.value.permissions.map((p) => p.permissionId)
  })

  async function loginAction(username, password) {
    const res = await loginApi({ username, password })
    if (res.status === 200) {
      token.value = res.data.token
      storage.set('ACCESS_TOKEN', res.data.token)
      return true
    }
    return false
  }

  async function getInfo() {
    const res = await getUserInfo()
    if (res.status === 200) {
      userInfo.value = res.data.userInfo
      roles.value = res.data.roles
      storage.set('USER_INFO', res.data.userInfo)
      storage.set('ROLES', res.data.roles)
    }
  }

  async function getStoreInfoAction() {
    const res = await getStoreInfo()
    if (res.status === 200) {
      storeInfo.value = res.data.storeInfo
    }
  }

  function logout() {
    token.value = ''
    userInfo.value = null
    roles.value = null
    storeInfo.value = null
    storage.remove('ACCESS_TOKEN')
    storage.remove('USER_INFO')
    storage.remove('ROLES')

    // 页签里存着客户名等业务数据，且登录走的是 router.replace（不整页刷新），
    // 不清掉的话换账号登录后，上一个账号的页签会原样留在页签栏里。
    // 放在这里而不是各个登出按钮里，是为了以后新增登出入口不会再漏。
    useTabsStore().reset()
    // AI 面板同理：messages 是对话内容，currentContext 里还有客户名/行业
    useAiStore().reset()
  }

  return {
    token, userInfo, roles, storeInfo,
    isLoggedIn, isSuper, permissionList,
    loginAction, getInfo, getStoreInfoAction, logout,
  }
})
