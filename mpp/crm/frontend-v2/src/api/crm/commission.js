import request from '@/utils/request'

export function getRecords(params) {
  return request({ url: '/crm.commission/lists', method: 'get', params })
}

export function getSummary(params) {
  return request({ url: '/crm.commission/summary', method: 'get', params })
}

export function getRatios() {
  return request({ url: '/crm.commission/ratios', method: 'get' })
}

export function saveRatios(data) {
  return request({ url: '/crm.commission/saveRule', method: 'post', data })
}

export function getOverrides(params) {
  return request({ url: '/crm.commission/overrides', method: 'get', params })
}

export function applyOverride(data) {
  return request({ url: '/crm.commission/applyOverride', method: 'post', data })
}

export function approveOverride(data) {
  return request({ url: '/crm.commission/approveOverride', method: 'post', data })
}
