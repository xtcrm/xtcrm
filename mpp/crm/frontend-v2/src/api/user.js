import request from '@/utils/request'

export function list(params) {
  return request({ url: '/crm.admin.user/list', method: 'get', params })
}

export function add(data) {
  return request({ url: '/crm.admin.user/add', method: 'post', data: { form: data } })
}

export function edit(userId, data) {
  return request({ url: '/crm.admin.user/edit', method: 'post', data: { userId, form: data } })
}

export function deleted(userId) {
  return request({ url: '/crm.admin.user/delete', method: 'post', data: { userId } })
}

export function renew(data) {
  return request({ url: '/crm.admin.user/renew', method: 'post', data })
}
