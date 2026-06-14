<template>
  <a-modal :title="mdl?.id ? '编辑合同' : '新增合同'" :width="600" :open="open" :confirm-loading="loading"
    :mask-closable="false" @ok="handleSubmit" @cancel="handleCancel">
    <a-spin :spinning="loading">
      <a-form ref="formRef" :model="formState" :label-col="{ span: 6 }" :wrapper-col="{ span: 18 }">
        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item label="合同名称" name="contract_name" :rules="[{ required: true, message: '请输入' }]">
              <a-input v-model:value="formState.contract_name" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="客户" name="customer_id" :rules="[{ required: true, message: '请选择' }]">
              <a-select v-model:value="formState.customer_id" show-search placeholder="搜索客户" :filter-option="filterOption" allow-clear>
                <a-select-option v-for="c in customers" :key="c.id" :value="c.id">{{ c.customer_name }}</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="8">
            <a-form-item label="币种" name="currency">
              <a-select v-model:value="formState.currency">
                <a-select-option v-for="op in currencies" :key="op.config_value" :value="op.config_name">{{ op.config_name }}</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="8">
            <a-form-item label="合同金额" name="contract_amount">
              <a-input-number v-model:value="formState.contract_amount" :min="0" :precision="2" style="width:100%" />
            </a-form-item>
          </a-col>
          <a-col :span="8">
            <a-form-item label="状态" name="status">
              <a-select v-model:value="formState.status">
                <a-select-option :value="1">草稿</a-select-option>
                <a-select-option :value="2">已签订</a-select-option>
                <a-select-option :value="3">履行中</a-select-option>
                <a-select-option :value="4">已完成</a-select-option>
                <a-select-option :value="5">已终止</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="8">
            <a-form-item label="签订日期" name="sign_date">
              <a-date-picker v-model:value="formState.sign_date" style="width:100%" />
            </a-form-item>
          </a-col>
          <a-col :span="8">
            <a-form-item label="开始日期" name="start_date">
              <a-date-picker v-model:value="formState.start_date" style="width:100%" />
            </a-form-item>
          </a-col>
          <a-col :span="8">
            <a-form-item label="结束日期" name="end_date">
              <a-date-picker v-model:value="formState.end_date" style="width:100%" />
            </a-form-item>
          </a-col>
          <a-col :span="24">
            <a-form-item label="合同内容" name="contract_content" :label-col="{ span: 3 }" :wrapper-col="{ span: 20 }">
              <a-textarea v-model:value="formState.contract_content" :rows="4" />
            </a-form-item>
          </a-col>
        </a-row>
      </a-form>
    </a-spin>
  </a-modal>
</template>

<script setup>
import { ref, reactive, watch, nextTick } from 'vue'
import { message } from 'ant-design-vue'
import dayjs from 'dayjs'
import { add, edit } from '@/api/crm/contract'
import { getSelect as getCustomers } from '@/api/crm/customer'
import { getAll as getConfigs } from '@/api/crm/config'

const props = defineProps({ open: Boolean, mdl: Object })
const emit = defineEmits(['update:open', 'ok'])

const formRef = ref()
const loading = ref(false)
const customers = ref([])
const currencies = ref([])

const formState = reactive({
  contract_name: '', customer_id: undefined, currency: 'CNY',
  contract_amount: undefined, status: 1,
  sign_date: null, start_date: null, end_date: null, contract_content: '',
})

function loadData() {
  getCustomers().then(r => { customers.value = r?.data?.list || [] })
  getConfigs().then(r => { const d = r?.data || {}; currencies.value = (d.currency || []).filter(i => i.config_value) })
}
loadData()

function filterOption(input, option) {
  const label = option.children?.default?.() || option.label || ''
  return String(label).toLowerCase().indexOf(input.toLowerCase()) >= 0
}

watch(() => props.open, (v) => {
  if (v && props.mdl?.id) {
    nextTick(() => {
      formRef.value?.resetFields()
      const vals = { ...props.mdl }
      if (vals.sign_date) vals.sign_date = dayjs(vals.sign_date * 1000)
      if (vals.start_date) vals.start_date = dayjs(vals.start_date * 1000)
      if (vals.end_date) vals.end_date = dayjs(vals.end_date * 1000)
      Object.assign(formState, vals)
    })
  } else if (v) {
    nextTick(() => { formRef.value?.resetFields(); formState.currency = 'CNY'; formState.status = 1 })
  }
})

function handleSubmit() {
  formRef.value.validate().then((vals) => {
    loading.value = true
    ;['sign_date', 'start_date', 'end_date'].forEach(k => { if (vals[k]) vals[k] = dayjs(vals[k]).format('YYYY-MM-DD') })
    const fn = props.mdl?.id ? edit : add
    if (props.mdl?.id) vals.id = props.mdl.id
    fn(vals).then(() => { message.success('保存成功'); loading.value = false; emit('update:open', false); emit('ok') }).catch(() => { loading.value = false })
  }).catch(() => {})
}

function handleCancel() { emit('update:open', false) }
</script>
