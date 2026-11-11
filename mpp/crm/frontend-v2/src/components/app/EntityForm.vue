<template>
  <a-form layout="vertical">
    <a-form-item v-for="f in fields" :key="f.key" :label="f.label" :required="f.required"
      :validate-status="errors[f.key] ? 'error' : ''" :help="errors[f.key]">
      <CustomerSelector v-if="isRefTo(f, 'crm_customer')" v-model="form[f.key]" />
      <ContactSelector v-else-if="isRefTo(f, 'crm_contact')" v-model="form[f.key]" />
      <UserSelector v-else-if="isRefTo(f, 'user') || isRefTo(f, 'store_user')" v-model="form[f.key]" />
      <AppEntitySelector
        v-else-if="isEntityRef(f)"
        v-model="form[f.key]"
        :app-id="appId"
        :entity="getEntityRef(f)"
        :label-field="getLabelField(f)"
        :placeholder="'选择' + f.label"
      />
      <a-textarea v-else-if="f.type === 'text'" v-model:value="form[f.key]" :rows="3" />
      <a-input-number v-else-if="f.type === 'number' || f.type === 'decimal' || f.type === 'int'" v-model:value="form[f.key]" style="width:100%" />
      <a-select v-else-if="f.type === 'enum'" v-model:value="form[f.key]" allow-clear>
        <a-select-option v-for="v in fieldOptions(f)" :key="v" :value="v">{{ v }}</a-select-option>
      </a-select>
      <a-date-picker v-else-if="f.type === 'date'" v-model:value="form[f.key]" style="width:100%" value-format="YYYY-MM-DD" />
      <a-input v-else v-model:value="form[f.key]" :placeholder="f.label" />
    </a-form-item>
  </a-form>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import request from '@/utils/request'
import CustomerSelector from './CustomerSelector.vue'
import ContactSelector from './ContactSelector.vue'
import UserSelector from './UserSelector.vue'
import AppEntitySelector from './AppEntitySelector.vue'
import { getDicts } from '@/api/crm/appBuilder'

const props = defineProps({
  appId: { type: String, required: true },
  entity: { type: String, required: true },
  record: { type: Object, default: null },
  fields: { type: Array, default: null },  // 外部传入字段配置（优先），null 则从 schema 读
})
const emit = defineEmits(['submit'])
const form = reactive({})
const fields = ref([])
const errors = reactive({})
const dicts = reactive({})

// 字段选项：优先固定 values，其次字典
function fieldOptions(f) {
  if (f.values?.length) return f.values
  const key = f.dict || f.key
  return dicts[key] || []
}

// 加载字典
async function loadDicts() {
  try {
    const res = await getDicts(props.appId)
    const grouped = res?.data?.dicts || {}
    Object.entries(grouped).forEach(([k, items]) => { dicts[k] = items.map(i => i.label) })
  } catch {}
}

function isRefTo(f, source) {
  const ref = f.ref || f.relation
  if (!ref) return false
  const src = typeof ref === 'string' ? ref : (ref.source || '')
  return src === source
}
function isEntityRef(f) {
  const ref = f.ref || f.relation
  if (!ref) return false
  const src = typeof ref === 'string' ? ref : (ref.source || '')
  return String(src).startsWith('entity:')
}
function getEntityRef(f) {
  const ref = f.ref || f.relation
  const src = typeof ref === 'string' ? ref : (ref.source || '')
  return String(src).replace('entity:', '')
}
function getLabelField(f) {
  const ref = f.ref || f.relation
  return typeof ref === 'object' && ref.labelField ? ref.labelField : 'name'
}

async function loadSchema() {
  if (props.fields) {
    fields.value = props.fields
    initForm(props.record)
    return
  }
  try {
    const res = await request({ url: '/crm.appEngine/entitySchema', method: 'get',
      params: { app_id: props.appId, entity_key: props.entity } })
    fields.value = res?.data?.schema?.fields || []
    initForm(props.record)
  } catch {}
}

function initForm(r) {
  fields.value.forEach(f => { form[f.key] = r ? (r[f.key] || null) : null })
}

watch(() => props.record, (r) => { initForm(r) }, { immediate: true })
watch(() => props.fields, (f) => { if (f) { fields.value = f; initForm(props.record) } })

// Auto-calculate total = quantity × price (for stock_in / stock_out forms)
watch(
  () => [form.quantity, form.price],
  ([qty, price]) => {
    const numQty = parseFloat(qty)
    const numPrice = parseFloat(price)
    if (!isNaN(numQty) && !isNaN(numPrice) && numQty > 0 && numPrice > 0) {
      form.total = Math.round(numQty * numPrice * 100) / 100
    }
  }
)

function validate() {
  let ok = true
  fields.value.forEach(f => {
    if (f.required && !form[f.key]) { errors[f.key] = '必填'; ok = false }
    else errors[f.key] = ''
  })
  return ok
}

async function submitForm() {
  if (!validate()) return false
  try {
    if (props.record?.id) {
      await request({ url: '/crm.appEngine/dataUpdate', method: 'post',
        data: { app_id: props.appId, entity_key: props.entity, id: props.record.id, ...form } })
    } else {
      await request({ url: '/crm.appEngine/dataCreate', method: 'post',
        data: { app_id: props.appId, entity_key: props.entity, ...form } })
    }
    emit('submit')
    return true
  } catch { return false }
}

onMounted(() => { loadSchema(); loadDicts() })
defineExpose({ submit: submitForm, form, fields })
</script>
