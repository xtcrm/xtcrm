import request from '@/utils/request'

export function login(data) {
  return request({
    url: '/crm.passport/login',
    method: 'post',
    data,
  })
}

export function getUserInfo() {
  return request({
    url: '/crm.admin.user/info',
    method: 'get',
  })
}

export function getStoreInfo() {
  return request({
    url: '/crm.admin.user/storeInfo',
    method: 'get',
  })
}
