<template>
  <a-card :bordered="false">
    <div class="toolbar">
      <div class="filters">
        <a-select v-model:value="query.biz_type" placeholder="业务类型" allow-clear style="width: 160px" @change="loadData(true)">
          <a-select-option v-for="item in bizTypes" :key="item.value" :value="item.value">{{ item.label }}</a-select-option>
        </a-select>
        <a-select v-model:value="query.status" placeholder="流程状态" allow-clear style="width: 140px" @change="loadData(true)">
          <a-select-option value="running">进行中</a-select-option>
          <a-select-option value="completed">已完成</a-select-option>
          <a-select-option value="rejected">已驳回</a-select-option>
          <a-select-option value="terminated">已终止</a-select-option>
          <a-select-option value="cancelled">已取消</a-select-option>
        </a-select>
      </div>
      <a-button @click="loadData(true)">刷新</a-button>
    </div>

    <a-table :columns="columns" :data-source="list" :loading="loading" :pagination="pagination"
      :row-key="r => r.id" size="middle" @change="onTableChange">
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'title'">
          <div class="title">{{ record.title || '—' }}</div>
          <div class="muted">{{ record.biz_label }} · #{{ record.biz_id }}</div>
        </template>
        <template v-else-if="column.key === 'template'">
          <div>{{ record.template_name }}</div>
          <div class="muted">v{{ record.template_version }}</div>
        </template>
        <template v-else-if="column.key === 'status'">
          <a-tag :color="statusColor(record.status)">{{ record.status_text }}</a-tag>
        </template>
        <template v-else-if="column.key === 'started_at'">{{ record.started_at_text || '—' }}</template>
        <template v-else-if="column.key === 'action'">
          <a @click="openDetail(record)">查看</a>
          <template v-if="record.status === 'running'">
            <a-divider type="vertical" />
            <a style="color: #ff4d4f" @click="openTerminate(record)">终止</a>
          </template>
        </template>
      </template>
    </a-table>

    <a-modal v-model:open="detailOpen" title="流程实例" width="900px" :footer="null">
      <a-spin :spinning="detailLoading">
        <template v-if="detail">
          <div class="detail-head">
            <div>
              <div class="detail-title">{{ detail.instance?.title }}</div>
              <div class="muted">{{ detail.instance?.biz_label }} · {{ detail.template?.name }} v{{ detail.template?.version }}</div>
            </div>
            <div class="head-actions">
              <a-tag :color="statusColor(detail.instance?.status)">{{ detail.instance?.status_text }}</a-tag>
              <a v-if="detail.instance?.biz_url" :href="detail.instance.biz_url" target="_blank">打开业务单</a>
            </div>
          </div>

          <div class="steps">
            <a-steps :current="currentStep" size="small" progress-dot>
              <a-step v-for="step in detail.steps" :key="step.node_key" :title="step.status === 'skipped' ? step.name + '（跳过）' : step.name"
                :status="stepStatus(step)" :description="step.assignees?.join('、') || ''" />
            </a-steps>
          </div>

          <a-descriptions size="small" bordered :column="3" style="margin: 16px 0">
            <a-descriptions-item label="发起人">{{ detail.instance?.started_by_name || detail.instance?.started_by }}</a-descriptions-item>
            <a-descriptions-item label="发起时间">{{ detail.instance?.started_at_text }}</a-descriptions-item>
            <a-descriptions-item label="结束时间">{{ detail.instance?.finished_at_text || '—' }}</a-descriptions-item>
          </a-descriptions>

          <a-tabs size="small">
            <a-tab-pane key="tasks" tab="待办记录">
              <a-table :data-source="detail.tasks" :columns="taskColumns" size="small" :pagination="false" :row-key="r => r.id">
                <template #bodyCell="{ column, record }">
                  <template v-if="column.key === 'status'">
                    <a-tag>{{ record.status_text }}</a-tag>
                  </template>
                  <template v-else-if="column.key === 'completed_at'">{{ formatTime(record.completed_at) }}</template>
                </template>
              </a-table>
            </a-tab-pane>
            <a-tab-pane key="logs" tab="操作日志">
              <a-timeline>
                <a-timeline-item v-for="log in detail.logs" :key="log.id">
                  <div class="log-line">
                    <span class="log-action">{{ log.action_text }}</span>
                    <span class="muted">{{ log.node_name }} · {{ log.operator_name }} · {{ log.create_time_text }}</span>
                  </div>
                  <div v-if="log.comment" class="log-comment">{{ log.comment }}</div>
                </a-timeline-item>
              </a-timeline>
            </a-tab-pane>
            <a-tab-pane key="variables" tab="流程变量">
              <pre class="json">{{ JSON.stringify(detail.variables, null, 2) }}</pre>
            </a-tab-pane>
          </a-tabs>
        </template>
      </a-spin>
    </a-modal>

    <a-modal v-model:open="terminateOpen" title="终止流程" :confirm-loading="terminating" @ok="handleTerminate">
      <a-alert type="warning" show-icon message="终止后所有待办会被取消，业务状态按业务 Hook 处理。" style="margin-bottom: 12px" />
      <a-textarea v-model:value="terminateReason" :rows="3" placeholder="请填写终止原因（必填）" />
    </a-modal>
  </a-card>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { message } from 'ant-design-vue'
