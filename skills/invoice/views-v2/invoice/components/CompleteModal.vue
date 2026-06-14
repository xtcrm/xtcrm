<template>
  <a-modal title="完成开票" :open="visible" :confirm-loading="submitting" @ok="handleOk" @cancel="$emit('update:visible', false)" width="420" wrapClassName="complete-invoice-modal">
    <a-form :model="form" layout="vertical">
      <a-row :gutter="12">
        <a-col :span="12">
          <a-form-item label="发票代码"><a-input v-model:value="form.invoice_code" size="small" /></a-form-item>
        </a-col>
        <a-col :span="12">
          <a-form-item label="发票号码"><a-input v-model:value="form.invoice_number" size="small" /></a-form-item>
        </a-col>
      </a-row>
      <a-form-item label="开票日期"><a-date-picker v-model:value="form.invoice_date" style="width:100%" size="small" /></a-form-item>
      <a-form-item label="票图上传">
        <a-upload :show-upload-list="true" :before-upload="(f) => { doUpload(f); return false }" accept="image/*" :max-count="1">
          <a-button><cloud-cloud-upload-outlined /> 选择图片</a-button>
        </a-upload>
        <a-input v-model:value="form.invoice_image_url" placeholder="或直接填入图片 URL" size="small" style="margin-top:8px" />
      </a-form-item>
      <a-form-item label="PDF链接"><a-input v-model:value="form.invoice_pdf_url" placeholder="发票PDF链接" size="small" /></a-form-item>
      <a-form-item label="开票文字"><a-textarea v-model:value="form.invoice_text" :rows="3" placeholder="票面关键信息（选填）" size="small" /></a-form-item>
    </a-form>
  </a-modal>
</template>

<script setup>
import { ref, reactive, watch } from 'vue'
import { message } from 'ant-design-vue'
import { CloudUploadOutlined } from '@ant-design/icons-vue'
import request from '@/utils/request'
import { completeInvoice } from '../../api'

const props = defineProps({ visible: Boolean, record: Object })
const emit = defineEmits(['update:visible', 'ok'])
const submitting = ref(false)
const form = reactive({ invoice_code: '', invoice_number: '', invoice_date: null, invoice_image_url: '', invoice_pdf_url: '', invoice_text: '' })

watch(() => props.visible, v => {
  if (v) {
    Object.assign(form, { invoice_code: '', invoice_number: '', invoice_date: null, invoice_image_url: '', invoice_pdf_url: '', invoice_text: '' })
  }
})

function doUpload(file) {
  const formData = new FormData()
  formData.append('iFile', file)
  request({ url: '/invoice.invoice/upload', method: 'post', data: formData, headers: { 'Content-Type': 'multipart/form-data' } })
    .then(r => {
      const url = r?.data?.url || r?.url || ''
      if (url) {
        form.invoice_image_url = url
        message.success('上传成功')
      }
    })
}

function handleOk() {
  if (!form.invoice_image_url && !form.invoice_text) {
    message.warning('至少上传发票图片或填写开票文字')
    return
  }
  submitting.value = true
  const data = { id: props.record?.id, ...form }
  if (form.invoice_date) data.invoice_date = form.invoice_date.format?.('YYYY-MM-DD') ?? form.invoice_date
  completeInvoice(data)
    .then(() => { message.success('开票完成'); emit('ok'); emit('update:visible', false) })
    .finally(() => { submitting.value = false })
}
</script>
<style>
.complete-invoice-modal .ant-modal { width: 420px !important; }
</style>
