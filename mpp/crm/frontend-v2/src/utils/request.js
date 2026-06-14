import axios from 'axios'
import { message } from 'ant-design-vue'
import router from '@/router'

const storage = {
  get(key) {
    try { return JSON.parse(localStorage.getItem(key) || 'null') } catch { return null }
  },
  set(key, val) {
    localStorage.setItem(key, JSON.stringify(val))
  },
  remove(key) {
    localStorage.removeItem(key)
  },
}

const service = axios.create({
  baseURL: window.publicConfig?.BASE_API || '',
  timeout: 60000,
})

// 请求拦截器
service.interceptors.request.use(
  (config) => {
    const token = storage.get('ACCESS_TOKEN')
    if (token) {
      config.headers['Access-Token'] = token
    }
    return config
  },
  (error) => Promise.reject(error),
)

// 响应拦截器
service.interceptors.response.use(
  (response) => {
    const result = response.data
    if (result.status === 500) {
      message.error(result.message || '请求失败')
      return Promise.reject(result)
    }
    if (result.status === 401) {
      storage.remove('ACCESS_TOKEN')
      storage.remove('USER_INFO')
      storage.remove('ROLES')
      message.warning('登录已过期，请重新登录')
      setTimeout(() => {
        router.replace('/passport/login')
      }, 1500)
      return Promise.reject(result)
    }
    return result
  },
  (error) => {
    message.error('网络异常，请稍后重试')
    return Promise.reject(error)
  },
)

export { storage }
export default service
