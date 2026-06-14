import request from '@/utils/request'

export function getTreeList(params) {
  return request({ url: '/manage.department/tree', method: 'get', params })
}

export function add(data) {
  return request({ url: '/manage.department/add', method: 'post', data })
}

export function edit(id, data) {
  return request({ url: `/manage.department/edit/${id}`, method: 'post', data: { id, ...data } })
}

export function deleteById(id) {
  return request({ url: '/manage.department/delete', method: 'post', data: { id } })
}

export function setManager(id, data) {
  return request({ url: '/manage.department/setManager', method: 'post', data: { id, ...data } })
}

export function getDepartmentUsers(id) {
  return request({ url: '/manage.department/getUsers', method: 'get', params: { id } })
}

export function assignUser(data) {
  return request({ url: '/manage.department/assignUser', method: 'post', data })
}

export function removeUser(data) {
  return request({ url: '/manage.department/removeUser', method: 'post', data })
}

export function syncToWxwork(id) {
  return request({ url: '/manage.department/syncToWxwork', method: 'post', data: { id } })
}

export function syncFromWxwork() {
  return request({ url: '/manage.department/syncFromWxwork', method: 'post' })
}

export function getSyncLog(params) {
  return request({ url: '/manage.department/syncLog', method: 'get', params })
}
