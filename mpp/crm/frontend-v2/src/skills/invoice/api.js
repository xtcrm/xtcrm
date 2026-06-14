import request from '@/utils/request'

// 发票
export function getInvoiceList(params) { return request({ url: '/invoice.invoice/lists', method: 'get', params }) }
export function getInvoiceDetail(id) { return request({ url: '/invoice.invoice/detail', method: 'get', params: { id } }) }
export function deleteById(id) { return request({ url: '/invoice.invoice/delete', method: 'post', data: { id } }) }
export function auditInvoice(data) { return request({ url: '/invoice.invoice/audit', method: 'post', data }) }
export function completeInvoice(data) { return request({ url: '/invoice.invoice/complete', method: 'post', data }) }
export function voidInvoice(id) { return request({ url: '/invoice.invoice/void', method: 'post', data: { id } }) }
export function notifyInvoice(id) { return request({ url: '/invoice.invoice/notify', method: 'post', data: { id } }) }

// 公司
export function getCompanyList(params) { return request({ url: '/invoice.company/lists', method: 'get', params }) }
export function getCompanyDetail(id) { return request({ url: '/invoice.company/detail', method: 'get', params: { id } }) }
export function addCompany(data) { return request({ url: '/invoice.company/add', method: 'post', data }) }
export function editCompany(data) { return request({ url: '/invoice.company/edit', method: 'post', data }) }
export function toggleCompanyStatus(id) { return request({ url: '/invoice.company/toggleStatus', method: 'post', data: { id } }) }
export function bindMember(data) { return request({ url: '/invoice.company/bindMember', method: 'post', data }) }
export function unbindMember(data) { return request({ url: '/invoice.company/unbindMember', method: 'post', data }) }

// 短信
export function getSmsTemplates() { return request({ url: '/invoice.config/smsTemplates', method: 'get' }) }
export function saveSmsTemplates(data) { return request({ url: '/invoice.config/saveSmsTemplates', method: 'post', data }) }
