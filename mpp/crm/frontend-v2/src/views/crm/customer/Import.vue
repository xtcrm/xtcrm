<template>
  <a-modal title="导入客户" :open="open" :footer="null" @cancel="handleCancel" :width="800" :mask-closable="false">
    <a-tabs v-model:active-key="mode" style="margin-top:-8px">
    <a-tab-pane key="excel" tab="Excel 导入">

    <!-- Step 1: 上传文件 -->
    <div v-if="step === 1">
      <a-upload-dragger
        name="file"
        :multiple="false"
        :before-upload="handleUpload"
        :show-upload-list="false"
        accept=".xlsx,.xls,.csv"
      >
        <p class="ant-upload-drag-icon"><inbox-outlined style="font-size:48px;color:#d6893b" /></p>
        <p class="ant-upload-text">点击或拖拽 Excel 文件到此区域</p>
        <p class="ant-upload-hint">支持 .xlsx / .xls / .csv 格式，文件大小不超过 10MB</p>
      </a-upload-dragger>
      <div style="text-align:center;margin-top:12px">
        <a @click="downloadTemplate">下载导入模板</a>
      </div>
      <div v-if="uploading" style="text-align:center;margin-top:16px">
        <a-spin /> 正在解析文件...
      </div>
    </div>

    <!-- Step 2: 预览 + 列映射 -->
    <div v-else-if="step === 2">
      <a-alert :message="`已识别 ${result?.total || 0} 条数据，请确认列映射后导入`" type="info" show-icon style="margin-bottom:12px" />

      <!-- 列映射 -->
      <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px">
        <div v-for="(h, idx) in result.headers" :key="idx" style="display:flex;align-items:center;gap:4px;background:#f5f5f5;padding:4px 10px;border-radius:4px">
          <span style="font-size:12px;color:#999;white-space:nowrap">{{ h }}</span>
          <span style="color:#d6893b">→</span>
          <a-select v-model:value="mapping[idx]" size="small" style="width:110px" :options="fieldOptions" placeholder="忽略" allow-clear />
        </div>
      </div>

      <!-- 预览表格 -->
      <a-table :columns="previewCols" :data-source="result.rows.slice(0, 10)" :pagination="false" size="small" bordered :scroll="{ x: 'max-content' }" />
      <div v-if="result.total > 10" style="text-align:center;color:#999;margin-top:8px">仅显示前 10 行，共 {{ result.total }} 行</div>

      <div style="margin-top:12px;display:flex;align-items:center;justify-content:space-between">
        <a-checkbox v-model:checked="useArchive">
          用企业档案核验工商信息
          <span style="color:#999;font-size:12px">（只查本地档案，不产生费用；税号与名称对不上的行会被跳过）</span>
        </a-checkbox>
      </div>

      <div style="text-align:right;margin-top:16px">
        <a-button @click="step = 1" style="margin-right:8px">重新选择</a-button>
        <a-button type="primary" :loading="importing" @click="handleConfirm">确认导入 {{ result.total }} 条</a-button>
      </div>
    </div>

    <!-- Step 3: 结果 -->
    <div v-else>
      <a-result
        :status="importResult.error > 0 ? 'warning' : 'success'"
        :title="`导入完成`"
        :sub-title="`成功 ${importResult.success} 条，跳过 ${importResult.skip} 条` + (importResult.error > 0 ? `，失败 ${importResult.error} 条` : '')"
      />
      <div v-if="importResult.errors && importResult.errors.length" style="max-height:200px;overflow-y:auto;margin-top:8px">
        <div v-for="(e, i) in importResult.errors" :key="i" style="color:#ff4d4f;font-size:12px">第 {{ e.row }} 行：{{ e.reason }}</div>
      </div>
      <div style="text-align:center;margin-top:16px">
        <a-button type="primary" @click="handleCancel">完成</a-button>
      </div>
    </div>
    </a-tab-pane>

    <!-- AI 识别 tab -->
    <a-tab-pane key="ai" tab="AI 识别">
      <div v-if="!aiResult">
        <a-textarea v-model:value="aiText" placeholder="粘贴企查查/天眼查/爱企查复制的企业页面文本..." :rows="10" style="margin-bottom:12px" />
        <div style="text-align:right">
          <a-button type="primary" :loading="aiLoading" @click="handleAiParse">AI 智能识别</a-button>
        </div>
      </div>
      <div v-else>
        <a-alert message="AI 已识别以下字段，请核对后确认导入" type="success" show-icon style="margin-bottom:12px" />
        <a-descriptions size="small" bordered :column="2">
          <a-descriptions-item v-for="(v,k) in aiResult" :key="k" :label="FIELD_LABELS[k] || k">
            {{ v || '-' }}
          </a-descriptions-item>
        </a-descriptions>
        <div style="text-align:right;margin-top:16px">
          <a-button @click="aiResult = null" style="margin-right:8px">重新识别</a-button>
          <a-button type="primary" :loading="aiImporting" @click="handleAiImport">确认导入</a-button>
        </div>
      </div>
    </a-tab-pane>
    </a-tabs>
  </a-modal>
</template>

