import request from '@/utils/request'

export function getList() {
  return request({ url: '/crm.demandRule/lists', method: 'get' })
}

export function aiGenerate(description) {
  return request({ url: '/crm.demandRule/aiGenerate', method: 'post', data: { description } })
}

export function add(data) {
  return request({ url: '/crm.demandRule/add', method: 'post', data })
}

export function edit(data) {
  return request({ url: '/crm.demandRule/edit', method: 'post', data })
}

export function deleteRule(id) {
  return request({ url: '/crm.demandRule/delete', method: 'post', data: { id } })
}

export function setStatus(id, status) {
  return request({ url: '/crm.demandRule/setStatus', method: 'post', data: { id, status } })
}
