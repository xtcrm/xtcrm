import request from '@/utils/request'

export function buildApp(dsl, appId = '') {
  return request({ url: '/crm.appBuilder/build', method: 'post', data: { dsl, app_id: appId } })
}
export function listApps() {
  return request({ url: '/crm.appBuilder/listApps', method: 'get' })
}
export function getDicts(appId) {
  return request({ url: '/crm.appBuilder/dicts', method: 'get', params: { app_id: appId } })
}
export function addDict(appId, dictKey, label) {
  return request({ url: '/crm.appBuilder/dictAdd', method: 'post', data: { app_id: appId, dict_key: dictKey, label } })
}
export function delDict(id) {
  return request({ url: '/crm.appBuilder/dictDel', method: 'post', data: { id } })
}
export function listConfigs() {
  return request({ url: '/crm.appBuilder/listConfigs', method: 'get' })
}
export function saveConfig(appId, pageKey, content) {
  return request({ url: '/crm.appBuilder/saveConfig', method: 'post', data: { app_id: appId, page_key: pageKey, content } })
}
