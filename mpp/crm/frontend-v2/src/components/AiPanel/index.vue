<template>
  <div class="ai-panel">
    <!-- 顶部栏 -->
    <div class="ai-panel-hd">
      <div class="ai-panel-role">
        <span class="ai-role-dot" :class="aiStore.currentRole"></span>
        <span>{{ aiStore.currentRoleName }}</span>
      </div>
      <a-button type="text" size="small" title="新对话" @click="aiStore.newConversation()">
        <plus-outlined />
      </a-button>
    </div>

    <!-- 上下文 + 快捷操作 -->
    <div class="ai-panel-ctx">
      <span class="ai-ctx-label">当前：</span>
      <span class="ai-ctx-value">{{ contextLabel }}</span>
    </div>
    <div class="ai-panel-quick">
      <a-button size="small" v-for="act in quickActions" :key="act.label" @click="act.handler()">
        {{ act.icon }} {{ act.label }}
      </a-button>
    </div>

    <!-- 消息列表 -->
    <div class="ai-panel-msgs" ref="msgBox">
      <!-- 空状态 -->
      <div v-if="aiStore.messages.length === 0" class="ai-panel-empty">
        <div class="ai-empty-icon">🤖</div>
        <div class="ai-empty-title">{{ roleGreeting.title }}</div>
        <div class="ai-empty-desc">{{ roleGreeting.desc }}</div>
        <div class="ai-empty-guide">
          <div class="ai-guide-group" v-for="g in roleGreeting.guides" :key="g.label">
            <div class="ai-guide-label">{{ g.label }}</div>
            <a-tag v-for="q in g.items" :key="q" color="blue" @click="ask(q)" style="cursor:pointer;margin:3px">{{ q }}</a-tag>
          </div>
        </div>
      </div>

      <!-- 消息 -->
      <div
        v-for="(m, i) in aiStore.messages"
        :key="i"
        :class="['ai-msg', 'ai-msg-' + m.role]"
      >
        <!-- 用户消息 -->
        <div v-if="m.role === 'user'" class="ai-bubble ai-bubble-user">
          {{ m.content }}
        </div>

        <!-- AI 文本消息 -->
        <div v-else-if="m.role === 'assistant'" class="ai-bubble ai-bubble-ai">
          <div v-html="renderContent(m.content)"></div>
          <!-- 流式光标 -->
          <span v-if="m.streaming" class="ai-cursor">|</span>
          <!-- 异步任务指示器 -->
          <div v-if="m.taskStatus === 'running'" class="ai-task-bar">
            <loading-outlined spin /> 生成中...
          </div>
        </div>

        <!-- 系统/工具消息 -->
        <div v-else-if="m.role === 'system'" class="ai-system-msg">
          <span v-if="m.toolStatus === 'calling' || m.toolStatus === 'executing'" class="ai-sys-loading">
            <loading-outlined spin style="margin-right:4px" />{{ m.content }}
          </span>
          <span v-else class="ai-sys-done">{{ m.content }}</span>
        </div>
      </div>

      <!-- 底部锚点 -->
      <div ref="msgEnd"></div>
    </div>

    <!-- 输入区 -->
    <div class="ai-panel-input">
      <a-textarea
        v-model:value="input"
        :rows="2"
        :placeholder="recording ? '正在聆听...' : '输入消息  (Enter 发送, Shift+Enter 换行)'"
        @press-enter="handleEnter"
        :disabled="aiStore.streaming || recording"
      />
      <a-button
        class="ai-voice-btn"
        :class="{ recording }"
        size="small"
        @mousedown="startRecord"
        @mouseup="stopRecord"
        @mouseleave="stopRecord"
        @touchstart.prevent="startRecord"
        @touchend.prevent="stopRecord"
        :disabled="aiStore.streaming || !speechSupported"
        :title="speechSupported ? '按住说话' : '浏览器不支持语音'"
      >
        <audio-outlined v-if="!recording" />
        <loading-outlined v-else spin />
      </a-button>
      <a-button
        type="primary"
        size="small"
        @click="send"
        :loading="aiStore.streaming"
      >
        <send-outlined v-if="!aiStore.streaming" />
      </a-button>
    </div>
  </div>
