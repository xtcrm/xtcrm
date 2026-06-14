<template>
  <a-card :bordered="false">
    <a-tabs default-active-key="presets">
      <a-tab-pane key="presets" tab="系统预设">
        <a-row :gutter="16">
          <a-col :md="8" v-for="s in presets" :key="s.preset_key">
            <a-card size="small" :title="s.name" style="margin-bottom:12px">
              <p style="color:#999;font-size:12px;min-height:36px">{{ s.role_desc }}</p>
              <p><a-tag>{{ s.tone }}</a-tag></p>
              <a-button type="primary" size="small" @click="copyPreset(s.preset_key)" :loading="copying === s.preset_key">复制到我的风格</a-button>
            </a-card>
          </a-col>
        </a-row>
      </a-tab-pane>
      <a-tab-pane key="custom" tab="我的风格">
        <a-button type="primary" @click="openAdd" style="margin-bottom:12px"><plus-outlined /> 新建风格</a-button>
        <a-table :data-source="custom" :loading="loading" row-key="id" size="small" :pagination="false">
          <a-table-column title="名称" data-index="name" key="name" />
          <a-table-column title="语气" data-index="tone" key="tone" width="80" />
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

    <a-modal :open="modalVisible" :title="editingId ? '编辑风格' : '新建风格'" @ok="handleSave" @cancel="modalVisible = false"
      :confirm-loading="saving" width="700">
      <a-form ref="editFormRef" :model="editForm" :label-col="{ span: 5 }" :wrapper-col="{ span: 19 }">
        <a-form-item label="名称" name="name" :rules="[{ required: true, message: '请输入' }]">
          <a-input v-model:value="editForm.name" />
        </a-form-item>
        <a-form-item label="人设" name="role_desc"><a-input v-model:value="editForm.role_desc" placeholder="如：行业技术顾问" /></a-form-item>
        <a-form-item label="语气" name="tone">
          <a-select v-model:value="editForm.tone">
            <a-select-option value="专业">专业</a-select-option><a-select-option value="亲切">亲切</a-select-option>
            <a-select-option value="犀利">犀利</a-select-option><a-select-option value="温和">温和</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="System Prompt" name="system_prompt">
          <a-textarea v-model:value="editForm.system_prompt" :rows="6" placeholder="支持{变量}占位符" />
        </a-form-item>
        <a-form-item label="禁用词" name="forbidden_words">
          <a-textarea v-model:value="editForm.forbidden_words" :rows="2" placeholder="逗号分隔" />
        </a-form-item>
      </a-form>
    </a-modal>
  </a-card>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { message } from 'ant-design-vue'
import { PlusOutlined } from '@ant-design/icons-vue'
import { getStyles, copyStylePreset, addStyle, editStyle, deleteStyle } from './api'

const loading = ref(false)
const saving = ref(false)
const copying = ref('')
const presets = ref([])
const custom = ref([])
const modalVisible = ref(false)
const editingId = ref(0)
const editFormRef = ref()
const editForm = reactive({ name: '', role_desc: '', tone: '专业', system_prompt: '', forbidden_words: '' })

function loadData() {
  loading.value = true
  getStyles().then(res => { const d = res?.data || {}; presets.value = d.presets || []; custom.value = d.custom || [] }).finally(() => { loading.value = false })
}

function copyPreset(key) { copying.value = key; copyStylePreset(key).then(() => { message.success('复制成功'); loadData(); copying.value = '' }).catch(() => { copying.value = '' }) }

function openAdd() { editingId.value = 0; modalVisible.value = true; Object.assign(editForm, { name: '', role_desc: '', tone: '专业', system_prompt: '', forbidden_words: '' }) }
function openEdit(record) { editingId.value = record.id; modalVisible.value = true; Object.assign(editForm, record) }

function handleSave() {
  editFormRef.value.validate().then(() => {
    saving.value = true
    const fn = editingId.value ? editStyle : addStyle
    const vals = { ...editForm }
    if (editingId.value) vals.id = editingId.value
    fn(vals).then(() => { message.success('保存成功'); modalVisible.value = false; loadData() }).finally(() => { saving.value = false })
  }).catch(() => {})
}

function handleDelete(id) { deleteStyle(id).then(() => { message.success('已删除'); loadData() }) }

loadData()
</script>
