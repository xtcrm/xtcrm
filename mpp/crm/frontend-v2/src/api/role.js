import request from '@/utils/request'

export function list(params) {
  return request({ url: '/crm.admin.role/list', method: 'get', params })
}

export function add(data) {
  return request({ url: '/crm.admin.role/add', method: 'post', data: { form: data } })
}

export function edit(roleId, data) {
  return request({ url: '/crm.admin.role/edit', method: 'post', data: { roleId, form: data } })
}

export function deleted(roleId) {
  return request({ url: '/crm.admin.role/delete', method: 'post', data: { roleId } })
}
