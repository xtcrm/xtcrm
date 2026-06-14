<template>
  <div class="ai-assistant-ctn">
    <a-button v-if="!open" type="primary" shape="circle" size="large" class="ai-assistant-fab" @click="open = true">
      🤖
    </a-button>

    <a-card v-else size="small" class="ai-assistant-panel" :bordered="false">
      <template #title>
        🤖 AI 助手
        <a-button type="link" size="small" style="float:right;padding:0" @click="open = false">✕</a-button>
      </template>

      <div class="ai-assistant-msgs" ref="msgBox">
        <div v-for="(m, i) in messages" :key="i" :class="['ai-assistant-msg', m.role]">
          <div class="ai-assistant-bubble" v-if="m.role === 'assistant'" v-html="renderContent(m.content)"></div>
          <div class="ai-assistant-bubble" v-else>{{ m.content }}</div>
        </div>
        <div v-if="loading" class="ai-assistant-msg assistant">
          <div class="ai-assistant-bubble ai-assistant-typing">思考中...</div>
        </div>
      </div>

      <div class="ai-assistant-quick">
        <a-tag v-for="q in quickQuestions" :key="q" color="blue" @click="ask(q)" style="cursor:pointer;margin:2px">{{ q }}</a-tag>
      </div>

      <div class="ai-assistant-row">
        <a-input v-model:value="input" placeholder="问AI..." @press-enter="send" :disabled="loading" />
        <a-button type="primary" size="small" @click="send" :loading="loading" style="margin-left:6px">发送</a-button>
      </div>
    </a-card>
  </div>
</template>

<script setup>
import { ref, nextTick, watch } from 'vue'
import { useRoute } from 'vue-router'
import { agentChat } from '@/api/crm/agent'

const route = useRoute()
const open = ref(false)
const input = ref('')
const loading = ref(false)
const msgBox = ref()

const messages = ref([
  { role: 'assistant', content: '你好！我是AI助手，可以直接帮你查数据、做分析。试试：\n• 查询东莞的客户\n• 帮我查深蓝包装的订单\n• 我的业绩怎么样\n• 有哪些UV油墨产品' },
])

const quickQuestions = [
  '查询最近30天有新订单的客户',
  '查看14号报价单的详细信息',
  '我的业绩怎么样',
]

function send() {
  const msg = input.value.trim()
  if (!msg || loading.value) return
  messages.value.push({ role: 'user', content: msg })
  input.value = ''
  loading.value = true
  nextTick(() => scrollBottom())

  agentChat(msg).then(res => {
    const reply = (res?.data?.content) || '抱歉，没理解你的问题'
    messages.value.push({ role: 'assistant', content: reply })
    loading.value = false
    nextTick(() => scrollBottom())
  }).catch(() => {
    messages.value.push({ role: 'assistant', content: '服务暂时不可用，请稍后重试' })
    loading.value = false
  })
}

function ask(q) { input.value = q; send() }

function renderContent(text) {
  if (!text) return ''
  return text
    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
    .replace(/\[([^\]]+)\]\((\/[^\s)]+)\)/g, '<a href="$2" target="_blank" style="color:#d6893b;font-weight:bold;text-decoration:underline">$1</a>')
    .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
    .replace(/\n/g, '<br>')
    .replace(/(\d+)\.\s/g, '<br>$1. ')
    .replace(/^-\s/gm, '<br>• ')
}

function scrollBottom() {
  const el = msgBox.value
  if (el) el.scrollTop = el.scrollHeight
}

watch(open, (v) => { if (v) nextTick(() => scrollBottom()) })
</script>

<style>
.ai-assistant-ctn { position: fixed; right: 24px; bottom: 24px; z-index: 999; }
.ai-assistant-fab { width: 56px; height: 56px; font-size: 24px; box-shadow: 0 4px 12px rgba(0,0,0,.15); }
.ai-assistant-panel { width: 420px !important; max-width: 90vw; max-height: 560px; box-shadow: 0 8px 24px rgba(0,0,0,.12); }
.ai-assistant-msgs { overflow-y: auto; max-height: 360px; padding: 12px 4px; }
.ai-assistant-msg { margin-bottom: 10px; }
.ai-assistant-msg.user { text-align: right; }
.ai-assistant-msg.user .ai-assistant-bubble { background: #1890ff; color: #fff; margin-left: auto; border-radius: 12px 12px 0 12px; }
.ai-assistant-msg.assistant .ai-assistant-bubble { background: #f0f2f5; border-radius: 12px 12px 12px 0; }
.ai-assistant-bubble { display: inline-block; max-width: 88%; padding: 10px 16px; font-size: 13px; line-height: 1.6; word-break: break-word; }
.ai-assistant-bubble strong { color: #d4380d; }
.ai-assistant-typing { color: #999; font-style: italic; }
.ai-assistant-quick { margin: 8px 0; }
.ai-assistant-row { display: flex; margin-top: 6px; }
</style>
