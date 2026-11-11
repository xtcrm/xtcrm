<template>
  <a-card :bordered="false">
    <template #title>
      报表
      <a-tag v-if="ov.scope" style="margin-left:8px">{{ ov.scope }}</a-tag>
    </template>
    <a-tabs v-model:activeKey="tab" @change="onTabChange">
      <!-- 经营总览 -->
      <a-tab-pane key="overview" tab="经营总览">
        <a-spin :spinning="loading.overview">
          <a-row :gutter="16" class="stats-row">
            <a-col :span="6"><div class="stat-card"><div class="stat-num">{{ ov.finance_visible === false ? '—' : '¥' + money(ov.revenue) }}</div><div class="stat-label">本月回款<span v-if="ov.finance_visible === false">（仅管理层可见）</span><span v-else-if="ov.revenue_growth !== null"> · 环比 {{ ov.revenue_growth }}%</span></div></div></a-col>
            <a-col :span="6"><div class="stat-card success"><div class="stat-num">¥{{ money(ov.commission) }}</div><div class="stat-label">本月业绩（按回款确认）</div></div></a-col>
            <a-col :span="6"><div class="stat-card warn"><div class="stat-num">{{ ov.finance_visible === false ? '—' : '¥' + money(ov.receivable) }}</div><div class="stat-label">未收款合计</div></div></a-col>
            <a-col :span="6"><div class="stat-card muted"><div class="stat-num">{{ ov.pending_cs || 0 }}</div><div class="stat-label">待分配客服</div></div></a-col>
          </a-row>

          <a-row :gutter="16">
            <a-col :span="14">
              <a-card size="small" title="近 12 个月营收与业绩">
                <div class="trend">
                  <div v-for="p in trend" :key="p.month" class="trend-col">
                    <div class="trend-bars">
                      <div class="bar bar-rev" :style="{ height: barH(p.revenue) + 'px' }" :title="'回款 ¥' + money(p.revenue)"></div>
                      <div class="bar bar-com" :style="{ height: barH(p.commission) + 'px' }" :title="'业绩 ¥' + money(p.commission)"></div>
                    </div>
                    <div class="trend-label">{{ p.month.slice(2) }}</div>
                  </div>
                </div>
                <div class="legend"><i class="dot rev"></i>回款 <i class="dot com"></i>业绩</div>
              </a-card>
            </a-col>
            <a-col :span="10">
              <a-card size="small" title="本月业绩结构">
                <a-table :columns="bizCols" :data-source="ov.by_biz || []" :pagination="false" size="small" row-key="biz_type">
                  <template #bodyCell="{ column, record }">
                    <template v-if="column.key === 'biz_type'">{{ bizText(record.biz_type) }}</template>
                    <template v-if="column.key === 'amount'">¥{{ money(record.amount) }}</template>
                  </template>
                </a-table>
              </a-card>
              <a-card size="small" title="客户等级结构" style="margin-top:12px">
                <a-table :columns="levelCols" :data-source="ov.levels || []" :pagination="false" size="small" row-key="level_name">
                  <template #bodyCell="{ column, record }">
                    <template v-if="column.key === 'level_name'">{{ record.level_name || '未分级' }}</template>
                  </template>
                </a-table>
              </a-card>
            </a-col>
          </a-row>
        </a-spin>
      </a-tab-pane>

      <!-- 销售 -->
      <a-tab-pane key="sales" tab="销售">
        <a-spin :spinning="loading.sales">
          <a-row :gutter="16">
            <a-col :span="12">
              <a-card size="small" title="销售漏斗">
                <div v-for="f in funnel" :key="f.stage" class="funnel-row">
                  <span class="funnel-name">{{ f.name }}</span>
                  <div class="funnel-track"><div class="funnel-fill" :style="{ width: funnelW(f.count) + '%' }"></div></div>
                  <span class="funnel-num">{{ f.count }}</span>
                  <span class="funnel-conv">{{ f.conv_from_prev === null ? '—' : f.conv_from_prev + '%' }}</span>
                </div>
              </a-card>
            </a-col>
            <a-col :span="12">
              <a-card size="small" title="报价成交">
                <a-row :gutter="8">
                  <a-col :span="8"><a-statistic title="报价数" :value="quo.quotation_count || 0" /></a-col>
                  <a-col :span="8"><a-statistic title="成交数" :value="quo.won_count || 0" /></a-col>
                  <a-col :span="8"><a-statistic title="成交率" :value="quo.win_rate ?? 0" suffix="%" /></a-col>
                </a-row>
                <a-row :gutter="8" style="margin-top:12px">
                  <a-col :span="8"><a-statistic title="成交金额" :value="quo.won_amount || 0" prefix="¥" /></a-col>
                  <a-col :span="8"><a-statistic title="平均周期" :value="quo.avg_cycle_days ?? 0" suffix="天" /></a-col>
                </a-row>
              </a-card>
            </a-col>
          </a-row>
          <a-card size="small" title="业绩排行" style="margin-top:16px">
            <template #extra>
              <a-input v-model:value="rankMonth" placeholder="YYYY-MM" style="width:120px" @press-enter="loadRanking" />
            </template>
            <a-table :columns="rankCols" :data-source="rankList" :pagination="false" size="middle" row-key="user_id">
              <template #bodyCell="{ column, record, index }">
                <template v-if="column.key === 'rank'">{{ index + 1 }}</template>
                <template v-if="column.key === 'amount'"><b>¥{{ money(record.amount) }}</b></template>
                <template v-if="column.key === 'post_code'">{{ record.post_code === 'cs' ? '客服' : '销售' }}</template>
              </template>
            </a-table>
          </a-card>
        </a-spin>
      </a-tab-pane>

      <!-- 客服 -->
      <a-tab-pane key="cs" tab="客服">
        <a-spin :spinning="loading.cs">
          <a-card size="small" :title="`续费到期（未来 ${renewalDays} 天，共 ${renewalList.length} 个）`">
            <template #extra>
              <a-select v-model:value="renewalDays" style="width:110px" @change="loadRenewal">
                <a-select-option :value="30">30 天</a-select-option>
                <a-select-option :value="60">60 天</a-select-option>
                <a-select-option :value="90">90 天</a-select-option>
              </a-select>
            </template>
            <a-table :columns="renewalCols" :data-source="renewalList" :pagination="{ pageSize: 10 }" size="middle" row-key="id">
              <template #bodyCell="{ column, record }">
                <template v-if="column.key === 'days_left'">
                  <a-tag :color="record.days_left <= 30 ? 'red' : (record.days_left <= 60 ? 'orange' : 'blue')">{{ record.days_left }} 天</a-tag>
                </template>
                <template v-if="column.key === 'service_end_time'">{{ date(record.service_end_time) }}</template>
              </template>
            </a-table>
          </a-card>
        </a-spin>
      </a-tab-pane>

      <!-- 交付 -->
      <a-tab-pane key="delivery" tab="交付">
        <a-spin :spinning="loading.delivery">
          <a-row :gutter="16">
            <a-col :span="6"><div class="stat-card"><div class="stat-num">{{ del.completed || 0 }}</div><div class="stat-label">本月完成项目</div></div></a-col>
            <a-col :span="6"><div class="stat-card success"><div class="stat-num">{{ del.on_time_rate === null ? '—' : del.on_time_rate + '%' }}</div><div class="stat-label">按时完成率</div></div></a-col>
            <a-col :span="6"><div class="stat-card warn"><div class="stat-num">{{ del.pending_reviews || 0 }}</div><div class="stat-label">待审核</div></div></a-col>
            <a-col :span="6"><div class="stat-card muted"><div class="stat-num">{{ del.reject_rate === null ? '—' : del.reject_rate + '%' }}</div><div class="stat-label">审核退回率</div></div></a-col>
          </a-row>
          <a-row :gutter="16" style="margin-top:16px">
            <a-col :span="8"><a-card size="small"><a-statistic title="本月审核次数" :value="del.review_count || 0" /></a-card></a-col>
            <a-col :span="8"><a-card size="small"><a-statistic title="平均审核时长" :value="del.avg_review_hours ?? 0" suffix="小时" /></a-card></a-col>
            <a-col :span="8"><a-card size="small"><a-statistic title="平均响应时长" :value="del.avg_response_hours ?? 0" suffix="小时" /></a-card></a-col>
          </a-row>
          <a-card size="small" title="退回原因 TOP5" style="margin-top:16px">
            <a-empty v-if="!reasonList.length" description="本月没有退回记录" />
            <div v-for="r in reasonList" :key="r.reason" class="reason-row">
              <span class="reason-text">{{ r.reason }}</span>
              <a-progress :percent="reasonPercent(r.count)" size="small" style="flex:1" />
              <span class="reason-num">{{ r.count }}</span>
            </div>
          </a-card>
        </a-spin>
      </a-tab-pane>

      <!-- 财务 -->
      <a-tab-pane key="finance" tab="财务">
        <a-spin :spinning="loading.finance">
          <a-row :gutter="16">
            <a-col v-for="b in recv.buckets || []" :key="b.key" :span="6">
              <div class="stat-card" :class="{ warn: b.key === 'd90p' }">
                <div class="stat-num">¥{{ money(b.amount) }}</div>
                <div class="stat-label">{{ b.label }} · {{ b.count }} 单</div>
              </div>
            </a-col>
          </a-row>
          <a-card size="small" title="未收款 TOP10" style="margin-top:16px">
            <a-table :columns="recvCols" :data-source="recv.top || []" :pagination="false" size="middle" row-key="id">
              <template #bodyCell="{ column, record }">
                <template v-if="column.key === 'unpaid_amount'"><b>¥{{ money(record.unpaid_amount) }}</b></template>
              </template>
            </a-table>
          </a-card>
        </a-spin>
      </a-tab-pane>
    </a-tabs>
  </a-card>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { message } from 'ant-design-vue'
