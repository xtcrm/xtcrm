<template>
  <a-card :bordered="false">
    <!-- 公司选择 -->
    <div style="margin-bottom:16px">
      <a-select
        v-model:value="companyId"
        placeholder="选择开票公司"
        style="width:260px"
        :loading="companyLoading"
        @change="onCompanyChange"
      >
        <a-select-option v-for="c in companies" :key="c.id" :value="c.id">
          {{ c.name }}
        </a-select-option>
      </a-select>
      <a-button v-if="companyId" type="link" @click="$router.push({ name: 'invoice-company-detail', query: { id: companyId } })">公司详情</a-button>
    </div>

    <a-spin :spinning="loading">
      <template v-if="!companyId">
        <a-empty description="请选择开票公司查看税负数据" style="padding:80px 0" />
      </template>
      <template v-else>
        <!-- 本月概览 -->
        <a-row :gutter="16" style="margin-bottom:16px">
          <a-col :span="6">
            <a-card :bordered="false" class="stat-card">
              <div class="stat-label">本月销项</div>
              <div class="stat-value">¥{{ fmt(current?.output_amount) }}</div>
              <div class="stat-sub">{{ current?.output_count || 0 }} 笔</div>
            </a-card>
          </a-col>
          <a-col :span="6">
            <a-card :bordered="false" class="stat-card">
              <div class="stat-label">本月进项</div>
              <div class="stat-value">¥{{ fmt(current?.input_amount) }}</div>
              <div class="stat-sub">{{ current?.input_count || 0 }} 笔</div>
            </a-card>
          </a-col>
          <a-col :span="6">
            <a-card :bordered="false" class="stat-card">
              <div class="stat-label">应纳税额</div>
              <div class="stat-value" :style="{ color: (current?.net_tax || 0) > 0 ? '#cf1322' : '#389e0d' }">¥{{ fmt(current?.net_tax) }}</div>
              <div class="stat-sub">{{ current?.month }}</div>
            </a-card>
          </a-col>
          <a-col :span="6">
            <a-card :bordered="false" class="stat-card" :class="{ 'alert-card': hasAlert }">
              <div class="stat-label">
                税负率
                <a-tooltip title="税负率 = (销项税额 - 进项税额) / 销项金额 × 100%">
                  <question-circle-outlined style="color:#999;font-size:12px" />
                </a-tooltip>
              </div>
              <div class="stat-value" :style="{ color: taxBurdenColor }">{{ current?.tax_burden ?? 0 }}%</div>
              <div class="stat-sub">安全线 {{ threshold }}%</div>
            </a-card>
          </a-col>
        </a-row>

        <!-- 预警 -->
        <a-alert
          v-for="(a, i) in alerts" :key="i"
          :message="a.message"
          :type="a.level"
          show-icon
          closable
          style="margin-bottom:8px"
        />

        <!-- 趋势图 + 年度汇总 -->
        <a-row :gutter="16" style="margin-top:16px">
          <a-col :span="16">
            <a-card title="近 6 个月税负趋势" :bordered="false" size="small">
              <div ref="chartRef" style="height:300px" />
            </a-card>
          </a-col>
          <a-col :span="8">
            <a-card title="年度累计" :bordered="false" size="small">
              <a-descriptions :column="1" size="small" bordered>
                <a-descriptions-item label="销项金额">¥{{ fmt(yearOutputAmount) }}</a-descriptions-item>
                <a-descriptions-item label="销项笔数">{{ yearOutputCount }}</a-descriptions-item>
                <a-descriptions-item label="进项金额">¥{{ fmt(yearInputAmount) }}</a-descriptions-item>
                <a-descriptions-item label="进项笔数">{{ yearInputCount }}</a-descriptions-item>
              </a-descriptions>
            </a-card>
          </a-col>
        </a-row>
      </template>
    </a-spin>
  </a-card>
</template>

<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { QuestionCircleOutlined } from '@ant-design/icons-vue'
import * as echarts from 'echarts'
import { getDashboard, getCompanyList } from '../api'

