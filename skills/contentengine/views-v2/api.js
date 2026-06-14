import request from '@/utils/request'

// 公司档案
export function getProfile() { return request({ url: '/crm.profile/detail', method: 'get' }) }
export function saveProfile(data) { return request({ url: '/crm.profile/edit', method: 'post', data }) }

// 关键词
export function getKeywords(params) { return request({ url: '/crm.keyword/lists', method: 'get', params }) }
export function addKeyword(data) { return request({ url: '/crm.keyword/add', method: 'post', data }) }
export function deleteKeyword(id) { return request({ url: '/crm.keyword/delete', method: 'post', data: { id } }) }
export function batchImportKeywords(text, type) { return request({ url: '/crm.keyword/batchImport', method: 'post', data: { text, type } }) }

// 解决方案
export function getSolutions(params) { return request({ url: '/crm.solution/lists', method: 'get', params }) }
export function getSolutionDetail(id) { return request({ url: '/crm.solution/detail', method: 'get', params: { id } }) }
export function addSolution(data) { return request({ url: '/crm.solution/add', method: 'post', data }) }
export function editSolution(data) { return request({ url: '/crm.solution/edit', method: 'post', data }) }
export function deleteSolution(id) { return request({ url: '/crm.solution/delete', method: 'post', data: { id } }) }

// 选题
export function getTopics(params) { return request({ url: '/crm.topic/lists', method: 'get', params }) }
export function scanTopics(agentId) { return request({ url: '/crm.topic/scan', method: 'post', data: { agent_id: agentId || '' } }) }
export function addTopic(data) { return request({ url: '/crm.topic/add', method: 'post', data }) }
export function approveTopic(id, status) { return request({ url: '/crm.topic/approve', method: 'post', data: { id, status } }) }

// 内容生成
export function generateContent(topicId, agentId) { return request({ url: '/crm.editor/generate', method: 'post', data: { topic_id: topicId, agent_id: agentId || '' } }) }
export function getOutputs(topicId) { return request({ url: '/crm.editor/outputs', method: 'get', params: { topic_id: topicId } }) }
export function saveOutput(id, content) { return request({ url: '/crm.editor/save', method: 'post', data: { id, content } }) }

// 日历 & 统计
export function getCalendar(month) { return request({ url: '/crm.editor/calendar', method: 'get', params: { month } }) }
export function getStats() { return request({ url: '/crm.editor/stats', method: 'get' }) }

// 写作风格
export function getStyles() { return request({ url: '/crm.style/lists', method: 'get' }) }
export function copyStylePreset(key) { return request({ url: '/crm.style/copyFromPreset', method: 'post', data: { preset_key: key } }) }
export function addStyle(data) { return request({ url: '/crm.style/add', method: 'post', data }) }
export function editStyle(data) { return request({ url: '/crm.style/edit', method: 'post', data }) }
export function deleteStyle(id) { return request({ url: '/crm.style/delete', method: 'post', data: { id } }) }

// 智能体
export function getAgents() { return request({ url: '/crm.agent/lists', method: 'get' }) }
export function copyAgentPreset(key) { return request({ url: '/crm.agent/copyFromPreset', method: 'post', data: { preset_key: key } }) }
export function addAgent(data) { return request({ url: '/crm.agent/add', method: 'post', data }) }
export function editAgent(data) { return request({ url: '/crm.agent/edit', method: 'post', data }) }
export function deleteAgent(id) { return request({ url: '/crm.agent/delete', method: 'post', data: { id } }) }
