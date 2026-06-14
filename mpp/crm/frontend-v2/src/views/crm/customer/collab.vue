<template>
  <a-card :bordered="false" title="协作客户">
    <p style="color:#999;margin-bottom:16px">同事共享给你的客户，你可以查看详情和跟进</p>

    <div class="table-page-search-wrapper">
      <a-form layout="inline">
        <a-row :gutter="24">
          <a-col :md="8" :sm="24">
            <a-form-item label="客户名称">
              <a-input v-model:value="queryParam.customer_name" allow-clear />
            </a-form-item>
          </a-col>
          <a-col :md="8" :sm="24">
            <span class="table-page-search-submitButtons">
              <a-button type="primary" @click="loadData(true)">查询</a-button>
              <a-button style="margin-left:8px" @click="resetSearch">重置</a-button>
            </span>
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
        <template v-if="column.key === 'collab_permission'">
          <a-tag :color="record.collab_permission === 2 ? 'blue' : ''">{{ record.collab_permission === 2 ? '可编辑' : '只读' }}</a-tag>
        </template>
      </template>
    </a-table>
  </a-card>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { getCollabList } from '@/api/crm/customer'

const router = useRouter()

const loading = ref(false)
const dataSource = ref([])
const queryParam = reactive({})
const pagination = reactive({ current: 1, pageSize: 15, total: 0, showSizeChanger: true, showTotal: t => `共 ${t} 条` })

const columns = [
  { title: '客户名称', dataIndex: 'customer_name', key: 'customer_name', width: 200 },
  { title: '行业', dataIndex: 'industry_name', key: 'industry_name', width: 100 },
  { title: '负责人', dataIndex: 'owner_user_name', key: 'owner_user_name', width: 100 },
  { title: '协作权限', dataIndex: 'collab_permission', key: 'collab_permission', width: 80 },
  { title: '未跟进', dataIndex: 'last_followup_time', key: 'last_followup_time', width: 80, align: 'center',
    customRender: ({ text }) => text ? Math.floor((Date.now() / 1000 - text) / 86400) + '天' : '-' },
]

async function loadData(reset = false) {
  if (reset) pagination.current = 1
  loading.value = true
  try {
    const params = { page: pagination.current, limit: pagination.pageSize, ...queryParam }
    const res = await getCollabList(params)
    if (res?.status === 200) {
      const list = res.data?.list
      dataSource.value = list?.data || []
      pagination.total = list?.total || 0
    }
  } finally { loading.value = false }
}

function onTableChange(pag) {
  pagination.current = pag.current
  pagination.pageSize = pag.pageSize
  loadData()
}

function handleDetail(record) {
  router.push({ path: '/crm/customer/detail', query: { id: record.id, from: 'collab' } })
}

function resetSearch() {
  Object.keys(queryParam).forEach(k => delete queryParam[k])
  loadData(true)
}

onMounted(() => { loadData() })
</script>
