<template>
  <a-card :bordered="false" title="审核队列">
    <p style="color:#999;margin-bottom:16px">
      全检：项目交付完成后必须送审通过才能完成。审核组可见全部待审，只有被指派的人能通过或退回。
    </p>

    <div class="table-page-search-wrapper">
      <a-form layout="inline">
        <a-row :gutter="24">
          <a-col :md="6" :sm="24">
            <a-form-item label="状态">
              <a-select v-model:value="queryParam.result" style="width:140px">
                <a-select-option :value="0">待审核</a-select-option>
                <a-select-option :value="1">已通过</a-select-option>
                <a-select-option :value="2">已退回</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :md="6" :sm="24">
            <a-button type="primary" @click="loadData(true)">查询</a-button>
          </a-col>
        </a-row>
      </a-form>
    </div>

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
        <template v-if="column.key === 'title'">
          <a @click="goProject(record)">{{ record.title || ('项目 #' + record.project_id) }}</a>
          <div style="color:#999;font-size:12px">{{ record.project_no }}</div>
        </template>
        <template v-if="column.key === 'waiting'">
          <span :style="{ color: record.waiting_seconds > 86400 ? '#f5222d' : '' }">{{ waitText(record.waiting_seconds) }}</span>
        </template>
        <template v-if="column.key === 'result'">
          <a-tag v-if="record.result === 0" color="orange">待审核</a-tag>
          <a-tag v-else-if="record.result === 1" color="green">已通过</a-tag>
          <a-tag v-else color="red">已退回</a-tag>
        </template>
        <template v-if="column.key === 'action'">
          <template v-if="record.result === 0">
            <a-button size="small" @click="openAssign(record)">指派</a-button>
            <a-button type="primary" size="small" style="margin-left:6px" @click="handlePass(record)">通过</a-button>
            <a-button danger size="small" style="margin-left:6px" @click="openReject(record)">退回</a-button>
          </template>
          <span v-else style="color:#999">{{ record.reason || '—' }}</span>
        </template>
      </template>
    </a-table>

    <a-modal v-model:open="assignOpen" title="指派审核人" :confirm-loading="saving" @ok="submitAssign">
      <a-form layout="vertical">
        <a-form-item label="项目">
          <a-input :value="current.project_no + ' ' + (current.title || '')" disabled />
        </a-form-item>
        <a-form-item label="审核人" required>
          <a-select v-model:value="assignForm.reviewer_user_id" show-search placeholder="选择审核人">
            <a-select-option v-for="u in storeUsers" :key="u.store_user_id" :value="u.store_user_id">
              {{ u.real_name || u.user_name }}
            </a-select-option>
          </a-select>
        </a-form-item>
      </a-form>
    </a-modal>

    <a-modal v-model:open="rejectOpen" title="退回原因" :confirm-loading="saving" @ok="submitReject">
      <a-textarea v-model:value="rejectReason" :rows="4" placeholder="必须填写退回原因，交付方据此整改" />
    </a-modal>
  </a-card>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { getReviewList, assignReviewer, reviewPass, reviewReject } from '@/api/crm/project'
import { list as getStoreUsers } from '@/api/user'

const router = useRouter()
const loading = ref(false)
const saving = ref(false)
const dataSource = ref([])
const storeUsers = ref([])
const queryParam = reactive({ result: 0 })
const pagination = reactive({ current: 1, pageSize: 15, total: 0, showSizeChanger: true, showTotal: t => `共 ${t} 条` })

const assignOpen = ref(false)
const rejectOpen = ref(false)
const current = ref({})
const assignForm = reactive({ reviewer_user_id: undefined })
const rejectReason = ref('')

const columns = [
  { title: '项目', key: 'title', width: 240 },
  { title: '送审人', dataIndex: 'submit_user_name', key: 'submit_user_name', width: 120 },
  { title: '送审时间', dataIndex: 'submit_time', key: 'submit_time', width: 120, customRender: ({ text }) => text ? new Date(text * 1000).toLocaleString() : '' },
  { title: '已等待', key: 'waiting', width: 110 },
  { title: '审核人', dataIndex: 'reviewer_user_name', key: 'reviewer_user_name', width: 120 },
  { title: '状态', key: 'result', width: 100 },
  { title: '操作', key: 'action', width: 200 },
]

function waitText(seconds) {
  const s = Number(seconds || 0)
  if (s < 3600) return Math.floor(s / 60) + ' 分钟'
  if (s < 86400) return Math.floor(s / 3600) + ' 小时'
  return Math.floor(s / 86400) + ' 天'
}

async function loadData(reset = false) {
  if (reset) pagination.current = 1
  loading.value = true
  try {
    const params = { page: pagination.current, list_rows: pagination.pageSize, ...queryParam }
    const res = await getReviewList(params)
    if (res?.status === 200) {
      dataSource.value = res.data?.list || []
      pagination.total = res.data?.total || 0
    }
  } finally { loading.value = false }
}

function onTableChange(pag) {
  pagination.current = pag.current
  pagination.pageSize = pag.pageSize
  loadData()
}

function goProject(record) {
  router.push({ path: '/crm/project/detail', query: { id: record.project_id } })
}

function ensureUsers() {
  if (storeUsers.value.length) return
  getStoreUsers({}).then(res => {
    const list = res?.data?.list?.data || res?.data?.list || []
    storeUsers.value = list.filter(u => u.is_supplier !== 1)
  }).catch(() => {})
}

function openAssign(record) {
  current.value = record
  assignForm.reviewer_user_id = undefined
  ensureUsers()
  assignOpen.value = true
}

async function submitAssign() {
  if (!assignForm.reviewer_user_id) return message.warning('请选择审核人')
  saving.value = true
  try {
    const res = await assignReviewer({ project_id: current.value.project_id, reviewer_user_id: assignForm.reviewer_user_id })
    if (res?.status === 200) {
      message.success('已指派')
      assignOpen.value = false
      loadData()
    } else {
      message.error(res?.message || '指派失败')
    }
  } catch (e) {
    message.error('指派失败')
  } finally { saving.value = false }
}

async function handlePass(record) {
  saving.value = true
  try {
    const res = await reviewPass(record.id)
    if (res?.status === 200) {
      message.success('已通过')
      loadData()
    } else {
      message.error(res?.message || '操作失败')
    }
  } catch (e) {
    message.error('操作失败')
  } finally { saving.value = false }
}

function openReject(record) {
  current.value = record
  rejectReason.value = ''
  rejectOpen.value = true
}

async function submitReject() {
  if (!rejectReason.value.trim()) return message.warning('请填写退回原因')
  saving.value = true
  try {
    const res = await reviewReject(current.value.id, rejectReason.value.trim())
    if (res?.status === 200) {
      message.success('已退回')
      rejectOpen.value = false
      loadData()
    } else {
      message.error(res?.message || '操作失败')
    }
  } catch (e) {
    message.error('操作失败')
  } finally { saving.value = false }
}

loadData()
</script>
