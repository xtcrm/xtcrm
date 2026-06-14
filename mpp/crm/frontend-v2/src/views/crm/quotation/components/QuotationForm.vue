<template>
  <a-modal :title="mdl?.id ? '编辑报价单' : '新增报价单'" :width="1000" :open="open" :confirm-loading="loading"
    :mask-closable="false" @ok="handleSubmit" @cancel="handleCancel">
    <a-spin :spinning="loading">
      <a-form ref="formRef" :model="formState" :label-col="{ span: 8 }" :wrapper-col="{ span: 16 }">
        <a-row :gutter="16">
          <a-col :span="8">
            <a-form-item label="客户" name="customer_id" :rules="[{ required: true, message: '请选择客户' }]">
              <a-select v-model:value="formState.customer_id" placeholder="搜索客户" show-search :filter-option="filterOption" allow-clear @change="onCustomerChange">
                <a-select-option v-for="c in customers" :key="c.id" :value="c.id">{{ c.customer_name }}{{ c.customer_code ? ' — ' + c.customer_code : '' }}</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="8">
            <a-form-item label="报价日期" name="quotation_date" :rules="[{ required: true, message: '请选择' }]">
              <a-date-picker v-model:value="formState.quotation_date" style="width:100%" />
            </a-form-item>
          </a-col>
          <a-col :span="4">
            <a-form-item label="币种" name="currency" :label-col="{ span: 10 }" :wrapper-col="{ span: 14 }">
              <a-select v-model:value="formState.currency">
                <a-select-option v-for="op in dictOptions.currencies" :key="op.config_value" :value="op.config_name">{{ op.config_name }}</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="4">
            <a-form-item label="有效天数" name="valid_days" :label-col="{ span: 14 }" :wrapper-col="{ span: 10 }">
              <a-input-number v-model:value="formState.valid_days" :min="1" :max="365" style="width:100%" />
            </a-form-item>
          </a-col>
        </a-row>

        <a-divider orientation="left">报价明细</a-divider>
        <a-table :data-source="items" :pagination="false" size="small" bordered row-key="key">
          <a-table-column title="#" key="idx" width="40" align="center">
            <template #default="{ index }">{{ index + 1 }}</template>
          </a-table-column>
          <a-table-column title="产品" key="product_id" width="180">
            <template #default="{ record }">
              <a-select v-model:value="record.product_id" placeholder="选择产品" show-search allow-clear size="small" style="width:100%" :filter-option="filterOption" @change="(v) => onProductSelect(v, record)">
                <a-select-option v-for="p in products" :key="p.id" :value="p.id">{{ p.product_name }}{{ p.specification ? '/' + p.specification : '' }}</a-select-option>
              </a-select>
            </template>
          </a-table-column>
          <a-table-column title="产品名称" key="product_name" width="150">
            <template #default="{ record }"><a-input v-model:value="record.product_name" size="small" /></template>
          </a-table-column>
          <a-table-column title="规格" key="specification" width="130">
            <template #default="{ record }"><a-input v-model:value="record.specification" size="small" /></template>
          </a-table-column>
          <a-table-column title="单位" key="unit" width="70">
            <template #default="{ record }"><a-input v-model:value="record.unit" size="small" /></template>
          </a-table-column>
          <a-table-column title="数量" key="quantity" width="90">
            <template #default="{ record }"><a-input-number v-model:value="record.quantity" :min="0" :precision="2" size="small" style="width:100%" /></template>
          </a-table-column>
          <a-table-column title="单价" key="unit_price" width="100">
            <template #default="{ record }"><a-input-number v-model:value="record.unit_price" :min="0" :precision="2" size="small" style="width:100%" /></template>
          </a-table-column>
          <a-table-column title="小计" key="amount" width="100" align="right">
            <template #default="{ record }">{{ calcItemAmount(record).toLocaleString('zh-CN', { minimumFractionDigits: 2 }) }}</template>
          </a-table-column>
          <a-table-column title="操作" key="action" width="50" align="center">
            <template #default="{ index }"><a-popconfirm title="删除?" @confirm="removeItem(index)"><a style="color:#ff4d4f">删</a></a-popconfirm></template>
          </a-table-column>
        </a-table>
        <a-button type="dashed" block @click="addItem" style="margin-top:8px"><plus-outlined /> 添加明细行</a-button>
        <a-row style="margin-top:8px">
          <a-col :span="24" style="text-align:right">
            合计: <strong>{{ calcTotal().toLocaleString('zh-CN', { minimumFractionDigits: 2 }) }}</strong>
            &nbsp; 折扣: <a-input-number v-model:value="discountAmount" :min="0" :precision="2" style="width:100px" />
            &nbsp; 折后: <strong style="font-size:16px">{{ (calcTotal() - discountAmount).toLocaleString('zh-CN', { minimumFractionDigits: 2 }) }}</strong>
          </a-col>
        </a-row>

        <a-divider orientation="left">备注</a-divider>
        <a-form-item label="备注" name="remark" :label-col="{ span: 2 }" :wrapper-col="{ span: 21 }">
          <a-textarea v-model:value="formState.remark" :rows="2" />
        </a-form-item>
      </a-form>
    </a-spin>
  </a-modal>
