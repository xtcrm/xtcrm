import request from '@/utils/request'

/**
 * 发起对话
 * POST /crm.ai/chatStart
 */
export function startChat(message, context, roleKey) {
  return request({
    url: '/crm.ai/chatStart',
    method: 'post',
    data: { message, context, role_key: roleKey || '' }
  })
}

/**
 * 消费 SSE 流
 * @param {string} streamUrl  如 '/crm.ai/chatStream?token=xxx'
 * @param {object} callbacks  { onText, onToolCall, onToolResult, onStatus, onIntent, onAsyncTask, onError, onDone }
 * @returns {AbortController}
 */
export function consumeStream(streamUrl, callbacks) {
  const controller = new AbortController()
  // 用和 Axios 一致的 BASE_API，不是 window.location.origin
  const base = window.publicConfig?.BASE_API || ''

  fetch(base + streamUrl, {
    signal: controller.signal,
    credentials: 'include',  // 跨域带 cookie
  })
    .then(async (resp) => {
      if (!resp.ok) {
        callbacks.onError?.(`HTTP ${resp.status}`)
        return
      }
      const reader = resp.body.getReader()
      const decoder = new TextDecoder()
      let buffer = ''

      while (true) {
        const { done, value } = await reader.read()
        if (done) break
        buffer += decoder.decode(value, { stream: true })
        const lines = buffer.split('\n')
        buffer = lines.pop()

        for (const line of lines) {
          if (!line.startsWith('data: ')) continue
          try {
            const event = JSON.parse(line.slice(6))
            switch (event.type) {
              case 'status':        callbacks.onStatus?.(event.text); break
              case 'intent':        callbacks.onIntent?.(event.role_key, event.role_name); break
              case 'text':          callbacks.onText?.(event.content); break
              case 'tool_call':     callbacks.onToolCall?.(event.name, event.args, event.status); break
              case 'tool_result':   callbacks.onToolResult?.(event.name, event.summary); break
              case 'async_task':    callbacks.onAsyncTask?.(event.task_id, event.preview); break
              case 'error':         callbacks.onError?.(event.message); break
              case 'done':          callbacks.onDone?.(event.session_id); break
            }
          } catch (e) {
            // JSON parse error on partial chunk, ignore
          }
        }
      }
    })
    .catch((err) => {
      if (err.name !== 'AbortError') {
        callbacks.onError?.(err.message || '网络错误')
      }
    })

  return controller
}

/**
 * 查询异步任务状态
 * GET /crm.ai/taskStatus
 */
export function getTaskStatus(taskId) {
  return request({ url: '/crm.ai/taskStatus', method: 'get', params: { task_id: taskId } })
}

/**
 * 获取会话列表
 * GET /crm.ai/conversations
 */
export function getConversations(limit = 20) {
  return request({ url: '/crm.ai/conversations', method: 'get', params: { limit } })
}

/**
 * 新建会话
 * POST /crm.ai/newConversation
 */
export function newConversation(context) {
  return request({ url: '/crm.ai/newConversation', method: 'post', data: { context } })
}

/**
 * 加载会话消息列表
 * GET /crm.ai/messages
 */
export function getMessages(sessionId) {
  return request({ url: '/crm.ai/messages', method: 'get', params: { session_id: sessionId } })
}

/**
 * 解析上下文 → 会话ID + 历史消息
 * GET /crm.ai/resolve
 */
export function resolveSession(context) {
  return request({ url: '/crm.ai/resolve', method: 'get', params: { context } })
}
