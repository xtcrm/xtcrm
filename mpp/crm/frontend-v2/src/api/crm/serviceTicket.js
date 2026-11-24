import request from '@/utils/request'

export function getList(params) {
  return request({ url: '/crm.serviceTicket/lists', method: 'get', params })
}

export function getDetail(id) {
  return request({ url: '/crm.serviceTicket/detail', method: 'get', params: { id } })
}

export function add(data) {
  return request({ url: '/crm.serviceTicket/add', method: 'post', data })
}

export function edit(data) {
  return request({ url: '/crm.serviceTicket/edit', method: 'post', data })
}

export function assign(data) {
  return request({ url: '/crm.serviceTicket/assign', method: 'post', data })
}

export function transfer(data) {
  return request({ url: '/crm.serviceTicket/transfer', method: 'post', data })
}

export function flowTemplates() {
  return request({ url: '/crm.serviceTicket/flowTemplates', method: 'get' })
}

export function startFlow(data) {
  return request({ url: '/crm.serviceTicket/startFlow', method: 'post', data })
}

export function cancelFlow(data) {
  return request({ url: '/crm.serviceTicket/cancelFlow', method: 'post', data })
}

export function changeStatus(data) {
  return request({ url: '/crm.serviceTicket/changeStatus', method: 'post', data })
}

export function rate(data) {
  return request({ url: '/crm.serviceTicket/rate', method: 'post', data })
}

export function comment(data) {
  return request({ url: '/crm.serviceTicket/comment', method: 'post', data })
}

export function deleteById(id) {
  return request({ url: '/crm.serviceTicket/delete', method: 'post', data: { id } })
}
