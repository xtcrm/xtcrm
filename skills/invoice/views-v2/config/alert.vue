<template>
  <a-card :bordered="false">
    <a-page-header title="预警配置" />
    <a-divider />

    <a-spin :spinning="loading">
      <a-alert
        message="税负率低于安全线时，仪表盘会变红提醒。月初和月底自动推送微信消息给绑定的会员。"
        type="info" show-icon style="margin-bottom:24px"
      />

      <a-form :model="form" layout="vertical" style="max-width:500px">
        <a-form-item label="预警开关">
          <a-switch v-model:checked="form.alert_enabled" checked-children="开" un-checked-children="关" />
        </a-form-item>

        <a-form-item label="税负率安全线 (%)">
          <a-input-number
            v-model:value="form.tax_burden_min"
            :min="0"
            :max="20"
            :step="0.1"
            style="width:200px"
            addon-after="%"
          />
          <div style="color:#999;font-size:12px;margin-top:4px">
            建议设为 1。当月税负率低于此值将触发预警。一般纳税人通常 1-3%，小规模 0-1%。
          </div>
        </a-form-item>

        <a-form-item>
          <a-button type="primary" :loading="saving" @click="handleSave">保存</a-button>
        </a-form-item>
      </a-form>
    </a-spin>
  </a-card>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { message } from 'ant-design-vue'
import { getAlertConfig, saveAlertConfig } from '../api'

const loading = ref(false)
const saving = ref(false)
const form = reactive({ alert_enabled: true, tax_burden_min: 1 })

function load() {
  loading.value = true
  getAlertConfig().then(r => {
    const d = r?.data
    if (d) {
      form.alert_enabled = d.alert_enabled ?? true
      form.tax_burden_min = d.tax_burden_min ?? 1
    }
  }).finally(() => { loading.value = false })
}

function handleSave() {
  saving.value = true
  saveAlertConfig({ ...form })
    .then(() => message.success('保存成功'))
    .finally(() => { saving.value = false })
}

onMounted(load)
</script>
