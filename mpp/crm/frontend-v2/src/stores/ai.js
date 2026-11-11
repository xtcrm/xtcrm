import { defineStore } from 'pinia'
import { ref, nextTick } from 'vue'
import { startChat, resolveSession } from '@/api/ai'

export const useAiStore = defineStore('ai', () => {
  // —— 面板 UI 状态 ——
  const panelOpen = ref(true)
  const messages = ref([])
  const currentSessionId = ref(null)
  const currentRole = ref('sales')
  const currentRoleName = ref('销售助手')
  const streaming = ref(false)
  const refreshFlag = ref(0)
  const formFill = ref(null)  // { tool: 'parse_company', data: {...} } → 页面监听填表  // 写操作后 +1，页面 watch 后自动刷新

  // —— 上下文（BasicLayout watch route 自动 set） ——
  const currentContext = ref({ page: '' })

  function setContext(ctx) { currentContext.value = ctx }
  function getContext() { return currentContext.value }

  // —— 消息发送 ——
  async function sendMessage(text, roleKey) {
    if (!text.trim() || streaming.value) return

    messages.value.push({ role: 'user', content: text.trim() })
    await nextTick()

    const loadingMsg = { role: 'system', content: '思考中...', loading: true, _key: '_loading_' + Date.now() }
    messages.value.push(loadingMsg)
    streaming.value = true
    await nextTick()

    try {
      const context = getContext()
      const res = await startChat(text.trim(), context, roleKey || '')
      const data = res?.data ?? res

      messages.value = messages.value.filter(m => m._key !== loadingMsg._key)

      messages.value.push({
        role: 'assistant',
        content: data.content || '抱歉，没有理解你的问题',
      })

      // 单次会话消息超过 100 条时裁剪到最近 50 条
      if (messages.value.length > 100) {
        messages.value = messages.value.slice(-50)
      }

      // 写操作 → 触发页面刷新；parse_company → 触发填表
      if (data.tools?.length) {
        const writeTools = ['add_followup', 'addFollowup',
          'add_customer_note', 'addCustomerNote',
          'create_contact', 'createContact',
          'create_customer', 'createCustomer',
          'update_customer', 'updateCustomer',
          'create_lead', 'createLead', 'batch_update', 'batchUpdate']
        if (data.tools.some(t => writeTools.includes(t.name) && t.result?.success)) {
          refreshFlag.value++
        }
        const parseTool = data.tools.find(t => (t.name === 'parse_company' || t.name === 'parseCompany') && t.result?.success)
        if (parseTool?.result?.data) {
          formFill.value = { tool: 'parse_company', data: parseTool.result.data }
        }
        // fetch_webpage 结果也尝试触发填表（提取公司信息）
        const fetchTool = data.tools.find(t => (t.name === 'fetch_webpage') && t.result?.success)
        if (fetchTool?.result?.data && !formFill.value) {
          const fd = fetchTool.result.data
          formFill.value = { tool: 'fetch_webpage', data: {
            customer_name: fd.title || '',
            website: fd.url || '',
            introduction: (fd.description || '') + ' ' + (fd.content_preview || '').substring(0, 500),
          }}
        }
      }

      currentSessionId.value = data.session_id
      currentRole.value = data.role_key || 'sales'
      currentRoleName.value = data.role_name || '销售助手'
      streaming.value = false
      await nextTick()
    } catch (e) {
      messages.value = messages.value.filter(m => m._key !== loadingMsg._key)
      let errMsg = '发送失败，请重试'
      if (e?.message) errMsg = e.message
      else if (e?.msg) errMsg = e.msg
      else if (typeof e === 'string') errMsg = e
      console.error('[AI] sendMessage error:', e)
      messages.value.push({ role: 'system', content: '❌ ' + errMsg, error: true })
      streaming.value = false
    }
  }

  // —— 面板开关 ——
  function togglePanel() {
    panelOpen.value = !panelOpen.value
    localStorage.setItem('ai_panel_open', panelOpen.value ? '1' : '0')
    // 打开时加载历史
    if (panelOpen.value && messages.value.length === 0) {
      loadHistory()
    }
  }

  // —— 加载当前上下文的历史消息 ——
  async function loadHistory() {
    if (messages.value.length > 0) return  // 已有消息，不重复加载
    const ctx = getContext()
    if (!ctx?.page) return
    try {
      const res = await resolveSession(ctx)
      const data = res?.data ?? res
      if (data?.messages?.length) {
        messages.value = data.messages
        currentSessionId.value = data.session_id
      }
    } catch (e) {
      // 静默失败
    }
  }

  // —— 新对话 ——
  function newConversation() {
    messages.value = []
    currentSessionId.value = null
  }

  /**
   * 登出时清空。
   * 登录走的是 router.replace（不整页刷新），Pinia store 活在整个 SPA 生命周期里，
   * 不清的话换账号后还能看到上一个账号的对话内容。
   * 注意 currentContext / formFill 也含业务数据（客户名、行业、解析出的公司信息），
   * 只清 messages 是不够的。
   */
  function reset() {
    messages.value = []
    currentSessionId.value = null
    currentContext.value = { page: '' }
    formFill.value = null
  }

  // —— 初始化（默认打开，除非用户主动关闭过） ——
  function init() {
    const saved = localStorage.getItem('ai_panel_open')
    panelOpen.value = saved === null ? true : saved === '1'
  }

  function scrollBottom() {}

  return {
    panelOpen, messages, currentSessionId, currentRole, currentRoleName, streaming,
    currentContext, refreshFlag, formFill,
    setContext, getContext,
    sendMessage, newConversation, loadHistory,
    togglePanel, init, scrollBottom, reset,
  }
})