</template>

<script setup>
import { ref, nextTick, watch, onMounted, computed } from 'vue'
import { useRoute } from 'vue-router'
import { PlusOutlined, LoadingOutlined, SendOutlined, AudioOutlined } from '@ant-design/icons-vue'
import { useAiStore } from '@/stores/ai'

const aiStore = useAiStore()
const route = useRoute()
const input = ref('')
const msgBox = ref(null)
const msgEnd = ref(null)

// 当前上下文标签 — 基于路由自动推导 + 页面 setContext 补充
const contextLabel = computed(() => {
  const ctx = aiStore.currentContext
  const meta = route.meta || {}

  // 详情页 — 有具体对象名时显示
  if (ctx.customer_name) return `客户 · ${ctx.customer_name}`
  if (ctx.lead_name) return `线索 · ${ctx.lead_name}`
  if (ctx.customer_id && ctx.page === 'customer-detail') return `客户详情 #${ctx.customer_id}`

  // 通用 — 用路由 meta.title
  if (meta.title) return meta.title

  // fallback
  return ctx.page || '当前页面'
})

// 快捷操作（按角色）
const quickActions = computed(() => {
  if (aiStore.currentRole === 'sales') return [
    { icon: '📝', label: '加跟进', handler: () => ask('帮我为当前客户添加一条跟进记录') },
    { icon: '🔍', label: '查客户', handler: () => ask('帮我查一下这个客户的详细信息') },
    { icon: '📋', label: '查报价', handler: () => ask('查这个客户的报价单') },
    { icon: '📦', label: '查订单', handler: () => ask('查这个客户的订单') },
    { icon: '🔎', label: '查产品', handler: () => ask('帮我搜索产品') },
    { icon: '💬', label: '写话术', handler: () => ask('帮我写一段电话邀约话术') },
    { icon: '📊', label: '报价单', handler: () => ask('出一份中文报价单') },
    { icon: '👤', label: '加联系人', handler: () => ask('帮我为当前客户添加联系人') },
  ]
  if (aiStore.currentRole === 'creator') return [
    { icon: '✏️', label: '写推文', handler: () => ask('写一篇公众号产品推文') },
    { icon: '📄', label: '写方案', handler: () => ask('帮我写一个方案') },
    { icon: '💰', label: '报价单', handler: () => ask('出一份中文报价单') },
    { icon: '📖', label: '查知识库', handler: () => ask('查一下知识库') },
    { icon: '💬', label: '写话术', handler: () => ask('帮我写销售话术') },
    { icon: '🔍', label: '查产品', handler: () => ask('帮我搜索产品') },
  ]
  if (aiStore.currentRole === 'coach') return [
    { icon: '📊', label: '销售周报', handler: () => ask('帮我生成销售周报') },
    { icon: '📈', label: '业绩分析', handler: () => ask('帮我分析本月的销售业绩') },
    { icon: '🎯', label: '丢单分析', handler: () => ask('帮我分析最近的丢单原因') },
    { icon: '🔮', label: '流失预警', handler: () => ask('哪些客户有流失风险') },
    { icon: '🎙️', label: '话术演练', handler: () => ask('模拟一次电话拜访演练') },
    { icon: '📋', label: '月度报表', handler: () => ask('生成本月经营分析报表') },
  ]
  return [
    { icon: '📝', label: '加跟进', handler: () => ask('帮我加一条跟进记录') },
    { icon: '🔍', label: '查产品', handler: () => ask('帮我搜索产品') },
  ]
})

const quickQuestions = [
  '帮我查最近30天有新订单的客户',
  '今天有哪些客户需要跟进',
  '我的业绩怎么样',
  '帮我写一个新能源电池的产品介绍',
]

