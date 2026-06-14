<template>
  <a-card :bordered="false">
    <a-page-header title="公司档案" sub-title="AI内容生成的人设基础" />
    <a-divider />
    <a-spin :spinning="loading">
      <a-form ref="formRef" :model="formState" :label-col="{ span: 3 }" :wrapper-col="{ span: 18 }">
        <a-form-item label="公司简介"><a-textarea v-model:value="formState.company_intro" :rows="3" placeholder="200-500字" /></a-form-item>
        <a-form-item label="核心优势">
          <div v-for="(s, idx) in strengths" :key="idx" style="display:flex;gap:8px;margin-bottom:8px">
            <a-input v-model:value="s.value" :placeholder="'第' + (idx + 1) + '条优势'" style="flex:1" />
            <a-button type="danger" size="small" @click="removeStrength(idx)" :disabled="strengths.length <= 1"><minus-outlined /></a-button>
          </div>
          <a-button type="dashed" size="small" @click="addStrength"><plus-outlined /> 添加优势</a-button>
        </a-form-item>
        <a-form-item label="技术实力"><a-textarea v-model:value="formState.tech_capability" :rows="3" placeholder="技术资质、研发团队、专利等" /></a-form-item>
        <a-form-item label="服务承诺"><a-textarea v-model:value="formState.service_commitment" :rows="2" placeholder="售后保障、交付周期等" /></a-form-item>
        <a-form-item label="联系信息"><a-textarea v-model:value="formState.contact_info" :rows="2" placeholder="电话、微信、地址" /></a-form-item>
        <a-form-item label="品牌调性">
          <a-radio-group v-model:value="formState.brand_voice">
            <a-radio value="专业">专业</a-radio><a-radio value="亲切">亲切</a-radio><a-radio value="技术流">技术流</a-radio><a-radio value="营销型">营销型</a-radio>
          </a-radio-group>
        </a-form-item>
        <a-form-item label="典型案例">
          <div v-for="(item, idx) in cases" :key="idx" style="display:flex;gap:8px;margin-bottom:8px">
            <a-input v-model:value="item.name" placeholder="客户名" style="width:200px" />
            <a-input v-model:value="item.result" placeholder="成果描述" style="flex:1" />
            <a-button type="danger" size="small" @click="removeCase(idx)" :disabled="cases.length <= 1"><minus-outlined /></a-button>
          </div>
          <a-button type="dashed" size="small" @click="addCase"><plus-outlined /> 添加案例</a-button>
        </a-form-item>
        <a-form-item :wrapper-col="{ span: 18, offset: 3 }">
          <a-button type="primary" :loading="saving" @click="handleSubmit">保存档案</a-button>
        </a-form-item>
      </a-form>
    </a-spin>
  </a-card>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { message } from 'ant-design-vue'
import { PlusOutlined, MinusOutlined } from '@ant-design/icons-vue'
import { getProfile, saveProfile } from './api'

const formRef = ref()
const loading = ref(false)
const saving = ref(false)

const formState = reactive({ company_intro: '', core_strengths: '', tech_capability: '', service_commitment: '', contact_info: '', brand_voice: '专业', contact_qrcode: '', case_stories: '' })
const strengths = ref([{ value: '' }])
const cases = ref([{ name: '', result: '' }])

function load() {
  loading.value = true
  getProfile().then(res => {
    const d = res?.data?.detail || {}
    if (d.core_strengths) {
      const lines = d.core_strengths.split('\n').filter(l => l.trim())
      if (lines.length) strengths.value = lines.map(l => ({ value: l }))
    }
    if (d.case_stories) {
      try { const p = JSON.parse(d.case_stories); if (Array.isArray(p) && p.length) cases.value = p } catch (e) {}
    }
    Object.assign(formState, d)
    formState.brand_voice = d.brand_voice || '专业'
  }).finally(() => { loading.value = false })
}

function addStrength() { strengths.value.push({ value: '' }) }
function removeStrength(idx) { if (strengths.value.length > 1) strengths.value.splice(idx, 1) }
function addCase() { cases.value.push({ name: '', result: '' }) }
function removeCase(idx) { if (cases.value.length > 1) cases.value.splice(idx, 1) }

function handleSubmit() {
  saving.value = true
  const vs = strengths.value.filter(s => s.value.trim())
  formState.core_strengths = vs.map(s => s.value).join('\n')
  const vc = cases.value.filter(c => c.name.trim())
  formState.case_stories = vc.length ? JSON.stringify(vc) : ''
  saveProfile({ ...formState }).then(() => { message.success('保存成功') }).finally(() => { saving.value = false })
}

onMounted(() => { load() })
</script>
