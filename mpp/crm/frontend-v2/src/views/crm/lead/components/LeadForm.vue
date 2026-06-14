<template>
  <a-modal :title="mdl?.id ? '编辑线索' : '新增线索'" :width="600" :open="open" :confirm-loading="loading"
    :mask-closable="false" @ok="handleSubmit" @cancel="handleCancel">
    <a-spin :spinning="loading">
      <a-form ref="formRef" :model="formState" :label-col="{ span: 6 }" :wrapper-col="{ span: 18 }">
        <a-row :gutter="16">
          <a-col :span="24">
            <a-form-item label="线索名称" name="lead_name" :rules="[{ required: true, message: '请输入线索名称' }]">
              <a-input v-model:value="formState.lead_name" placeholder="如：张经理-UV油墨采购" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="联系人" name="contact_person">
              <a-input v-model:value="formState.contact_person" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="电话" name="contact_phone">
              <a-input v-model:value="formState.contact_phone" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="职位" name="contact_position">
              <a-input v-model:value="formState.contact_position" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="公司名称" name="company_name">
              <a-input v-model:value="formState.company_name" placeholder="未知可留空" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="来源" name="source">
              <a-select v-model:value="formState.source" placeholder="选择来源" allow-clear show-search :filter-option="filterOption">
                <a-select-option v-for="op in sourceOptions" :key="op.value" :value="op.value">{{ op.name }}</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="行业" name="industry">
              <a-input v-model:value="formState.industry" />
            </a-form-item>
          </a-col>
          <a-col :span="6">
            <a-form-item label="优先级" name="priority" label-col="{ span: 12 }" wrapper-col="{ span: 12 }">
              <a-select v-model:value="formState.priority">
                <a-select-option :value="1">低</a-select-option>
                <a-select-option :value="2">中</a-select-option>
                <a-select-option :value="3">高</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="6">
            <a-form-item label="状态" name="status" label-col="{ span: 12 }" wrapper-col="{ span: 12 }">
              <a-select v-model:value="formState.status">
                <a-select-option :value="1">新建</a-select-option>
                <a-select-option :value="2">跟进中</a-select-option>
                <a-select-option :value="4">已关闭</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="预计金额" name="expected_amount">
              <a-input-number v-model:value="formState.expected_amount" :min="0" :precision="2" style="width:100%" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="下次跟进" name="next_follow_date">
              <a-date-picker v-model:value="formState.next_follow_date" style="width:100%" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="负责人" name="owner_user_id">
              <a-select v-model:value="formState.owner_user_id" show-search :filter-option="filterOption" allow-clear>
                <a-select-option v-for="u in users" :key="u.store_user_id" :value="u.store_user_id">{{ u.real_name || u.user_name }}</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="24">
            <a-form-item label="需求描述" name="requirement" :label-col="{ span: 3 }" :wrapper-col="{ span: 20 }">
              <a-textarea v-model:value="formState.requirement" :rows="3" />
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
import { add, edit } from '@/api/crm/lead'
import { list as getUsers } from '@/api/user'
import { getAll as getConfigs } from '@/api/crm/config'

const props = defineProps({ open: Boolean, mdl: Object })
const emit = defineEmits(['update:open', 'ok'])

const formRef = ref()
const loading = ref(false)
const users = ref([])
const sourceOptions = ref([])

const formState = reactive({
  lead_name: '', contact_person: '', contact_phone: '', contact_position: '',
  company_name: '', source: undefined, industry: '',
  priority: 2, status: 1, expected_amount: undefined,
  next_follow_date: null, owner_user_id: undefined, requirement: '',
})

function loadUsers() {
  getUsers({}).then(r => {
    const l = r?.data?.list?.data || r?.data?.list || []
    users.value = l.filter(u => u.is_supplier !== 1)
  })
}

function loadSources() {
  getConfigs().then(res => {
    const data = res?.data || {}
    const arr = data.customer_source || []
    sourceOptions.value = arr.filter(i => i.config_value).map(i => ({
      value: parseInt(i.config_value), name: i.config_name,
    }))
  })
}

loadUsers()
loadSources()

watch(() => props.open, (v) => {
  if (v && props.mdl?.id) {
    nextTick(() => {
      formRef.value?.resetFields()
      const vals = { ...props.mdl }
      if (vals.next_follow_date) vals.next_follow_date = dayjs(vals.next_follow_date * 1000)
      if (vals.source != null) vals.source = parseInt(vals.source)
      Object.assign(formState, {
        lead_name: vals.lead_name || '',
        contact_person: vals.contact_person || '',
        contact_phone: vals.contact_phone || '',
        contact_position: vals.contact_position || '',
        company_name: vals.company_name || '',
        source: vals.source,
        industry: vals.industry || '',
        priority: vals.priority ?? 2,
        status: vals.status ?? 1,
        expected_amount: vals.expected_amount,
        next_follow_date: vals.next_follow_date || null,
        owner_user_id: vals.owner_user_id,
        requirement: vals.requirement || '',
      })
    })
  } else if (v) {
    nextTick(() => {
      formRef.value?.resetFields()
      formState.priority = 2
      formState.status = 1
    })
  }
})

function filterOption(input, option) {
  const label = option.children?.default?.() || option.label || ''
  return String(label).toLowerCase().indexOf(input.toLowerCase()) >= 0
}

function handleSubmit() {
  formRef.value.validate().then((vals) => {
    loading.value = true
    if (vals.next_follow_date) vals.next_follow_date = dayjs(vals.next_follow_date).format('YYYY-MM-DD')
    vals.source = vals.source ? parseInt(vals.source) : undefined
    const fn = props.mdl?.id ? edit : add
    if (props.mdl?.id) vals.id = props.mdl.id
    fn(vals).then(() => {
      message.success('保存成功')
      loading.value = false
      emit('update:open', false)
      emit('ok')
    }).catch(() => { loading.value = false })
  }).catch(() => {})
}

function handleCancel() { emit('update:open', false) }
</script>
