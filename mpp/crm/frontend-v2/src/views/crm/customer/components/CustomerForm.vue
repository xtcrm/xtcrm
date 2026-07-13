<template>
  <!-- Page mode -->
  <a-card v-if="mode === 'page'" :bordered="false">
    <a-page-header :title="mdl?.id ? '编辑客户' : '新增客户'" @back="handleCancel" style="padding:0 0 16px" />
    <a-spin :spinning="loading">
      <CustomerFormFields ref="fieldsRef" :form-state="formState" :rules="customerNameRules"
        :dicts="dictOptions" :store-users="storeUsers" :dept-tree="deptTree"
        @set-dict-name="setDictName" @on-owner-change="onOwnerChange" />
      <a-row><a-col :span="24" style="text-align:center;padding-top:24px">
        <a-button type="primary" :loading="loading" @click="handleSubmit">保存</a-button>
        <a-button style="margin-left:8px" @click="handleCancel">取消</a-button>
      </a-col></a-row>
    </a-spin>
  </a-card>

  <!-- Modal mode -->
  <a-modal v-else :title="mdl?.id ? '编辑客户' : '新增客户'" :width="800" :open="open" :confirm-loading="loading"
    :mask-closable="false" @ok="handleSubmit" @cancel="handleCancel">
    <a-spin :spinning="loading">
      <CustomerFormFields ref="fieldsRef" :form-state="formState" :rules="customerNameRules"
        :dicts="dictOptions" :store-users="storeUsers" :dept-tree="deptTree"
        @set-dict-name="setDictName" @on-owner-change="onOwnerChange" />
    </a-spin>
  </a-modal>
</template>

<script setup>
import { ref, reactive, watch, nextTick } from 'vue'
import { message } from 'ant-design-vue'
import { add, edit, checkNameDup } from '@/api/crm/customer'
import { getAll as getConfigs } from '@/api/crm/config'
import { list as getStoreUsers } from '@/api/user'
import { getTreeList as getDepts } from '@/api/department'
import { useUserStore } from '@/stores/user'
import CustomerFormFields from './CustomerFormFields.vue'

const props = defineProps({
  open: Boolean,
  mdl: Object,
  mode: { type: String, default: 'modal' },
})
const emit = defineEmits(['update:open', 'ok'])

const fieldsRef = ref()
const loading = ref(false)
const storeUsers = ref([])
const deptTree = ref([])

const dictOptions = reactive({ industry: [], customer_level: [], customer_source: [], customer_group: [] })

const formState = reactive({
  customer_name: '', short_name: '',
  industry_id: undefined, industry: '',
  group_id: undefined, customer_group: '',
  level_id: undefined, level_name: '',
  source_id: undefined, source: '',
  tax_number: '', credit_limit: undefined, payment_terms: '', website: '',
  telephone: '', email: '', legal_representative: '', registered_capital: '', paid_in_capital: '',
  established_date: '', registration_status: undefined, business_registration_no: '',
  organization_code: '', business_term_start: '', business_term_end: '', taxpayer_qualification: undefined, insured_count: '',
  approval_date: '', registration_authority: '', national_industry: '',
  registered_address: '', business_scope: '', introduction: '',
  region_cascader: [], address: '',
  owner_user_id: useUserStore().userInfo?.store_user_id || undefined, owner_department_id: undefined, remark: '',
})

const customerNameRules = [
  { required: true, message: '请输入客户名称' },
  { validator: validateCustomerName, trigger: 'blur' },
]

function setDictName(dictType, idVal, nameField) {
  const name = resolveName(dictType, idVal)
  if (name) formState[nameField] = name
}
function resolveName(dictType, idVal) {
  const opt = (dictOptions[dictType] || []).find(o => o.value === idVal)
  return opt ? opt.name : ''
}
function onOwnerChange(userId) {
  const user = storeUsers.value.find(u => u.store_user_id === userId)
  if (user) formState.owner_department_id = user.department_id || undefined
}

function buildDeptTree(list, parentId = 0) {
  if (!Array.isArray(list)) return []
  return list.filter(d => d.parent_id === parentId).map(d => ({
    title: d.department_name, value: d.id, key: d.id, children: buildDeptTree(list, d.id),
  }))
}
async function validateCustomerName(_rule, value) {
  if (!value) return Promise.resolve()
  try {
    const res = await checkNameDup(value, props.mdl?.id || 0)
    if (res?.exists || res?.data?.exists) return Promise.reject(new Error('客户名称已存在'))
    return Promise.resolve()
  } catch { return Promise.resolve() }
}

