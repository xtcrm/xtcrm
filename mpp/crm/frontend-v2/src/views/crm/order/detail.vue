<template>
  <a-card :bordered="false">
    <a-page-header :title="'订单 ' + detail.order_no" @back="() => router.go(-1)">
      <template #tags>
        <a-tag>{{ detail.status_text }}</a-tag>
        <a-tag :color="detail.payment_status === 3 ? 'green' : detail.payment_status === 2 ? 'orange' : 'default'">{{ detail.payment_status_text }}</a-tag>
      </template>
      <template #extra>
        <a-select v-model:value="newStatus" style="width:100px" @change="changeStatus">
          <a-select-option :value="1">待确认</a-select-option>
          <a-select-option :value="2">生产中</a-select-option>
          <a-select-option :value="3">待发货</a-select-option>
          <a-select-option :value="4">已发货</a-select-option>
          <a-select-option :value="5">已完成</a-select-option>
          <a-select-option :value="6">已取消</a-select-option>
        </a-select>
        <a-button @click="formVisible = true" style="margin-left:8px">编辑</a-button>
        <a-popconfirm title="删除?" @confirm="handleDelete">
          <a-button danger style="margin-left:8px">删除</a-button>
        </a-popconfirm>
      </template>
    </a-page-header>

    <a-descriptions bordered size="small" :column="3">
      <a-descriptions-item label="客户">{{ detail.customer_name }}</a-descriptions-item>
      <a-descriptions-item label="币种">{{ detail.currency }}</a-descriptions-item>
      <a-descriptions-item label="金额">{{ (detail.final_amount || 0).toLocaleString('zh-CN', { minimumFractionDigits: 2 }) }}</a-descriptions-item>
      <a-descriptions-item label="已付">{{ (detail.paid_amount || 0).toLocaleString('zh-CN', { minimumFractionDigits: 2 }) }}</a-descriptions-item>
      <a-descriptions-item label="未付">{{ (detail.unpaid_amount || 0).toLocaleString('zh-CN', { minimumFractionDigits: 2 }) }}</a-descriptions-item>
      <a-descriptions-item label="备注">{{ detail.remark }}</a-descriptions-item>
    </a-descriptions>

    <a-divider />

    <a-table :data-source="detail.items || []" :pagination="false" bordered size="small" row-key="id">
      <a-table-column title="#" key="idx" width="40" align="center">
        <template #default="{ index }">{{ index + 1 }}</template>
      </a-table-column>
      <a-table-column title="产品" data-index="product_name" key="product_name" />
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
  </a-card>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { getDetail, deleteById, changeStatus } from '@/api/crm/order'

const route = useRoute()
const router = useRouter()
const detail = reactive({})
const formVisible = ref(false)
const newStatus = ref(null)

function loadDetail() {
  getDetail(route.query.id).then(r => {
    Object.assign(detail, r?.data?.d || r?.data?.detail || r?.data || {})
    newStatus.value = detail.status
  }).catch(() => {})
}

function handleDelete() { deleteById(detail.id).then(() => { message.success('已删除'); router.go(-1) }) }
function doChangeStatus(s) { changeStatus(detail.id, s).then(() => { message.success('已更新'); loadDetail() }) }

onMounted(() => { loadDetail() })
</script>
