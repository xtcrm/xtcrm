import request from '@/utils/request'

// 手机号查重
export function lookupByMobile(mobile) { return request({ url: '/crm.contact/lookup', method: 'get', params: { mobile } }) }
// 联系人菜单 → 全局列表
export function getList(params) { return request({ url: '/crm.contact/lists', method: 'get', params }) }
// 联系人菜单 → 详情
export function getDetail(id) { return request({ url: '/crm.contact/detail', method: 'get', params: { id } }) }
// 客户下联系人
export function getByCustomer(customerId) { return request({ url: '/crm.contact/listsByCustomer', method: 'get', params: { customer_id: customerId } }) }
// 新增/绑定
export function add(data) { return request({ url: '/crm.contact/add', method: 'post', data }) }
// 编辑
export function edit(data) { return request({ url: '/crm.contact/edit', method: 'post', data }) }
// 解除绑定
export function unbind(contactId, customerId) { return request({ url: '/crm.contact/unbind', method: 'post', data: { contact_id: contactId, customer_id: customerId } }) }
// 删除
export function deleteById(id) { return request({ url: '/crm.contact/delete', method: 'post', data: { id } }) }
