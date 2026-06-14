import request from '@/utils/request'

export function getList(customerId) {
  return request({ url: '/crm.collaborator/lists', method: 'get', params: { customer_id: customerId } })
}

export function add(data) {
  return request({ url: '/crm.collaborator/add', method: 'post', data })
}

export function remove(id) {
  return request({ url: '/crm.collaborator/delete', method: 'post', data: { id } })
}
