<template>
  <a-card :bordered="false">
    <div class="table-operator">
      <a-button type="primary" @click="handleAdd"><plus-outlined /> 新增合同</a-button>
    </div>

    <div class="table-page-search-wrapper">
      <a-form layout="inline">
        <a-row :gutter="24">
          <a-col :md="8">
            <a-form-item label="合同号">
              <a-input v-model:value="queryParam.contract_no" allow-clear />
            </a-form-item>
          </a-col>
          <a-col :md="8">
            <a-form-item label="状态">
              <a-select v-model:value="queryParam.status" allow-clear placeholder="全部">
                <a-select-option value="">全部</a-select-option>
                <a-select-option :value="1">草稿</a-select-option>
                <a-select-option :value="2">已签订</a-select-option>
                <a-select-option :value="3">履行中</a-select-option>
                <a-select-option :value="4">已完成</a-select-option>
                <a-select-option :value="5">已终止</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :md="8">
            <a-button type="primary" @click="loadData(true)">查询</a-button>
            <a-button style="margin-left:8px" @click="resetSearch">重置</a-button>
          </a-col>
        </a-row>
      </a-form>
    </div>

    <a-table :columns="columns" :data-source="dataSource" :loading="loading" :pagination="pagination"
      :row-key="r => r.id" size="middle" @change="onTableChange">
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'contract_no'">
          <a @click="handleDetail(record)">{{ record.contract_no }}</a>
        </template>
        <template v-else-if="column.key === 'status'">
          <a-tag>{{ ['','草稿','已签订','履行中','已完成','已终止'][record.status] }}</a-tag>
        </template>
        <template v-else-if="column.key === 'contract_amount'">
          {{ (record.contract_amount || 0).toLocaleString('zh-CN', { minimumFractionDigits: 2 }) }}
        </template>
        <template v-else-if="column.key === 'action'">
          <a @click="handleEdit(record)">编辑</a>
          <a-divider type="vertical" />
          <a-popconfirm title="确认删除?" @confirm="handleDelete(record)">
            <a style="color:#ff4d4f">删除</a>
          </a-popconfirm>
        </template>
      </template>
    </a-table>
    <ContractForm v-model:open="formVisible" :mdl="selected" @ok="onFormOk" />
  </a-card>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { PlusOutlined } from '@ant-design/icons-vue'
import { getList, deleteById } from '@/api/crm/contract'
import ContractForm from './components/ContractForm.vue'

const router = useRouter()
const loading = ref(false)
const formVisible = ref(false)
const selected = ref(null)
const dataSource = ref([])
const queryParam = reactive({})
const pagination = reactive({ current: 1, pageSize: 15, total: 0, showSizeChanger: true, showTotal: t => `共 ${t} 条` })

const columns = [
  { title: '合同号', dataIndex: 'contract_no', key: 'contract_no', width: 180 },
  { title: '合同名称', dataIndex: 'contract_name', key: 'contract_name', width: 160 },
  { title: '客户', dataIndex: 'customer_name', key: 'customer_name', width: 160 },
  { title: '币种', dataIndex: 'currency', key: 'currency', width: 60 },
  { title: '金额', dataIndex: 'contract_amount', key: 'contract_amount', width: 120, align: 'right' },
  { title: '状态', dataIndex: 'status', key: 'status', width: 90 },
  { title: '签订日期', dataIndex: 'sign_date', key: 'sign_date', width: 110,
    customRender: ({ text }) => text ? new Date(text * 1000).toLocaleDateString() : '' },
  { title: '操作', key: 'action', width: 120 },
]

async function loadData(reset = false) {
  if (reset) pagination.current = 1
  loading.value = true
  try {
    const params = { page: pagination.current, limit: pagination.pageSize, ...queryParam }
    const res = await getList(params)
    if (res?.status === 200) {
      const list = res.data?.list
      dataSource.value = list?.data || []
      pagination.total = list?.total || 0
    }
  } finally { loading.value = false }
}

function onTableChange(pag) { pagination.current = pag.current; pagination.pageSize = pag.pageSize; loadData() }
function handleAdd() { selected.value = null; formVisible.value = true }
function handleEdit(r) { selected.value = r; formVisible.value = true }
function handleDetail(r) { router.push({ path: '/crm/contract/detail', query: { id: r.id } }) }
async function handleDelete(r) { deleteById(r.id).then(() => { message.success('删除成功'); loadData() }) }
function onFormOk() { formVisible.value = false; loadData() }
function resetSearch() { Object.keys(queryParam).forEach(k => delete queryParam[k]); loadData(true) }

onMounted(() => { loadData() })
</script>
