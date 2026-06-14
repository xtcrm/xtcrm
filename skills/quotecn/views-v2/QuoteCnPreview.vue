<template>
  <a-card :bordered="false">
    <a-page-header title="中文报价单" @back="() => router.go(-1)">
      <template #extra>
        <a-button-group style="margin-right:8px">
          <a-button type="primary" :loading="downloading" @click="handleDownload('pdf')">PDF</a-button>
          <a-button type="primary" :loading="downloading" @click="handleDownload('word')">Word</a-button>
          <a-button type="primary" :loading="downloading" @click="handleDownload('excel')">Excel</a-button>
        </a-button-group>
        <a-button @click="handlePrint">打印</a-button>
        <a-button style="margin-left:8px" @click="showSettings = !showSettings">{{ showSettings ? '关闭设置' : '模板设置' }}</a-button>
      </template>
    </a-page-header>
    <a-divider />

    <!-- 模板设置表单 -->
    <a-card v-if="showSettings" title="公司信息设置" style="margin-bottom:16px">
      <a-form ref="settingsFormRef" :model="settingsForm" layout="inline">
        <a-row :gutter="16" style="margin-bottom:12px">
          <a-col :span="24">
            <a-form-item label="公司Logo" extra="建议尺寸：200×60像素">
              <SelectImage v-model="settingsForm.company_logo" :width="80" />
            </a-form-item>
          </a-col>
        </a-row>
        <a-row :gutter="16">
          <a-col :md="8" :sm="24">
            <a-form-item label="公司名称"><a-input v-model:value="settingsForm.company_name" placeholder="公司全称" /></a-form-item>
          </a-col>
          <a-col :md="8" :sm="24">
            <a-form-item label="地址"><a-input v-model:value="settingsForm.company_address" placeholder="公司地址" /></a-form-item>
          </a-col>
          <a-col :md="8" :sm="24">
            <a-form-item label="电话"><a-input v-model:value="settingsForm.company_phone" placeholder="联系电话" /></a-form-item>
          </a-col>
        </a-row>
        <a-row :gutter="16" style="margin-top:8px">
          <a-col :md="6" :sm="24">
            <a-form-item label="开户行"><a-input v-model:value="settingsForm.bank_name" placeholder="开户银行" /></a-form-item>
          </a-col>
          <a-col :md="6" :sm="24">
            <a-form-item label="账号"><a-input v-model:value="settingsForm.bank_account" placeholder="银行账号" /></a-form-item>
          </a-col>
          <a-col :md="6" :sm="24">
            <a-form-item label="税号"><a-input v-model:value="settingsForm.tax_no" placeholder="纳税识别号" /></a-form-item>
          </a-col>
          <a-col :md="6" :sm="24" style="padding-top:40px">
            <a-button type="primary" :loading="saving" @click="handleSaveSettings">保存</a-button>
          </a-col>
        </a-row>
        <a-form-item label="条款" :label-col="{ span: 2 }" :wrapper-col="{ span: 22 }" style="margin-top:8px">
          <a-textarea v-model:value="settingsForm.terms_text" :rows="4" placeholder="报价条款，每条一行" />
        </a-form-item>
        <a-form-item label="页脚" :label-col="{ span: 2 }" :wrapper-col="{ span: 22 }">
          <a-input v-model:value="settingsForm.footer_text" placeholder="页脚声明文字" />
        </a-form-item>
      </a-form>
    </a-card>

    <!-- 报价预览 -->
    <a-spin :spinning="loading">
      <div v-if="previewHtml" class="preview-wrapper" v-html="previewHtml"></div>
      <a-empty v-else-if="!loading" description="暂无数据" />
    </a-spin>
  </a-card>
</template>

<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import SelectImage from '@/components/SelectImage/index.vue'
import { getPreview, exportPdf, exportWord, exportExcel, getTemplate, saveTemplate } from './api'

