import request from '@/utils/request'

export function getList(params) {
  return request({ url: '/crm.customField/lists', method: 'get', params })
}

export function getActive(entity = 'customer') {
  return request({ url: '/crm.customField/active', method: 'get', params: { entity } })
}

export function add(data) {
  return request({ url: '/crm.customField/add', method: 'post', data })
}

export function edit(data) {
  return request({ url: '/crm.customField/edit', method: 'post', data })
}

export function deleteById(id) {
  return request({ url: '/crm.customField/delete', method: 'post', data: { id } })
}

export function setStatus(id, status) {
  return request({ url: '/crm.customField/setStatus', method: 'post', data: { id, status } })
}