import {
  getOverview, getRevenueTrend, getRanking, getFunnel,
  getQuotation, getRenewal, getDelivery, getReceivable,
} from '@/api/crm/report'

const route = useRoute()
const tab = ref(route.meta?.tab || 'overview')
const loading = reactive({ overview: false, sales: false, cs: false, delivery: false, finance: false })

const ov = reactive({})
const trend = ref([])
const funnel = ref([])
const quo = reactive({})
const rankList = ref([])
const rankMonth = ref('')
const renewalDays = ref(90)
const renewalList = ref([])
const del = reactive({})
const recv = reactive({})

const bizCols = [
  { title: '业务类型', key: 'biz_type', width: 100 },
  { title: '笔数', dataIndex: 'cnt', key: 'cnt', width: 80 },
  { title: '金额', key: 'amount' },
]
const levelCols = [
  { title: '等级', key: 'level_name' },
  { title: '客户数', dataIndex: 'cnt', key: 'cnt', width: 90 },
]
const rankCols = [
  { title: '#', key: 'rank', width: 50 },
  { title: '姓名', dataIndex: 'user_name', key: 'user_name', width: 120 },
  { title: '部门', dataIndex: 'department_name', key: 'department_name', width: 120 },
  { title: '岗位', key: 'post_code', width: 80 },
  { title: '业绩', key: 'amount', width: 140 },
]
const renewalCols = [
  { title: '客户', dataIndex: 'customer_name', key: 'customer_name', width: 200 },
  { title: '项目', dataIndex: 'title', key: 'title' },
  { title: '客服', dataIndex: 'cs_user_name', key: 'cs_user_name', width: 100 },
  { title: '到期日', key: 'service_end_time', width: 120 },
  { title: '剩余', key: 'days_left', width: 100 },
]
const recvCols = [
  { title: '客户', dataIndex: 'customer_name', key: 'customer_name', width: 200 },
  { title: '订单号', dataIndex: 'order_no', key: 'order_no', width: 160 },
  { title: '未收金额', key: 'unpaid_amount', width: 140 },
]

