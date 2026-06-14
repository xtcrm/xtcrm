<template>
  <a-card :bordered="false">
    <div style="display:flex;justify-content:space-between;margin-bottom:16px">
      <a-input-search v-model:value="keyword" placeholder="搜索方案" @search="loadList" style="width:300px" />
      <a-button type="primary" @click="openAdd"><plus-outlined /> 新增方案</a-button>
    </div>

    <a-table :data-source="list" :loading="loading" :pagination="pagination" @change="onPageChange" row-key="id" size="small">
      <a-table-column title="方案名称" data-index="title" key="title" />
      <a-table-column title="适用行业" data-index="target_industry" key="target_industry" width="120" />
      <a-table-column title="排序" data-index="sort_order" key="sort_order" width="60" />
      <a-table-column title="操作" key="action" width="120">
        <template #default="{ record }">
          <a @click="openEdit(record)">编辑</a>
          <a-divider type="vertical" />
          <a-popconfirm title="确认删除?" @confirm="handleDelete(record.id)"><a style="color:#ff4d4f">删除</a></a-popconfirm>
        </template>
      </a-table-column>
    </a-table>

    <a-modal :open="modalVisible" :title="editingId ? '编辑方案' : '新增方案'" @ok="handleSave" @cancel="modalVisible = false"
      :confirm-loading="saving" width="640" wrap-class-name="solution-modal">
      <a-form ref="editFormRef" :model="editForm" :label-col="{ span: 5 }" :wrapper-col="{ span: 19 }">
        <a-form-item label="方案名称" name="title" :rules="[{ required: true, message: '请输入' }]">
          <a-input v-model:value="editForm.title" />
        </a-form-item>
        <a-form-item label="适用行业" name="target_industry">
          <a-input v-model:value="editForm.target_industry" placeholder="如：PCB制造" />
        </a-form-item>
        <a-form-item label="客户痛点" name="customer_pain_points">
          <a-textarea v-model:value="editForm.customer_pain_points" :rows="3" />
        </a-form-item>
        <a-form-item label="技术优势" name="advantages">
          <a-textarea v-model:value="editForm.advantages" :rows="3" />
        </a-form-item>
        <a-form-item label="对应产品">
          <div v-for="(p, idx) in products" :key="idx" style="display:flex;gap:6px;margin-bottom:6px">
            <a-input v-model:value="p.name" placeholder="产品名称" style="flex:1" />
            <a-button type="danger" size="small" @click="removeProduct(idx)" :disabled="products.length <= 1"><minus-outlined /></a-button>
          </div>
          <a-button type="dashed" size="small" @click="addProduct"><plus-outlined /> 添加产品</a-button>
        </a-form-item>
        <a-form-item label="客户案例">
          <div v-for="(c, idx) in solutionCases" :key="idx" style="display:flex;gap:6px;margin-bottom:6px">
            <a-input v-model:value="c.name" placeholder="客户名" style="width:160px" />
            <a-input v-model:value="c.result" placeholder="成果" style="flex:1" />
            <a-button type="danger" size="small" @click="removeCase(idx)" :disabled="solutionCases.length <= 1"><minus-outlined /></a-button>
          </div>
          <a-button type="dashed" size="small" @click="addCase"><plus-outlined /> 添加案例</a-button>
        </a-form-item>
        <a-form-item label="排序" name="sort_order">
          <a-input-number v-model:value="editForm.sort_order" :min="0" :max="999" />
        </a-form-item>
      </a-form>
    </a-modal>
  </a-card>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { message } from 'ant-design-vue'
import { PlusOutlined, MinusOutlined } from '@ant-design/icons-vue'
import { getSolutions, getSolutionDetail, addSolution, editSolution, deleteSolution } from './api'

const loading = ref(false)
const saving = ref(false)
const keyword = ref('')
const modalVisible = ref(false)
const editingId = ref(0)
const list = ref([])
const pagination = reactive({ current: 1, pageSize: 20, total: 0 })
const editFormRef = ref()
const editForm = reactive({ title: '', target_industry: '', customer_pain_points: '', advantages: '', sort_order: 0, products: '', customer_cases: '' })
const products = ref([{ name: '' }])
const solutionCases = ref([{ name: '', result: '' }])

function loadList(page = 1) {
  loading.value = true
  const params = { page, list_rows: pagination.pageSize }
  if (keyword.value) params.keyword = keyword.value
  getSolutions(params).then(res => {
    const d = res?.data?.list || {}
    list.value = d.data || []
    pagination.total = d.total || 0
    pagination.current = d.current_page || page
  }).finally(() => { loading.value = false })
}

function onPageChange(p) { loadList(p.current) }

function openAdd() {
  editingId.value = 0; products.value = [{ name: '' }]; solutionCases.value = [{ name: '', result: '' }]
  modalVisible.value = true
  Object.assign(editForm, { title: '', target_industry: '', customer_pain_points: '', advantages: '', sort_order: 0 })
}

function openEdit(record) {
  editingId.value = record.id; modalVisible.value = true
  getSolutionDetail(record.id).then(res => {
    const d = res?.data?.detail || {}
    try { if (d.products) { const p = JSON.parse(d.products); if (Array.isArray(p) && p.length) products.value = p } } catch (e) {}
    try { if (d.customer_cases) { const c = JSON.parse(d.customer_cases); if (Array.isArray(c) && c.length) solutionCases.value = c } } catch (e) {}
    Object.assign(editForm, d)
  })
}

function addProduct() { products.value.push({ name: '' }) }
function removeProduct(idx) { if (products.value.length > 1) products.value.splice(idx, 1) }
function addCase() { solutionCases.value.push({ name: '', result: '' }) }
function removeCase(idx) { if (solutionCases.value.length > 1) solutionCases.value.splice(idx, 1) }

function handleSave() {
  editFormRef.value.validate().then(() => {
    const vp = products.value.filter(p => p.name.trim())
    const vc = solutionCases.value.filter(c => c.name.trim())
    editForm.products = vp.length ? JSON.stringify(vp) : ''
    editForm.customer_cases = vc.length ? JSON.stringify(vc) : ''
    saving.value = true
    const fn = editingId.value ? editSolution : addSolution
    const vals = { ...editForm }
    if (editingId.value) vals.id = editingId.value
    fn(vals).then(() => { message.success('保存成功'); modalVisible.value = false; loadList() }).finally(() => { saving.value = false })
  }).catch(() => {})
}

function handleDelete(id) { deleteSolution(id).then(() => { message.success('已删除'); loadList() }) }

onMounted(() => { loadList() })
</script>
<style>.solution-modal .ant-modal { width: 640px !important; }</style>
