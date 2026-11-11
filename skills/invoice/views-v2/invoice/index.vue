<template>
  <a-card :bordered="false">
    <div style="margin-bottom:12px">
      <a-button type="primary" @click="openAdd">录入发票</a-button>
    </div>

    <div class="table-page-search-wrapper">
      <a-row :gutter="12">
        <a-col :md="3">
          <a-select v-model:value="queryParam.direction" placeholder="进项/销项" allow-clear @change="refresh" style="width:100%">
            <a-select-option value="">全部</a-select-option>
            <a-select-option :value="1">进项</a-select-option>
            <a-select-option :value="2">销项</a-select-option>
          </a-select>
        </a-col>
        <a-col :md="3">
          <a-select v-model:value="queryParam.status" placeholder="状态" allow-clear @change="refresh" style="width:100%">
            <a-select-option value="">全部状态</a-select-option>
            <a-select-option :value="1">已录入</a-select-option>
            <a-select-option :value="3">已作废</a-select-option>
            <a-select-option :value="0">待审核</a-select-option>
            <a-select-option :value="2">已开票</a-select-option>
            <a-select-option :value="4">已驳回</a-select-option>
          </a-select>
        </a-col>
        <a-col :md="3">
          <a-input v-model:value="queryParam.invoice_number" placeholder="发票号码" allow-clear />
        </a-col>
        <a-col :md="3">
          <a-input v-model:value="queryParam.company_id" placeholder="公司ID" allow-clear />
        </a-col>
        <a-col :md="4">
          <a-button type="primary" @click="refresh">查询</a-button>
          <a-button style="margin-left:8px" @click="resetQuery();refresh()">重置</a-button>
        </a-col>
      </a-row>
    </div>
    <a-table :columns="columns" :data-source="list" :loading="loading" :pagination="pagination" :row-key="r => r.id" size="small" @change="handleTableChange">
      <template #bodyCell="{ column, record }">
        <template v-if="column.dataIndex === 'direction'">
          <a-tag :color="record.direction === 1 ? 'green' : 'orange'">{{ record.direction === 1 ? '进项' : '销项' }}</a-tag>
        </template>
        <template v-else-if="column.dataIndex === 'status'">
          <a-tag :color="statusColor[record.status]">{{ statusText[record.status] }}</a-tag>
        </template>
        <template v-else-if="column.dataIndex === 'action'">
          <a @click="handleDetail(record)">详情</a>
          <a-divider type="vertical" />
          <a-popconfirm title="确认删除?" @confirm="handleDelete(record)"><a class="danger">删除</a></a-popconfirm>
        </template>
      </template>
    </a-table>

    <!-- 录入弹窗 -->
    <a-modal title="录入发票" :open="addVisible" :confirm-loading="adding" @ok="handleAdd" @cancel="addVisible = false" width="520" wrapClassName="add-invoice-modal">
      <a-spin :spinning="ocrLoading" tip="识别中...">
        <a-form :model="addForm" layout="vertical">
          <!-- 上传图片 OCR -->
          <a-form-item label="拍照/上传识别">
            <a-upload :show-upload-list="false" :before-upload="doOcrUpload" accept="image/*">
              <a-button><cloud-upload-outlined /> 选择发票图片自动识别</a-button>
            </a-upload>
            <div v-if="ocrPreview" style="margin-top:8px">
              <a-image :src="ocrPreview" :width="80" :height="80" style="object-fit:cover;border-radius:4px" />
            </div>
          </a-form-item>
          <a-row :gutter="12">
            <a-col :span="12">
              <a-form-item label="公司"><a-select v-model:value="addForm.company_id" placeholder="选择公司" style="width:100%">
                <a-select-option v-for="c in companyOptions" :key="c.id" :value="c.id">{{ c.name }}</a-select-option>
              </a-select></a-form-item>
            </a-col>
            <a-col :span="12">
              <a-form-item label="进/销"><a-select v-model:value="addForm.direction" style="width:100%">
                <a-select-option :value="2">销项</a-select-option>
                <a-select-option :value="1">进项</a-select-option>
              </a-select></a-form-item>
            </a-col>
          </a-row>
          <a-row :gutter="12">
            <a-col :span="12">
              <a-form-item label="对方名称"><a-input v-model:value="addForm.buyer_name" /></a-form-item>
            </a-col>
            <a-col :span="12">
              <a-form-item label="对方税号"><a-input v-model:value="addForm.buyer_tax_number" /></a-form-item>
            </a-col>
          </a-row>
          <a-row :gutter="12">
            <a-col :span="8">
              <a-form-item label="金额"><a-input-number v-model:value="addForm.total_amount" :min="0" :step="0.01" style="width:100%" /></a-form-item>
            </a-col>
            <a-col :span="8">
              <a-form-item label="税率(%)"><a-input-number v-model:value="addForm.tax_rate" :min="0" :max="100" style="width:100%" /></a-form-item>
            </a-col>
            <a-col :span="8">
              <a-form-item label="发票号码"><a-input v-model:value="addForm.invoice_number" /></a-form-item>
            </a-col>
          </a-row>
          <!-- 商品明细 -->
          <a-form-item v-if="addForm.goods_info && addForm.goods_info.length" label="商品明细">
            <a-table :columns="goodsCols" :data-source="addForm.goods_info" :pagination="false" size="small" :row-key="(r,i)=>i" />
          </a-form-item>
          <a-form-item label="备注"><a-textarea v-model:value="addForm.remark" :rows="2" /></a-form-item>
        </a-form>
      </a-spin>
    </a-modal>
  </a-card>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { CloudUploadOutlined } from '@ant-design/icons-vue'
