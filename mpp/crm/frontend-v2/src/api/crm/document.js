import request from '@/utils/request'

export function list(params) {
  return request({ url: '/crm.document/lists', method: 'get', params })
}

export function upload(formData) {
  return request({ url: '/crm.document/upload', method: 'post', data: formData, headers: { 'Content-Type': 'multipart/form-data' } })
}

export function edit(data) {
  return request({ url: '/crm.document/edit', method: 'post', data })
}

export function deleteById(id) {
  return request({ url: '/crm.document/delete', method: 'post', data: { id } })
}