const companyId = ref(0)
const companies = ref([])
const companyLoading = ref(false)
const loading = ref(false)
const current = ref({})
const alerts = ref([])
const hasAlert = ref(false)
const threshold = ref(1)
const yearInputAmount = ref(0)
const yearOutputAmount = ref(0)
const yearInputCount = ref(0)
const yearOutputCount = ref(0)
const trends = ref([])
const chartRef = ref(null)
let chartInstance = null

const taxBurdenColor = computed(() => {
  const v = current.value?.tax_burden ?? 0
  if (v < threshold.value) return '#cf1322'
  if (v < threshold.value * 1.5) return '#faad14'
  return '#389e0d'
})

function fmt(v) {
  if (v == null) return '0.00'
  return Number(v).toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

function loadCompanies() {
  companyLoading.value = true
  getCompanyList({ page: 1, pageSize: 200, status: '' }).then(r => {
    const res = r?.data?.list || r?.data || {}
    companies.value = res.data || res.list || []
    // 不自动选中，等用户手动选
  }).finally(() => { companyLoading.value = false })
}

function onCompanyChange() {
  if (!companyId.value) return
  load()
}

function load() {
  if (!companyId.value) return
  loading.value = true
  getDashboard(companyId.value).then(r => {
    const d = r?.data
    if (d?.empty) {
      current.value = {}
      return
    }
    current.value = d?.current ?? {}
    alerts.value = d?.alert ?? []
    hasAlert.value = d?.has_alert ?? false
    threshold.value = d?.threshold ?? 1
    yearInputAmount.value = d?.year_input_amount ?? 0
    yearOutputAmount.value = d?.year_output_amount ?? 0
    yearInputCount.value = d?.year_input_count ?? 0
    yearOutputCount.value = d?.year_output_count ?? 0
    trends.value = d?.trends ?? []
    nextTick(() => renderChart())
  }).finally(() => { loading.value = false })
}

function renderChart() {
  if (!chartRef.value) return
  if (chartInstance) chartInstance.dispose()
  chartInstance = echarts.init(chartRef.value)
  const months = trends.value.map(t => (t.month || '').substring(5) + '月')
  const rates = trends.value.map(t => t.tax_burden ?? 0)
  const outputs = trends.value.map(t => t.output_amount ?? 0)
  const inputs = trends.value.map(t => t.input_amount ?? 0)

  chartInstance.setOption({
    tooltip: { trigger: 'axis' },
    legend: { data: ['税负率', '销项金额', '进项金额'], bottom: 0 },
    grid: { left: 60, right: 60, top: 20, bottom: 30 },
    xAxis: { type: 'category', data: months },
    yAxis: [
      { type: 'value', name: '金额(元)', axisLabel: { formatter: v => (v / 10000).toFixed(0) + '万' } },
      { type: 'value', name: '税负率(%)', axisLabel: { formatter: '{value}%' } },
    ],
    series: [
      {
        name: '税负率', type: 'line', yAxisIndex: 1,
        data: rates,
        markLine: {
          silent: true,
          data: [{ yAxis: threshold.value, label: { formatter: '安全线 {c}%' }, lineStyle: { color: '#cf1322', type: 'dashed' } }],
        },
        itemStyle: { color: '#1890ff' },
      },
      { name: '销项金额', type: 'bar', data: outputs, itemStyle: { color: '#ff7a45' } },
      { name: '进项金额', type: 'bar', data: inputs, itemStyle: { color: '#52c41a' } },
    ],
  })
}

onMounted(loadCompanies)
</script>

<style scoped>
.stat-card { text-align: center; }
.stat-card .stat-label { font-size: 13px; color: #999; margin-bottom: 8px; }
.stat-card .stat-value { font-size: 24px; font-weight: 600; color: #333; }
.stat-card .stat-sub { font-size: 12px; color: #bbb; margin-top: 4px; }
.alert-card { border: 1px solid #ff4d4f; background: #fff1f0; }
</style>
