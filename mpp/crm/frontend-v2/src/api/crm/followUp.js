import request from '@/utils/request'

export function getList(customerId) {
  return request({ url: '/crm.followUp/lists', method: 'get', params: { customer_id: customerId } })
}

export function getTodayVisits(params) {
  return request({ url: '/crm.followUp/todayVisits', method: 'get', params })
}

export function getWeekVisits(params) {
  return request({ url: '/crm.followUp/weekVisits', method: 'get', params })
}

export function getCalendarEvents(params) {
  return request({ url: '/crm.followUp/calendarEvents', method: 'get', params })
}

export function getGreeting(data) {
  return request({ url: '/crm.followUp/greeting', method: 'post', data })
}

export function add(data) {
  return request({ url: '/crm.followUp/add', method: 'post', data })
}

export function edit(data) {
  return request({ url: '/crm.followUp/edit', method: 'post', data })
}

export function deleteById(id) {
  return request({ url: '/crm.followUp/delete', method: 'post', data: { id } })
}
