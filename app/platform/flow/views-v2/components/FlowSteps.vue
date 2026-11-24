<template>
  <div class="flow-steps">
    <div class="fs-head">
      <div class="fs-title">审批流程</div>
      <div v-if="instance" class="fs-meta">
        <a-tag :color="statusColor(instance.status)">{{ instance.status_text }}</a-tag>
        <span class="muted">{{ instance.template_name }} v{{ instance.template_version }}</span>
      </div>
    </div>

    <a-spin :spinning="loading">
      <template v-if="instance">
        <a-steps :current="currentIndex" size="small" progress-dot class="fs-steps">
          <a-step v-for="step in steps" :key="step.node_key" :title="step.status === 'skipped' ? step.name + '（跳过）' : step.name"
            :status="stepStatus(step)" :description="step.assignees?.join('、') || ''" />
        </a-steps>

        <div v-if="myTasks.length" class="my-tasks">
          <div class="mt-title">待我处理</div>
          <div v-for="task in myTasks" :key="task.id" class="mt-item">
            <span>{{ task.node_name }}</span>
            <a-tag v-if="task.overdue" color="red">超时</a-tag>
            <span class="muted">{{ task.due_at_text || '' }}</span>
            <a-button size="small" type="primary" @click="goTask(task)">去处理</a-button>
          </div>
        </div>
      </template>
      <a-empty v-else :description="emptyText" />
    </a-spin>
  </div>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { getFlowSteps } from '../api'

const props = defineProps({
  bizType: { type: String, required: true },
  bizId: { type: [Number, String], required: true },
  emptyText: { type: String, default: '尚未进入流程' },
})

const router = useRouter()
const loading = ref(false)
const instance = ref(null)
const steps = ref([])
const myTasks = ref([])

const currentIndex = computed(() => {
  const idx = steps.value.findIndex(s => s.status === 'current')
  return idx >= 0 ? idx : steps.value.filter(s => s.status === 'done').length
})

function statusColor(status) {
  return { running: 'blue', completed: 'green', rejected: 'red', cancelled: 'default', terminated: 'orange' }[status] || 'default'
}
function stepStatus(step) {
  if (step.status === 'done') return 'finish'
  if (step.status === 'current') return 'process'
  if (step.status === 'skipped') return 'wait'
  return 'wait'
}

async function load() {
  if (!props.bizType || !props.bizId) return
  loading.value = true
  try {
    const res = await getFlowSteps(props.bizType, props.bizId)
    if (res?.status === 200) {
      instance.value = res.data?.instance || null
      steps.value = res.data?.steps || []
      myTasks.value = res.data?.my_tasks || []
    }
  } finally {
    loading.value = false
  }
}

function goTask(task) {
  router.push({ path: '/index/flow/task', query: { focus: task.id } })
}

defineExpose({ load })

watch(() => [props.bizType, props.bizId], load)
onMounted(load)
</script>

<style scoped>
.flow-steps { border: 1px solid #eef1f5; border-radius: 8px; padding: 12px 16px; background: #fff; }
.fs-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.fs-title { font-weight: 600; }
.fs-meta { display: flex; align-items: center; gap: 8px; }
.fs-steps { overflow-x: auto; padding-bottom: 4px; }
.my-tasks { margin-top: 14px; border-top: 1px dashed #eef1f5; padding-top: 10px; }
.mt-title { font-size: 12px; color: #8a949e; margin-bottom: 6px; }
.mt-item { display: flex; align-items: center; gap: 10px; padding: 6px 0; }
.muted { color: #8a949e; font-size: 12px; }
</style>
