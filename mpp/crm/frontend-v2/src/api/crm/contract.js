import request from '@/utils/request'

export function getList(p) { return request({ url: '/crm.contract/lists', method: 'get', params: p }) }
export function getDetail(id) { return request({ url: '/crm.contract/detail', method: 'get', params: { id } }) }
export function add(d) { return request({ url: '/crm.contract/add', method: 'post', data: d }) }
export function edit(d) { return request({ url: '/crm.contract/edit', method: 'post', data: d }) }
export function deleteById(id) { return request({ url: '/crm.contract/delete', method: 'post', data: { id } }) }
export function changeStatus(id, status) { return request({ url: '/crm.contract/changeStatus', method: 'post', data: { id, status } }) }
