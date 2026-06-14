import request from '@/utils/request'

export function acceptSuggestion(id) {
  return request({ url: '/crm.suggestion/accept', method: 'post', data: { id } })
}

export function rejectSuggestion(id) {
  return request({ url: '/crm.suggestion/reject', method: 'post', data: { id } })
}

export function getSuggestionStats() {
  return request({ url: '/crm.suggestion/stats', method: 'get' })
}
