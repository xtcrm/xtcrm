import request from '@/utils/request'

export function getList(customerId) {
  return request({ url: '/crm.contact/lists', method: 'get', params: { customer_id: customerId } })
}

export function getDetail(id) {
  return request({ url: '/crm.contact/detail', method: 'get', params: { id } })
}

export function add(data) {
  return request({ url: '/crm.contact/add', method: 'post', data })
}

export function edit(data) {
  return request({ url: '/crm.contact/edit', method: 'post', data })
}

export function deleteById(id) {
  return request({ url: '/crm.contact/delete', method: 'post', data: { id } })
}
