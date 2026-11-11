import request from '@/utils/request'

export function getList() {
  return request({ url: '/crm.apiKey/lists', method: 'get' })
}

export function generate(name, scopes = 'read', storeUserId = 0) {
  return request({ url: '/crm.apiKey/generate', method: 'post', data: { name, scopes, store_user_id: storeUserId } })
}

export function deleteKey(id) {
  return request({ url: '/crm.apiKey/delete', method: 'post', data: { id } })
}
