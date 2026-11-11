<template>
  <a-modal :title="mdl?.id ? '编辑合同' : '新增合同'" :width="1000" :open="open" :confirm-loading="loading"
    :mask-closable="false" @ok="handleSubmit" @cancel="handleCancel">
    <a-spin :spinning="loading">
      <a-form ref="formRef" :model="formState" :label-col="{ span: 8 }" :wrapper-col="{ span: 16 }">
        <a-row :gutter="16">
          <a-col :span="8">
            <a-form-item label="合同名称" name="contract_name" :rules="[{ required: true, message: '请输入' }]">
              <a-input v-model:value="formState.contract_name" />
            </a-form-item>
          </a-col>
          <a-col :span="8">
            <a-form-item label="客户" name="customer_id" :rules="[{ required: true, message: '请选择客户' }]">
              <a-select v-model:value="formState.customer_id" placeholder="搜索客户" show-search :filter-option="false" allow-clear @search="onCustomerSearch">
                <a-select-option v-for="c in filteredCustomers" :key="c.id" :value="c.id">{{ c.customer_name }}{{ c.customer_code ? ' — ' + c.customer_code : '' }}</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="4">
            <a-form-item label="币种" :label-col="{ span: 10 }" :wrapper-col="{ span: 14 }">
              <a-select v-model:value="formState.currency">
                <a-select-option v-for="op in dictOptions.currencies" :key="op.config_value" :value="op.config_name">{{ op.config_name }}</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="4">
            <a-form-item label="关联订单" :label-col="{ span: 14 }" :wrapper-col="{ span: 10 }">
              <a-select v-model:value="formState.order_id" placeholder="可选" allow-clear show-search :filter-option="false" @search="(v) => orders.value = allOrders.value.filter(o => o.order_no.toLowerCase().includes(v.toLowerCase()))">
                <a-select-option v-for="o in orders" :key="o.id" :value="o.id">{{ o.order_no }}</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
        </a-row>
        <a-row :gutter="16">
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
        </a-row>

        <a-divider orientation="left">产品明细</a-divider>
        <a-table :data-source="items" :pagination="false" size="small" bordered row-key="key">
          <a-table-column title="#" key="idx" width="40" align="center">
            <template #default="{ index }">{{ index + 1 }}</template>
          </a-table-column>
          <a-table-column title="产品" key="product_id" width="180">
            <template #default="{ record }">
              <a-select v-model:value="record.product_id" placeholder="选择产品" show-search allow-clear size="small" style="width:100%" :filter-option="false" @search="onProductSearch" @change="(v) => onProductSelect(v, record)">
                <a-select-option v-for="p in filteredProducts" :key="p.id" :value="p.id">{{ p.product_name }}{{ p.specification ? '/' + p.specification : '' }}</a-select-option>
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

        <a-divider orientation="left">合同条款</a-divider>
        <a-form-item label="合同内容" :label-col="{ span: 2 }" :wrapper-col="{ span: 21 }">
          <a-textarea v-model:value="formState.contract_content" :rows="4" />
        </a-form-item>
      </a-form>
    </a-spin>
  </a-modal>
</template>

<script setup>
import { ref, reactive, computed, watch, nextTick } from 'vue'
import { message } from 'ant-design-vue'
import { PlusOutlined } from '@ant-design/icons-vue'
import dayjs from 'dayjs'
import { add, edit } from '@/api/crm/contract'
import { getSelect as getCustomerSelect } from '@/api/crm/customer'
import { getSelect as getProductSelect } from '@/api/crm/product'
import { getList as getOrderList, getDetail as getOrderDetail } from '@/api/crm/order'
import { getAll as getConfigs } from '@/api/crm/config'

let itemKey = 0

const props = defineProps({ open: Boolean, mdl: Object, order: Object, preset: Object })
const emit = defineEmits(['update:open', 'ok'])

const formRef = ref()
const loading = ref(false)
const customers = ref([])
const products = ref([])
const orders = ref([])
const items = ref([])
const discountAmount = ref(0)
const dictOptions = reactive({ currencies: [] })

const formState = reactive({
  contract_name: '', customer_id: undefined, project_id: 0, currency: 'CNY', order_id: undefined,
  sign_date: null, start_date: null, end_date: null, contract_content: '',
})

function loadData() {
  getCustomerSelect().then(res => { customers.value = res?.data?.list || [] })
  getProductSelect().then(res => { products.value = res?.data?.list || [] })
  getOrderList({ limit: 200 }).then(res => { orders.value = res?.data?.list?.data || [] })
  getConfigs().then(res => { const d = res?.data || {}; dictOptions.currencies = (d.currency || []).filter(i => i.config_value) })
}
loadData()

const customerSearch = ref('')
const productSearch = ref('')
const filteredCustomers = computed(() => {
  if (!customerSearch.value) return customers.value
  const kw = customerSearch.value.toLowerCase()
  return customers.value.filter(c => c.customer_name.toLowerCase().includes(kw) || (c.customer_code || '').toLowerCase().includes(kw))
})
const filteredProducts = computed(() => {
  if (!productSearch.value) return products.value
  const kw = productSearch.value.toLowerCase()
  return products.value.filter(p => p.product_name.toLowerCase().includes(kw) || (p.specification || '').toLowerCase().includes(kw))
})
function onCustomerSearch(v) { customerSearch.value = v }
function onProductSearch(v) { productSearch.value = v }

