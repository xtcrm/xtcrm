<template>
  <a-card :bordered="false">
    <a-page-header :title="'报价单 ' + detail.quotation_no" @back="() => router.go(-1)">
      <template #tags><a-tag :color="statusColor">{{ detail.status_text }}</a-tag></template>
      <template #extra>
        <template v-if="detail.status === 1"><a-button type="primary" @click="doChangeStatus(2)">发送</a-button><a-divider type="vertical" /></template>
        <template v-if="detail.status === 2"><a-button type="primary" @click="doChangeStatus(3)">确认</a-button><a-button danger @click="doChangeStatus(4)" style="margin-left:8px">拒绝</a-button><a-divider type="vertical" /></template>
        <a-button @click="formVisible = true">编辑</a-button>
        <a-button type="primary" ghost @click="router.push({ path: '/crm/quotation/cn-preview', query: { id: detail.id } })" style="margin-left:8px">中文报价</a-button>
        <a-popconfirm title="确认删除?" @confirm="handleDelete"><a-button danger style="margin-left:8px">删除</a-button></a-popconfirm>
      </template>
    </a-page-header>
    <a-divider />
    <a-row :gutter="24">
      <a-col :span="8">
        <a-descriptions title="客户信息" bordered size="small" :column="1">
          <a-descriptions-item label="客户">{{ detail.customer_name }}</a-descriptions-item>
          <a-descriptions-item label="币种">{{ detail.currency }}</a-descriptions-item>
        </a-descriptions>
      </a-col>
      <a-col :span="8">
        <a-descriptions title="报价信息" bordered size="small" :column="1">
          <a-descriptions-item label="报价日期">{{ fmtDate(detail.quotation_date) }}</a-descriptions-item>
          <a-descriptions-item label="有效期">{{ detail.valid_days }}天（至{{ fmtDate(detail.quotation_date + detail.valid_days * 86400) }}）</a-descriptions-item>
          <a-descriptions-item label="负责人">{{ detail.owner_user_name }}</a-descriptions-item>
        </a-descriptions>
      </a-col>
      <a-col :span="8">
        <a-descriptions title="金额" bordered size="small" :column="1">
          <a-descriptions-item label="合计">{{ (detail.total_amount || 0).toLocaleString('zh-CN', { minimumFractionDigits: 2 }) }}</a-descriptions-item>
          <a-descriptions-item label="折扣">-{{ (detail.discount_amount || 0).toLocaleString('zh-CN', { minimumFractionDigits: 2 }) }}</a-descriptions-item>
          <a-descriptions-item label="折后"><strong>{{ (detail.final_amount || 0).toLocaleString('zh-CN', { minimumFractionDigits: 2 }) }}</strong></a-descriptions-item>
        </a-descriptions>
      </a-col>
    </a-row>
    <a-divider />
    <a-table :data-source="detail.items || []" :pagination="false" bordered size="small" row-key="id" :loading="loading">
      <a-table-column title="#" key="idx" width="40" align="center">
        <template #default="{ index }">{{ index + 1 }}</template>
      </a-table-column>
      <a-table-column title="产品名称" data-index="product_name" key="product_name" />
      <a-table-column title="规格" data-index="specification" key="specification" />
      <a-table-column title="单位" data-index="unit" key="unit" width="60" />
      <a-table-column title="数量" data-index="quantity" key="quantity" width="80" align="right" />
      <a-table-column title="单价" data-index="unit_price" key="unit_price" width="100" align="right">
        <template #default="{ text }">{{ (text || 0).toLocaleString('zh-CN', { minimumFractionDigits: 2 }) }}</template>
      </a-table-column>
      <a-table-column title="小计" data-index="amount" key="amount" width="100" align="right">
        <template #default="{ text }">{{ (text || 0).toLocaleString('zh-CN', { minimumFractionDigits: 2 }) }}</template>
      </a-table-column>
    </a-table>
    <a-row v-if="detail.remark" style="margin-top:16px"><a-col :span="24"><strong>备注：</strong>{{ detail.remark }}</a-col></a-row>
    <QuotationForm v-model:open="formVisible" :mdl="detail" @ok="loadDetail" />
  </a-card>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import dayjs from 'dayjs'
import { getDetail, deleteById, changeStatus } from '@/api/crm/quotation'
import QuotationForm from './components/QuotationForm.vue'

const route = useRoute()
const router = useRouter()
const loading = ref(false)
const detail = reactive({})
const formVisible = ref(false)

const statusColor = computed(() => {
  const m = { 1: 'default', 2: 'blue', 3: 'green', 4: 'red', 5: 'purple' }
  return m[detail.status] || 'default'
})

function fmtDate(ts) { return ts ? dayjs(ts * 1000).format('YYYY-MM-DD') : '' }

function loadDetail() {
  const id = route.query.id
  if (!id) return
  loading.value = true
  getDetail(id).then(res => { Object.assign(detail, res?.data?.detail || {}); loading.value = false }).catch(() => { loading.value = false })
}

function handleDelete() { deleteById(detail.id).then(() => { message.success('已删除'); router.go(-1) }) }
function doChangeStatus(s) { changeStatus(detail.id, s).then(() => { message.success('状态更新成功'); loadDetail() }) }

onMounted(() => { loadDetail() })
</script>
