<template>
  <a-card :bordered="false">
    <div class="toolbar">
      <a-tabs v-model:activeKey="scope" @change="loadData(true)">
        <a-tab-pane key="pending" tab="待我处理" />
        <a-tab-pane key="done" tab="我已处理" />
        <a-tab-pane key="created" tab="我发起的" />
      </a-tabs>
      <div class="filters">
        <a-select
          v-model:value="query.biz_type"
          placeholder="全部业务"
          allow-clear
          style="width: 180px"
          @change="loadData(true)"
        >
          <a-select-option v-for="b in bizTypes" :key="b.value" :value="b.value">
            {{ b.label }}<template v-if="b.count">（{{ b.count }}）</template>
          </a-select-option>
        </a-select>
        <a-input-search v-model:value="query.keyword" placeholder="节点/处理人/意见" style="width: 220px" allow-clear @search="loadData(true)" />
      </div>
    </div>

    <a-table :columns="columns" :data-source="list" :loading="loading" :pagination="pagination"
      :row-key="r => r.id" size="middle" @change="onTableChange">
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'biz'">
          <div class="title">{{ record.instance?.title || '—' }}</div>
          <div class="muted">
            <a-tag :color="bizColor(record.instance?.biz_type)">{{ record.instance?.biz_label }}</a-tag>
            {{ record.instance?.template_name }}
          </div>
        </template>
        <template v-else-if="column.key === 'node'">
          <div>{{ record.node_name }}</div>
          <div class="muted">{{ record.node_key }}</div>
        </template>
        <template v-else-if="column.key === 'status'">
          <a-tag :color="statusColor(record.status)">{{ record.status_text }}</a-tag>
          <a-tag v-if="record.overdue" color="red">已超时</a-tag>
        </template>
        <template v-else-if="column.key === 'due_at'">{{ record.due_at_text || '—' }}</template>
        <template v-else-if="column.key === 'action'">
          <a @click="openDetail(record)">查看</a>
          <template v-if="isOpen(record) && record.is_mine">
            <a-divider type="vertical" />
            <a @click="openDetail(record, true)">处理</a>
          </template>
        </template>
      </template>
    </a-table>

    <a-modal v-model:open="detailOpen" title="待办详情" width="800px" :footer="null">
      <a-spin :spinning="detailLoading">
        <template v-if="detail">
          <div class="detail-head">
            <div>
              <div class="detail-title">{{ detail.instance?.title }}</div>
              <div class="muted">{{ detail.instance?.biz_label }} · {{ detail.instance?.template_name }} v{{ detail.instance?.template_version }}</div>
            </div>
            <a v-if="detail.biz_url" :href="detail.biz_url" target="_blank">打开业务单</a>
          </div>

          <a-descriptions size="small" bordered :column="3" style="margin: 12px 0">
            <a-descriptions-item label="当前节点">{{ detail.task?.node_name }}</a-descriptions-item>
            <a-descriptions-item label="处理人">{{ detail.task?.assignee_name }}</a-descriptions-item>
            <a-descriptions-item label="状态">{{ detail.task?.status_text }}</a-descriptions-item>
            <a-descriptions-item label="截止时间">{{ detail.task?.due_at_text || '—' }}</a-descriptions-item>
            <a-descriptions-item label="发起时间">{{ detail.instance?.started_at_text }}</a-descriptions-item>
            <a-descriptions-item label="流程状态">{{ detail.instance?.status_text }}</a-descriptions-item>
          </a-descriptions>

          <template v-if="canAct">
            <div v-if="formFields.length" class="form-block">
              <div class="block-title">节点表单</div>
              <a-form layout="vertical" size="small">
                <a-form-item v-for="field in formFields" :key="field.key" :label="field.label" :required="field.required">
                  <a-textarea v-if="field.type === 'textarea'" v-model:value="formData[field.key]" :rows="3" />
                  <a-input-number v-else-if="field.type === 'number'" v-model:value="formData[field.key]" style="width: 100%" />
                  <a-input v-else-if="field.type === 'date'" v-model:value="formData[field.key]" type="date" />
                  <a-upload v-else-if="field.type === 'upload'" :show-upload-list="false" :before-upload="file => handleUpload(file, field.key)">
                    <a-button size="small"><upload-outlined /> {{ formData[field.key] ? '已上传（ID ' + formData[field.key] + '）' : '上传附件' }}</a-button>
                  </a-upload>
                  <a-input v-else v-model:value="formData[field.key]" />
                </a-form-item>
              </a-form>
            </div>
            <div class="form-block">
              <div class="block-title">处理意见</div>
              <a-textarea v-model:value="comment" :rows="3" placeholder="通过可留空；驳回必须填写意见" />
            </div>
            <div class="actions">
              <a-button v-for="action in detail.actions" :key="action" :type="action === 'reject' ? 'default' : 'primary'"
                :danger="action === 'reject'" :loading="submitting" @click="handleComplete(action)">
                {{ actionLabel(action) }}
              </a-button>
              <a-button v-if="detail.allow_transfer" @click="transferOpen = true">转交</a-button>
              <a-button v-if="detail.allow_add_sign" @click="addSignOpen = true">加签</a-button>
            </div>
          </template>
          <a-alert v-else-if="isOpen(detail.task)" type="warning" show-icon
            message="这条待办不在你名下，无法处理" style="margin-bottom: 12px" />

          <div class="block-title" style="margin-top: 16px">流程记录</div>
          <a-timeline>
            <a-timeline-item v-for="log in detail.logs" :key="log.id" :color="logColor(log.action)">
              <div class="log-line">
                <span class="log-action">{{ log.action_text }}</span>
                <span class="muted">{{ log.node_name }} · {{ log.operator_name }} · {{ log.create_time_text }}</span>
              </div>
              <div v-if="log.comment" class="log-comment">{{ log.comment }}</div>
            </a-timeline-item>
          </a-timeline>
        </template>
      </a-spin>
    </a-modal>

    <a-modal v-model:open="transferOpen" title="转交待办" :confirm-loading="transferring" @ok="handleTransfer">
      <a-form layout="vertical" size="small">
        <a-form-item label="转交给" required>
          <a-select v-model:value="targetUserId" show-search :filter-option="filterUser" placeholder="选择人员">
            <a-select-option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="说明">
          <a-input v-model:value="targetComment" />
        </a-form-item>
      </a-form>
    </a-modal>

    <a-modal v-model:open="addSignOpen" title="加签" :confirm-loading="addingSign" @ok="handleAddSign">
      <a-form layout="vertical" size="small">
        <a-form-item label="加签人" required>
          <a-select v-model:value="targetUserId" show-search :filter-option="filterUser" placeholder="选择人员">
            <a-select-option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="说明">
          <a-input v-model:value="targetComment" />
        </a-form-item>
      </a-form>
    </a-modal>
  </a-card>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { message } from 'ant-design-vue'
