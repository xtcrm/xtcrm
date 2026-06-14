<template>
  <a-modal :title="mdl?.id ? '编辑产品' : '新增产品'" :width="600" :open="open" :confirm-loading="loading"
    :mask-closable="false" @ok="handleSubmit" @cancel="handleCancel">
    <a-spin :spinning="loading">
      <a-form ref="formRef" :model="formState" :label-col="{ span: 6 }" :wrapper-col="{ span: 18 }">
        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item label="产品名称" name="product_name" :rules="[{ required: true, message: '请输入产品名称' }]">
              <a-input v-model:value="formState.product_name" placeholder="产品名称" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="产品编码" name="product_code">
              <a-input v-model:value="formState.product_code" placeholder="产品编码" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="规格型号" name="specification">
              <a-input v-model:value="formState.specification" placeholder="规格型号" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="单位" name="unit">
              <a-input v-model:value="formState.unit" placeholder="kg / 桶 / 套" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="产品分类" name="category_id">
              <a-select v-model:value="formState.category_id" placeholder="选择分类" allow-clear show-search
                :filter-option="filterOption" @change="(v) => setDictName(v)">
                <a-select-option v-for="op in dictOptions.product_category" :key="op.value" :value="op.value">{{ op.name }}</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="参考单价" name="reference_price">
              <a-input-number v-model:value="formState.reference_price" :min="0" :precision="2" style="width:100%" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="成本价" name="cost_price">
              <a-input-number v-model:value="formState.cost_price" :min="0" :precision="2" style="width:100%" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="图片URL" name="image_url">
              <a-input v-model:value="formState.image_url" placeholder="图片链接" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="排序" name="sort_order">
              <a-input-number v-model:value="formState.sort_order" :min="0" style="width:100%" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="状态" name="status">
              <a-select v-model:value="formState.status">
                <a-select-option :value="1">上架</a-select-option>
                <a-select-option :value="0">下架</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="24">
            <a-form-item label="描述" name="description" :label-col="{ span: 3 }" :wrapper-col="{ span: 20 }">
              <a-textarea v-model:value="formState.description" placeholder="产品描述" :rows="2" />
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
import { add, edit } from '@/api/crm/product'
import { getAll as getConfigs } from '@/api/crm/config'

const props = defineProps({ open: Boolean, mdl: Object })
const emit = defineEmits(['update:open', 'ok'])

const formRef = ref()
const loading = ref(false)
const dictOptions = reactive({ product_category: [] })

const formState = reactive({
  product_name: '', product_code: '', specification: '', unit: '',
  category_id: undefined, category: '',
  reference_price: undefined, cost_price: undefined,
  image_url: '', sort_order: 100, status: 1, description: '',
})

function loadDicts() {
  getConfigs().then(res => {
    const data = res?.data || {}
    const toOpts = (arr) => (arr || []).filter(i => i.config_value).map(i => ({
      value: parseInt(i.config_value), name: i.config_name,
    }))
    dictOptions.product_category = toOpts(data.product_category)
  })
}
loadDicts()

function setDictName(idVal) {
  const opt = dictOptions.product_category.find(o => o.value === idVal)
  if (opt) formState.category = opt.name
}

function filterOption(input, option) {
  const label = option.children?.default?.() || option.label || ''
  return String(label).toLowerCase().indexOf(input.toLowerCase()) >= 0
}

watch(() => props.open, (v) => {
  if (v && props.mdl?.id) {
    nextTick(() => {
      formRef.value?.resetFields()
      Object.assign(formState, {
        product_name: props.mdl.product_name || '',
        product_code: props.mdl.product_code || '',
        specification: props.mdl.specification || '',
        unit: props.mdl.unit || '',
        category_id: props.mdl.category_id,
        category: props.mdl.category_name || '',
        reference_price: props.mdl.reference_price,
        cost_price: props.mdl.cost_price,
        image_url: props.mdl.image_url || '',
        sort_order: props.mdl.sort_order ?? 100,
        status: props.mdl.status ?? 1,
        description: props.mdl.description || '',
      })
    })
  } else if (v) {
    nextTick(() => { formRef.value?.resetFields(); formState.status = 1; formState.sort_order = 100 })
  }
})

function handleSubmit() {
  formRef.value.validate().then((values) => {
    loading.value = true
    const fn = props.mdl?.id ? edit : add
    if (props.mdl?.id) values.id = props.mdl.id
    fn(values).then(() => {
      message.success('保存成功')
      loading.value = false
      emit('update:open', false)
      emit('ok')
    }).catch(() => { loading.value = false })
  }).catch(() => {})
}

function handleCancel() { emit('update:open', false) }
</script>
