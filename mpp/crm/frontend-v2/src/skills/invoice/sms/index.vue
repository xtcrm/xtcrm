<template>
  <a-card :bordered="false">
    <a-page-header title="短信配置" />
    <a-divider />

    <a-tabs>
      <!-- 阿里云 API 配置 -->
      <a-tab-pane key="api" tab="API 配置">
        <a-spin :spinning="loading">
          <a-alert message="使用阿里云短信服务（SendSms），需在阿里云控制台申请签名和模板。" type="info" show-icon style="margin-bottom:16px" />
          <a-form :model="apiForm" layout="vertical">
            <a-row :gutter="16">
              <a-col :span="12">
                <a-form-item label="AccessKey ID"><a-input v-model:value="apiForm.access_key_id" placeholder="阿里云 AccessKey ID" /></a-form-item>
              </a-col>
              <a-col :span="12">
                <a-form-item label="AccessKey Secret"><a-input-password v-model:value="apiForm.access_key_secret" placeholder="阿里云 AccessKey Secret" /></a-form-item>
              </a-col>
            </a-row>
            <a-row :gutter="16">
              <a-col :span="12">
                <a-form-item label="短信签名"><a-input v-model:value="apiForm.sign_name" placeholder="如：雄韬财税" /></a-form-item>
              </a-col>
              <a-col :span="12">
                <a-form-item label="模板CODE"><a-input v-model:value="apiForm.template_code" placeholder="如：SMS_123456789" /></a-form-item>
              </a-col>
            </a-row>
            <a-form-item>
              <a-button type="primary" :loading="saving" @click="saveApi">保存</a-button>
            </a-form-item>
          </a-form>
        </a-spin>
      </a-tab-pane>

      <!-- 模板文案 -->
      <a-tab-pane key="tpl" tab="模板文案">
        <a-spin :spinning="loading">
          <a-form :model="tplForm" layout="vertical">
            <a-form-item label="已开票通知" extra="阿里云模板变量: ${invoice_number} 发票号码, ${total_amount} 金额">
              <a-textarea v-model:value="tplForm.completed" :rows="3" />
            </a-form-item>
            <a-form-item label="驳回通知" extra="阿里云模板变量: ${audit_remark} 驳回原因">
              <a-textarea v-model:value="tplForm.rejected" :rows="3" />
            </a-form-item>
            <a-form-item>
              <a-button type="primary" :loading="saving" @click="saveTpl">保存</a-button>
            </a-form-item>
          </a-form>
        </a-spin>
      </a-tab-pane>
    </a-tabs>
  </a-card>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { message } from 'ant-design-vue'
import request from '@/utils/request'

const loading = ref(false)
const saving = ref(false)
const apiForm = reactive({ access_key_id: '', access_key_secret: '', sign_name: '', template_code: '' })
const tplForm = reactive({ completed: '', rejected: '' })

function load() {
  loading.value = true
  Promise.all([
    request({ url: '/invoice.config/smsConfig', method: 'get' }),
    request({ url: '/invoice.config/smsTemplates', method: 'get' }),
  ]).then(([api, tpl]) => {
    Object.assign(apiForm, api?.data || api)
    Object.assign(tplForm, tpl?.data || tpl)
  }).finally(() => { loading.value = false })
}

function saveApi() {
  saving.value = true
  request({ url: '/invoice.config/saveSmsConfig', method: 'post', data: { ...apiForm } })
    .then(() => message.success('保存成功'))
    .finally(() => { saving.value = false })
}

function saveTpl() {
  saving.value = true
  request({ url: '/invoice.config/saveSmsTemplates', method: 'post', data: { ...tplForm } })
    .then(() => message.success('保存成功'))
    .finally(() => { saving.value = false })
}

onMounted(load)
</script>
