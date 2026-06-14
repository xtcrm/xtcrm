import request from '@/utils/request'

export function list(params) {
  return request({ url: '/files/list', method: 'get', params })
}

export function deleted(data) {
  return request({ url: '/files/delete', method: 'post', data })
}

export function uploadImage(formData) {
  return request({ url: '/upload/image', method: 'post', data: formData })
}
