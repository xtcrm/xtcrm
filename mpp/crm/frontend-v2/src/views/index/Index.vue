<template>
  <div class="workbench">
    <!-- 顶部欢迎栏 -->
    <div class="wb-hero">
      <div class="hero-left">
        <div class="hero-greeting">{{ greeting }}，{{ nickname }}</div>
        <div class="hero-date">{{ today }}</div>
      </div>
      <div class="hero-stats">
        <div class="hero-stat">
          <div class="hs-num">{{ data.newCustomers || 0 }}</div>
          <div class="hs-label">本月新客</div>
        </div>
        <div class="hero-stat">
          <div class="hs-num warn">{{ data.pendingFollowup || 0 }}</div>
          <div class="hs-label">待跟进</div>
        </div>
        <div class="hero-stat">
          <div class="hs-num">{{ formatMoney(data.monthOrder || 0) }}</div>
          <div class="hs-label">本月订单</div>
        </div>
        <div class="hero-stat">
          <div class="hs-num danger">{{ formatMoney(data.unpaid || 0) }}</div>
          <div class="hs-label">待收款</div>
        </div>
      </div>
    </div>

    <!-- 洞察区 -->
    <div v-if="attentionInsights.length || opportunityInsights.length" class="wb-insights">
      <div v-if="attentionInsights.length" class="insight-section">
        <div class="insight-label danger">⚠ 需关注</div>
        <div class="insight-cards">
          <div v-for="item in attentionInsights.slice(0, 3)" :key="item.id" class="insight-card danger" @click="handleInsightAction(item)">
            <div class="ic-head">
              <span class="ic-title">{{ item.title }}</span>
              <a-button type="link" size="small" @click.stop="dismissInsightItem(item)">忽略</a-button>
            </div>
            <div class="ic-summary">{{ item.summary }}</div>
            <div v-if="item.suggestion_text && item.suggestion_accepted === null" class="ic-suggestion">
              💡 {{ item.suggestion_text }}
              <span class="ic-sug-actions">
                <a @click.stop="adoptSuggestion(item)">采纳</a>
                <a @click.stop="rejectSuggestion(item)" style="color:#999">不采纳</a>
              </span>
            </div>
          </div>
        </div>
      </div>
      <div v-if="opportunityInsights.length" class="insight-section">
        <div class="insight-label success">💡 机会发现</div>
        <div class="insight-cards">
          <div v-for="item in opportunityInsights.slice(0, 3)" :key="item.id" class="insight-card success" @click="handleInsightAction(item)">
            <div class="ic-head"><span class="ic-title">{{ item.title }}</span></div>
            <div class="ic-summary">{{ item.summary }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- 核心指标卡 -->
    <div class="wb-metrics">
      <div class="metric-card">
        <div class="mc-icon" style="background: linear-gradient(135deg, #d6893b, #e8a85f)"><team-outlined /></div>
        <div class="mc-body">
          <div class="mc-num">{{ data.poolCount || 0 }}</div>
          <div class="mc-label">公海客户</div>
        </div>
      </div>
      <div class="metric-card">
        <div class="mc-icon" style="background: linear-gradient(135deg, #1677ff, #4096ff)"><file-text-outlined /></div>
        <div class="mc-body">
          <div class="mc-num">{{ formatMoney(data.monthQuotation || 0) }}</div>
          <div class="mc-label">本月报价总额</div>
        </div>
      </div>
      <div v-if="data.kpi" class="metric-card" v-for="k in (Array.isArray(data.kpi) ? data.kpi : [])" :key="k.label">
        <div class="mc-icon" style="background: linear-gradient(135deg, #52c41a, #73d13d)"><rise-outlined /></div>
        <div class="mc-body"><div class="mc-num">{{ k.value }}</div><div class="mc-label">{{ k.label }}</div></div>
      </div>
    </div>

    <!-- 漏斗 + 趋势 -->
    <div class="wb-charts">
      <div class="chart-card">
        <div class="chart-title">销售漏斗</div>
        <div v-if="funnel.length" class="funnel-chart">
          <div class="funnel-bar" v-for="(s, i) in funnel" :key="i">
            <div class="funnel-label">{{ s.label }}</div>
            <div class="funnel-track">
              <div class="funnel-fill" :style="{ width: Math.max(s.count / maxFunnel * 100, 3) + '%', background: funnelColors[i] }">
                <span class="funnel-cnt">{{ s.count }}个</span>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="chart-empty">暂无漏斗数据</div>
      </div>
      <div class="chart-card">
        <div class="chart-title">营收趋势</div>
        <div v-if="trendData.length" class="trend-chart">
          <div class="trend-col" v-for="(t, i) in trendData" :key="i">
            <span class="trend-amount" v-if="t.amount > 0">{{ (t.amount / 10000).toFixed(1) }}万</span>
            <div class="trend-bar" :style="{ height: Math.max(t.amount / maxTrend * 100, 2) + '%' }"></div>
            <span class="trend-month">{{ t.month?.slice(2) }}</span>
          </div>
        </div>
        <div v-else class="chart-empty">暂无趋势数据</div>
      </div>
    </div>

    <!-- 快捷入口 -->
    <div class="wb-quick">
      <div class="quick-item" v-for="q in quickEntries" :key="q.label" @click="router.push(q.path)">
        <div class="qi-icon"><component :is="q.icon" /></div>
        <div class="qi-label">{{ q.label }}</div>
      </div>
    </div>

    <!-- 最近跟进 -->
    <div class="wb-follow">
      <div class="follow-title">最近跟进</div>
      <div v-if="(data.recentFollows || []).length" class="follow-list">
        <div class="follow-item" v-for="f in (data.recentFollows || []).slice(0, 10)" :key="f.id">
          <div class="fi-dot" :class="f.result === '有效' ? 'active' : ''"></div>
          <div class="fi-body">
            <div class="fi-head">
              <strong>{{ f.customer_name }}</strong>
              <span class="fi-user">{{ f.owner_user_name }}</span>
              <span class="fi-time">{{ f.follow_date ? new Date(f.follow_date * 1000).toLocaleDateString() : '' }}</span>
            </div>
            <div class="fi-content" v-html="decodeHtml(f.follow_content?.substring(0, 120))"></div>
          </div>
        </div>
      </div>
      <div v-else class="follow-empty">暂无跟进记录</div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { TeamOutlined, FileTextOutlined, RiseOutlined, PlusCircleOutlined, UsergroupAddOutlined, BulbOutlined, BookOutlined, BarChartOutlined, FileAddOutlined } from '@ant-design/icons-vue'
import { getIndex } from '@/api/crm/dashboard'
import { dismissInsight } from '@/api/crm/insight'
import { acceptSuggestion as acceptSuggestionApi, rejectSuggestion as rejectSuggestionApi } from '@/api/crm/suggestion'
import { getProjection } from '@/api/crm/projection'

const router = useRouter()
const loading = ref(false)
const data = reactive({})
const trendData = ref([])

const funnelColors = ['#e8a85f', '#d6893b', '#c67a2f', '#b87326', '#9e621e']
const WARNING_TYPES = ['followup_overdue', 'pool_warning', 'churn_risk']
const OPPORTUNITY_TYPES = ['repurchase_window', 'cross_sell', 'dormant']

const nickname = ref('')

const greeting = computed(() => {
  const h = new Date().getHours()
  return h < 12 ? '早上好' : h < 18 ? '下午好' : '晚上好'
})

const today = computed(() => {
  const d = new Date()
  const week = ['周日', '周一', '周二', '周三', '周四', '周五', '周六']
  return `${d.getFullYear()}年${d.getMonth() + 1}月${d.getDate()}日 ${week[d.getDay()]}`
})

const quickEntries = [
  { label: '新增客户', path: '/crm/customer/create', icon: PlusCircleOutlined },
  { label: '客户列表', path: '/crm/customer/index', icon: UsergroupAddOutlined },
  { label: '新建报价', path: '/crm/quotation/index', icon: FileAddOutlined },
  { label: '线索管理', path: '/crm/lead/index', icon: BulbOutlined },
  { label: '知识库', path: '/crm/knowledge/index', icon: BookOutlined },
  { label: '内容效果', path: '/crm/content/analytics', icon: BarChartOutlined },
]

const funnel = computed(() => data.funnel || [])
const maxFunnel = computed(() => Math.max(...funnel.value.map(s => s.count), 1))
const maxTrend = computed(() => Math.max(...trendData.value.map(t => t.amount || 0), 1))
const attentionInsights = computed(() => (data.insights || []).filter(i => WARNING_TYPES.includes(i.type)))
const opportunityInsights = computed(() => (data.insights || []).filter(i => OPPORTUNITY_TYPES.includes(i.type)))

function formatMoney(v) { return (v || 0).toLocaleString('zh-CN', { minimumFractionDigits: 2 }) }
function decodeHtml(html) { if (!html) return ''; return html.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&').replace(/&quot;/g, '"').replace(/<[^>]*>/g, '') }

async function loadData() {
  loading.value = true
  try {
    const res = await getIndex()
    if (res?.status === 200) {
      const d = res.data || {}
      if (typeof d.kpi === 'string') { try { d.kpi = JSON.parse(d.kpi) } catch { delete d.kpi } }
      if (d.kpi && !Array.isArray(d.kpi)) {
        const kpiObj = d.kpi
        d.kpi = [
          { label: '本月营收', value: formatMoney(kpiObj.revenue || 0) },
          { label: '订单数', value: kpiObj.order_count || 0 },
          { label: '新客户', value: kpiObj.new_customers || 0 },
        ]
      }
      if (typeof d.ranking === 'string') { try { d.ranking = JSON.parse(d.ranking) } catch { delete d.ranking } }
      Object.assign(data, d)
      loadTrend()
    }
  } catch {} finally { loading.value = false }
}

async function loadTrend() {
  try {
    const res = await getProjection('revenue_trend')
    trendData.value = res?.data || []
  } catch {}
}

function dismissInsightItem(item) {
  dismissInsight(item.id, '手动忽略').then(() => {
    data.insights = (data.insights || []).filter(i => i.id !== item.id)
  })
}

function adoptSuggestion(item) {
  const id = item.suggestion_record_id
  if (!id) return
  acceptSuggestionApi(id).then(() => { item.suggestion_accepted = 1 })
}

function rejectSuggestion(item) {
  const id = item.suggestion_record_id
  if (!id) return
  rejectSuggestionApi(id).then(() => { item.suggestion_accepted = 0 })
}

function handleInsightAction(item) {
  if (!item.action_url) return
  const [path, qs] = item.action_url.split('?')
  const query = {}
  if (qs) qs.split('&').forEach(p => { const [k, v] = p.split('='); if (k) query[k] = decodeURIComponent(v || '') })
  router.push({ path, query })
}

onMounted(() => { loadData() })
</script>

<style scoped>
.workbench { margin: -8px -12px; }

/* ===== Hero ===== */
.wb-hero {
  background: linear-gradient(160deg, #fbf7f0 0%, #f4eadb 40%, #f0e4cf 100%);
  border-radius: 12px; padding: 28px 32px; margin-bottom: 24px;
  display: flex; justify-content: space-between; align-items: center;
  position: relative; overflow: hidden;
  box-shadow: 0 1px 3px rgba(0,0,0,.04), inset 0 1px 0 rgba(255,255,255,.6);
}
.wb-hero::after {
  content: ''; position: absolute; right: 32px; bottom: -20px;
  width: 180px; height: 180px; border-radius: 50%;
  background: radial-gradient(circle, rgba(214,137,59,.08) 0%, transparent 70%);
  pointer-events: none;
}
.hero-left { position: relative; z-index: 1; }
.hero-greeting { font-size: 22px; font-weight: 700; color: #2c2416; letter-spacing: -.3px; }
.hero-date { font-size: 13px; color: #8c7a60; margin-top: 6px; }
.hero-stats { display: flex; gap: 40px; position: relative; z-index: 1; }
.hero-stat { text-align: center; }
.hs-num { font-size: 26px; font-weight: 700; color: #3d3629; line-height: 1.2; font-variant-numeric: tabular-nums; font-family: 'SF Mono', 'Consolas', 'Menlo', monospace; }
.hs-num.warn { color: #c67a2f; }
.hs-num.danger { color: #d9534f; }
.hs-label { font-size: 11px; color: #8c7a60; margin-top: 3px; text-transform: uppercase; letter-spacing: .5px; }

/* ===== Insights ===== */
.wb-insights { margin-bottom: 24px; }
.insight-section { margin-bottom: 10px; }
.insight-label { font-size: 12px; font-weight: 600; margin-bottom: 8px; text-transform: uppercase; letter-spacing: .6px; }
.insight-label.danger { color: #d9534f; }
.insight-label.success { color: #5b8c5a; }
.insight-cards { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
.insight-card {
  background: #fff; border-radius: 8px; padding: 14px 16px; cursor: pointer;
  border: 1px solid #f0f0f0; transition: all .2s;
  position: relative;
}
.insight-card::before { content: ''; position: absolute; left: 0; top: 10%; height: 80%; width: 3px; border-radius: 0 2px 2px 0; }
.insight-card.danger::before { background: #ff4d4f; }
.insight-card.success::before { background: #52c41a; }
.insight-card:hover { box-shadow: 0 3px 12px rgba(0,0,0,.07); transform: translateY(-1px); }
.ic-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
.ic-title { font-size: 13px; font-weight: 600; color: #333; }
.ic-summary { font-size: 12px; color: #666; line-height: 1.65; }
.ic-suggestion { margin-top: 8px; background: #f6ffed; padding: 6px 10px; border-radius: 4px; font-size: 12px; color: #52c41a; display: flex; justify-content: space-between; align-items: center; }
.ic-sug-actions { display: flex; gap: 8px; flex-shrink: 0; }

/* ===== Metrics ===== */
.wb-metrics { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 12px; margin-bottom: 24px; }
.metric-card {
  background: #fff; border-radius: 8px; padding: 18px 16px;
  display: flex; align-items: center; gap: 14px;
  border: 1px solid #f0f0f0; transition: all .2s;
}
.metric-card:hover { box-shadow: 0 3px 12px rgba(0,0,0,.05); transform: translateY(-1px); }
.mc-icon {
  width: 42px; height: 42px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  color: #fff; font-size: 18px; flex-shrink: 0;
}
.mc-body { min-width: 0; }
.mc-num { font-size: 22px; font-weight: 700; color: #2c2416; line-height: 1.2; font-variant-numeric: tabular-nums; font-family: 'SF Mono', 'Consolas', monospace; }
.mc-label { font-size: 11px; color: #999; margin-top: 3px; }

/* ===== Charts ===== */
.wb-charts { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 24px; }
.chart-card { background: #fff; border-radius: 8px; padding: 20px 22px; border: 1px solid #f0f0f0; }
.chart-title { font-size: 13px; font-weight: 600; color: #333; margin-bottom: 18px; text-transform: uppercase; letter-spacing: .5px; }
.chart-empty { height: 160px; display: flex; align-items: center; justify-content: center; color: #ccc; font-size: 13px; }

.funnel-chart { display: flex; flex-direction: column; gap: 10px; }
.funnel-bar { display: flex; align-items: center; gap: 10px; }
.funnel-label { font-size: 12px; color: #666; width: 72px; flex-shrink: 0; text-align: right; }
.funnel-track { flex: 1; height: 28px; background: #f5f5f5; border-radius: 4px; overflow: hidden; }
.funnel-fill { height: 100%; border-radius: 4px; display: flex; align-items: center; justify-content: flex-end; padding-right: 8px; transition: width .5s cubic-bezier(.4,0,.2,1); }
.funnel-cnt { font-size: 11px; color: #fff; font-weight: 600; white-space: nowrap; text-shadow: 0 1px 2px rgba(0,0,0,.15); }

.trend-chart { display: flex; align-items: flex-end; gap: 3px; height: 180px; padding: 0 6px; }
.trend-col { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; }
.trend-amount { font-size: 9px; color: #aaa; margin-bottom: 4px; }
.trend-bar { width: 100%; max-width: 38px; background: linear-gradient(180deg, #e8a85f, #d6893b); border-radius: 3px 3px 0 0; min-height: 4px; transition: height .5s cubic-bezier(.4,0,.2,1); }
.trend-month { font-size: 10px; color: #bbb; margin-top: 6px; }

/* ===== Quick ===== */
.wb-quick { display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px; margin-bottom: 24px; }
.quick-item {
  background: #fff; border: 1px solid #f0f0f0; border-radius: 8px;
  padding: 22px 12px; text-align: center; cursor: pointer;
  transition: all .2s;
}
.quick-item:hover { border-color: #d6893b; background: #fefaf3; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(214,137,59,.1); }
.qi-icon { font-size: 28px; color: #d6893b; margin-bottom: 10px; }
.qi-label { font-size: 12px; color: #666; font-weight: 500; }

/* ===== Follow ===== */
.wb-follow { background: #fff; border-radius: 8px; border: 1px solid #f0f0f0; padding: 20px 22px; }
.follow-title { font-size: 13px; font-weight: 600; color: #333; margin-bottom: 16px; text-transform: uppercase; letter-spacing: .5px; }
.follow-empty { height: 80px; display: flex; align-items: center; justify-content: center; color: #ccc; font-size: 13px; }
.follow-item { display: flex; gap: 14px; padding: 10px 0; border-bottom: 1px solid #fafafa; transition: background .15s; }
.follow-item:hover { background: #fdfcf9; }
.follow-item:last-child { border-bottom: none; }
.fi-dot { width: 8px; height: 8px; border-radius: 50%; background: #e8e8e8; margin-top: 7px; flex-shrink: 0; transition: background .2s; }
.fi-dot.active { background: #d6893b; box-shadow: 0 0 0 3px rgba(214,137,59,.15); }
.fi-body { flex: 1; min-width: 0; }
.fi-head { display: flex; align-items: center; gap: 12px; margin-bottom: 4px; font-size: 13px; }
.fi-head strong { color: #333; }
.fi-user { color: #999; font-size: 12px; }
.fi-time { color: #ccc; font-size: 11px; margin-left: auto; flex-shrink: 0; }
.fi-content { font-size: 12px; color: #999; line-height: 1.5; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>