import { UploadOutlined } from '@ant-design/icons-vue'
import request from '@/utils/request'
import {
  getTaskList, getTaskDetail, getTaskCount, completeTask, transferTask, addSignTask, getTemplateMeta,
} from '../api'

const route = useRoute()
const scope = ref('pending')
const query = reactive({ keyword: '', biz_type: undefined })
const bizTypes = ref([])
const list = ref([])
const loading = ref(false)
const pagination = reactive({ current: 1, pageSize: 20, total: 0, showSizeChanger: true, showTotal: t => `共 ${t} 条` })

const columns = [
  { title: '业务', key: 'biz', ellipsis: true },
  { title: '节点', key: 'node', width: 150 },
  { title: '处理人', dataIndex: 'assignee_name', key: 'assignee_name', width: 110 },
  { title: '状态', key: 'status', width: 120 },
  { title: '截止时间', key: 'due_at', width: 150 },
  { title: '操作', key: 'action', width: 130 },
]

function bizColor(type) {
  return {
    fiscal_order: 'orange',
    workorder_ticket: 'blue',
    crm_service_ticket: 'green',
  }[type] || 'default'
}

async function loadCounts() {
  try {
    const res = await getTaskCount()
    if (res?.status === 200) bizTypes.value = res.data?.biz_types || []
  } catch (e) {
    console.error('loadTaskCount failed:', e)
  }
}

function statusColor(status) {
  return { pending: 'blue', claimed: 'cyan', done: 'green', rejected: 'red', transferred: 'orange', cancelled: 'default' }[status] || 'default'
}
function isOpen(task) {
  return ['pending', 'claimed'].includes(task?.status)
}
function logColor(action) {
  return { reject: 'red', terminate: 'red', approve: 'green', complete: 'green', sign: 'orange', pay: 'cyan' }[action] || 'gray'
}
function actionLabel(action) {
  return { approve: '通过', complete: '完成', sign: '确认签字', pay: '确认收款', reject: '驳回' }[action] || action
}

