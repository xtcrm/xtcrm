<template>
  <div class="workshop">
    <!-- 左栏：选题管道 -->
    <div class="ws-left">
      <div class="ws-left-hd">
        <div class="ws-section-label">选题管道</div>
        <div class="ws-left-actions">
          <a-select v-model:value="topicAgentId" placeholder="选题智能体" size="small" style="width:140px" allow-clear>
            <a-select-option v-for="a in topicAgents" :key="a.preset_key || a.id" :value="a.preset_key || String(a.id)">{{ a.name }}</a-select-option>
          </a-select>
          <a-button size="small" type="primary" :loading="scanning" :disabled="!topicAgentId" @click="scanTopics">扫描</a-button>
          <a-button size="small" @click="showAddTopic = true"><plus-outlined /></a-button>
        </div>
      </div>
      <div class="ws-topic-list">
        <div v-for="t in topics" :key="t.id" :class="['ws-topic', { active: t.id === activeTopicId, approved: t.status === 2 }]" @click="selectTopic(t)">
          <div class="ws-topic-status"><a-badge :status="t.status === 1 ? 'processing' : t.status === 2 ? 'success' : t.status === 3 ? 'default' : 'error'" /></div>
          <div class="ws-topic-body">
            <div class="ws-topic-title">{{ t.title }}</div>
            <div class="ws-topic-meta">{{ t.keywords || '无关键词' }} · {{ fmtTime(t.create_time) }}</div>
          </div>
          <div class="ws-topic-actions" v-if="t.status === 1">
            <a-button size="small" type="link" @click.stop="approve(t.id, 2)">通过</a-button>
            <a-button size="small" type="link" style="color:#999" @click.stop="approve(t.id, 5)">拒绝</a-button>
          </div>
        </div>
        <a-empty v-if="!topics.length" description="暂无选题，点击扫描获取" :image-style="{ height: '48px' }" />
      </div>
    </div>

    <!-- 右栏：创作画布 -->
    <div class="ws-right">
      <template v-if="activeTopic">
        <div class="ws-right-hd">
          <div class="ws-right-title">{{ activeTopic.title }}</div>
          <div class="ws-right-actions">
            <a-button type="primary" :loading="generating" @click="generate">生成全部</a-button>
            <a-button :loading="saving" :disabled="!outputIds[activeFormat]" @click="save" style="margin-left:8px">保存</a-button>
          </div>
        </div>
        <div class="ws-tabs">
          <div v-for="(item, key) in formats" :key="key" :class="['ws-tab', { active: activeFormat === key }]" @click="activeFormat = key">
            <span class="ws-tab-label">{{ item.label }}</span>
            <a-select :value="formatAgentIds[key]" size="small" style="width:110px;margin-top:3px" @click.stop @change="(v) => formatAgentIds[key] = v">
              <a-select-option v-for="a in agentsForFormat(key)" :key="a.preset_key || a.id" :value="a.preset_key || String(a.id)">{{ a.name }}</a-select-option>
            </a-select>
          </div>
        </div>
        <div class="ws-editor">
          <a-textarea v-if="formats[activeFormat]" v-model:value="formats[activeFormat].content" :auto-size="{ minRows: 18, maxRows: 26 }"
            style="font-size:15px;line-height:2;border:none;resize:none" placeholder="点击「生成全部」开始创作..." />
          <div v-else style="padding:40px;text-align:center;color:#c8c0b4">点击「生成全部」开始创作</div>
        </div>
      </template>
      <div v-else class="ws-empty">
        <div class="ws-empty-icon">←</div>
        <div class="ws-empty-text">从左侧选题管道选择一个选题<br/>然后点击「生成全部」开始创作</div>
      </div>
    </div>

    <!-- 添加选题弹窗 -->
    <a-modal :open="showAddTopic" title="手动添加选题" @ok="addTopic" @cancel="showAddTopic = false" :confirm-loading="saving">
      <a-form ref="addFormRef" :model="addForm" :label-col="{ span: 6 }" :wrapper-col="{ span: 16 }">
        <a-form-item label="标题" name="title" :rules="[{ required: true, message: '请输入' }]">
          <a-input v-model:value="addForm.title" />
        </a-form-item>
        <a-form-item label="关键词" name="keywords"><a-input v-model:value="addForm.keywords" /></a-form-item>
        <a-form-item label="角度" name="angle"><a-input v-model:value="addForm.angle" /></a-form-item>
      </a-form>
    </a-modal>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { message } from 'ant-design-vue'
import { PlusOutlined } from '@ant-design/icons-vue'
import { getTopics, scanTopics, addTopic, approveTopic, generateContent, getOutputs, saveOutput, getAgents } from './api'

const topicAgentId = ref(null)
const topicAgents = ref([])
const contentAgents = ref([])
const topics = ref([])
const activeTopicId = ref(null)
const activeTopic = ref(null)
const scanning = ref(false)
const showAddTopic = ref(false)
const generating = ref(false)
const saving = ref(false)
const activeFormat = ref('long')
const formatLabels = { long: '长文', video: '视频', social: '朋友圈', seo: 'SEO' }
const formatAgentIds = reactive({ long: 'content-writer', video: 'video-writer', social: 'social-writer', seo: 'seo-writer' })
const formatTypeMap = { long: 'content_writer', video: 'video_writer', social: 'social_writer', seo: 'seo_writer' }
const formats = ref({})
const outputIds = ref({})

const addFormRef = ref()
const addForm = reactive({ title: '', keywords: '', angle: '' })

function loadAgents() {
  getAgents().then(res => {
    const d = res?.data || {}
    const all = [...(d.presets || []), ...(d.custom || [])]
    topicAgents.value = all.filter(a => (a.type || a.agent_type) === 'topic_scanner')
    contentAgents.value = all.filter(a => (a.type || a.agent_type) !== 'topic_scanner')
  })
}

