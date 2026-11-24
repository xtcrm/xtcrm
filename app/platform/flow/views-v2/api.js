import request from '@/utils/request'

// ========== 模板 ==========

export function getTemplateList(params) {
  return request({ url: '/flow.template/lists', method: 'get', params })
}

export function getTemplateDetail(id) {
  return request({ url: '/flow.template/detail', method: 'get', params: { id } })
}

export function createTemplate(data) {
  return request({ url: '/flow.template/create', method: 'post', data })
}

export function saveTemplateDraft(data) {
  return request({ url: '/flow.template/saveDraft', method: 'post', data })
}

export function validateTemplate(id) {
  return request({ url: '/flow.template/check', method: 'get', params: { id } })
}

export function checkTemplateAssignment(id) {
  return request({ url: '/flow.template/assignmentCheck', method: 'get', params: { id } })
}

export function publishTemplate(data) {
  return request({ url: '/flow.template/publish', method: 'post', data })
}

export function archiveTemplate(id) {
  return request({ url: '/flow.template/archive', method: 'post', data: { id } })
}

export function setDefaultTemplate(id) {
  return request({ url: '/flow.template/setDefault', method: 'post', data: { id } })
}

export function copyTemplateVersion(id) {
  return request({ url: '/flow.template/copyVersion', method: 'post', data: { id } })
}

export function removeTemplate(id) {
  return request({ url: '/flow.template/remove', method: 'post', data: { id } })
}

export function getTemplatePresets(bizType) {
  return request({ url: '/flow.template/presets', method: 'get', params: { biz_type: bizType } })
}

export function getTemplateMeta(params) {
  return request({ url: '/flow.template/meta', method: 'get', params })
}

// ========== 待办 ==========

export function getTaskList(params) {
  return request({ url: '/flow.task/lists', method: 'get', params })
}

export function getTaskCount() {
  return request({ url: '/flow.task/count', method: 'get' })
}

export function getTaskDetail(id) {
  return request({ url: '/flow.task/detail', method: 'get', params: { id } })
}

export function completeTask(data) {
  return request({ url: '/flow.task/complete', method: 'post', data })
}

export function transferTask(data) {
  return request({ url: '/flow.task/transfer', method: 'post', data })
}

export function addSignTask(data) {
  return request({ url: '/flow.task/addSign', method: 'post', data })
}

export function getFlowSteps(bizType, bizId) {
  return request({ url: '/flow.task/steps', method: 'get', params: { biz_type: bizType, biz_id: bizId } })
}

// ========== 实例 ==========

export function getInstanceList(params) {
  return request({ url: '/flow.instance/lists', method: 'get', params })
}

export function getInstanceDetail(id) {
  return request({ url: '/flow.instance/detail', method: 'get', params: { id } })
}

export function startFlow(data) {
  return request({ url: '/flow.instance/start', method: 'post', data })
}

export function terminateFlow(data) {
  return request({ url: '/flow.instance/terminate', method: 'post', data })
}