import { getInstanceList, getInstanceDetail, terminateFlow, getTemplateMeta } from '../api'

const route = useRoute()
const bizTypes = ref([])
const query = reactive({ biz_type: undefined, status: undefined })
const list = ref([])
const loading = ref(false)
const pagination = reactive({ current: 1, pageSize: 20, total: 0, showSizeChanger: true, showTotal: t => `共 ${t} 条` })

const columns = [
  { title: '业务', key: 'title', ellipsis: true },
  { title: '流程', key: 'template', width: 220 },
  { title: '状态', key: 'status', width: 100 },
  { title: '发起时间', key: 'started_at', width: 160 },
  { title: '操作', key: 'action', width: 120 },
]

const taskColumns = [
  { title: '节点', dataIndex: 'node_name', key: 'node_name', width: 140 },
  { title: '处理人', dataIndex: 'assignee_name', key: 'assignee_name', width: 120 },
  { title: '状态', key: 'status', width: 100 },
  { title: '意见', dataIndex: 'comment', key: 'comment', ellipsis: true },
  { title: '完成时间', key: 'completed_at', width: 160 },
]

function statusColor(status) {
  return { running: 'blue', completed: 'green', rejected: 'red', cancelled: 'default', terminated: 'orange' }[status] || 'default'
}
function stepStatus(step) {
  if (step.status === 'done') return 'finish'
  if (step.status === 'current') return 'process'
  if (step.status === 'skipped') return 'wait'
  return 'wait'
}
const currentStep = computed(() => {
  const steps = detail.value?.steps || []
  const idx = steps.findIndex(s => s.status === 'current')
  return idx >= 0 ? idx : steps.filter(s => s.status === 'done').length
})

function formatTime(ts) {
  if (!ts) return '—'
  const d = new Date(Number(ts) * 1000)
  const p = n => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}`
}

async function loadData(reset = false) {
  if (reset) pagination.current = 1
  loading.value = true
  try {
    const res = await getInstanceList({ page: pagination.current, limit: pagination.pageSize, ...query })
    if (res?.status === 200) {
      list.value = res.data?.list || []
      pagination.total = res.data?.total || 0
    }
  } finally {
    loading.value = false
  }
}
function onTableChange(pag) {
  pagination.current = pag.current
  pagination.pageSize = pag.pageSize
  loadData()
}

const detailOpen = ref(false)
const detailLoading = ref(false)
const detail = ref(null)

async function openDetail(record) {
  detailOpen.value = true
  detailLoading.value = true
  detail.value = null
  try {
    const res = await getInstanceDetail(record.id)
    if (res?.status === 200) detail.value = res.data
  } finally {
    detailLoading.value = false
  }
}

const terminateOpen = ref(false)
const terminating = ref(false)
const terminateReason = ref('')
const terminateTarget = ref(null)

function openTerminate(record) {
  terminateTarget.value = record
  terminateReason.value = ''
  terminateOpen.value = true
}

async function handleTerminate() {
  if (!terminateReason.value.trim()) return message.warning('请填写终止原因')
  terminating.value = true
  try {
    const res = await terminateFlow({ id: terminateTarget.value.id, reason: terminateReason.value })
    if (res?.status === 200) {
      message.success('已终止')
      terminateOpen.value = false
      loadData()
    }
  } finally {
    terminating.value = false
  }
}

onMounted(async () => {
  const metaRes = await getTemplateMeta()
  if (metaRes?.status === 200) bizTypes.value = metaRes.data.biz_types || []
  await loadData()
  const focus = Number(route.query.focus || 0)
  if (focus) openDetail({ id: focus })
})
</script>

<style scoped>
.toolbar { display: flex; justify-content: space-between; align-items: center; gap: 10px; margin-bottom: 16px; }
.filters { display: flex; gap: 10px; }
.title { font-weight: 600; }
.muted { color: #8a949e; font-size: 12px; margin-top: 2px; }
.detail-head { display: flex; justify-content: space-between; align-items: flex-start; }
.detail-title { font-size: 16px; font-weight: 600; }
.head-actions { display: flex; align-items: center; gap: 10px; }
.steps { margin-top: 18px; overflow-x: auto; }
.log-line { display: flex; gap: 8px; flex-wrap: wrap; align-items: baseline; }
.log-action { font-weight: 600; }
.log-comment { color: #5c6670; font-size: 12px; }
.json { background: #f7f9fb; padding: 12px; border-radius: 6px; font-size: 12px; max-height: 360px; overflow: auto; }
</style>
