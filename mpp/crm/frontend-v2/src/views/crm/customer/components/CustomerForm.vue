<template>
  <!-- Page mode -->
  <a-card v-if="mode === 'page'" :bordered="false">
    <a-page-header :title="mdl?.id ? '编辑客户' : '新增客户'" @back="handleCancel" style="padding:0 0 16px" />
    <a-spin :spinning="loading">
      <div style="margin-bottom:12px">
        <CompanySearchSelect v-model:value="companyCode" @select="handleCompanySelect" />
      </div>
      <CustomerFormFields ref="fieldsRef" :form-state="formState" :rules="customerNameRules"
        :dicts="dictOptions" :store-users="storeUsers" :dept-tree="deptTree"
        :extra="formState.extra" @update:extra="v => formState.extra = v"
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
      <div style="margin-bottom:12px">
        <CompanySearchSelect v-model:value="companyCode" @select="handleCompanySelect" />
      </div>
      <CustomerFormFields ref="fieldsRef" :form-state="formState" :rules="customerNameRules"
        :dicts="dictOptions" :store-users="storeUsers" :dept-tree="deptTree"
        :extra="formState.extra" @update:extra="v => formState.extra = v"
        @set-dict-name="setDictName" @on-owner-change="onOwnerChange" />
    </a-spin>
  </a-modal>
</template>

<script setup>
import { ref, reactive, watch, nextTick, onMounted } from 'vue'
import { message } from 'ant-design-vue'
import { add, edit, checkNameDup } from '@/api/crm/customer'
import { getAll as getConfigs } from '@/api/crm/config'
import { list as getStoreUsers } from '@/api/user'
import { getTreeList as getDepts } from '@/api/department'
import { useUserStore } from '@/stores/user'
import CustomerFormFields from './CustomerFormFields.vue'
import CompanySearchSelect from '@skills/entquery/views-v2/components/CompanySearchSelect.vue'
import { fillCompany } from '@skills/entquery/views-v2/api'

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
  owner_user_id: useUserStore().userInfo?.store_user_id || undefined, owner_department_id: undefined,
  cs_user_id: undefined, remark: '',
  extra: {},
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
  // 只在负责人确实有部门时覆盖；否则保留手动选择
  if (user && user.department_id) formState.owner_department_id = user.department_id
}

function toTreeData(list) {
  if (!Array.isArray(list)) return []
  return list.map(d => ({
    title: d.department_name,
    value: d.id,
    key: d.id,
    children: d.children && d.children.length ? toTreeData(d.children) : undefined,
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
    // 后端 /manage.department/tree 返回 { tree: [...] }，已是嵌套结构
    deptTree.value = toTreeData(res?.data?.tree || [])
  })
}
loadDicts(); loadUsers(); loadDepts()

// 提取为公共函数：将客户数据填充到表单
function populateForm(vals) {
  if (!vals) return
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
    owner_user_id: vals.owner_user_id, owner_department_id: vals.owner_department_id,
    cs_user_id: vals.cs_user_id || undefined, remark: vals.remark || '',
    extra: (() => { try { const e = vals.extra; if (typeof e === 'string' && e) { const d = e.replace(/&quot;/g, '"').replace(/&#039;/g, "'").replace(/&amp;/g, '&'); return JSON.parse(d) } if (typeof e === 'object' && e !== null) return e; return {} } catch { return {} } })(),
  })
}

// Modal 模式：监听 open 变化来填充/清空表单
watch([() => props.open, () => props.mdl], ([v]) => {
  if (v && props.mdl?.id) {
    nextTick(() => {
      fieldsRef.value?.resetFields()
      populateForm({ ...props.mdl })
    })
  } else if (v) {
    nextTick(() => {
      Object.assign(formState, {
        customer_name: '', short_name: '', industry_id: undefined, industry: '',
        group_id: undefined, customer_group: '', level_id: undefined, level_name: '',
        source_id: undefined, source: '', tax_number: '', credit_limit: undefined, payment_terms: '', website: '',
        telephone: '', email: '', legal_representative: '', registered_capital: '', paid_in_capital: '',
        established_date: '', registration_status: undefined, business_registration_no: '',
        organization_code: '', business_term_start: '', business_term_end: '', taxpayer_qualification: undefined, insured_count: '',
        approval_date: '', registration_authority: '', national_industry: '',
        registered_address: '', business_scope: '', introduction: '',
        region_cascader: [], address: '',
        owner_user_id: useUserStore().userInfo?.store_user_id || undefined, owner_department_id: undefined,
  cs_user_id: undefined, remark: '',
        extra: {},
      })
      fieldsRef.value?.resetFields()
    })
  }
})

// Page 模式：open 始终为 false，靠 mdl prop 变化和 onMounted 初始化
watch(() => props.mdl, (mdl) => {
  if (props.mode === 'page' && mdl) {
    nextTick(() => populateForm({ ...mdl }))
  }
})
onMounted(() => {
  if (props.mode === 'page' && props.mdl) {
    nextTick(() => populateForm({ ...props.mdl }))
  }
})

// ===== 企业查询（entquery skill）：选中企业后带出工商信息 =====
const companyCode = ref('')

/** 只填空字段，不静默覆盖用户已录内容 */
function applyCompanyFields(f) {
  if (!f) return
  Object.keys(f).forEach((k) => {
    if (k === 'region_cascader' || k === 'business_term') return
    const cur = formState[k]
    if (cur === undefined || cur === null || cur === '') formState[k] = f[k]
  })
  if (f.province || f.city || f.area) {
    const ids = fieldsRef.value?.getRegionIds({ province: f.province, city: f.city, area: f.area }) || []
    if (!formState.region_cascader?.length) formState.region_cascader = ids
  }
  if (f.business_term_start && !formState.business_term_start) formState.business_term_start = f.business_term_start
  if (f.business_term_end && !formState.business_term_end) formState.business_term_end = f.business_term_end
}

async function handleCompanySelect(company) {
  if (!company?.credit_code) return
  // 先用搜索结果里已有的字段免费回填
  applyCompanyFields({
    customer_name: company.name,
    tax_number: company.credit_code,
    registration_status: company.registration_status,
    legal_representative: company.legal_representative,
    established_date: company.establish_date,
  })
  try {
    const res = await fillCompany(company.credit_code)
    applyCompanyFields(res?.data?.fields || {})
    message.success(res?.data?.cache_hit ? '已从现有档案带出工商信息' : '已从工商库带出工商信息')
  } catch (e) {
    // 不静默：搜索到的字段已经填上，但完整工商信息失败要让用户知道
    message.error(e?.msg || e?.message || '带出完整工商信息失败，可稍后重试')
  }
}

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
    values.extra = JSON.stringify(formState.extra || {})
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