import { getInvoiceList, deleteById, getCompanyList } from '../api'
import request from '@/utils/request'

const router = useRouter()
const queryParam = reactive({})
const list = ref([])
const loading = ref(false)
const pagination = reactive({ current: 1, pageSize: 20, total: 0 })

const statusText = { 0: '待审核', 1: '已录入', 2: '已开票', 3: '已作废', 4: '已驳回' }
const statusColor = { 0: 'orange', 1: 'green', 2: 'blue', 3: 'default', 4: 'red' }

const columns = [
  { title: '进/销', dataIndex: 'direction', width: 70 },
  { title: '发票号码', dataIndex: 'invoice_number', width: 130 },
  { title: '销方', dataIndex: 'seller_name', width: 120 },
  { title: '购方', dataIndex: 'buyer_name' },
  { title: '金额', dataIndex: 'total_amount', width: 100 },
  { title: '状态', dataIndex: 'status', width: 80 },
  { title: '时间', dataIndex: 'create_time', width: 110 },
  { title: '操作', dataIndex: 'action', width: 150 },
]

function loadData() {
  loading.value = true
  const params = { page: pagination.current, pageSize: pagination.pageSize, ...queryParam }
  getInvoiceList(params).then(r => {
    const res = r?.data?.list || r?.data || {}
    list.value = res.data || res.list || []
    pagination.total = res.total || 0
  }).finally(() => { loading.value = false })
}

function handleTableChange(p) { pagination.current = p.current; loadData() }
function resetQuery() { Object.keys(queryParam).forEach(k => delete queryParam[k]) }
function refresh() { pagination.current = 1; loadData() }
function handleDetail(record) {
  if (!record?.id) return
  router.push({ name: 'invoice-detail', query: { id: record.id } })
}
const addVisible = ref(false)
const adding = ref(false)
const addForm = reactive({ company_id: null, direction: 2, buyer_name: '', buyer_tax_number: '', total_amount: null, tax_rate: null, invoice_number: '', remark: '', goods_info: [] })

const ocrLoading = ref(false)
const ocrPreview = ref('')
const companyOptions = ref([])
const goodsCols = [
  { title: '名称', dataIndex: 'name' },
  { title: '规格', dataIndex: 'quantity', width: 60 },
  { title: '单价', dataIndex: 'price' },
  { title: '金额', dataIndex: 'amount' },
  { title: '类目', dataIndex: 'category' },
]

function openAdd() {
  Object.assign(addForm, { company_id: null, direction: 2, buyer_name: '', buyer_tax_number: '', total_amount: null, tax_rate: null, invoice_number: '', remark: '', goods_info: [] })
  ocrPreview.value = ''
  if (!companyOptions.value.length) loadCompanyOptions()
  addVisible.value = true
}

function loadCompanyOptions() {
  getCompanyList({ page: 1, pageSize: 200 }).then(r => {
    const res = r?.data?.list || r?.data || {}
    companyOptions.value = (res.data || res.list || []).filter(c => c.status === 1)
  })
}

function doOcrUpload(file) {
  ocrLoading.value = true
  const formData = new FormData()
  formData.append('image', file)
  request({ url: '/invoice.invoice/addOcr', method: 'post', data: formData, headers: { 'Content-Type': 'multipart/form-data' } })
    .then(r => {
      const d = r?.data || r
      if (d.counterparty_name) addForm.buyer_name = d.counterparty_name
      if (d.counterparty_tax_number) addForm.buyer_tax_number = d.counterparty_tax_number
      if (d.total_amount) addForm.total_amount = Number(d.total_amount)
      if (d.tax_rate) addForm.tax_rate = Number(d.tax_rate)
      if (d.amount) addForm.amount = Number(d.amount)
      if (d.remark) addForm.remark = d.remark
      if (d.invoice_type) addForm.invoice_type = d.invoice_type
      if (d.goods_info && d.goods_info.length) addForm.goods_info = d.goods_info
      if (d.image_url) ocrPreview.value = d.image_url
      message.success('识别完成，请核对')
    })
    .finally(() => { ocrLoading.value = false })
  return false
}
function handleAdd() {
  if (!addForm.company_id) { message.warning('请选择公司'); return }
  if (!addForm.total_amount) { message.warning('请填写金额'); return }
  adding.value = true
  const data = { ...addForm }
  if (ocrPreview.value) data.attach_urls = [ocrPreview.value]
  if (data.goods_info && data.goods_info.length) {
    data.goods_info = JSON.stringify(data.goods_info)
  } else {
    delete data.goods_info
  }
  request({ url: '/invoice.invoice/add', method: 'post', data })
    .then(() => { message.success('录入成功'); addVisible.value = false; refresh() })
    .finally(() => { adding.value = false })
}

function handleDelete(record) {
  deleteById(record.id).then(() => { message.success('删除成功'); refresh() })
}

onMounted(loadData)
</script>
<style>
.add-invoice-modal .ant-modal { width: 520px !important; }
</style>