const reasonList = computed(() => Object.entries(del.reasons || {}).map(([reason, count]) => ({ reason, count })))
const maxFunnel = computed(() => Math.max(1, ...funnel.value.map(f => f.count)))
const maxTrend = computed(() => Math.max(1, ...trend.value.map(p => Math.max(p.revenue, p.commission))))

function barH(v) { return Math.max(2, Math.round(Number(v || 0) / maxTrend.value * 110)) }
function funnelW(v) { return Math.round(Number(v || 0) / maxFunnel.value * 100) }
function reasonPercent(v) { return Math.round(v / Math.max(1, ...reasonList.value.map(r => r.count)) * 100) }
function money(v) { return Number(v || 0).toLocaleString(undefined, { maximumFractionDigits: 0 }) }
function date(t) { return t ? new Date(Number(t) * 1000).toLocaleDateString() : '' }
function bizText(v) { return { 1: '新签', 2: '续费', 3: '增购' }[v] || v }

async function loadOverview() {
  loading.overview = true
  try {
    const [a, b] = await Promise.allSettled([getOverview(), getRevenueTrend()])
    if (a.status === 'fulfilled' && a.value?.status === 200) Object.assign(ov, a.value.data || {})
    if (b.status === 'fulfilled' && b.value?.status === 200) trend.value = b.value.data || []
  } finally { loading.overview = false }
}

