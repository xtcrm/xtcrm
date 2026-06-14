<template>
  <a-card :bordered="false" title="知识库">
    <div class="table-operator">
      <a-button type="primary" @click="handleAdd"><plus-outlined /> 新增知识</a-button>
      <a-select v-model:value="filterCategory" placeholder="分类筛选" allow-clear style="width:160px;margin-left:12px" @change="onFilter">
        <a-select-option v-for="c in categories" :key="c" :value="c">{{ c }}</a-select-option>
      </a-select>
      <a-input-search v-model:value="filterKeyword" placeholder="搜索标题/内容" style="width:260px;margin-left:12px" @search="onFilter" />
    </div>

    <a-table :columns="columns" :data-source="list" :pagination="pagination" :loading="loading" row-key="id" size="middle" @change="onPageChange">
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'category'">
          <a-tag>{{ record.category }}</a-tag>
        </template>
        <template v-if="column.key === 'action'">
          <a @click="handleEdit(record)">编辑</a>
          <a-divider type="vertical" />
          <a-popconfirm title="确认删除?" @confirm="handleDelete(record)"><a style="color:#ff4d4f">删除</a></a-popconfirm>
        </template>
      </template>
    </a-table>

    <a-modal :title="editId ? '编辑' : '新增'" :open="visible" :confirm-loading="saving" @ok="handleSubmit" @cancel="visible = false" :width="900" :mask-closable="false" :destroy-on-close="true">
      <a-form ref="formRef" :model="formState" :label-col="{ span: 3 }" :wrapper-col="{ span: 20 }">
        <a-form-item label="标题" name="title" :rules="[{ required: true, message: '请输入标题' }]">
          <a-input v-model:value="formState.title" />
        </a-form-item>
        <a-form-item label="分类" name="category" :rules="[{ required: true, message: '请选择分类' }]">
          <a-select v-model:value="formState.category">
            <a-select-option v-for="c in categories" :key="c" :value="c">{{ c }}</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="标签" name="tags">
          <a-input v-model:value="formState.tags" placeholder="逗号分隔，如: PCB,油墨,快干" />
        </a-form-item>
        <a-form-item v-if="visible" label="内容" name="content" :rules="[{ required: true, message: '内容不能为空' }]">
          <RichEditor v-model="formState.content" :height="400" />
        </a-form-item>
        <a-form-item v-else label="内容" name="content" :rules="[{ required: true, message: '内容不能为空' }]">
          <a-textarea v-model:value="formState.content" placeholder="内容（打开编辑框使用富文本编辑器）" :rows="6" />
        </a-form-item>
      </a-form>
    </a-modal>
  </a-card>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { message } from 'ant-design-vue'
import { PlusOutlined } from '@ant-design/icons-vue'
import { getList, getCategories, add, edit, deleteById } from '@/api/crm/knowledge'
import { defineAsyncComponent } from 'vue'
const RichEditor = defineAsyncComponent(() => import('@/components/RichEditor/index.vue'))

const filterCategory = ref('')
const filterKeyword = ref('')
const categories = ref([])
const list = ref([])
const loading = ref(false)
const pagination = reactive({ current: 1, pageSize: 15, total: 0, showSizeChanger: true, showTotal: t => `共 ${t} 条` })

const columns = [
  { title: '标题', dataIndex: 'title', key: 'title', width: 260 },
  { title: '分类', dataIndex: 'category', key: 'category', width: 100 },
  { title: '标签', dataIndex: 'tags', key: 'tags', width: 160 },
  { title: '操作', key: 'action', width: 120 },
]

// Form
const formRef = ref()
const visible = ref(false)
const saving = ref(false)
const editId = ref(0)
const formState = reactive({ title: '', category: '', tags: '', content: '' })

function loadCategories() { getCategories().then(r => { categories.value = r?.data?.cats || [] }) }

function loadList() {
  loading.value = true
  const params = { page: pagination.current }
  if (filterCategory.value) params.category = filterCategory.value
  if (filterKeyword.value) params.keyword = filterKeyword.value
  getList(params).then(r => {
    const d = r?.data?.list || {}
    list.value = d.data || []
    pagination.total = d.total || 0
    loading.value = false
  }).catch(() => { loading.value = false })
}

function onFilter() { pagination.current = 1; loadList() }
function onPageChange(p) { pagination.current = p.current; loadList() }

function handleAdd() {
  editId.value = 0; visible.value = true
  formRef.value?.resetFields()
  Object.assign(formState, { title: '', category: '', tags: '', content: '' })
}
function handleEdit(row) {
  editId.value = row.id; visible.value = true
  Object.assign(formState, { title: row.title, category: row.category, tags: row.tags || '', content: row.content || '' })
}
function handleDelete(row) { deleteById(row.id).then(() => { message.success('已删除'); loadList() }) }

function handleSubmit() {
  formRef.value.validate().then((vals) => {
    saving.value = true
    const fn = editId.value ? edit : add
    if (editId.value) vals.id = editId.value
    fn(vals).then(() => { message.success('保存成功'); saving.value = false; visible.value = false; loadList() }).catch(() => { saving.value = false })
  }).catch(() => {})
}

onMounted(() => { loadCategories(); loadList() })
</script>
