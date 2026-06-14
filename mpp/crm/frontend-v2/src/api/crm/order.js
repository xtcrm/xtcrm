import request from '@/utils/request'

export function getList(p) { return request({ url: '/crm.order/lists', method: 'get', params: p }) }
export function getDetail(id) { return request({ url: '/crm.order/detail', method: 'get', params: { id } }) }
export function add(d) { return request({ url: '/crm.order/add', method: 'post', data: d }) }
export function edit(d) { return request({ url: '/crm.order/edit', method: 'post', data: d }) }
export function deleteById(id) { return request({ url: '/crm.order/delete', method: 'post', data: { id } }) }
export function changeStatus(id, status) { return request({ url: '/crm.order/changeStatus', method: 'post', data: { id, status } }) }
