<template>
  <a-card :bordered="false">
    <a-page-header :title="'合同 ' + detail.contract_no" @back="() => router.go(-1)">
      <template #tags>
        <a-tag :color="statusColor">{{ detail.status_text }}</a-tag>
      </template>
      <template #extra>
        <a-select v-model:value="newStatus" style="width:100px" @change="doChangeStatus">
          <a-select-option :value="1">草稿</a-select-option>
          <a-select-option :value="2">已签订</a-select-option>
          <a-select-option :value="3">履行中</a-select-option>
          <a-select-option :value="4">已完成</a-select-option>
          <a-select-option :value="5">已终止</a-select-option>
        </a-select>
        <a-button @click="formVisible = true" style="margin-left:8px">编辑</a-button>
        <a-popconfirm title="删除?" @confirm="handleDelete">
          <a-button danger style="margin-left:8px">删除</a-button>
        </a-popconfirm>
      </template>
    </a-page-header>

    <a-descriptions bordered size="small" :column="2">
      <a-descriptions-item label="合同名称">{{ detail.contract_name }}</a-descriptions-item>
      <a-descriptions-item label="客户">{{ detail.customer_name }}</a-descriptions-item>
      <a-descriptions-item label="币种">{{ detail.currency }}</a-descriptions-item>
      <a-descriptions-item label="金额">{{ fmtAmount(detail.contract_amount) }}</a-descriptions-item>
      <a-descriptions-item label="签订日期">{{ fmt(detail.sign_date) }}</a-descriptions-item>
      <a-descriptions-item label="有效期">{{ fmt(detail.start_date) }} ~ {{ fmt(detail.end_date) }}</a-descriptions-item>
      <a-descriptions-item label="负责人">{{ detail.owner_user_name }}</a-descriptions-item>
      <a-descriptions-item label="关联订单">
        <a v-if="detail.order_id" @click="router.push({ path: '/crm/order/detail', query: { id: detail.order_id } })">{{ detail.order_id }}</a>
        <span v-else>-</span>
      </a-descriptions-item>
    </a-descriptions>

    <a-divider />
    <h4>产品明细</h4>
    <a-table :data-source="detail.items || []" :pagination="false" bordered size="small" row-key="id">
      <a-table-column title="#" key="idx" width="40" align="center">
        <template #default="{ index }">{{ index + 1 }}</template>
      </a-table-column>
      <a-table-column title="产品" data-index="product_name" key="product_name" />
      <a-table-column title="规格" data-index="specification" key="specification" />
      <a-table-column title="单位" data-index="unit" key="unit" width="60" />
      <a-table-column title="数量" data-index="quantity" key="quantity" width="80" align="right" />
      <a-table-column title="单价" data-index="unit_price" key="unit_price" width="100" align="right">
        <template #default="{ text }">{{ fmtAmount(text) }}</template>
      </a-table-column>
      <a-table-column title="小计" data-index="amount" key="amount" width="100" align="right">
        <template #default="{ text }">{{ fmtAmount(text) }}</template>
      </a-table-column>
    </a-table>

    <a-row v-if="detail.contract_content" style="margin-top:16px">
      <a-col :span="24"><strong>合同条款：</strong>{{ detail.contract_content }}</a-col>
    </a-row>

    <ContractForm v-model:open="formVisible" :mdl="detail" @ok="loadDetail" />
  </a-card>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import dayjs from 'dayjs'
import { getDetail, deleteById, changeStatus } from '@/api/crm/contract'
import ContractForm from './components/ContractForm.vue'
import { useTabsStore, tabKeyOf } from '@/stores/tabs'

const route = useRoute()
const router = useRouter()
const tabsStore = useTabsStore()
const detail = reactive({})
const formVisible = ref(false)
const newStatus = ref(null)

const statusColor = computed(() => {
  const m = { 1: 'default', 2: 'blue', 3: 'orange', 4: 'green', 5: 'red' }
  return m[detail.status] || 'default'
})

function fmt(ts) { return ts ? dayjs(ts * 1000).format('YYYY-MM-DD') : '-' }
function fmtAmount(v) { return (v || 0).toLocaleString('zh-CN', { minimumFractionDigits: 2 }) }

function loadDetail() {
  // 捕获页签 key：本页被 keep-alive 缓存，异步响应回来时 route 可能已变
  const tabKey = tabKeyOf(route)
  getDetail(route.query.id).then(r => {
    Object.assign(detail, r?.data?.detail || r?.data || {})
    newStatus.value = detail.status
    tabsStore.setTitle(tabKey, detail.contract_no)
  }).catch(() => {})
}
function doChangeStatus(s) {
  changeStatus(detail.id, s).then(() => { message.success('状态已更新'); loadDetail() })
}
function handleDelete() { deleteById(detail.id).then(() => { message.success('已删除'); router.go(-1) }) }

onMounted(() => { loadDetail() })
</script>