async function loadSales() {
  loading.sales = true
  try {
    const [f, q] = await Promise.allSettled([getFunnel(), getQuotation({})])
    if (f.status === 'fulfilled' && f.value?.status === 200) funnel.value = f.value.data || []
    if (q.status === 'fulfilled' && q.value?.status === 200) Object.assign(quo, q.value.data || {})
    await loadRanking()
  } finally { loading.sales = false }
}

async function loadRanking() {
  const res = await getRanking({ month: rankMonth.value })
  if (res?.status === 200) rankList.value = res.data?.list || []
}

async function loadRenewal() {
  loading.cs = true
  try {
    const res = await getRenewal({ days: renewalDays.value })
    if (res?.status === 200) renewalList.value = res.data?.list || []
  } finally { loading.cs = false }
}

async function loadDelivery() {
  loading.delivery = true
  try {
    const res = await getDelivery()
    if (res?.status === 200) Object.assign(del, res.data || {})
  } finally { loading.delivery = false }
}

async function loadFinance() {
  loading.finance = true
  try {
    const res = await getReceivable()
    if (res?.status === 200) Object.assign(recv, res.data || {})
  } finally { loading.finance = false }
}

const loaded = {}
function onTabChange(key) {
  if (loaded[key]) return
  loaded[key] = true
  if (key === 'overview') loadOverview()
  if (key === 'sales') loadSales()
  if (key === 'cs') loadRenewal()
  if (key === 'delivery') loadDelivery()
  if (key === 'finance') loadFinance()
}

watch(() => route.meta?.tab, (t) => { if (t && t !== tab.value) { tab.value = t; onTabChange(t) } }, { immediate: true })
onTabChange(tab.value)
</script>

<style scoped>
.stats-row { margin-bottom: 16px; }
.stat-card {
  background: #fff; border-radius: 6px; padding: 16px 20px;
  border: 1px solid #f0f0f0;
}
.stat-card.success { border-left: 3px solid #52c41a; }
.stat-card.warn { border-left: 3px solid #faad14; }
.stat-card.muted { border-left: 3px solid #bfbfbf; }
.stat-card .stat-num { font-size: 24px; font-weight: 700; color: #1a1a1a; line-height: 1.3; }
.stat-card .stat-label { font-size: 12px; color: #999; margin-top: 4px; }

.trend { display: flex; align-items: flex-end; gap: 6px; height: 150px; padding: 0 4px; }
.trend-col { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; }
.trend-bars { display: flex; gap: 2px; align-items: flex-end; height: 120px; }
.bar { width: 8px; border-radius: 2px 2px 0 0; }
.bar-rev { background: #1890ff; }
.bar-com { background: #52c41a; }
.trend-label { font-size: 11px; color: #999; margin-top: 4px; }
.legend { font-size: 12px; color: #666; margin-top: 8px; }
.legend .dot { display: inline-block; width: 8px; height: 8px; border-radius: 2px; margin: 0 4px 0 12px; }
.legend .dot.rev { background: #1890ff; }
.legend .dot.com { background: #52c41a; }

.funnel-row { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
.funnel-name { width: 70px; font-size: 13px; color: #666; }
.funnel-track { flex: 1; height: 18px; background: #f5f5f5; border-radius: 4px; overflow: hidden; }
.funnel-fill { height: 100%; background: linear-gradient(90deg, #1890ff, #69c0ff); }
.funnel-num { width: 50px; text-align: right; font-weight: 600; }
.funnel-conv { width: 60px; text-align: right; font-size: 12px; color: #999; }

.reason-row { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; }
.reason-text { width: 200px; font-size: 13px; color: #333; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.reason-num { width: 40px; text-align: right; font-weight: 600; }
</style>
