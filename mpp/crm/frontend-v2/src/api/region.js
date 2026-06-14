import request from '@/utils/request'

// 获取所有地区
export function all(params) {
  return request({ url: '/region/all', method: 'get', params })
}

// 获取所有地区(树状)
export function tree(params) {
  return request({ url: '/region/tree', method: 'get', params })
}
