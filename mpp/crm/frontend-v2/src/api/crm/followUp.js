import request from '@/utils/request'

export function getList(customerId) {
  return request({ url: '/crm.followUp/lists', method: 'get', params: { customer_id: customerId } })
}

export function add(data) {
  return request({ url: '/crm.followUp/add', method: 'post', data })
}

export function edit(data) {
  return request({ url: '/crm.followUp/edit', method: 'post', data })
}

export function deleteById(id) {
  return request({ url: '/crm.followUp/delete', method: 'post', data: { id } })
}
