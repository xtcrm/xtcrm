<template>
  <a-modal v-model:open="visible" title="发布前确认指派" width="820px"
    :confirm-loading="publishing" ok-text="确认并发布" cancel-text="取消"
    :ok-button-props="{ disabled: !canPublish }" @ok="handlePublish">
    <a-spin :spinning="loading">
      <a-alert v-if="!check.passed" type="error" show-icon style="margin-bottom: 12px"
        message="以下节点没有可用指派，请先修改节点或补角色/人员，改完再发布" />
      <a-alert v-else type="info" show-icon style="margin-bottom: 12px"
        message="请逐个确认节点的处理人。发布后新单立即使用，在途单仍按原版本执行。" />

      <a-table :columns="columns" :data-source="check.nodes" size="small" :pagination="false" :row-key="r => r.node_key">
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'assignees'">
            <span v-if="record.assignees && record.assignees.length">{{ record.assignees.join('、') }}</span>
            <span v-else class="muted">{{ record.message || '—' }}</span>
          </template>
          <template v-else-if="column.key === 'status'">
            <a-tag :color="statusColor(record.status)">{{ statusText(record.status) }}</a-tag>
          </template>
        </template>
      </a-table>

      <a-checkbox v-model:checked="confirmed" style="margin-top: 14px">
        我已确认以上节点的指派；系统会按业务单上的销售/复核人等字段动态解析的节点，我也确认业务单会正确填写。
      </a-checkbox>
    </a-spin>
  </a-modal>
</template>

<script setup>
import { computed, reactive, ref } from 'vue'
import { message } from 'ant-design-vue'
import { checkTemplateAssignment, publishTemplate } from '../api'

const emit = defineEmits(['published'])

const visible = ref(false)
const loading = ref(false)
const publishing = ref(false)
const confirmed = ref(false)
const templateId = ref(0)
const check = reactive({ passed: true, needs_confirm: false, errors: [], nodes: [] })

const columns = [
  { title: '节点', dataIndex: 'name', key: 'name', width: 150 },
  { title: '指派规则', dataIndex: 'rule_text', key: 'rule_text', width: 240 },
  { title: '当前解析', key: 'assignees' },
  { title: '状态', key: 'status', width: 110 },
]

const canPublish = computed(() => check.passed && confirmed.value && !loading.value && !publishing.value)

function statusText(status) {
  return { ok: '可解析', fallback: '将用兜底', dynamic: '运行时解析', error: '无可用指派' }[status] || status
}
function statusColor(status) {
  return { ok: 'green', fallback: 'orange', dynamic: 'blue', error: 'red' }[status] || 'default'
}

async function open(id) {
  templateId.value = id
  visible.value = true
  confirmed.value = false
  loading.value = true
  Object.assign(check, { passed: true, needs_confirm: false, errors: [], nodes: [] })
  try {
    const res = await checkTemplateAssignment(id)
    if (res?.status === 200) Object.assign(check, res.data)
  } finally {
    loading.value = false
  }
}

async function handlePublish() {
  if (!canPublish.value) return
  publishing.value = true
  try {
    const res = await publishTemplate({ id: templateId.value, as_default: 1, confirm_assignment: 1 })
    if (res?.status === 200) {
      message.success('发布成功')
      visible.value = false
      emit('published', res.data)
    }
  } finally {
    publishing.value = false
  }
}

defineExpose({ open })
</script>

<style scoped>
.muted { color: #8a949e; font-size: 12px; }
</style>