function loadDicts() {
  getConfigs().then(res => {
    const data = res?.data || {}
    const toOpts = (arr) => (arr || []).filter(i => i.config_value).map(i => ({ value: parseInt(i.config_value), name: i.config_name }))
    dictOptions.industry = toOpts(data.industry)
    dictOptions.customer_level = toOpts(data.customer_level)
    dictOptions.customer_source = toOpts(data.customer_source)
    dictOptions.customer_group = toOpts(data.customer_group)
  })
}
function loadUsers() {
  getStoreUsers({}).then(res => {
    const list = res?.data?.list?.data || res?.data?.list || []
    storeUsers.value = list.filter(u => u.is_supplier !== 1)
    if (props.mode === 'page') {
      const uid = list.find(u => u.is_supplier !== 1)?.store_user_id
      if (uid && !formState.owner_user_id) formState.owner_user_id = uid
    }
  })
}
function loadDepts() {
  getDepts({}).then(res => {
    const list = res?.data?.list?.data || res?.data?.list || res?.data || []
    deptTree.value = buildDeptTree(list)
  })
}
loadDicts(); loadUsers(); loadDepts()

watch(() => props.open, (v) => {
  if (v && props.mdl?.id) {
    nextTick(() => {
      fieldsRef.value?.resetFields()
      const vals = { ...props.mdl }
      if (vals.province || vals.city || vals.area) {
        vals.region_cascader = fieldsRef.value?.getRegionIds({ province: vals.province, city: vals.city, area: vals.area }) || []
      }
      Object.assign(formState, {
        customer_name: vals.customer_name || '', short_name: vals.short_name || '',
        industry_id: vals.industry_id, industry: vals.industry || '',
        group_id: vals.group_id, customer_group: vals.customer_group || '',
        level_id: vals.level_id, level_name: vals.level_name || '',
        source_id: vals.source_id, source: vals.source || '',
        tax_number: vals.tax_number || '', credit_limit: vals.credit_limit, payment_terms: vals.payment_terms || '',
        website: vals.website || '', telephone: vals.telephone || '', email: vals.email || '',
        legal_representative: vals.legal_representative || '', registered_capital: vals.registered_capital || '',
        paid_in_capital: vals.paid_in_capital || '', established_date: vals.established_date || '',
        registration_status: vals.registration_status, business_registration_no: vals.business_registration_no || '',
        organization_code: vals.organization_code || '',
        business_term_start: (vals.business_term || '').split('至')[0] || '',
        business_term_end: (vals.business_term || '').split('至')[1] || '',
        taxpayer_qualification: vals.taxpayer_qualification, insured_count: vals.insured_count || '',
        approval_date: vals.approval_date || '', registration_authority: vals.registration_authority || '',
        national_industry: vals.national_industry || '', registered_address: vals.registered_address || '',
        business_scope: vals.business_scope || '', introduction: vals.introduction || '',
        region_cascader: vals.region_cascader || [], address: vals.address || '',
        owner_user_id: vals.owner_user_id, owner_department_id: vals.owner_department_id, remark: vals.remark || '',
      })
    })
  } else if (v) { // 新增模式：先清空 model 再重置 UI，避免上次编辑残留
    nextTick(() => {
      Object.assign(formState, {
        customer_name: '', short_name: '',
        industry_id: undefined, industry: '',
        group_id: undefined, customer_group: '',
        level_id: undefined, level_name: '',
        source_id: undefined, source: '',
        tax_number: '', credit_limit: undefined, payment_terms: '', website: '',
        telephone: '', email: '', legal_representative: '', registered_capital: '', paid_in_capital: '',
        established_date: '', registration_status: undefined, business_registration_no: '',
        organization_code: '', business_term_start: '', business_term_end: '', taxpayer_qualification: undefined, insured_count: '',
        approval_date: '', registration_authority: '', national_industry: '',
        registered_address: '', business_scope: '', introduction: '',
        region_cascader: [], address: '',
        owner_user_id: useUserStore().userInfo?.store_user_id || undefined, owner_department_id: undefined, remark: '',
      })
      fieldsRef.value?.resetFields()
    })
  }
})

function handleSubmit() {
  const fields = fieldsRef.value
  if (!fields?.validate) { message.error('表单未就绪'); return }
  fields.validate().then((values) => {
    loading.value = true
    const names = fields?.getRegionNames(values.region_cascader) || {}
    values.province = names.province || ''; values.city = names.city || ''; values.area = names.area || ''
    delete values.region_cascader
    values.industry = resolveName('industry', values.industry_id)
    values.source = resolveName('customer_source', values.source_id)
    values.customer_group = resolveName('customer_group', values.group_id)
    values.level_name = resolveName('customer_level', values.level_id)
    values.business_term = [formState.business_term_start, formState.business_term_end].filter(Boolean).join('至')
    const fn = props.mdl?.id ? edit : add
    if (props.mdl?.id) values.id = props.mdl.id
    fn(values).then(() => {
      message.success('保存成功')
      loading.value = false
      emit('update:open', false)
      emit('ok')
    }).catch((e) => { loading.value = false; message.error(e?.msg || e?.message || '保存失败') })
  }).catch((e) => {
    if (e?.errorFields) message.error('请完善必填项')
  })
}

function handleCancel() {
  emit('update:open', false)
}
</script>