function onProductSelect(productId, record) {
  const p = products.value.find(p => p.id === productId)
  if (p) {
    record.product_name = p.product_name
    record.specification = p.specification || ''
    record.unit = p.unit || ''
    record.unit_price = parseFloat(p.reference_price) || 0
  }
}

function onOrderChange(orderId) {
  if (!orderId) return
  getOrderDetail(orderId).then(res => {
    const order = res?.data?.detail || res?.data || {}
    if (!order.id) return
    // 自动填充客户
    if (!formState.customer_id && order.customer_id) {
      formState.customer_id = order.customer_id
    }
    if (!formState.contract_name) {
      formState.contract_name = '合同-' + (order.order_no || '')
    }
    // 带入产品明细
    const orderItems = order.items || []
    if (orderItems.length > 0) {
      items.value = orderItems.map(item => ({
        ...item, key: ++itemKey, quantity: parseFloat(item.quantity) || 0, unit_price: parseFloat(item.unit_price) || 0
      }))
      discountAmount.value = parseFloat(order.discount_amount) || 0
    }
  }).catch(() => {})
}

function newItem() { return { key: ++itemKey, product_id: null, product_name: '', specification: '', unit: '', quantity: 0, unit_price: 0 } }
function addItem() { items.value.push(newItem()) }
function removeItem(i) { items.value.splice(i, 1) }
function calcItemAmount(item) { return Math.round((parseFloat(item.quantity) || 0) * (parseFloat(item.unit_price) || 0) * 100) / 100 }
function calcTotal() { return items.value.reduce((s, i) => s + calcItemAmount(i), 0) }

// 订单转合同：预填订单数据
watch(() => props.open, (v) => {
  if (v && props.mdl?.id) {
    nextTick(() => {
      formRef.value?.resetFields()
      const vals = { ...props.mdl }
      if (vals.sign_date) vals.sign_date = dayjs(vals.sign_date * 1000)
      if (vals.start_date) vals.start_date = dayjs(vals.start_date * 1000)
      if (vals.end_date) vals.end_date = dayjs(vals.end_date * 1000)
      Object.assign(formState, vals)
      items.value = (props.mdl.items || []).map(item => ({ ...item, key: ++itemKey, quantity: parseFloat(item.quantity) || 0, unit_price: parseFloat(item.unit_price) || 0 }))
      discountAmount.value = parseFloat(props.mdl.discount_amount) || 0
    })
  } else if (v && props.order?.id) {
    nextTick(() => {
      formRef.value?.resetFields()
      Object.assign(formState, {
        customer_id: props.order.customer_id,
        project_id: props.order.project_id || 0,
        currency: props.order.currency || 'CNY',
        order_id: props.order.id,
        contract_name: '合同-' + (props.order.order_no || ''),
        contract_content: '',
      })
      items.value = (props.order.items || []).map(item => ({
        ...item, key: ++itemKey, quantity: parseFloat(item.quantity) || 0, unit_price: parseFloat(item.unit_price) || 0
      }))
      discountAmount.value = parseFloat(props.order.discount_amount) || 0
    })
  } else if (v && props.preset) {
    nextTick(() => {
      formRef.value?.resetFields()
      Object.assign(formState, {
        customer_id: props.preset.customer_id,
        project_id: props.preset.project_id || 0,
        contract_name: props.preset.contract_name || '',
        currency: 'CNY',
        order_id: undefined,
      })
      items.value = props.preset.items?.length
        ? props.preset.items.map(item => ({
            key: ++itemKey, product_name: item.product_name || '', specification: item.specification || '',
            unit: item.unit || '', quantity: parseFloat(item.quantity) || 1, unit_price: parseFloat(item.unit_price) || 0,
          }))
        : [newItem()]
      discountAmount.value = 0
    })
  } else if (v) {
    nextTick(() => {
      formRef.value?.resetFields()
      items.value = [newItem()]
      discountAmount.value = 0
      formState.currency = 'CNY'
      formState.order_id = undefined
      formState.project_id = 0
    })
  }
})

function handleSubmit() {
  formRef.value.validate().then((values) => {
    if (items.value.length === 0) { message.warning('请至少添加一条产品明细'); return }
    for (let i = 0; i < items.value.length; i++) { if (!items.value[i].product_name) { message.warning(`第${i + 1}行产品名称不能为空`); return } }
    loading.value = true
    ;['sign_date', 'start_date', 'end_date'].forEach(k => { if (values[k]) values[k] = dayjs(values[k]).format('YYYY-MM-DD') })
    values.discount_amount = discountAmount.value
    values.project_id = formState.project_id || props.preset?.project_id || props.order?.project_id || 0
    values.items = items.value.map((item, i) => ({ product_id: item.product_id, product_name: item.product_name, specification: item.specification, unit: item.unit, quantity: parseFloat(item.quantity) || 0, unit_price: parseFloat(item.unit_price) || 0, sort_order: i }))
    const fn = props.mdl?.id ? edit : add
    if (props.mdl?.id) values.id = props.mdl.id
    fn(values).then(() => { message.success('保存成功'); loading.value = false; emit('update:open', false); emit('ok') }).catch(() => { loading.value = false })
  }).catch(() => { message.warning('请完善必填项') })
}

function handleCancel() { emit('update:open', false) }
</script>
