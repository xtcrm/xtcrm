import request from '@/utils/request'

export function getList(p) { return request({ url: '/crm.lead/lists', method: 'get', params: p }) }
export function getDetail(id) { return request({ url: '/crm.lead/detail', method: 'get', params: { id } }) }
export function add(d) { return request({ url: '/crm.lead/add', method: 'post', data: d }) }
export function edit(d) { return request({ url: '/crm.lead/edit', method: 'post', data: d }) }
export function deleteById(id) { return request({ url: '/crm.lead/delete', method: 'post', data: { id } }) }
export function convert(id) { return request({ url: '/crm.lead/convert', method: 'post', data: { id } }) }
