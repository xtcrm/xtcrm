import request from '@/utils/request'

export function getGreetings(params) {
  return request({ url: '/crm.followUp/greetings', method: 'get', params })
}

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

// 自定义日历事件
export function eventAdd(data) { return request({ url: '/crm.followUp/eventAdd', method: 'post', data }) }
export function eventEdit(data) { return request({ url: '/crm.followUp/eventEdit', method: 'post', data }) }
export function eventDelete(id) { return request({ url: '/crm.followUp/eventDelete', method: 'post', data: { id } }) }
