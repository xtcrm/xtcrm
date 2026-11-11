import request from '@/utils/request'

export function getUnread() {
  return request({ url: '/crm.notification/unread', method: 'get' })
}

export function markRead(id) {
  return request({ url: '/crm.notification/markRead', method: 'post', data: { id } })
}

export function markAllRead() {
  return request({ url: '/crm.notification/markAllRead', method: 'post' })
}
