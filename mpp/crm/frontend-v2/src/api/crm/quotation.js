import request from '@/utils/request'

export function getList(params) {
  return request({ url: '/crm.quotation/lists', method: 'get', params })
}

export function getDetail(id) {
  return request({ url: '/crm.quotation/detail', method: 'get', params: { id } })
}

export function add(data) {
  return request({ url: '/crm.quotation/add', method: 'post', data })
}

export function edit(data) {
  return request({ url: '/crm.quotation/edit', method: 'post', data })
}

export function deleteById(id) {
  return request({ url: '/crm.quotation/delete', method: 'post', data: { id } })
}

export function changeStatus(id, status) {
  return request({ url: '/crm.quotation/changeStatus', method: 'post', data: { id, status } })
}
