<template>
  <a-card :bordered="false" class="intent-detail" :loading="loading">
    <template #title>
      <div class="head">
        <a-button size="small" @click="router.back()">← 返回</a-button>
        <span class="head-title">{{ intent.title || '需求详情' }}</span>
        <a-tag v-if="intent.status">{{ intent.status_text }}</a-tag>
      </div>
    </template>

    <a-descriptions :column="3" size="small" bordered style="margin-bottom:16px">
      <a-descriptions-item label="需求号">{{ intent.intent_no || '-' }}</a-descriptions-item>
      <a-descriptions-item label="客户">{{ intent.customer_name || '-' }}</a-descriptions-item>
      <a-descriptions-item label="服务品类">{{ intent.catalog_name || '-' }}</a-descriptions-item>
      <a-descriptions-item label="数量">{{ intent.quantity }} {{ intent.unit }}</a-descriptions-item>
      <a-descriptions-item label="承接人">{{ intent.assignee_user_name || '未指派' }}</a-descriptions-item>
      <a-descriptions-item label="销售负责人">{{ intent.sales_user_name || '-' }}</a-descriptions-item>
      <a-descriptions-item label="期望时间">{{ intent.expected_date ? new Date(intent.expected_date * 1000).toLocaleDateString() : '-' }}</a-descriptions-item>
      <a-descriptions-item label="所属项目">
        <a v-if="intent.project_id" @click="goProject">{{ '#' + intent.project_id }}</a>
        <span v-else>未立项</span>
      </a-descriptions-item>
      <a-descriptions-item label="关闭原因">{{ intent.closed_reason || '-' }}</a-descriptions-item>
      <a-descriptions-item label="客户要求" :span="3">{{ intent.description || '-' }}</a-descriptions-item>
    </a-descriptions>

    <a-tabs>
      <a-tab-pane key="quotes" :tab="`报价 ${quotations.length}`">
        <a-table :data-source="quotations" :pagination="false" row-key="id" size="small" bordered>
          <a-table-column title="报价单号" data-index="quotation_no" key="quotation_no" />
          <a-table-column title="金额" key="amount"><template #default="{ record }">{{ money(record.final_amount) }}</template></a-table-column>
          <a-table-column title="状态" data-index="status" key="status" width="90" />
          <a-table-column title="操作" key="action" width="80">
            <template #default="{ record }"><a @click="router.push({ path: '/crm/quotation/detail', query: { id: record.id } })">详情</a></template>
          </a-table-column>
        </a-table>
      </a-tab-pane>

      <a-tab-pane key="deliveries" :tab="`交付 ${deliveries.length}`">
        <a-table :data-source="deliveries" :pagination="false" row-key="id" size="small" bordered>
          <a-table-column title="工单号" data-index="ticket_no" key="ticket_no" />
          <a-table-column title="标题" data-index="title" key="title" />
          <a-table-column title="类型" key="kind">
            <template #default="{ record }">{{ record.kind === 2 ? '第' + record.period_no + '期' : '一次性' }}</template>
          </a-table-column>
          <a-table-column title="状态" data-index="status" key="status" width="100" />
          <a-table-column title="操作" key="action" width="80">
            <template #default="{ record }"><a @click="router.push({ path: '/app/workorder/detail', query: { id: record.id } })">详情</a></template>
          </a-table-column>
        </a-table>
      </a-tab-pane>

      <a-tab-pane key="activities" tab="活动">
        <a-timeline>
          <a-timeline-item v-for="item in activities" :key="item.id">
            <div class="tl-meta">{{ item.create_time ? new Date(item.create_time * 1000).toLocaleString() : '' }} · {{ item.event_type }}</div>
            <div class="tl-content">{{ eventText(item) }}</div>
          </a-timeline-item>
          <a-timeline-item v-if="!activities.length" color="gray">暂无记录</a-timeline-item>
        </a-timeline>
      </a-tab-pane>
    </a-tabs>
  </a-card>
</template>

<script setup>
import { ref, reactive, onMounted, onActivated } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { getDetail } from '@/api/crm/intent'
import { useTabsStore, tabKeyOf } from '@/stores/tabs'

const route = useRoute()
const router = useRouter()
const tabsStore = useTabsStore()

const loading = ref(false)
const intent = reactive({})
const quotations = ref([])
const deliveries = ref([])
const activities = ref([])

function money(v) { return Number(v || 0).toLocaleString('zh-CN', { minimumFractionDigits: 2 }) }

function eventText(item) {
  try {
    const d = typeof item.data === 'string' ? JSON.parse(item.data || '{}') : (item.data || {})
    return d.reason || d.summary || d.title || (d.from !== undefined ? `${d.from} → ${d.to}` : '') || ''
  } catch { return '' }
}

async function load() {
  const id = parseInt(route.query.id)
  if (!id) return
  loading.value = true
  try {
    const res = await getDetail(id)
    const d = res?.data || {}
    Object.keys(intent).forEach(k => delete intent[k])
    Object.assign(intent, d.intent || {})
    quotations.value = d.quotations || []
    deliveries.value = d.deliveries || []
    activities.value = d.activities || []
    if (intent.title) tabsStore.setTitle(tabKeyOf(route), intent.title)
  } catch (e) {
    console.error('[需求详情] 加载失败：', e)
  } finally { loading.value = false }
}

function goProject() {
  router.push({ path: '/crm/project/detail', query: { id: intent.project_id } })
}

onMounted(load)
onActivated(() => { if (route.query.id) load() })
</script>

<style scoped>
.head { display: flex; align-items: center; gap: 10px; }
.head-title { font-size: 16px; font-weight: 600; }
.tl-meta { font-size: 12px; color: #999; }
.tl-content { font-size: 13px; color: #333; margin-top: 2px; }
</style>
