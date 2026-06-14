import request from '@/utils/request'

export function agentChat(message) {
  return request({ url: '/crm.agent/chat', method: 'post', data: { message } })
}
