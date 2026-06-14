import request from '@/utils/request'

export function getList(params) {
  return request({ url: '/crm.product/lists', method: 'get', params })
}

export function getSelect() {
  return request({ url: '/crm.product/select', method: 'get' })
}

export function getDetail(id) {
  return request({ url: '/crm.product/detail', method: 'get', params: { id } })
}

export function add(data) {
  return request({ url: '/crm.product/add', method: 'post', data })
}

export function edit(data) {
  return request({ url: '/crm.product/edit', method: 'post', data })
}

export function deleteById(id) {
  return request({ url: '/crm.product/delete', method: 'post', data: { id } })
}
