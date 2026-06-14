import request from '@/utils/request'

export function getInsights() {
  return request({ url: '/crm.insight/list', method: 'get' })
}

export function dismissInsight(id, reason) {
  return request({ url: '/crm.insight/dismiss', method: 'post', data: { id, reason } })
}

export function runInsightScan() {
  return request({ url: '/crm.insight/runNow', method: 'post' })
}
