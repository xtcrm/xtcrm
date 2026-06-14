<template>
  <a-card :bordered="false">
    <a-page-header :title="'合同 ' + detail.contract_no" @back="() => router.go(-1)">
      <template #tags><a-tag>{{ detail.status_text }}</a-tag></template>
      <template #extra>
        <a-button @click="formVisible = true">编辑</a-button>
        <a-popconfirm title="删除?" @confirm="handleDelete">
          <a-button danger style="margin-left:8px">删除</a-button>
        </a-popconfirm>
      </template>
    </a-page-header>

    <a-descriptions bordered size="small" :column="2">
      <a-descriptions-item label="合同名称">{{ detail.contract_name }}</a-descriptions-item>
      <a-descriptions-item label="客户">{{ detail.customer_name }}</a-descriptions-item>
      <a-descriptions-item label="币种">{{ detail.currency }}</a-descriptions-item>
      <a-descriptions-item label="金额">{{ (detail.contract_amount || 0).toLocaleString('zh-CN', { minimumFractionDigits: 2 }) }}</a-descriptions-item>
      <a-descriptions-item label="签订日期">{{ fmt(detail.sign_date) }}</a-descriptions-item>
      <a-descriptions-item label="有效期">{{ fmt(detail.start_date) }} ~ {{ fmt(detail.end_date) }}</a-descriptions-item>
      <a-descriptions-item label="合同内容" :span="2">{{ detail.contract_content }}</a-descriptions-item>
    </a-descriptions>
  </a-card>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import dayjs from 'dayjs'
import { getDetail, deleteById } from '@/api/crm/contract'

const route = useRoute()
const router = useRouter()
const detail = reactive({})
const formVisible = ref(false)

function fmt(ts) { return ts ? dayjs(ts * 1000).format('YYYY-MM-DD') : '' }

function loadDetail() {
  getDetail(route.query.id).then(r => {
    Object.assign(detail, r?.data?.d || r?.data?.detail || r?.data || {})
  }).catch(() => {})
}
function handleDelete() { deleteById(detail.id).then(() => { message.success('已删除'); router.go(-1) }) }

onMounted(() => { loadDetail() })
</script>
