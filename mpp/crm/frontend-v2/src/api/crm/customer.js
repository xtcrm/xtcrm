import request from '@/utils/request'

export function getList(params) {
  return request({ url: '/crm.customer/lists', method: 'get', params })
}

export function getSelect(keyword) {
  return request({ url: '/crm.customer/select', method: 'get', params: { keyword: keyword || '' } })
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

export function batchUpdate(data) {
  return request({ url: '/crm.customer/batchUpdate', method: 'post', data })
}

export function batchDelete(ids) {
  return request({ url: '/crm.customer/batchDelete', method: 'post', data: { ids } })
}

export function uploadImport(file) {
  const fd = new FormData()
  fd.append('file', file)
  return request({ url: '/crm.customerImport/upload', method: 'post', data: fd, headers: { 'Content-Type': 'multipart/form-data' } })
}

export function confirmImport(rows, mapping, useArchive = false) {
  return request({
    url: '/crm.customerImport/confirm',
    method: 'post',
    data: { rows, mapping, use_archive: useArchive ? 1 : 0 },
  })
}

export function aiParse(text) {
  return request({ url: '/crm.customerImport/aiParse', method: 'post', data: { text } })
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

// ---- 客服责任位 / 待分配池 ----
export function getAssignList(params) {
  return request({ url: '/crm.customer/assignLists', method: 'get', params })
}

export function assignCs(data) {
  return request({ url: '/crm.customer/assignCs', method: 'post', data })
}

export function claimCs(id) {
  return request({ url: '/crm.customer/claimCs', method: 'post', data: { id } })
}

export function setCs(data) {
  return request({ url: '/crm.customer/setCs', method: 'post', data })
}
