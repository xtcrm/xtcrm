import request from '@/utils/request'

export function getSettings() { return request({ url: '/crm.setting/detail', method: 'get' }) }
export function saveSettings(data) { return request({ url: '/crm.setting/save', method: 'post', data: { form: data } }) }
export function testAi(data = {}) { return request({ url: '/crm.setting/testAi', method: 'post', data }) }
export function getAiModels(data = {}) { return request({ url: '/crm.setting/aiModels', method: 'post', data }) }