// 角色欢迎引导
const roleGreeting = computed(() => {
  if (aiStore.currentRole === 'sales') return {
    title: '销售助手',
    desc: '查客户、记跟进、催续约，一句话搞定',
    guides: [
      { label: '📋 跟进 & 客户', items: ['添加跟进记录', '查这个客户的报价单', '生成客户跟进摘要'] },
      { label: '💬 话术', items: ['写一段电话邀约话术', '客户说价格贵怎么回复', '帮我写跟进邮件模板'] },
      { label: '📊 报价', items: ['查报价单', '出一份中文报价单', '这个客户有哪些报价'] },
    ]
  }
  if (aiStore.currentRole === 'creator') return {
    title: '创作与方案专家',
    desc: '写推文、做方案、出话术，AI 帮你动笔',
    guides: [
      { label: '📝 文章 & 推文', items: ['写一篇公众号产品推文', '写一段朋友圈营销文案', '生成SEO产品描述'] },
      { label: '📄 方案 & 标书', items: ['写一份技术方案', '做产品对比表', '生成投标文件大纲'] },
      { label: '💰 报价', items: ['出一份中文报价单', '做报价对比分析', '写报价说明文档'] },
    ]
  }
  if (aiStore.currentRole === 'coach') return {
    title: '业绩教练',
    desc: '看数据、做分析、练话术',
    guides: [
      { label: '📈 数据 & 报表', items: ['本月销售数据概览', '我的KPI完成情况', '生成销售周报'] },
      { label: '🎯 分析', items: ['分析丢单原因', '对比本月和上月业绩', '哪些客户有流失风险'] },
      { label: '🎙️ 话术演练', items: ['模拟一次电话拜访', '演练客户异议处理', '分析我的跟进话术'] },
    ]
  }
  return { title: 'AI 助手', desc: '试试下面的快捷入口', guides: [] }
})

function handleEnter(e) {
  if (!e.shiftKey) {
    e.preventDefault()
    send()
  }
}

function send() {
  const msg = input.value.trim()
  if (!msg) return
  input.value = ''
  aiStore.sendMessage(msg)
}

// 语音输入 — 按住说话
const recording = ref(false)
const speechSupported = ref(false)
let recognition = null

// 初始化语音识别（Chrome/Edge 支持）
try {
  const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition
  if (SpeechRecognition) {
    recognition = new SpeechRecognition()
    recognition.lang = 'zh-CN'
    recognition.continuous = true
    recognition.interimResults = true
    recognition.onresult = (e) => {
      let final = ''; let interim = ''
      for (let i = 0; i < e.results.length; i++) {
        if (e.results[i].isFinal) final += e.results[i][0].transcript
        else interim += e.results[i][0].transcript
      }
      input.value = final + interim
    }
    recognition.onerror = () => { recording.value = false }
    recognition.onend = () => { recording.value = false }
    speechSupported.value = true
  }
} catch (e) { /* 不支持 */ }

function startRecord() {
  if (!recognition || recording.value) return
  recording.value = true
  recognition.start()
}
function stopRecord() {
  if (!recognition || !recording.value) return
  recognition.stop()
}

function ask(q) {
  input.value = q
  send()
}

