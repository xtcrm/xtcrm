import request from '@/utils/request'

export function getProjection(type) {
  return request({ url: '/crm.projection/get', method: 'get', params: { type } })
}
