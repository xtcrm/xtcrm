import request from '@/utils/request'

export function getList(params) {
  return request({ url: '/crm.oplog/lists', method: 'get', params })
}
