<template>
  <a-card :bordered="false">
    <a-tabs default-active-key="presets">
      <a-tab-pane key="presets" tab="系统预设">
        <a-row :gutter="16">
          <a-col :md="8" v-for="a in presets" :key="a.preset_key">
            <a-card size="small" :title="a.name" style="margin-bottom:12px">
              <p><a-tag>{{ typeLabel[a.type] || a.type }}</a-tag> <a-tag color="blue">{{ a.preset_style }}</a-tag></p>
              <p style="color:#999;font-size:12px;min-height:24px">变量：{{ (a.allowed_vars || []).join(', ') }}</p>
              <a-button type="primary" size="small" @click="copyPreset(a.preset_key)" :loading="copying === a.preset_key">复制到我的智能体</a-button>
            </a-card>
          </a-col>
        </a-row>
      </a-tab-pane>
      <a-tab-pane key="custom" tab="我的智能体">
        <a-button type="primary" @click="openAdd" style="margin-bottom:12px"><plus-outlined /> 新建智能体</a-button>
        <a-table :data-source="custom" :loading="loading" row-key="id" size="small" :pagination="false">
          <a-table-column title="名称" data-index="name" key="name" />
          <a-table-column title="类型" data-index="agent_type" key="agent_type" width="80">
            <template #default="{ text }">{{ typeLabel[text] || text }}</template>
          </a-table-column>
          <a-table-column title="风格" data-index="preset_style" key="preset_style" width="120" />
          <a-table-column title="操作" key="action" width="120">
            <template #default="{ record }">
              <a @click="openEdit(record)">编辑</a>
              <a-divider type="vertical" />
              <a-popconfirm title="确认删除?" @confirm="handleDelete(record.id)"><a style="color:#ff4d4f">删除</a></a-popconfirm>
            </template>
          </a-table-column>
        </a-table>
      </a-tab-pane>
    </a-tabs>

    <a-modal :open="modalVisible" :title="editingId ? '编辑智能体' : '新建智能体'" @ok="handleSave" @cancel="modalVisible = false"
      :confirm-loading="saving" width="700" wrap-class-name="agent-modal">
      <a-form ref="editFormRef" :model="editForm" :label-col="{ span: 5 }" :wrapper-col="{ span: 19 }">
        <a-form-item label="名称" name="name" :rules="[{ required: true, message: '请输入' }]">
          <a-input v-model:value="editForm.name" />
        </a-form-item>
        <a-form-item label="类型" name="agent_type">
          <a-select v-model:value="editForm.agent_type">
            <a-select-option value="topic_scanner">选题顾问</a-select-option>
            <a-select-option value="content_writer">长文作者</a-select-option>
            <a-select-option value="video_writer">视频脚本</a-select-option>
            <a-select-option value="social_writer">朋友圈</a-select-option>
            <a-select-option value="seo_writer">SEO专家</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="引用风格" name="preset_style">
          <a-select v-model:value="editForm.preset_style" placeholder="选择风格" allow-clear>
            <a-select-option v-for="s in styleList" :key="s.preset_key || s.id" :value="s.preset_key || String(s.id)">
              {{ s.name }}<span style="color:#999;font-size:12px;margin-left:8px">{{ s.tone || '' }}</span>
            </a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="可用变量" name="allowed_vars">
          <a-checkbox-group v-model:value="editForm.allowed_vars">
            <a-checkbox value="company_intro">公司简介</a-checkbox>
            <a-checkbox value="core_strengths">核心优势</a-checkbox>
            <a-checkbox value="keywords">关键词库</a-checkbox>
            <a-checkbox value="solutions">方案库</a-checkbox>
            <a-checkbox value="products">产品列表</a-checkbox>
            <a-checkbox value="topic_title">选题标题</a-checkbox>
            <a-checkbox value="topic_angle">切入角度</a-checkbox>
          </a-checkbox-group>
        </a-form-item>
        <a-form-item label="附加指令" name="instructions">
          <a-textarea v-model:value="editForm.instructions" :rows="3" placeholder="选填，会追加到system prompt末尾" />
        </a-form-item>
      </a-form>
    </a-modal>
  </a-card>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { message } from 'ant-design-vue'
import { PlusOutlined } from '@ant-design/icons-vue'
import { getAgents, copyAgentPreset, addAgent, editAgent, deleteAgent } from './api'
import { getStyles } from './api'

const loading = ref(false)
const saving = ref(false)
const copying = ref('')
const presets = ref([])
const custom = ref([])
const styleList = ref([])
const modalVisible = ref(false)
const editingId = ref(0)
const editFormRef = ref()

const typeLabel = { topic_scanner: '选题', content_writer: '长文', video_writer: '视频', social_writer: '社交', seo_writer: 'SEO' }

const editForm = reactive({
  name: '', agent_type: 'content_writer', preset_style: undefined,
  allowed_vars: [], instructions: '',
})

function loadData() {
  loading.value = true
  Promise.all([getAgents(), getStyles()]).then(([aRes, sRes]) => {
    const ad = aRes?.data || {}; presets.value = ad.presets || []; custom.value = ad.custom || []
    const sd = sRes?.data || {}; styleList.value = [...(sd.presets || []), ...(sd.custom || [])]
  }).finally(() => { loading.value = false })
}

function copyPreset(key) { copying.value = key; copyAgentPreset(key).then(() => { message.success('复制成功'); loadData(); copying.value = '' }).catch(() => { copying.value = '' }) }

function openAdd() {
  editingId.value = 0; modalVisible.value = true
  Object.assign(editForm, { name: '', agent_type: 'content_writer', preset_style: undefined, allowed_vars: [], instructions: '' })
}

function openEdit(record) {
  editingId.value = record.id; modalVisible.value = true
  Object.assign(editForm, {
    name: record.name || '', agent_type: record.agent_type || 'content_writer',
    preset_style: record.preset_style, allowed_vars: record.allowed_vars || [], instructions: record.instructions || '',
  })
}

function handleSave() {
  editFormRef.value.validate().then(() => {
    saving.value = true
    const fn = editingId.value ? editAgent : addAgent
    const vals = { ...editForm }
    if (editingId.value) vals.id = editingId.value
    fn(vals).then(() => { message.success('保存成功'); modalVisible.value = false; loadData() }).finally(() => { saving.value = false })
  }).catch(() => {})
}

function handleDelete(id) { deleteAgent(id).then(() => { message.success('已删除'); loadData() }) }

loadData()
</script>
<style>.agent-modal .ant-modal { width: 700px !important; }</style>
