import request from '@/utils/request'

export function getList(params) {
  return request({ url: '/crm.project/lists', method: 'get', params })
}

export function getDetail(id) {
  return request({ url: '/crm.project/detail', method: 'get', params: { id } })
}

export function add(data) {
  return request({ url: '/crm.project/add', method: 'post', data })
}

export function edit(data) {
  return request({ url: '/crm.project/edit', method: 'post', data })
}

export function cancel(id, reason) {
  return request({ url: '/crm.project/cancel', method: 'post', data: { id, reason } })
}

export function archive(id) {
  return request({ url: '/crm.project/archive', method: 'post', data: { id } })
}

export function getParticipants(projectId) {
  return request({ url: '/crm.project/participants', method: 'get', params: { project_id: projectId } })
}

export function addParticipants(data) {
  return request({ url: '/crm.project/addParticipant', method: 'post', data })
}

export function removeParticipant(data) {
  return request({ url: '/crm.project/removeParticipant', method: 'post', data })
}

export function startProject(id) {
  return request({ url: '/crm.project/start', method: 'post', data: { id } })
}

export function completeProject(id) {
  return request({ url: '/crm.project/complete', method: 'post', data: { id } })
}

export function reopenProject(id) {
  return request({ url: '/crm.project/reopen', method: 'post', data: { id } })
}

export function updateParticipant(data) {
  return request({ url: '/crm.project/updateParticipant', method: 'post', data })
}

export function aiSummary(id) {
  return request({ url: '/crm.project/aiSummary', method: 'post', data: { id } })
}

// ---- 审核（全检 · 指派制）----
export function submitReview(id) {
  return request({ url: '/crm.project/submitReview', method: 'post', data: { id } })
}

export function assignReviewer(data) {
  return request({ url: '/crm.project/assignReviewer', method: 'post', data })
}

export function getReviewList(params) {
  return request({ url: '/crm.project/reviewList', method: 'get', params })
}

export function reviewPass(review_id) {
  return request({ url: '/crm.project/reviewPass', method: 'post', data: { review_id } })
}

export function reviewReject(review_id, reason) {
  return request({ url: '/crm.project/reviewReject', method: 'post', data: { review_id, reason } })
}

export function getReviewLog(project_id) {
  return request({ url: '/crm.project/reviewLog', method: 'get', params: { project_id } })
}
