import request from '@/utils/request'

export function list(params) {
  return request({ url: '/files.group/list', method: 'get', params })
}

export function add(data) {
  return request({ url: '/files.group/add', method: 'post', data: { form: data } })
}