const route = useRoute()
const router = useRouter()

const loading = ref(false)
const downloading = ref(false)
const saving = ref(false)
const previewHtml = ref('')
const showSettings = ref(false)
const settingsFormRef = ref()
const quotationId = ref(null)

const settingsForm = reactive({
  company_logo: '',
  company_name: '', company_address: '', company_phone: '',
  bank_name: '', bank_account: '', tax_no: '',
  terms_text: '1. 本报价单有效期30天。\n2. 付款方式：合同签订后预付30%，发货前付清余款。\n3. 交货期：收到预付款后15个工作日内。\n4. 本报价不含运输费、安装费，如需另行协商。',
  footer_text: '本报价单一式两份，供需双方各执一份，具有同等法律效力。',
})

const defaults = { ...settingsForm }

function loadPreview() {
  loading.value = true
  getPreview(quotationId.value).then(res => {
    previewHtml.value = (res?.data?.html) || ''
  }).finally(() => { loading.value = false })
}

function handleDownload(type) {
  downloading.value = true
  const apiMap = { pdf: exportPdf, word: exportWord, excel: exportExcel }
  const mimeMap = { pdf: 'application/pdf', word: 'application/msword', excel: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }
  const fn = apiMap[type]
  if (!fn) { downloading.value = false; return }
  fn(quotationId.value).then(res => {
    const data = res?.data || {}
    if (data.pdf_base64 || data.file_base64) {
      const b64 = data.pdf_base64 || data.file_base64
      const byteChars = atob(b64)
      const byteNums = new Array(byteChars.length)
      for (let i = 0; i < byteChars.length; i++) byteNums[i] = byteChars.charCodeAt(i)
      const blob = new Blob([new Uint8Array(byteNums)], { type: mimeMap[type] })
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url; a.download = data.filename || ('quotation.' + type); document.body.appendChild(a); a.click()
      document.body.removeChild(a); URL.revokeObjectURL(url)
      message.success('已导出 ' + type.toUpperCase())
    } else if (data.html) {
      const blob = new Blob(['﻿' + data.html], { type: mimeMap[type] + ';charset=utf-8' })
      const url = URL.createObjectURL(blob)
      const a = document.createElement('a')
      a.href = url; a.download = data.filename || ('quotation.doc'); document.body.appendChild(a); a.click()
      document.body.removeChild(a); URL.revokeObjectURL(url)
      message.success('已导出 ' + type.toUpperCase())
    } else {
      message.warning(type === 'pdf' ? 'PDF引擎未配置，请安装wkhtmltopdf' : '导出失败')
    }
  }).finally(() => { downloading.value = false })
}

function handlePrint() {
  const printWin = window.open('', '_blank')
  printWin.document.write(previewHtml.value)
  printWin.document.close()
  printWin.focus()
  printWin.print()
}

function handleSaveSettings() {
  saving.value = true
  saveTemplate({ ...settingsForm }).then(() => {
    message.success('保存成功')
    loadPreview()
  }).finally(() => { saving.value = false })
}

watch(showSettings, (v) => {
  if (v) {
    getTemplate().then(res => {
      const serverData = res?.data || {}
      Object.keys(defaults).forEach(k => {
        if (serverData[k] !== undefined && serverData[k] !== '' && serverData[k] !== null) {
          settingsForm[k] = serverData[k]
        } else {
          settingsForm[k] = defaults[k]
        }
      })
    })
  }
})

onMounted(() => {
  quotationId.value = route.query.id
  if (quotationId.value) loadPreview()
})
</script>

<style scoped>
.preview-wrapper {
  background: #fff;
  border: 1px solid #e8e8e8;
  border-radius: 4px;
  overflow: auto;
  max-height: calc(100vh - 200px);
  padding: 24px 0;
  display: flex;
  justify-content: center;
}
.preview-wrapper :deep(.quote-cn-wrapper) { margin: 0; }
</style>
