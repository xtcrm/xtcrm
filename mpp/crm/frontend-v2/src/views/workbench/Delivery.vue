<template>
  <a-card :bordered="false" title="我的交付">
    <a-row :gutter="16" style="margin-bottom:16px">
      <a-col :span="8">
        <a-card size="small"><a-statistic title="我的项目" :value="pagination.total" /></a-card>
      </a-col>
      <a-col :span="8">
        <a-card size="small"><a-statistic title="进行中" :value="activeCount" /></a-card>
      </a-col>
      <a-col :span="8">
        <a-card size="small"><a-statistic title="待审核" :value="reviewCount" /></a-card>
      </a-col>
    </a-row>

    <a-form layout="inline" style="margin-bottom:16px">
      <a-form-item label="状态">
        <a-select v-model:value="queryParam.status" allow-clear placeholder="全部" style="width:120px">
          <a-select-option :value="2">进行中</a-select-option>
          <a-select-option :value="3">已完成</a-select-option>
          <a-select-option :value="1">潜在</a-select-option>
          <a-select-option :value="4">已取消</a-select-option>
        </a-select>
      </a-form-item>
      <a-form-item label="项目">
        <a-input v-model:value="queryParam.keyword" placeholder="编号 / 名称" allow-clear />
      </a-form-item>
      <a-form-item>
        <a-button type="primary" @click="loadData(true)">查询</a-button>
      </a-form-item>
    </a-form>
    <p style="color:#999;margin-bottom:12px">交付完成后需送审，审核通过才能完成项目。</p>

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
        <template v-if="column.key === 'title'">
          <a @click="goDetail(record)">{{ record.title }}</a>
          <div style="color:#999;font-size:12px">{{ record.project_no }}</div>
        </template>
        <template v-if="column.key === 'review'">
          <a-tag v-if="record.review_status === 2" color="green">已通过</a-tag>
          <a-tag v-else-if="record.review_status === 1" color="orange">待审核</a-tag>
          <a-tag v-else-if="record.review_status === 3" color="red">已退回</a-tag>
          <a-tag v-else>未送审</a-tag>
        </template>
      </template>
    </a-table>
  </a-card>
</template>

<script setup>
import { ref, reactive, computed, onActivated } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/user'
import { getList } from '@/api/crm/project'

const router = useRouter()
const userStore = useUserStore()
const loading = ref(false)
const dataSource = ref([])
const queryParam = reactive({})
const pagination = reactive({ current: 1, pageSize: 15, total: 0, showSizeChanger: true, showTotal: t => `共 ${t} 条` })

const activeCount = computed(() => dataSource.value.filter(r => Number(r.status) === 2).length)
const reviewCount = computed(() => dataSource.value.filter(r => Number(r.review_status) === 1).length)

const columns = [
  { title: '项目', key: 'title', width: 240 },
  { title: '客户', dataIndex: 'customer_name', key: 'customer_name', width: 200 },
  { title: '状态', dataIndex: 'status_text', key: 'status_text', width: 110 },
  { title: '审核', key: 'review', width: 110 },
  { title: '服务期', key: 'service', width: 200,
    customRender: ({ record }) => [fmt(record.service_start_time), fmt(record.service_end_time)].filter(Boolean).join(' ~ ') },
]

function fmt(t) {
  return t ? new Date(Number(t) * 1000).toLocaleDateString() : ''
}

async function loadData(reset = false) {
  if (reset) pagination.current = 1
  loading.value = true
  try {
    const params = {
      mine_user_id: userStore.userInfo?.store_user_id || 0,
      scope: 'delivery',
      page: pagination.current,
      list_rows: pagination.pageSize,
      ...queryParam,
    }
    const res = await getList(params)
    if (res?.status === 200) {
      const list = res.data?.list || {}
      dataSource.value = list.data || []
      pagination.total = list.total || 0
    }
  } finally { loading.value = false }
}

function onTableChange(pag) {
  pagination.current = pag.current
  pagination.pageSize = pag.pageSize
  loadData()
}

function goDetail(record) {
  router.push({ path: '/crm/project/detail', query: { id: record.id } })
}

loadData()
onActivated(() => loadData())
</script>
