import request from '@/utils/request'

export function listAll() {
  return request({ url: '/crm.demand/listAll', method: 'get' })
}

export function getCustomerDemands(customerId) {
  return request({ url: '/crm.demand/customerDemands', method: 'get', params: { customer_id: customerId } })
}

export function dismissDemand(id) {
  return request({ url: '/crm.demand/dismiss', method: 'post', data: { id } })
}

export function setDemandStatus(id, status) {
  return request({ url: '/crm.demand/setStatus', method: 'post', data: { id, status } })
}
