import request from '@/utils/request'

export function getList(params) { return request({ url: '/crm.knowledge/lists', method: 'get', params }) }
export function getCategories() { return request({ url: '/crm.knowledge/categories', method: 'get' }) }
export function add(data) { return request({ url: '/crm.knowledge/add', method: 'post', data }) }
export function edit(data) { return request({ url: '/crm.knowledge/edit', method: 'post', data }) }
export function deleteById(id) { return request({ url: '/crm.knowledge/delete', method: 'post', data: { id } }) }