<script setup>
import { ref, computed } from 'vue'
import { message } from 'ant-design-vue'
import { InboxOutlined } from '@ant-design/icons-vue'
import { uploadImport, confirmImport, aiParse } from '@/api/crm/customer'

const mode = ref('excel')

// AI 识别
const aiText = ref('')
const aiLoading = ref(false)
const aiResult = ref(null)
const aiImporting = ref(false)

const props = defineProps({ open: Boolean })
const emit = defineEmits(['update:open', 'ok'])

const step = ref(1)
const useArchive = ref(false)
const uploading = ref(false)
const importing = ref(false)
const result = ref(null)
const mapping = ref({})
const importResult = ref({ success: 0, skip: 0, error: 0, errors: [] })

const TEMPLATE_HEADERS = ['公司名称', '简称', '行业', '客户等级', '客户来源', '客户分组',
  '法定代表人', '注册资本', '实缴资本', '成立日期',
  '经营范围', '注册地址', '详细地址', '统一社会信用代码', '登记状态',
  '工商注册号', '组织机构代码', '公司电话', '公司邮箱', '网址',
  '纳税人资质', '参保人数', '公司简介', '营业期限', '备注']

function downloadTemplate() {
  // 生成 CSV（Excel 直接打开），前端本地生成无需后端认证
  const bom = '﻿' // BOM for Excel Chinese
  const csv = bom + TEMPLATE_HEADERS.join(',') + '\n'
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' })
  const url = URL.createObjectURL(blob)
  const a = document.createElement('a')
  a.href = url; a.download = '客户导入模板.csv'; a.click()
  URL.revokeObjectURL(url)
}

const FIELD_LABELS = {
  customer_name: '公司名称', short_name: '简称',
  industry: '行业名称', level_name: '客户等级', source: '客户来源', customer_group: '客户分组',
  legal_representative: '法定代表人', registered_capital: '注册资本', paid_in_capital: '实缴资本',
  established_date: '成立日期', business_scope: '经营范围',
  registered_address: '注册地址', address: '详细地址',
  tax_number: '统一信用代码', registration_status: '登记状态',
  business_registration_no: '工商注册号', organization_code: '组织机构代码',
  telephone: '公司电话', email: '公司邮箱', website: '网址',
  taxpayer_qualification: '纳税人资质', insured_count: '参保人数',
  introduction: '公司简介', remark: '备注',
}

const fieldOptions = computed(() => {
  const opts = [{ value: '', label: '— 忽略 —' }]
  Object.entries(FIELD_LABELS).forEach(([k, v]) => opts.push({ value: k, label: v }))
  return opts
})

const previewCols = computed(() => {
  if (!result.value) return []
  const cols = [{ title: '#', width: 50, customRender: ({ index }) => index + 1 }]
  result.value.headers.forEach((h, idx) => {
    cols.push({
      title: h,
      dataIndex: idx,
      key: idx,
      width: 120,
      ellipsis: true,
      customRender: ({ text }) => text || '-'
    })
  })
  return cols
})

async function handleUpload(file) {
  if (file.size > 10 * 1024 * 1024) { message.warning('文件不能超过 10MB'); return false }
  uploading.value = true
  try {
    const res = await uploadImport(file)
    result.value = res?.data || res
    // 初始化列映射
    const auto = {}
    result.value.headers.forEach((h, idx) => {
      auto[idx] = result.value.mapping?.[idx] || ''
    })
    mapping.value = auto
    step.value = 2
  } catch (e) {
    message.error('解析失败：' + (e?.message || '请检查文件格式'))
  } finally { uploading.value = false }
  return false // 阻止默认上传
}

async function handleConfirm() {
  importing.value = true
  try {
    const res = await confirmImport(result.value.rows, mapping.value, useArchive.value)
    importResult.value = res?.data || res || {}
    step.value = 3
  } catch (e) {
    message.error('导入失败')
  } finally { importing.value = false }
}

async function handleAiParse() {
  if (!aiText.value.trim()) { message.warning('请粘贴企业信息文本'); return }
  aiLoading.value = true
  try {
    const res = await aiParse(aiText.value)
    aiResult.value = res?.data || res
    if (!aiResult.value?.customer_name) message.warning('AI 未能识别出公司名称，请检查文本')
  } catch (e) { message.error('AI 识别失败') }
  finally { aiLoading.value = false }
}

async function handleAiImport() {
  if (!aiResult.value?.customer_name) return
  aiImporting.value = true
  try {
    // 用 AI 结果作为单行数据，字段映射用 key 直接匹配
    const row = Object.values(aiResult.value)
    const mapping = {}
    Object.keys(aiResult.value).forEach((k, i) => { mapping[i] = k })
    const res = await confirmImport([row], mapping)
    importResult.value = res?.data || res || {}
    mode.value = 'excel'; step.value = 3 // 切到结果显示
    aiResult.value = null; aiText.value = ''
  } catch (e) { message.error('导入失败') }
  finally { aiImporting.value = false }
}

function handleCancel() {
  step.value = 1; result.value = null; mapping.value = {}; importResult.value = { success: 0, skip: 0, error: 0, errors: [] }
  aiResult.value = null; aiText.value = ''
  emit('update:open', false)
  emit('ok')
}
</script>