function loadTopics() {
  getTopics({ list_rows: 50 }).then(res => { topics.value = (res?.data?.list?.data) || [] }).catch(() => {})
}

function doScanTopics() {
  scanning.value = true
  scanTopics(topicAgentId.value || '').then(res => { message.success(res?.message || '完成'); loadTopics() }).finally(() => { scanning.value = false })
}

function selectTopic(t) {
  activeTopicId.value = t.id; activeTopic.value = t
  formats.value = { long: { label: '长文', content: '' }, video: { label: '视频', content: '' }, social: { label: '朋友圈', content: '' }, seo: { label: 'SEO', content: '' } }
  outputIds.value = {}; activeFormat.value = 'long'; loadExistingOutputs()
}

function approve(id, s) { approveTopic(id, s).then(() => { message.success(s === 2 ? '已通过' : '已拒绝'); loadTopics() }) }

function handleAddTopic() {
  addFormRef.value.validate().then(() => {
    saving.value = true
    addTopic({ ...addForm }).then(() => { message.success('已添加'); showAddTopic.value = false; loadTopics() }).finally(() => { saving.value = false })
  }).catch(() => {})
}

function generate() {
  if (!activeTopicId.value) return
  generating.value = true
  generateContent(activeTopicId.value, JSON.stringify(formatAgentIds)).then(() => { message.success('四格式生成完成'); loadExistingOutputs() }).finally(() => { generating.value = false })
}

function loadExistingOutputs() {
  getOutputs(activeTopicId.value).then(res => {
    const list = res?.data?.list || []
    formats.value = { long: { label: '长文', content: '' }, video: { label: '视频', content: '' }, social: { label: '朋友圈', content: '' }, seo: { label: 'SEO', content: '' } }
    outputIds.value = {}
    list.forEach(item => {
      formats.value[item.format] = { label: formatLabels[item.format], content: item.content || '' }
      outputIds.value[item.format] = item.id
    })
  })
}

function save() {
  const id = outputIds.value[activeFormat.value]
  if (!id) return
  saving.value = true
  saveOutput(id, formats.value[activeFormat.value].content).then(() => message.success('已保存')).finally(() => { saving.value = false })
}

function agentsForFormat(f) {
  const t = formatTypeMap[f] || 'content_writer'
  return contentAgents.value.filter(a => (a.type || a.agent_type) === t)
}

function fmtTime(ts) {
  if (!ts) return ''
  const d = new Date(ts * 1000)
  const pad = n => String(n).padStart(2, '0')
  return `${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}`
}

onMounted(() => { loadTopics(); loadAgents() })
</script>

<style scoped>
.workshop { display:flex; gap:0; height:calc(100vh - 140px); min-height:600px; background:#f5f3f0; border-radius:6px; overflow:hidden; }
.ws-left { width:400px; min-width:360px; background:#fff; border-right:1px solid #ebe8e3; display:flex; flex-direction:column; }
.ws-left-hd { padding:16px 20px 12px; border-bottom:1px solid #f0ede8; display:flex; align-items:center; justify-content:space-between; gap:8px; }
.ws-section-label { font-size:13px; font-weight:600; color:#8c8273; }
.ws-left-actions { display:flex; gap:6px; align-items:center; }
.ws-topic-list { flex:1; overflow-y:auto; padding:8px 12px; }
.ws-topic { display:flex; align-items:flex-start; gap:10px; padding:12px 12px; border-radius:6px; cursor:pointer; transition:all 0.15s; margin-bottom:4px; border:1px solid transparent; }
.ws-topic:hover { background:#faf8f5; }
.ws-topic.active { background:#fef9f0; border-color:#f0d9a8; }
.ws-topic.approved { opacity:0.65; }
.ws-topic-status { padding-top:3px; }
.ws-topic-body { flex:1; min-width:0; }
.ws-topic-title { font-size:13px; font-weight:500; color:#3d3629; line-height:1.5; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; margin-bottom:3px; }
.ws-topic-meta { font-size:11px; color:#b8b0a4; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.ws-topic-actions { display:flex; flex-direction:column; gap:2px; padding-top:1px; flex-shrink:0; }
.ws-right { flex:1; display:flex; flex-direction:column; background:#fff; min-width:0; }
.ws-right-hd { padding:16px 24px 12px; border-bottom:1px solid #f0ede8; display:flex; align-items:center; justify-content:space-between; gap:12px; }
.ws-right-title { font-size:16px; font-weight:600; color:#2c2416; flex:1; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.ws-right-actions { display:flex; align-items:center; flex-shrink:0; }
.ws-tabs { display:flex; gap:0; padding:0 24px; border-bottom:1px solid #f0ede8; }
.ws-tab { padding:12px 20px; cursor:pointer; border-bottom:2px solid transparent; transition:all 0.15s; display:flex; flex-direction:column; align-items:center; gap:2px; }
.ws-tab:hover { background:#faf8f5; }
.ws-tab.active { border-bottom-color:#d6893b; }
.ws-tab-label { font-size:13px; font-weight:500; color:#8c8273; }
.ws-tab.active .ws-tab-label { color:#d6893b; }
.ws-editor { flex:1; overflow-y:auto; padding:20px 24px; }
.ws-empty { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:16px; color:#c8c0b4; }
.ws-empty-icon { font-size:48px; font-weight:200; color:#e8e3da; }
.ws-empty-text { font-size:14px; text-align:center; line-height:1.8; }
.ws-topic-list::-webkit-scrollbar, .ws-editor::-webkit-scrollbar { width:4px; }
.ws-topic-list::-webkit-scrollbar-thumb { background:#e0dbd2; border-radius:2px; }
</style>
