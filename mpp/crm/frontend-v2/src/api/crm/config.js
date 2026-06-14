import request from '@/utils/request'

export function getAll() {
  return request({ url: '/crm.config/all', method: 'get' })
}

export function getList(config_type) {
  return request({ url: '/crm.config/lists', method: 'get', params: { config_type } })
}

export function add(data) {
  return request({ url: '/crm.config/add', method: 'post', data: { form: data } })
}

export function edit(data) {
  return request({ url: '/crm.config/edit', method: 'post', data: { form: data } })
}

export function deleteById(id) {
  return request({ url: '/crm.config/delete', method: 'post', data: { id } })
}

export function initType(config_type) {
  return request({ url: '/crm.config/init', method: 'post', data: { config_type } })
}
