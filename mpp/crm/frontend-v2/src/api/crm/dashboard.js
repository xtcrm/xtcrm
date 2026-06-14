import request from '@/utils/request'

export function getIndex() {
  return request({ url: '/crm.dashboard/index', method: 'get' })
}
