import request from '@/utils/request'

export function getOverview() {
  return request({ url: '/crm.report/overview', method: 'get' })
}

export function getRevenueTrend() {
  return request({ url: '/crm.report/revenueTrend', method: 'get' })
}

export function getRanking(params) {
  return request({ url: '/crm.report/ranking', method: 'get', params })
}

export function getFunnel() {
  return request({ url: '/crm.report/funnel', method: 'get' })
}

export function getQuotation(params) {
  return request({ url: '/crm.report/quotation', method: 'get', params })
}

export function getRenewal(params) {
  return request({ url: '/crm.report/renewal', method: 'get', params })
}

export function getDelivery() {
  return request({ url: '/crm.report/delivery', method: 'get' })
}

export function getReceivable() {
  return request({ url: '/crm.report/receivable', method: 'get' })
}