</template>

<script setup>
import { ref, reactive, watch, nextTick } from 'vue'
import { message } from 'ant-design-vue'
import { PlusOutlined } from '@ant-design/icons-vue'
import dayjs from 'dayjs'
import { add, edit } from '@/api/crm/quotation'
import { getSelect as getCustomerSelect } from '@/api/crm/customer'
import { getSelect as getProductSelect } from '@/api/crm/product'
import { getAll as getConfigs } from '@/api/crm/config'

let itemKey = 0

const props = defineProps({ open: Boolean, mdl: Object })
const emit = defineEmits(['update:open', 'ok'])

const formRef = ref()
const loading = ref(false)
const customers = ref([])
const products = ref([])
const items = ref([])
const discountAmount = ref(0)
const dictOptions = reactive({ currencies: [] })

const formState = reactive({
  customer_id: undefined, quotation_date: null, currency: 'CNY', valid_days: 30, remark: '',
})

function loadCustomers() { getCustomerSelect().then(res => { customers.value = res?.data?.list || [] }) }
function loadProducts() { getProductSelect().then(res => { products.value = res?.data?.list || [] }) }
function loadConfigs() { getConfigs().then(res => { const d = res?.data || {}; dictOptions.currencies = (d.currency || []).filter(i => i.config_value) }) }

loadCustomers(); loadProducts(); loadConfigs()

function filterOption(input, option) {
  const label = option.children?.default?.() || option.label || ''
  return String(label).toLowerCase().indexOf(input.toLowerCase()) >= 0
}

function onCustomerChange() {}
function onProductSelect(productId, record) {
  const p = products.value.find(p => p.id === productId)
  if (p) {
    record.product_name = p.product_name
    record.specification = p.specification || ''
    record.unit = p.unit || ''
    record.unit_price = parseFloat(p.reference_price) || 0
  }
}

function newItem() { return { key: ++itemKey, product_id: null, product_name: '', specification: '', unit: '', quantity: 0, unit_price: 0 } }
function addItem() { items.value.push(newItem()) }
function removeItem(i) { items.value.splice(i, 1) }
function calcItemAmount(item) { return Math.round((parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0) * 100) / 100 }
function calcTotal() { return items.value.reduce((s, i) => s + calcItemAmount(i), 0) }

watch(() => props.open, (v) => {
  if (v && props.mdl?.id) {
    nextTick(() => {
      formRef.value?.resetFields()
      const vals = { customer_id: props.mdl.customer_id, valid_days: props.mdl.valid_days, remark: props.mdl.remark, currency: props.mdl.currency || 'CNY' }
      if (props.mdl.quotation_date) vals.quotation_date = dayjs(props.mdl.quotation_date * 1000)
      Object.assign(formState, vals)
      items.value = (props.mdl.items || []).map(item => ({ ...item, key: ++itemKey, quantity: parseFloat(item.quantity) || 0, unit_price: parseFloat(item.unit_price) || 0 }))
      discountAmount.value = parseFloat(props.mdl.discount_amount) || 0
    })
  } else if (v) {
    nextTick(() => { formRef.value?.resetFields(); items.value = [newItem()]; discountAmount.value = 0; formState.currency = 'CNY'; formState.valid_days = 30 })
  }
})

function handleSubmit() {
  formRef.value.validate().then((values) => {
    if (items.value.length === 0) { message.warning('请至少添加一条明细'); return }
    for (let i = 0; i < items.value.length; i++) { if (!items.value[i].product_name) { message.warning(`第${i + 1}行产品名称不能为空`); return } }
    loading.value = true
    if (values.quotation_date) values.quotation_date = dayjs(values.quotation_date).format('YYYY-MM-DD')
    values.discount_amount = discountAmount.value
    values.items = items.value.map((item, i) => ({ product_id: item.product_id, product_name: item.product_name, specification: item.specification, unit: item.unit, quantity: parseFloat(item.quantity) || 0, unit_price: parseFloat(item.unit_price) || 0, sort_order: i }))
    const fn = props.mdl?.id ? edit : add
    if (props.mdl?.id) values.id = props.mdl.id
    fn(values).then(() => { message.success('保存成功'); loading.value = false; emit('update:open', false); emit('ok') }).catch((err) => { loading.value = false; message.error(err?.data?.message || err?.message || '保存失败，请重试') })
  }).catch(() => { message.warning('请检查必填项：客户、报价日期、产品明细') })
}

function handleCancel() { emit('update:open', false) }
</script>
