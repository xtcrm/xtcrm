<template>
  <a-card :bordered="false">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
      <a-radio-group v-model:value="filterType" button-style="solid" size="small" @change="loadList">
        <a-radio-button :value="0">全部</a-radio-button>
        <a-radio-button :value="1">核心词</a-radio-button>
        <a-radio-button :value="2">长尾词</a-radio-button>
        <a-radio-button :value="3">行业词</a-radio-button>
        <a-radio-button :value="4">地域词</a-radio-button>
      </a-radio-group>
      <div>
        <a-button type="primary" @click="showAdd = true" style="margin-right:8px"><plus-outlined /> 添加关键词</a-button>
        <a-button @click="showImport = true">批量导入</a-button>
      </div>
    </div>

    <a-table :data-source="list" :loading="loading" :pagination="pagination" @change="onPageChange" row-key="id" size="small">
      <a-table-column title="关键词" data-index="keyword" key="keyword" />
      <a-table-column title="类型" data-index="type" key="type" width="100">
        <template #default="{ text }">{{ ['', '核心词', '长尾词', '行业词', '地域词'][text] }}</template>
      </a-table-column>
      <a-table-column title="搜索量" data-index="search_volume" key="search_volume" width="80" />
      <a-table-column title="难度" data-index="difficulty" key="difficulty" width="60" />
      <a-table-column title="分组" data-index="group_tag" key="group_tag" width="100" />
      <a-table-column title="操作" key="action" width="80">
        <template #default="{ record }">
          <a-popconfirm title="确认删除?" @confirm="handleDelete(record.id)"><a style="color:#ff4d4f">删除</a></a-popconfirm>
        </template>
      </a-table-column>
    </a-table>

    <!-- Add Modal -->
    <a-modal :open="showAdd" title="添加关键词" @ok="handleAdd" @cancel="showAdd = false" :confirm-loading="saving">
      <a-form ref="addFormRef" :model="addForm" :label-col="{ span: 6 }" :wrapper-col="{ span: 16 }">
        <a-form-item label="关键词" name="keyword" :rules="[{ required: true, message: '请输入' }]">
          <a-input v-model:value="addForm.keyword" />
        </a-form-item>
        <a-form-item label="类型" name="type"><a-select v-model:value="addForm.type"><a-select-option :value="1">核心词</a-select-option><a-select-option :value="2">长尾词</a-select-option><a-select-option :value="3">行业词</a-select-option><a-select-option :value="4">地域词</a-select-option></a-select></a-form-item>
        <a-form-item label="分组" name="group_tag"><a-input v-model:value="addForm.group_tag" placeholder="如：UV油墨" /></a-form-item>
      </a-form>
    </a-modal>

    <!-- Import Modal -->
    <a-modal :open="showImport" title="批量导入关键词" @ok="handleImport" @cancel="showImport = false" :confirm-loading="saving">
      <a-form-item label="类型"><a-select v-model:value="importType" style="width:120px"><a-select-option :value="2">长尾词</a-select-option><a-select-option :value="1">核心词</a-select-option><a-select-option :value="3">行业词</a-select-option><a-select-option :value="4">地域词</a-select-option></a-select></a-form-item>
      <a-textarea v-model:value="importText" :rows="10" placeholder="每行一个关键词" />
    </a-modal>
  </a-card>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { message } from 'ant-design-vue'
import { PlusOutlined } from '@ant-design/icons-vue'
import { getKeywords, addKeyword, deleteKeyword, batchImportKeywords } from './api'

const loading = ref(false)
const saving = ref(false)
const filterType = ref(0)
const list = ref([])
const pagination = reactive({ current: 1, pageSize: 20, total: 0 })
const showAdd = ref(false)
const showImport = ref(false)
const importType = ref(2)
const importText = ref('')
const addFormRef = ref()
const addForm = reactive({ keyword: '', type: 2, group_tag: '' })

function loadList(page = 1) {
  loading.value = true
  const params = { page, list_rows: pagination.pageSize }
  if (filterType.value > 0) params.type = filterType.value
  getKeywords(params).then(res => {
    const d = res?.data?.list || {}
    list.value = d.data || []
    pagination.total = d.total || 0
    pagination.current = d.current_page || page
  }).finally(() => { loading.value = false })
}

function onPageChange(p) { loadList(p.current) }
function handleAdd() {
  addFormRef.value.validate().then(() => {
    saving.value = true
    addKeyword({ ...addForm }).then(() => { message.success('添加成功'); showAdd.value = false; loadList() }).finally(() => { saving.value = false })
  }).catch(() => {})
}
function handleDelete(id) { deleteKeyword(id).then(() => { message.success('已删除'); loadList() }) }
function handleImport() {
  if (!importText.value.trim()) return message.warning('请输入关键词')
  saving.value = true
  batchImportKeywords(importText.value, importType.value).then(res => { message.success(res?.message || '导入完成'); showImport.value = false; importText.value = ''; loadList() }).finally(() => { saving.value = false })
}

onMounted(() => { loadList() })
</script>
