<template>
  <a-modal
    :title="mdl?.id ? '编辑客户' : '新增客户'"
    :width="800"
    :open="open"
    :confirm-loading="loading"
    :mask-closable="false"
    @ok="handleSubmit"
    @cancel="handleCancel"
  >
    <a-spin :spinning="loading">
      <a-form ref="formRef" :model="formState" :label-col="{ span: 6 }" :wrapper-col="{ span: 18 }">
        <a-divider orientation="left">基本信息</a-divider>
        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item label="客户名称" name="customer_name" :rules="customerNameRules">
              <a-input v-model:value="formState.customer_name" placeholder="公司全称" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="简称" name="short_name">
              <a-input v-model:value="formState.short_name" placeholder="简称" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="行业" name="industry_id" :rules="[{ required: true, message: '请选择行业' }]">
              <a-select
                v-model:value="formState.industry_id"
                placeholder="选择行业"
                show-search
                :filter-option="filterOption"
                @change="(v) => setDictName('industry', v, 'industry')"
              >
                <a-select-option v-for="op in dictOptions.industry" :key="op.value" :value="op.value">{{ op.name }}</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="客户分组" name="group_id" :rules="[{ required: true, message: '请选择客户分组' }]">
              <a-select
                v-model:value="formState.group_id"
                placeholder="选择分组"
                show-search
                :filter-option="filterOption"
                @change="(v) => setDictName('customer_group', v, 'customer_group')"
              >
                <a-select-option v-for="op in dictOptions.customer_group" :key="op.value" :value="op.value">{{ op.name }}</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="客户等级" name="level_id" :rules="[{ required: true, message: '请选择客户等级' }]">
              <a-select
                v-model:value="formState.level_id"
                placeholder="选择等级"
                @change="(v) => setDictName('customer_level', v, 'level_name')"
              >
                <a-select-option v-for="op in dictOptions.customer_level" :key="op.value" :value="op.value">{{ op.name }}</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="客户来源" name="source_id" :rules="[{ required: true, message: '请选择客户来源' }]">
              <a-select
                v-model:value="formState.source_id"
                placeholder="选择来源"
                show-search
                :filter-option="filterOption"
                @change="(v) => setDictName('customer_source', v, 'source')"
              >
                <a-select-option v-for="op in dictOptions.customer_source" :key="op.value" :value="op.value">{{ op.name }}</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="税号" name="tax_number">
              <a-input v-model:value="formState.tax_number" placeholder="统一社会信用代码" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="信用额度" name="credit_limit">
              <a-input-number v-model:value="formState.credit_limit" :min="0" :precision="2" style="width: 100%" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="结算方式" name="payment_terms">
              <a-input v-model:value="formState.payment_terms" placeholder="如：月结30天、款到发货" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="网址" name="website">
              <a-input v-model:value="formState.website" placeholder="https://" />
            </a-form-item>
          </a-col>
        </a-row>

        <a-divider orientation="left">地址信息</a-divider>
        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item label="所在地区" name="region_cascader">
              <SelectRegion ref="selectRegionRef" v-model:value="formState.region_cascader" placeholder="省市区" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="详细地址" name="address">
              <a-input v-model:value="formState.address" placeholder="详细地址" />
            </a-form-item>
          </a-col>
        </a-row>

        <a-divider orientation="left">归属信息</a-divider>
        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item label="负责人" name="owner_user_id">
              <a-select
                v-model:value="formState.owner_user_id"
                placeholder="选择负责人"
                show-search
                :filter-option="filterOption"
                @change="onOwnerChange"
              >
                <a-select-option v-for="u in storeUsers" :key="u.store_user_id" :value="u.store_user_id">
                  {{ u.real_name || u.user_name }}
                </a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="负责人部门" name="owner_department_id">
              <a-tree-select
                v-model:value="formState.owner_department_id"
                placeholder="选负责人后自动填入，也可手动选"
                allow-clear
                tree-default-expand-all
                :tree-data="deptTree"
                :dropdown-style="{ maxHeight: '300px', overflow: 'auto' }"
              />
            </a-form-item>
          </a-col>
        </a-row>

        <a-divider orientation="left">备注</a-divider>
        <a-row :gutter="16">
          <a-col :span="24">
            <a-form-item label="备注" name="remark" :label-col="{ span: 2 }" :wrapper-col="{ span: 21 }">
              <a-textarea v-model:value="formState.remark" placeholder="备注信息" :rows="2" />
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
import { add, edit, checkNameDup } from '@/api/crm/customer'
import { getAll as getConfigs } from '@/api/crm/config'
import { list as getStoreUsers } from '@/api/user'
import { getTreeList as getDepts } from '@/api/department'
import { useUserStore } from '@/stores/user'
import SelectRegion from '@/components/SelectRegion/index.vue'

const props = defineProps({
  open: Boolean,
  mdl: Object,
})
const emit = defineEmits(['update:open', 'ok'])

