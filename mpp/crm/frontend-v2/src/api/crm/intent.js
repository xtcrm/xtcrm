import request from '@/utils/request'

export function getList(params) {
  return request({ url: '/crm.intent/lists', method: 'get', params })
}

export function getDetail(id) {
  return request({ url: '/crm.intent/detail', method: 'get', params: { id } })
}

export function add(data) {
  return request({ url: '/crm.intent/add', method: 'post', data })
}

export function edit(data) {
  return request({ url: '/crm.intent/edit', method: 'post', data })
}

export function assign(id, assigneeUserId) {
  return request({ url: '/crm.intent/assign', method: 'post', data: { id, assignee_user_id: assigneeUserId } })
}

export function hold(id, reason, heldUntil) {
  return request({ url: '/crm.intent/hold', method: 'post', data: { id, reason, held_until: heldUntil } })
}

export function resume(id) {
  return request({ url: '/crm.intent/resume', method: 'post', data: { id } })
}

export function close(id, reason) {
  return request({ url: '/crm.intent/close', method: 'post', data: { id, reason } })
}

export function confirm(id) {
  return request({ url: '/crm.intent/confirm', method: 'post', data: { id } })
}
