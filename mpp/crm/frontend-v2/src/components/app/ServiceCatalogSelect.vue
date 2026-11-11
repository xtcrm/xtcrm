<template>
  <div class="catalog-select">
    <div class="cs-row">
      <a-select
        v-model:value="value"
        :options="options"
        :placeholder="placeholder"
        :disabled="disabled"
        allow-clear
        show-search
        style="flex:1"
        @change="handleChange"
      />
      <a-tooltip title="新建服务品类">
        <a-button size="small" :disabled="disabled" @click="openCreate">
          <template #icon><plus-outlined /></template>
        </a-button>
      </a-tooltip>
    </div>

    <div v-if="loaded && !options.length" class="cs-hint">
      还没有服务品类，<a @click="openCreate">新建一个</a>（也可以在「项目管理 → 服务目录」维护）
    </div>

    <a-modal v-model:open="createVisible" title="新建服务品类" :confirm-loading="saving" width="480px" @ok="submitCreate">
      <a-form :label-col="{ span: 6 }" :wrapper-col="{ span: 16 }">
        <a-form-item label="品类名称" required>
          <a-input v-model:value="createForm.name" placeholder="例如：注册公司 / 代理记账" />
        </a-form-item>
        <a-form-item label="标准价">
          <a-input-number v-model:value="createForm.price" :min="0" :precision="2" style="width:100%" />
          <div class="cs-hint" style="margin-top:4px">0 表示面议，可稍后在服务目录里补充</div>
        </a-form-item>
        <a-form-item label="计费周期">
          <a-select v-model:value="createForm.billing_cycle" style="width:100%">
            <a-select-option :value="1">一次性</a-select-option>
            <a-select-option :value="2">按月</a-select-option>
            <a-select-option :value="3">按季</a-select-option>
            <a-select-option :value="4">按年</a-select-option>
            <a-select-option :value="5">按次</a-select-option>
          </a-select>
        </a-form-item>
      </a-form>
    </a-modal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { message } from 'ant-design-vue'
import { PlusOutlined } from '@ant-design/icons-vue'
import { getList, add } from '@/api/crm/catalog'

const props = defineProps({
  modelValue: { type: [Number, String], default: undefined },
  placeholder: { type: String, default: '选择客户要办理的服务' },
  disabled: { type: Boolean, default: false },
})
const emit = defineEmits(['update:modelValue', 'change'])

const rows = ref([])
const loaded = ref(false)
const value = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

const options = computed(() => rows.value.map(t => ({
  value: t.id,
  label: Number(t.price) > 0 ? `${t.name} · ¥${Number(t.price).toLocaleString('zh-CN')}` : t.name,
})))

const createVisible = ref(false)
const saving = ref(false)
const createForm = reactive({ name: '', price: 0, billing_cycle: 1 })

function handleChange(v) {
  const row = rows.value.find(t => t.id === v) || null
  emit('change', row)
}

async function load() {
  try {
    const res = await getList()
    const list = Array.isArray(res?.data) ? res.data : (res?.data?.list || [])
    rows.value = list.filter(t => Number(t.status) === 1)
  } catch (e) {
    console.error('[服务品类] 加载失败：', e)
  } finally {
    loaded.value = true
  }
}

function openCreate() {
  createForm.name = ''
  createForm.price = 0
  createForm.billing_cycle = 1
  createVisible.value = true
}

async function submitCreate() {
  if (!createForm.name.trim()) { message.warning('请输入品类名称'); return }
  saving.value = true
  try {
    const res = await add({
      name: createForm.name.trim(),
      price: createForm.price || 0,
      billing_cycle: createForm.billing_cycle || 1,
    })
    const id = res?.data?.id
    message.success('已新建')
    createVisible.value = false
    await load()
    if (id) {
      emit('update:modelValue', id)
      handleChange(id)
    }
  } catch (e) {
    console.error('[服务品类] 新建失败：', e)
  } finally { saving.value = false }
}

onMounted(load)
defineExpose({ reload: load })
</script>

<style scoped>
.cs-row { display: flex; align-items: center; gap: 8px; }
.cs-hint { color: #999; font-size: 12px; line-height: 1.6; margin-top: 4px; }
.cs-hint a { color: #1677ff; }
</style>