function renderContent(text) {
  if (!text) return ''
  // XSS 防护
  const esc = (s) => s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')

  // 处理行内 Markdown（粗体/代码/链接）
  const inline = (s) => s
    .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
    .replace(/`([^`]+)`/g, '<code>$1</code>')
    .replace(/\[([^\]]+)\]\(([^)]+)\)/g, '<a href="$2" target="_blank">$1</a>')

  // 先处理 Markdown 表格（多行结构，优先于行处理）
  let html = text
  html = html.replace(/(\|[^\n]+\|\n)(\|[-: |]+\|\n)((?:\|[^\n]*\|\n?)+)/g, (_, header, sep, body) => {
    const thead = '<thead><tr>' + header
      .split('|').filter(c => c.trim()).map(c => '<th>' + esc(c.trim()) + '</th>').join('') + '</tr></thead>'
    const tbody = '<tbody>' + body.trim().split('\n').map(row =>
      '<tr>' + row.split('|').filter(c => c.trim()).map(c => '<td>' + inline(esc(c.trim())) + '</td>').join('') + '</tr>'
    ).join('') + '</tbody>'
    return '<table class="ai-table">' + thead + tbody + '</table>'
  })

  // 逐行处理块级元素
  const lines = html.split('\n')
  const out = []
  let inList = false, listType = ''

  for (let i = 0; i < lines.length; i++) {
    let line = lines[i]

    // 跳过表格行（已被替换为 <table>）
    if (line.startsWith('<table')) { out.push(line); continue }
    if (line.match(/^\|/)) continue  // 残留在表格内的行
    if (line.match(/^\|?[-: |]+\|?$/)) continue  // 分隔线

    // 标题 ### / ## / #
    let m = line.match(/^(#{1,6})\s+(.+)/)
    if (m) {
      const lvl = m[1].length
      out.push(`<h${lvl}>${inline(esc(m[2]))}</h${lvl}>`)
      continue
    }

    // 水平线 --- / ***
    if (line.match(/^[-*]{3,}\s*$/)) { out.push('<hr>'); continue }

    // 引用 >
    m = line.match(/^>\s?(.*)/)
    if (m) { out.push(`<blockquote>${inline(esc(m[1]))}</blockquote>`); continue }

    // 无序列表 - / *
    m = line.match(/^[-*]\s+(.+)/)
    if (m) {
      if (!inList || listType !== 'ul') {
        if (inList) out.push(listType === 'ul' ? '</ul>' : '</ol>')
        out.push('<ul>'); inList = true; listType = 'ul'
      }
      out.push(`<li>${inline(esc(m[1]))}</li>`)
      continue
    }

    // 有序列表 1. / 2.
    m = line.match(/^(\d+)\.\s+(.+)/)
    if (m) {
      if (!inList || listType !== 'ol') {
        if (inList) out.push(listType === 'ul' ? '</ul>' : '</ol>')
        out.push('<ol>'); inList = true; listType = 'ol'
      }
      out.push(`<li>${inline(esc(m[2]))}</li>`)
      continue
    }

    // 列表结束
    if (inList && line.trim() === '') {
      out.push(listType === 'ul' ? '</ul>' : '</ol>')
      inList = false; listType = ''
      continue
    }
    if (inList && line.trim() !== '') {
      // 非列表行出现在列表中，关闭列表
      out.push(listType === 'ul' ? '</ul>' : '</ol>')
      inList = false; listType = ''
    }

    // 普通段落
    out.push(line ? inline(esc(line)) : '<br>')
  }

  // 关闭未闭合列表
  if (inList) out.push(listType === 'ul' ? '</ul>' : '</ol>')

  return out.join('\n')
}

function scrollBottom() {
  nextTick(() => {
    msgEnd.value?.scrollIntoView({ behavior: 'smooth' })
  })
}

// 监听消息变化自动滚动
watch(() => aiStore.messages.length, () => scrollBottom())
onMounted(() => scrollBottom())
</script>

<style lang="less" scoped>
.ai-panel {
  height: 100%;
  display: flex;
  flex-direction: column;
  background: #fff;
}

// ===== Header =====
.ai-panel-hd {
  flex-shrink: 0;
  height: 44px;
  padding: 0 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #f0f0f0;
  background: #fafafa;
}
.ai-panel-role {
  display: flex; align-items: center; gap: 6px;
  font-size: 14px; font-weight: 500; color: #333;
}
.ai-role-dot {
  width: 8px; height: 8px; border-radius: 50%;
  &.sales   { background: #1890ff; }
  &.creator { background: #52c41a; }
  &.coach   { background: #722ed1; }
}

// Context bar
.ai-panel-ctx {
  flex-shrink: 0;
  padding: 6px 12px;
  font-size: 12px;
  color: #999;
  background: #fafafa;
  border-bottom: 1px solid #f0f0f0;
  .ai-ctx-value { color: #333; }
}

// Quick actions
.ai-panel-quick {
  flex-shrink: 0;
  padding: 6px 10px;
  display: flex; gap: 4px;
  flex-wrap: wrap;
  border-bottom: 1px solid #f0f0f0;
  :deep(.ant-btn-sm) { font-size: 12px; padding: 0 8px; height: 28px; }
}

// ===== Messages =====
.ai-panel-msgs {
  flex: 1;
  overflow-y: auto;
  padding: 12px;
  display: flex;
  flex-direction: column;
  gap: 8px;

  &::-webkit-scrollbar { width: 4px; }
  &::-webkit-scrollbar-thumb { background: #e0e0e0; border-radius: 2px; }
}

// Empty state
.ai-panel-empty {
  text-align: center;
  padding: 24px 16px;
  .ai-empty-icon { font-size: 48px; margin-bottom: 8px; }
  .ai-empty-title { font-size: 18px; font-weight: 600; color: #333; margin-bottom: 4px; }
  .ai-empty-desc { font-size: 13px; color: #999; margin-bottom: 16px; }
}

.ai-empty-guide { text-align: left; }
.ai-guide-group { margin-bottom: 12px; }
.ai-guide-label { font-size: 12px; color: #666; font-weight: 500; margin-bottom: 4px; }

// Message bubbles
.ai-msg { display: flex; flex-direction: column; }
.ai-msg-user { align-items: flex-end; }
.ai-msg-assistant { align-items: flex-start; }

.ai-bubble {
  max-width: 85%;
  padding: 8px 12px;
  border-radius: 8px;
  font-size: 13px;
  line-height: 1.6;
  word-break: break-word;
}
.ai-bubble-user {
  background: #1890ff;
  color: #fff;
  border-bottom-right-radius: 2px;
}
.ai-bubble-ai {
  background: #f5f5f5;
  color: #333;
  border-bottom-left-radius: 2px;
}

// Streaming cursor
.ai-cursor {
  display: inline;
  animation: blink 0.8s infinite;
  color: #1890ff;
  font-weight: 700;
}
@keyframes blink {
  0%, 50% { opacity: 1; }
  51%, 100% { opacity: 0; }
}

// Markdown table
:deep(.ai-table) {
  width: 100%;
  border-collapse: collapse;
  margin: 8px 0;
  font-size: 12px;
  th, td {
    border: 1px solid #e8e8e8;
    padding: 6px 8px;
    text-align: left;
  }
  th {
    background: #fafafa;
    font-weight: 600;
  }
  tr:nth-child(even) td { background: #fafafa; }
}

:deep(code) {
  background: #f5f5f5;
  padding: 2px 5px;
  border-radius: 3px;
  font-size: 12px;
  color: #d4380d;
}

:deep(h1), :deep(h2), :deep(h3), :deep(h4) {
  margin: 8px 0 4px;
  font-weight: 600;
  line-height: 1.4;
}
:deep(h1) { font-size: 18px; }
:deep(h2) { font-size: 16px; }
:deep(h3) { font-size: 14px; }

:deep(hr) {
  border: none;
  border-top: 1px solid #e8e8e8;
  margin: 10px 0;
}

:deep(blockquote) {
  border-left: 3px solid #1890ff;
  padding: 4px 10px;
  margin: 6px 0;
  color: #666;
  background: #f0f5ff;
  border-radius: 0 4px 4px 0;
}

:deep(ul), :deep(ol) {
  margin: 4px 0;
  padding-left: 20px;
}

:deep(li) {
  margin: 2px 0;
}

// Tool/system messages
.ai-system-msg {
  align-self: center;
  font-size: 12px;
  color: #999;
  padding: 2px 0;
}
.ai-sys-loading { color: #faad14; }
.ai-sys-done { color: #52c41a; }

// Async task bar
.ai-task-bar {
  margin-top: 8px;
  padding: 6px 10px;
  background: #fffbe6;
  border: 1px solid #ffe58f;
  border-radius: 4px;
  font-size: 12px;
  color: #d48806;
}

// ===== Input =====
.ai-panel-input {
  flex-shrink: 0;
  padding: 8px 12px 12px;
  border-top: 1px solid #f0f0f0;
  display: flex;
  align-items: flex-end;
  gap: 6px;

  :deep(.ant-input) { font-size: 13px; resize: none; }
}
.ai-voice-btn {
  flex-shrink: 0;
  color: #999;
  transition: all .2s;
  &:hover { color: #1890ff; }
  &.recording {
    color: #fff;
    background: #ff4d4f;
    border-color: #ff4d4f;
    animation: pulse 1.2s infinite;
  }
}
@keyframes pulse {
  0%, 100% { box-shadow: 0 0 0 0 rgba(255,77,79,.4); }
  50% { box-shadow: 0 0 0 6px rgba(255,77,79,0); }
}
</style>
