<template>
  <a-card :bordered="false">
    <a-page-header :title="detail.lead_name || '线索详情'" @back="() => router.go(-1)">
      <template #tags>
        <a-tag :color="detail.status === 3 ? 'green' : detail.status === 4 ? 'default' : 'blue'">{{ detail.status_text }}</a-tag>
      </template>
      <template #extra>
        <a-button v-if="detail.status !== 3" type="primary" @click="handleConvert">转为客户</a-button>
        <a-button @click="formVisible = true" style="margin-left:8px">编辑</a-button>
        <a-popconfirm title="删除?" @confirm="handleDelete">
          <a-button danger style="margin-left:8px">删除</a-button>
        </a-popconfirm>
      </template>
    </a-page-header>

    <a-descriptions bordered size="small" :column="2">
      <a-descriptions-item label="线索名称">{{ detail.lead_name }}</a-descriptions-item>
      <a-descriptions-item label="公司">{{ detail.company_name || '未知' }}</a-descriptions-item>
      <a-descriptions-item label="联系人">{{ detail.contact_person }}</a-descriptions-item>
      <a-descriptions-item label="电话">{{ detail.contact_phone }}</a-descriptions-item>
      <a-descriptions-item label="职位">{{ detail.contact_position }}</a-descriptions-item>
      <a-descriptions-item label="来源">{{ detail.source }}</a-descriptions-item>
      <a-descriptions-item label="行业">{{ detail.industry }}</a-descriptions-item>
      <a-descriptions-item label="优先级">{{ detail.priority_text }}</a-descriptions-item>
      <a-descriptions-item label="预计金额">{{ (detail.expected_amount || 0).toLocaleString('zh-CN', { minimumFractionDigits: 2 }) }} 元</a-descriptions-item>
      <a-descriptions-item label="负责人">{{ detail.owner_user_name }}</a-descriptions-item>
      <a-descriptions-item label="下次跟进">{{ detail.next_follow_date ? new Date(detail.next_follow_date * 1000).toLocaleDateString() : '' }}</a-descriptions-item>
      <a-descriptions-item v-if="detail.customer_id" label="已转客户">
        <a @click="router.push({ path: '/crm/customer/detail', query: { id: detail.customer_id } })">#{{ detail.customer_id }}</a>
      </a-descriptions-item>
    </a-descriptions>

    <a-divider v-if="detail.requirement" />
    <p v-if="detail.requirement"><strong>需求描述：</strong>{{ detail.requirement }}</p>

    <LeadForm v-model:open="formVisible" :mdl="detail" @ok="loadDetail" />
  </a-card>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { getDetail, deleteById, convert } from '@/api/crm/lead'
import LeadForm from './components/LeadForm.vue'

const route = useRoute()
const router = useRouter()
const detail = reactive({})
const formVisible = ref(false)

function loadDetail() {
  getDetail(route.query.id).then(r => {
    Object.assign(detail, r?.data?.d || r?.data?.detail || r?.data || {})
  }).catch(() => {})
}

function handleConvert() {
  convert(detail.id).then(res => {
    message.success('已转为客户: ' + (res?.data?.customer_name || ''))
    loadDetail()
  })
}

function handleDelete() {
  deleteById(detail.id).then(() => { message.success('已删除'); router.go(-1) })
}

onMounted(() => { loadDetail() })
</script>
