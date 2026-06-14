import request from '@/utils/request'

export function getSettings() { return request({ url: '/crm.setting/detail', method: 'get' }) }
export function saveSettings(data) { return request({ url: '/crm.setting/save', method: 'post', data: { form: data } }) }
export function testAi() { return request({ url: '/crm.setting/testAi', method: 'post' }) }
