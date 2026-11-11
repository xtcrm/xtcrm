import request from '@/utils/request'

export function getList() {
  return request({ url: '/crm.catalog/lists', method: 'get' })
}

export function add(data) {
  return request({ url: '/crm.catalog/add', method: 'post', data })
}

export function edit(data) {
  return request({ url: '/crm.catalog/edit', method: 'post', data })
}

export function deleteById(id) {
  return request({ url: '/crm.catalog/delete', method: 'post', data: { id } })
}

export function setStatus(id, status) {
  return request({ url: '/crm.catalog/setStatus', method: 'post', data: { id, status } })
}
