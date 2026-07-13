<template>
  <a-card :bordered="false" :loading="loading">
    <template #title>
      <a-space>
        <a @click="$router.back()"><arrow-left-outlined /></a>
        <span>{{ contact?.contact_name || '联系人详情' }}</span>
      </a-space>
    </template>

    <!-- 个人信息 -->
    <a-descriptions v-if="contact" :column="3" bordered size="small" title="个人信息">
      <a-descriptions-item label="姓名">{{ contact.contact_name }}</a-descriptions-item>
      <a-descriptions-item label="手机">{{ contact.mobile }}</a-descriptions-item>
      <a-descriptions-item label="性别">{{ ['未知','男','女'][contact.gender] || '未知' }}</a-descriptions-item>
      <a-descriptions-item label="生日">{{ contact.birthday || '-' }}</a-descriptions-item>
      <a-descriptions-item label="籍贯">{{ contact.hometown || '-' }}</a-descriptions-item>
      <a-descriptions-item label="身份证号">{{ contact.id_card || '-' }}</a-descriptions-item>
      <a-descriptions-item label="邮箱">{{ contact.email || '-' }}</a-descriptions-item>
      <a-descriptions-item label="微信">{{ contact.wechat || '-' }}</a-descriptions-item>
      <a-descriptions-item label="固话">{{ contact.telephone || '-' }}</a-descriptions-item>
      <a-descriptions-item label="家庭住址" :span="2">{{ contact.address || '-' }}</a-descriptions-item>
      <a-descriptions-item label="备注">{{ contact.remark || '-' }}</a-descriptions-item>
    </a-descriptions>

    <a-divider />

    <!-- 关联公司 -->
    <h4>关联公司 ({{ companies.length }})</h4>
    <a-table v-if="companies.length" :data-source="companies" :pagination="false" row-key="id" size="small" style="margin-top:12px">
      <a-table-column title="公司名" data-index="customer_name">
        <template #default="{ record }">
          <a @click="goCustomer(record.customer_id)">{{ record.customer_name }}</a>
        </template>
      </a-table-column>
      <a-table-column title="职位" data-index="position" />
      <a-table-column title="部门" data-index="department" />
      <a-table-column title="首要" data-index="is_primary" width="60">
        <template #default="{ text }"><a-tag v-if="text" color="blue">是</a-tag></template>
      </a-table-column>
    </a-table>
    <a-empty v-else description="暂未关联任何公司" />

    <a-divider v-if="companies.length" />

    <!-- 跟进记录 -->
    <h4 v-if="companies.length">最近跟进记录</h4>
    <a-timeline v-if="allFollowups.length" style="margin-top:12px">
      <a-timeline-item v-for="f in allFollowups" :key="f.id" :color="f.follow_type === 'phone' ? 'blue' : f.follow_type === 'visit' ? 'green' : 'gray'">
        <a-tag>{{ f.customer_name }}</a-tag>
        {{ f.follow_content }}
        <br /><small style="color:#999">{{ formatTime(f.create_time) }}</small>
      </a-timeline-item>
    </a-timeline>
    <a-empty v-else description="暂无跟进记录" />
  </a-card>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { ArrowLeftOutlined } from '@ant-design/icons-vue'
import { getDetail } from '@/api/crm/contact'
import dayjs from 'dayjs'

function formatTime(v) {
  if (!v) return ''
  const ts = typeof v === 'string' ? parseInt(v) : v
  return dayjs(ts * 1000).format('YYYY-MM-DD HH:mm')
}

const route = useRoute()
const router = useRouter()
const loading = ref(false)
const contact = ref(null)
const companies = ref([])

const allFollowups = computed(() => {
  const result = []
  companies.value.forEach(c => {
    (c.followups || []).forEach(f => {
      result.push({ ...f, customer_name: c.customer_name })
    })
  })
  result.sort((a, b) => b.id - a.id)
  return result.slice(0, 20)
})

function loadDetail() {
  const id = route.query.id
  if (!id) return
  loading.value = true
  getDetail(id).then(res => {
    const d = res?.data || res
    contact.value = d?.contact
    companies.value = d?.companies || []
  }).finally(() => { loading.value = false })
}

function goCustomer(customerId) {
  router.push({ name: 'customer-detail', query: { id: customerId } })
}

onMounted(loadDetail)
</script>
