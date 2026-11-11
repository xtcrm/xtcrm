import request from '@/utils/request'

// ==================== 分组 ====================
export function getGroups(target_type) {
  return request({ url: '/crm.tag/groupLists', method: 'get', params: { target_type } })
}
export function addGroup(data) {
  return request({ url: '/crm.tag/groupAdd', method: 'post', data: data })
}
export function editGroup(id, data) {
  return request({ url: '/crm.tag/groupEdit', method: 'post', data: { id, ...data } })
}
export function deleteGroup(id) {
  return request({ url: '/crm.tag/groupDelete', method: 'post', data: { id } })
}

// ==================== 标签 ====================
export function getTags(params) {
  return request({ url: '/crm.tag/lists', method: 'get', params })
}
export function addTag(data) {
  return request({ url: '/crm.tag/add', method: 'post', data: data })
}
export function editTag(id, data) {
  return request({ url: '/crm.tag/edit', method: 'post', data: { id, ...data } })
}
export function deleteTag(id) {
  return request({ url: '/crm.tag/delete', method: 'post', data: { id } })
}

// ==================== 分组树（下拉选择器用） ====================
export function getAllByTarget(target_type) {
  return request({ url: '/crm.tag/allByTarget', method: 'get', params: { target_type } })
}

// ==================== 客户标签 ====================
export function getCustomerTags(customer_id) {
  return request({ url: '/crm.tag/getCustomerTags', method: 'get', params: { customer_id } })
}
export function setCustomerTags(customer_id, names) {
  return request({ url: '/crm.tag/setCustomerTags', method: 'post', data: { customer_id, names } })
}

// ==================== 联系人标签 ====================
export function getContactTags(contact_id) {
  return request({ url: '/crm.tag/getContactTags', method: 'get', params: { contact_id } })
}
export function setContactTags(contact_id, names) {
  return request({ url: '/crm.tag/setContactTags', method: 'post', data: { contact_id, names } })
}
