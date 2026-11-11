import request from '@/utils/request'

export function list(params) {
  return request({ url: '/files/list', method: 'get', params })
}

export function deleted(data) {
  return request({ url: '/files/delete', method: 'post', data })
}

export function uploadImage(formData) {
  return request({ url: '/upload/image', method: 'post', data: formData, headers: { 'Content-Type': 'multipart/form-data' } })
}

/** 上传文件（文档等，不限图片）。配套组件：@/components/SelectFile */
export function uploadFile(formData) {
  return request({ url: '/upload/file', method: 'post', data: formData, headers: { 'Content-Type': 'multipart/form-data' } })
}
