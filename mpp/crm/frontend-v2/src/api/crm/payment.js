import request from '@/utils/request'

export function getList(params) {
  return request({ url: '/crm.payment/lists', method: 'get', params })
}

export function add(data) {
  return request({ url: '/crm.payment/add', method: 'post', data })
}

export function deleteById(id) {
  return request({ url: '/crm.payment/delete', method: 'post', data: { id } })
}