async function loadData(reset = false) {
  if (reset) pagination.current = 1
  loading.value = true
  try {
    const res = await getTaskList({ scope: scope.value, page: pagination.current, limit: pagination.pageSize, ...query })
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
const formData = reactive({})
const comment = ref('')
const submitting = ref(false)
const users = ref([])
const targetUserId = ref(undefined)
const targetComment = ref('')
const transferOpen = ref(false)
const addSignOpen = ref(false)
const transferring = ref(false)
const addingSign = ref(false)

const formFields = computed(() => Array.isArray(detail.value?.form_schema) ? detail.value.form_schema : [])
const canAct = computed(() => detail.value?.can_handle && isOpen(detail.value?.task))

async function openDetail(record) {
  detailOpen.value = true
  detailLoading.value = true
  detail.value = null
  comment.value = ''
  Object.keys(formData).forEach(k => delete formData[k])
  try {
    const res = await getTaskDetail(record.id)
    if (res?.status === 200) {
      detail.value = res.data
      for (const field of res.data.form_schema || []) {
        if (field.key in formData) continue
        if (field.type === 'date') {
          formData[field.key] = new Date().toISOString().slice(0, 10)
        } else if (field.key === 'paid_amount' && res.data.instance?.snapshot?.total_amount) {
          formData[field.key] = Number(res.data.instance.snapshot.total_amount)
        } else {
          formData[field.key] = undefined
        }
      }
    }
  } finally {
    detailLoading.value = false
  }
}

function validateForm() {
  for (const field of formFields.value) {
    if (!field.required) continue
    const value = formData[field.key]
    if (value === undefined || value === null || value === '') {
      message.warning(`请填写「${field.label}」`)
      return false
    }
  }
  return true
}

async function handleComplete(action) {
  if (action === 'reject' && !comment.value.trim()) return message.warning('驳回必须填写意见')
  if (action !== 'reject' && !validateForm()) return
  submitting.value = true
  try {
    const res = await completeTask({
      id: detail.value.task.id,
      action,
      comment: comment.value,
      form_data: JSON.stringify(formData),
    })
    if (res?.status === 200) {
      message.success(action === 'reject' ? '已驳回' : '已处理')
      detailOpen.value = false
      loadData()
      loadCounts()
    }
  } finally {
    submitting.value = false
  }
}

async function handleUpload(file, key) {
  const fd = new FormData()
  fd.append('iFile', file)   // 平台上传约定的文件域名
  try {
    const res = await request({ url: '/upload/file', method: 'post', data: fd, headers: { 'Content-Type': 'multipart/form-data' } })
    if (res?.status === 200) {
      formData[key] = res.data?.file_id || res.data?.id
      message.success('上传成功')
    }
  } catch (e) {
    message.error('上传失败')
  }
  return false
}

async function handleTransfer() {
  if (!targetUserId.value) return message.warning('请选择转交对象')
  transferring.value = true
  try {
    const res = await transferTask({ id: detail.value.task.id, target_user_id: targetUserId.value, comment: targetComment.value })
    if (res?.status === 200) {
      message.success('已转交')
      transferOpen.value = false
      detailOpen.value = false
      loadData()
      loadCounts()
    }
  } finally {
    transferring.value = false
  }
}

async function handleAddSign() {
  if (!targetUserId.value) return message.warning('请选择加签人')
  addingSign.value = true
  try {
    const res = await addSignTask({ id: detail.value.task.id, target_user_id: targetUserId.value, comment: targetComment.value })
    if (res?.status === 200) {
      message.success('已加签')
      addSignOpen.value = false
      detailOpen.value = false
      loadData()
      loadCounts()
    }
  } finally {
    addingSign.value = false
  }
}

function filterUser(input, option) {
  const u = users.value.find(x => x.id === option.value)
  return (u?.name || '').toLowerCase().includes(input.toLowerCase())
}

onMounted(async () => {
  const metaRes = await getTemplateMeta()
  if (metaRes?.status === 200) users.value = metaRes.data.users || []
  await loadCounts()
  await loadData()
  const focus = Number(route.query.focus || 0)
  if (focus) openDetail({ id: focus })
})
</script>

<style scoped>
.toolbar { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 8px; }
.toolbar :deep(.ant-tabs-nav) { margin-bottom: 0; }
.filters { display: flex; gap: 10px; }
.title { font-weight: 600; }
.muted { color: #8a949e; font-size: 12px; margin-top: 2px; }
.detail-head { display: flex; justify-content: space-between; align-items: flex-start; }
.detail-title { font-size: 16px; font-weight: 600; }
.form-block { margin-bottom: 12px; }
.block-title { font-weight: 600; margin-bottom: 8px; }
.actions { display: flex; gap: 8px; flex-wrap: wrap; margin: 12px 0; }
.log-line { display: flex; gap: 8px; align-items: baseline; flex-wrap: wrap; }
.log-action { font-weight: 600; }
.log-comment { color: #5c6670; font-size: 12px; margin-top: 2px; }
</style>
