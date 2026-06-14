import request from '@/utils/request'

export function list(params) {
  return request({ url: '/crm.admin.menu/list', method: 'get', params })
}