// State
const formRef = ref()
const loading = ref(false)
const storeUsers = ref([])
const deptTree = ref([])
const selectRegionRef = ref()

const dictOptions = reactive({
  industry: [],
  customer_level: [],
  customer_source: [],
  customer_group: [],
})

const formState = reactive({
  customer_name: '',
  short_name: '',
  industry_id: undefined,
  industry: '',
  group_id: undefined,
  customer_group: '',
  level_id: undefined,
  level_name: '',
  source_id: undefined,
  source: '',
  tax_number: '',
  credit_limit: undefined,
  payment_terms: '',
  website: '',
  region_cascader: [],
  address: '',
  owner_user_id: undefined,
  owner_department_id: undefined,
  remark: '',
})

const customerNameRules = [
  { required: true, message: '请输入客户名称' },
  { validator: validateCustomerName, trigger: 'blur' },
]

// Init
function loadDicts() {
  getConfigs().then(res => {
    const data = res?.data || {}
    const toOpts = (arr) => (arr || []).filter(i => i.config_value).map(i => ({
      value: parseInt(i.config_value),
      name: i.config_name,
    }))
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
  })
}

function loadDepts() {
  getDepts({}).then(res => {
    const list = res?.data?.list?.data || res?.data?.list || res?.data || []
    deptTree.value = buildDeptTree(list)
  })
}

loadDicts()
loadUsers()
loadDepts()

// Watch visible — reset form when opened
watch(() => props.open, (v) => {
  if (v && props.mdl?.id) {
    nextTick(() => {
      formRef.value?.resetFields()
      const vals = { ...props.mdl }
      // region cascader: convert province/city/area names to IDs
      if (vals.province || vals.city || vals.area) {
        vals.region_cascader = selectRegionRef.value?.getIds({
          province: vals.province, city: vals.city, area: vals.area,
        }) || []
      }
      Object.assign(formState, {
        customer_name: vals.customer_name || '',
        short_name: vals.short_name || '',
        industry_id: vals.industry_id,
        industry: vals.industry || '',
        group_id: vals.group_id,
        customer_group: vals.customer_group || '',
        level_id: vals.level_id,
        level_name: vals.level_name || '',
        source_id: vals.source_id,
        source: vals.source || '',
        tax_number: vals.tax_number || '',
        credit_limit: vals.credit_limit,
        payment_terms: vals.payment_terms || '',
        website: vals.website || '',
        region_cascader: vals.region_cascader || [],
        address: vals.address || '',
        owner_user_id: vals.owner_user_id,
        owner_department_id: vals.owner_department_id,
        remark: vals.remark || '',
      })
    })
  } else if (v) {
    nextTick(() => {
      formRef.value?.resetFields()
      // 默认负责人 = 当前登录用户
      const user = useUserStore().userInfo
      if (user) {
        formState.owner_user_id = user.store_user_id
        formState.owner_department_id = user.department_id || undefined
      }
    })
  }
})

// Helpers
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

function filterOption(input, option) {
  const label = option.children?.default?.() || option.label || ''
  return String(label).toLowerCase().indexOf(input.toLowerCase()) >= 0
}

function buildDeptTree(list, parentId = 0) {
  if (!Array.isArray(list)) return []
  return list.filter(d => d.parent_id === parentId).map(d => ({
    title: d.department_name,
    value: d.id,
    key: d.id,
    children: buildDeptTree(list, d.id),
  }))
}

async function validateCustomerName(_rule, value) {
  if (!value) return Promise.resolve()
  const excludeId = props.mdl?.id || 0
  try {
    const res = await checkNameDup(value, excludeId)
    if (res?.exists || res?.data?.exists) return Promise.reject(new Error('客户名称已存在'))
    return Promise.resolve()
  } catch {
    return Promise.resolve()
  }
}

// Actions
function handleSubmit() {
  formRef.value.validate().then((values) => {
    loading.value = true
    // Resolve region from cascader IDs
    const names = selectRegionRef.value?.getNames(values.region_cascader) || {}
    values.province = names.province || ''
    values.city = names.city || ''
    values.area = names.area || ''
    delete values.region_cascader

    // Resolve dict names from IDs
    values.industry = resolveName('industry', values.industry_id)
    values.source = resolveName('customer_source', values.source_id)
    values.customer_group = resolveName('customer_group', values.group_id)
    values.level_name = resolveName('customer_level', values.level_id)

    const fn = props.mdl?.id ? edit : add
    if (props.mdl?.id) values.id = props.mdl.id

    fn(values).then(() => {
      message.success('保存成功')
      loading.value = false
      emit('update:open', false)
      emit('ok')
    }).catch(() => {
      loading.value = false
    })
  }).catch(() => {
    // validation failed
  })
}

function handleCancel() {
  emit('update:open', false)
}
</script>
