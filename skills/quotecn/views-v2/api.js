import request from '@/utils/request'

/** 获取报价预览 HTML */
export function getPreview(id) { return request({ url: '/crm.quotation_cn/preview', method: 'get', params: { id } }) }

/** 导出 PDF */
export function exportPdf(id) { return request({ url: '/crm.quotation_cn/exportPdf', method: 'get', params: { id } }) }

/** 导出 Word */
export function exportWord(id) { return request({ url: '/crm.quotation_cn/exportWord', method: 'get', params: { id } }) }

/** 导出 Excel */
export function exportExcel(id) { return request({ url: '/crm.quotation_cn/exportExcel', method: 'get', params: { id } }) }

/** 获取公司模板配置 */
export function getTemplate() { return request({ url: '/crm.quotation_cn/getTemplate', method: 'get' }) }

/** 保存公司模板配置 */
export function saveTemplate(data) { return request({ url: '/crm.quotation_cn/saveTemplate', method: 'post', data }) }
