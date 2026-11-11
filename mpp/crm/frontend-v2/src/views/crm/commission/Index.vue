<template>
  <a-card :bordered="false" title="业绩明细">
    <p style="color:#999;margin-bottom:16px">
      业绩按回款确认：归属月 = 回款到账月；退款生成负数记录冲销，历史不重算。
    </p>

    <a-row :gutter="16" style="margin-bottom:16px">
      <a-col :span="8">
        <a-card size="small"><a-statistic title="本期业绩合计" :value="totalAmount" :precision="2" prefix="¥" /></a-card>
      </a-col>
      <a-col :span="8">
        <a-card size="small"><a-statistic title="本期明细条数" :value="pagination.total" /></a-card>
      </a-col>
      <a-col :span="8">
        <a-card size="small"><a-statistic title="涉及人数" :value="byUser.length" /></a-card>
      </a-col>
    </a-row>

    <a-form layout="inline" style="margin-bottom:16px">
      <a-form-item label="归属月">
        <a-input v-model:value="queryParam.month" placeholder="YYYY-MM" style="width:120px" allow-clear />
      </a-form-item>
      <a-form-item label="业务类型">
        <a-select v-model:value="queryParam.biz_type" allow-clear placeholder="全部" style="width:120px">
          <a-select-option :value="1">新签</a-select-option>
          <a-select-option :value="2">续费</a-select-option>
          <a-select-option :value="3">增购</a-select-option>
        </a-select>
      </a-form-item>
      <a-form-item>
        <a-button type="primary" @click="loadData(true)">查询</a-button>
      </a-form-item>
    </a-form>

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
        <template v-if="column.key === 'amount'">
          <span :style="{ color: record.amount < 0 ? '#f5222d' : '' }">{{ record.amount }}</span>
        </template>
        <template v-if="column.key === 'biz_type'">
          {{ bizText(record.biz_type) }}
        </template>
      </template>
    </a-table>
  </a-card>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { getRecords, getSummary } from '@/api/crm/commission'

const loading = ref(false)
const dataSource = ref([])
const byUser = ref([])
const queryParam = reactive({})
const pagination = reactive({ current: 1, pageSize: 20, total: 0, showSizeChanger: true, showTotal: t => `共 ${t} 条` })

const totalAmount = computed(() => byUser.value.reduce((s, r) => s + Number(r.total || 0), 0))

const columns = [
  { title: '归属月', dataIndex: 'confirm_month', key: 'confirm_month', width: 100 },
  { title: '业务类型', key: 'biz_type', width: 100 },
  { title: '归属人ID', dataIndex: 'user_id', key: 'user_id', width: 100 },
  { title: '岗位', dataIndex: 'post_code', key: 'post_code', width: 90 },
  { title: '比例%', dataIndex: 'ratio', key: 'ratio', width: 90 },
  { title: '本次回款', dataIndex: 'base_amount', key: 'base_amount', width: 120 },
  { title: '确认业绩', key: 'amount', width: 120 },
  { title: '备注', dataIndex: 'remark', key: 'remark' },
]

function bizText(v) {
  return { 1: '新签', 2: '续费', 3: '增购' }[v] || v
}

async function loadData(reset = false) {
  if (reset) pagination.current = 1
  loading.value = true
  try {
    const params = { page: pagination.current, list_rows: pagination.pageSize, ...queryParam }
    const [listRes, sumRes] = await Promise.allSettled([getRecords(params), getSummary(queryParam)])
    if (listRes.status === 'fulfilled' && listRes.value?.status === 200) {
      dataSource.value = listRes.value.data?.list || []
      pagination.total = listRes.value.data?.total || 0
    }
    if (sumRes.status === 'fulfilled' && sumRes.value?.status === 200) {
      byUser.value = sumRes.value.data?.by_user || []
    }
  } finally { loading.value = false }
}

function onTableChange(pag) {
  pagination.current = pag.current
  pagination.pageSize = pag.pageSize
  loadData()
}

loadData()
</script>
