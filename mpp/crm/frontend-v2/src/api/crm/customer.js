import request from '@/utils/request'

export function getList(params) {
  return request({ url: '/crm.customer/lists', method: 'get', params })
}

export function getSelect() {
  return request({ url: '/crm.customer/select', method: 'get' })
}

export function getDetail(id, params = {}) {
  return request({ url: '/crm.customer/detail', method: 'get', params: { id, ...params } })
}

export function getPoolList(params) {
  return request({ url: '/crm.customer/poolLists', method: 'get', params })
}

export function getCollabList(params) {
  return request({ url: '/crm.customer/collabLists', method: 'get', params })
}

export function add(data) {
  return request({ url: '/crm.customer/add', method: 'post', data })
}

export function edit(data) {
  return request({ url: '/crm.customer/edit', method: 'post', data })
}

export function deleteById(id) {
  return request({ url: '/crm.customer/delete', method: 'post', data: { id } })
}

export function claim(id) {
  return request({ url: '/crm.customer/claim', method: 'post', data: { id } })
}

export function release(id) {
  return request({ url: '/crm.customer/release', method: 'post', data: { id } })
}

export function analyze(id) {
  return request({ url: '/crm.customer/analyze', method: 'post', data: { id } })
}

export function portrait(id) {
  return request({ url: '/crm.customer/portrait', method: 'post', data: { id } })
}

export function smartSearch(q) {
  return request({ url: '/crm.customer/smartSearch', method: 'post', data: { q } })
}

export function checkNameDup(name, excludeId = 0) {
  return request({ url: '/crm.customer/checkName', method: 'get', params: { customer_name: name, exclude_id: excludeId } })
}
