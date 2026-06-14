<template>
  <a-card :bordered="false" title="公海池">
    <p style="color:#999;margin-bottom:16px">未分配负责人的客户，可认领到自己名下</p>

    <div class="table-page-search-wrapper">
      <a-form layout="inline">
        <a-row :gutter="24">
          <a-col :md="8" :sm="24">
            <a-form-item label="客户名称">
              <a-input v-model:value="queryParam.customer_name" placeholder="客户名称" allow-clear />
            </a-form-item>
          </a-col>
          <a-col :md="8" :sm="24">
            <a-button type="primary" @click="loadData(true)">查询</a-button>
            <a-button style="margin-left:8px" @click="resetSearch">重置</a-button>
          </a-col>
        </a-row>
      </a-form>
    </div>

    <a-table
      :columns="columns"
      :data-source="dataSource"
      :loading="loading"
      :pagination="pagination"
      :row-key="r => r.id"
      size="middle"
      @change="onTableChange"
    >
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'customer_name'">
          <a @click="handleDetail(record)">{{ record.customer_name }}</a>
        </template>
        <template v-if="column.key === 'action'">
          <a-button type="primary" size="small" @click="handleClaim(record)">认领</a-button>
        </template>
      </template>
    </a-table>
  </a-card>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { getPoolList, claim } from '@/api/crm/customer'

const router = useRouter()

const loading = ref(false)
const dataSource = ref([])
const queryParam = reactive({})
const pagination = reactive({ current: 1, pageSize: 15, total: 0, showSizeChanger: true, showTotal: t => `共 ${t} 条` })

const columns = [
  { title: '客户名称', dataIndex: 'customer_name', key: 'customer_name', width: 200 },
  { title: '行业', dataIndex: 'industry', key: 'industry', width: 100 },
  { title: '来源', dataIndex: 'source', key: 'source', width: 100 },
  { title: '掉入公海时间', dataIndex: 'enter_pool_time', key: 'enter_pool_time', width: 150,
    customRender: ({ text }) => text ? new Date(text * 1000).toLocaleDateString() : '' },
  { title: '操作', key: 'action', width: 100 },
]

async function loadData(reset = false) {
  if (reset) pagination.current = 1
  loading.value = true
  try {
    const params = { page: pagination.current, limit: pagination.pageSize, ...queryParam }
    const res = await getPoolList(params)
    if (res?.status === 200) {
      const list = res.data?.list || {}
      dataSource.value = list?.data || []
      pagination.total = list?.total || 0
    }
  } finally { loading.value = false }
}

function onTableChange(pag) { pagination.current = pag.current; pagination.pageSize = pag.pageSize; loadData() }
function handleDetail(record) { router.push({ path: '/crm/customer/detail', query: { id: record.id, from: 'pool' } }) }
async function handleClaim(record) {
  const res = await claim(record.id)
  if (res?.status === 200) { message.success('认领成功'); loadData() }
}
function resetSearch() { Object.keys(queryParam).forEach(k => delete queryParam[k]); loadData(true) }

onMounted(() => { loadData() })
</script>
