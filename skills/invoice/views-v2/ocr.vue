<template>
  <a-card :bordered="false">
    <a-page-header title="OCR 识图配置" sub-title="阿里云 RecognizeAllText 接口" />
    <a-divider />
    <a-spin :spinning="loading">
      <a-alert message="用于拍照识图功能，将收据/小票图片转为文字。申请地址：https://ocr.console.aliyun.com" type="info" show-icon style="margin-bottom:24px" />
      <a-form :model="form" layout="vertical">
        <a-row :gutter="24">
          <a-col :span="12">
            <a-form-item label="API Endpoint">
              <a-input v-model:value="form.endpoint" placeholder="ocr-api.cn-hangzhou.aliyuncs.com" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="AccessKey ID">
              <a-input v-model:value="form.access_key_id" placeholder="阿里云 AccessKey ID" />
            </a-form-item>
          </a-col>
        </a-row>
        <a-row :gutter="24">
          <a-col :span="12">
            <a-form-item label="AccessKey Secret">
              <a-input-password v-model:value="form.access_key_secret" placeholder="阿里云 AccessKey Secret" />
            </a-form-item>
          </a-col>
          <a-col :span="12" style="padding-top:32px">
            <a-button type="primary" :loading="saving" @click="handleSave">保存配置</a-button>
            <a-button style="margin-left:12px" @click="handleTest" :loading="testing">测试连接</a-button>
          </a-col>
        </a-row>
      </a-form>
    </a-spin>
  </a-card>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { message } from 'ant-design-vue'
import request from '@/utils/request'

const loading = ref(false)
const saving = ref(false)
const testing = ref(false)
const form = reactive({ endpoint: '', access_key_id: '', access_key_secret: '' })

onMounted(() => {
  loading.value = true
  request({ url: '/invoice.config/ocrConfig', method: 'get' }).then(r => {
    const d = r?.data || r
    Object.assign(form, d)
  }).finally(() => { loading.value = false })
})

function handleSave() {
  saving.value = true
  request({ url: '/invoice.config/saveOcrConfig', method: 'post', data: { ...form } })
    .then(() => message.success('保存成功'))
    .finally(() => { saving.value = false })
}

function handleTest() {
  testing.value = true
  request({ url: '/invoice.config/testOcr', method: 'post' })
    .then(() => message.success('连接成功'))
    .catch(() => message.error('连接失败，请检查配置'))
    .finally(() => { testing.value = false })
}
</script>
