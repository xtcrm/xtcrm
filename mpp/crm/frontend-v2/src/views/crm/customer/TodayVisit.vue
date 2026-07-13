<template>
  <div class="visit-calendar">
    <!-- 头部 -->
    <div class="vc-header">
      <div class="vc-nav">
        <a-button type="text" @click="prev"><left-outlined /></a-button>
        <h2 class="vc-title">{{ titleText }}</h2>
        <a-button type="text" @click="next"><right-outlined /></a-button>
        <a-button size="small" @click="goToday" style="margin-left:8px">今天</a-button>
      </div>
      <div class="vc-actions">
        <a-radio-group v-model:value="viewMode" size="small" button-style="solid">
          <a-radio-button value="month">月</a-radio-button>
          <a-radio-button value="week">周</a-radio-button>
        </a-radio-group>
      </div>
    </div>

    <a-spin :spinning="loading">
      <!-- ==================== 月视图 ==================== -->
      <div v-if="viewMode === 'month'" class="vc-month">
        <div class="mc-headers">
          <div v-for="h in ['一','二','三','四','五','六','日']" :key="h" class="mc-header">{{ h }}</div>
        </div>
        <div class="mc-grid">
          <div v-for="d in monthDays" :key="d.key" class="mc-day"
            :class="{ today: d.isToday, other: d.isOtherMonth, weekend: d.isWeekend }"
            @click="d.events.length && selectedDayKey !== d.key ? selectedDayKey = d.key : selectedDayKey = ''"
          >
            <div class="md-num" :class="{ active: d.isToday }">{{ d.dayNum }}</div>
            <div class="md-events">
              <div v-for="e in d.events.slice(0, 3)" :key="e.id" class="md-event" :class="e.event_type"
                @click.stop="goEvent(e)" :title="e.next_follow_content || e.customer_name || e.contact_name">
                <span class="me-dot"></span>{{ eventLabel(e) }}
              </div>
              <div v-if="d.events.length > 3" class="md-more">+{{ d.events.length - 3 }} 更多</div>
            </div>
          </div>
        </div>
      </div>

      <!-- ==================== 周视图 ==================== -->
      <div v-else class="vc-week">
        <div class="vc-day-headers">
          <div v-for="d in weekDays" :key="d.key" class="vc-day-header" :class="{ today: d.isToday }">
            <span class="dh-name">{{ d.label }}</span>
            <span class="dh-date" :class="{ active: d.isToday }">{{ d.dayNum }}</span>
            <span class="dh-count" v-if="d.events.length">{{ d.events.length }}</span>
          </div>
        </div>
        <div class="vc-day-cells">
          <div v-for="d in weekDays" :key="d.key" class="vc-day-cell" :class="{ today: d.isToday }">
            <div v-if="d.events.length">
              <div v-for="e in d.events" :key="e.id" class="visit-item" :class="e.event_type" @click="goEvent(e)">
                <div class="vi-time">{{ e.next_follow_date ? dayjs(e.next_follow_date*1000).format('HH:mm') : '全天' }}</div>
                <div class="vi-body">
                  <span class="vi-tag" :class="e.event_type">{{ e.follow_type || '生日' }}</span>
                  <span class="vi-name">{{ e.customer_name || e.contact_name }}</span>
                </div>
                <div v-if="e.next_follow_content" class="vi-plan">{{ e.next_follow_content }}</div>
                <div v-if="e._greeting" class="vi-greet" @click.stop="copyText(e._greeting)" title="点击复制">✨ {{ e._greeting }}</div>
                <a-button v-if="!e._greeting && e.event_type !== 'visit'" type="link" size="small" @click.stop="genGreeting(e)" :loading="e._greetingLoading" style="padding:0;font-size:10px;height:auto">✨ 祝福语</a-button>
              </div>
            </div>
            <div v-else class="cell-empty">·</div>
          </div>
        </div>
      </div>

      <!-- 选中日详情 -->
      <div v-if="selectedDayDetail" class="vc-detail">
        <a-divider>{{ selectedDayDetail.label }} {{ selectedDayDetail.fullDate }}</a-divider>
        <a-table v-if="selectedDayDetail.events.length" :data-source="selectedDayDetail.events" :pagination="false"
          row-key="id" size="small" :columns="detailCols" @row-click="(r) => goEvent(r)" class="detail-table">
          <template #bodyCell="{ column, record }">
            <template v-if="column.key === 'time'">
              {{ record.next_follow_date ? dayjs(record.next_follow_date*1000).format('HH:mm') : '全天' }}
            </template>
            <template v-if="column.key === 'type'">
              <a-tag :color="record.event_type === 'birthday' ? 'pink' : record.event_type === 'established' ? 'green' : 'blue'" size="small">
                {{ record.follow_type || '生日' }}
              </a-tag>
            </template>
            <template v-if="column.key === 'name'">
              <span class="detail-name">{{ record.customer_name || record.contact_name }}</span>
            </template>
            <template v-if="column.key === 'content'">{{ record.next_follow_content || cleanHtml(record.follow_content).substring(0, 60) || '-' }}</template>
            <template v-if="column.key === 'owner'">{{ record.owner_user_name || record.user_name || '-' }}</template>
            <template v-if="column.key === 'greet'">
              <span v-if="record._greeting" style="color:#eb2f96;font-size:12px;cursor:pointer" @click.stop="copyText(record._greeting)" title="点击复制">✨ {{ record._greeting }}</span>
              <a-button v-else-if="record.event_type !== 'visit'" type="link" size="small" :loading="record._greetingLoading" @click.stop="genGreeting(record)">✨ 祝福语</a-button>
            </template>
          </template>
        </a-table>
        <a-empty v-else description="当天无事件" style="padding:24px" />
      </div>

      <!-- 图例 -->
      <div class="vc-legend">
        <span class="lg-item"><span class="lg-dot visit"></span> 拜访</span>
        <span class="lg-item"><span class="lg-dot birthday"></span> 生日</span>
        <span class="lg-item"><span class="lg-dot established"></span> 周年</span>
      </div>
    </a-spin>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { getCalendarEvents, getGreeting } from '@/api/crm/followup'
import dayjs from 'dayjs'
import { LeftOutlined, RightOutlined } from '@ant-design/icons-vue'

const router = useRouter()
const loading = ref(false)
const viewMode = ref('week')
const currentDate = ref(dayjs())
const allEvents = ref([])
const selectedDayKey = ref('')

const weekNames = ['周一', '周二', '周三', '周四', '周五', '周六', '周日']

// ============ 月视图 ============
const monthDays = computed(() => {
  const start = currentDate.value.startOf('month')
  const end = currentDate.value.endOf('month')
  const firstDay = start.day() || 7 // 周一=1...周日=7
  const days = []

  // 前置空白（上月）
  for (let i = 1; i < firstDay; i++) {
    const d = start.subtract(firstDay - i, 'day')
    days.push(makeDay(d, true))
  }
  // 本月
  for (let i = 0; i < end.date(); i++) {
    days.push(makeDay(start.add(i, 'day'), false))
  }
  // 补齐到完整行（6行×7=42格）
  const lastDay = end.day() || 7
  for (let i = 1; i <= 7 - lastDay; i++) {
    const d = end.add(i, 'day')
    days.push(makeDay(d, true))
  }
  return days
})

function makeDay(d, otherMonth) {
  const key = d.format('YYYY-MM-DD')
  return {
    key,
    dayNum: d.date(),
    label: weekNames[d.day() === 0 ? 6 : d.day() - 1],
    fullDate: d.format('M月D日'),
    isToday: d.isSame(dayjs(), 'day'),
    isWeekend: d.day() === 0 || d.day() === 6,
    isOtherMonth: otherMonth,
    events: allEvents.value.filter(e => e._date === key),
  }
}

// ============ 周视图 ============
const weekStart = computed(() => {
  const d = currentDate.value
  const day = d.day() || 7 // 周一=1...周日=7
  return d.subtract(day - 1, 'day')
})
const weekDays = computed(() => {
  return Array.from({ length: 7 }, (_, i) => {
    const d = weekStart.value.add(i, 'day')
    const key = d.format('YYYY-MM-DD')
    return {
      key, label: weekNames[i], dayNum: d.date(), fullDate: d.format('M月D日'),
      isToday: d.isSame(dayjs(), 'day'),
      events: allEvents.value.filter(e => e._date === key).sort((a, b) => (a.next_follow_date || 0) - (b.next_follow_date || 0)),
    }
  })
})

const titleText = computed(() => {
  if (viewMode.value === 'month') return currentDate.value.format('YYYY年M月')
  const s = weekStart.value; const e = s.add(6, 'day')
  if (s.month() === e.month()) return s.format('M月D日') + ' - ' + e.format('D日')
  return s.format('M月D日') + ' - ' + e.format('M月D日')
})

// ============ 导航 ============
function prev() {
  currentDate.value = currentDate.value.subtract(1, viewMode.value === 'month' ? 'month' : 'week')
}
function next() {
  currentDate.value = currentDate.value.add(1, viewMode.value === 'month' ? 'month' : 'week')
}
function goToday() { currentDate.value = dayjs(); selectedDayKey.value = dayjs().format('YYYY-MM-DD') }

// HTML 解码 + 去标签
const cleanHtml = (h) => {
  if (!h) return ''
  const txt = h.replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&#39;/g, "'")
  return txt.replace(/<[^>]*>/g, '').trim()
}

// 选中日详情
const selectedDayDetail = computed(() => {
  if (!selectedDayKey.value) return null
  const pool = viewMode.value === 'week' ? weekDays.value : monthDays.value
  return pool.find(d => d.key === selectedDayKey.value) || null
})
const detailCols = [
  { title: '时间', key: 'time', width: 60 },
  { title: '类型', key: 'type', width: 70 },
  { title: '客户/联系人', key: 'name', width: 160 },
  { title: '内容', key: 'content' },
  { title: '负责人', key: 'owner', width: 80 },
  { title: '', key: 'greet', width: 390 },
]

// ============ 事件 ============
function eventLabel(e) {
  const name = e.customer_name || e.contact_name || ''
  const type = e.event_type === 'birthday' ? '🎂' : e.event_type === 'established' ? '🏢' : ''
  return type + name.substring(0, 6)
}

function goEvent(e) {
  if (e.event_type === 'birthday') return
  if (e.customer_id > 0) router.push({ name: 'customer-detail', query: { id: e.customer_id } })
}

function copyText(text) {
  navigator.clipboard?.writeText(text).then(() => message.success('已复制')).catch(() => {})
}
function genGreeting(e) {
  e._greetingLoading = true
  getGreeting({
    type: e.event_type,
    name: e.customer_name || e.contact_name,
    context: e.next_follow_content || cleanHtml(e.follow_content || '').substring(0, 100) || '',
    event_date: e._event_date || e._date,
    target_id: String(e._target_id || e.id || ''),
    followup_id: e.event_type === 'visit' ? e.id : 0,
  }).then(res => {
    e._greeting = res?.data?.text || '🎉 祝一切顺利！'
    if (e.event_type === 'visit') e.next_follow_content = e._greeting
  }).finally(() => { e._greetingLoading = false })
}

// 周/月切换时重新加载
watch(viewMode, () => { loadData() })

function loadData() {
  loading.value = true
  let start, end
  if (viewMode.value === 'month') {
    start = currentDate.value.startOf('month').subtract(7, 'day').format('YYYY-MM-DD')
    end = currentDate.value.endOf('month').add(7, 'day').format('YYYY-MM-DD')
  } else {
    start = weekStart.value.format('YYYY-MM-DD')
    end = weekStart.value.add(6, 'day').format('YYYY-MM-DD')
  }
  getCalendarEvents({ start, end }).then(res => {
    const list = res?.data?.list || []
    list.forEach(e => { e._date = e.next_follow_date ? dayjs(e.next_follow_date * 1000).format('YYYY-MM-DD') : '' })
    allEvents.value = list
    selectedDayKey.value = dayjs().format('YYYY-MM-DD')
  }).finally(() => { loading.value = false })
}

onMounted(loadData)
</script>

<style scoped>
.visit-calendar { max-width: 100%; margin: 0 auto; }

/* Header */
.vc-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 0; }
.vc-nav { display: flex; align-items: center; gap: 4px; }
.vc-title { font-size: 18px; font-weight: 700; margin: 0; min-width: 160px; text-align: center; }

/* ============ 月视图 ============ */
.mc-headers { display: grid; grid-template-columns: repeat(7, 1fr); background: #fafafa; border: 1px solid #f0f0f0; border-bottom: none; border-radius: 8px 8px 0 0; }
.mc-header { padding: 8px; text-align: center; font-size: 12px; color: #888; font-weight: 600; }
.mc-grid { display: grid; grid-template-columns: repeat(7, 1fr); border: 1px solid #f0f0f0; border-radius: 0 0 8px 8px; overflow: hidden; }
.mc-day { min-height: 90px; padding: 4px 6px; border-right: 1px solid #f5f5f5; border-bottom: 1px solid #f5f5f5; cursor: pointer; transition: background .1s; }
.mc-day:nth-child(7n) { border-right: none; }
.mc-day:hover { background: #fafcff; }
.mc-day.today { background: #e6f4ff; }
.mc-day.other { opacity: 0.4; }
.md-num { font-size: 13px; font-weight: 600; color: #555; margin-bottom: 2px; }
.md-num.active { color: #fff; background: #1677ff; border-radius: 50%; width: 22px; height: 22px; display: inline-flex; align-items: center; justify-content: center; }

.md-events { display: flex; flex-direction: column; gap: 1px; }
.md-event { font-size: 10px; padding: 1px 4px; border-radius: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.6; }
.md-event.visit { background: #e6f4ff; color: #1677ff; }
.md-event.birthday { background: #fff0f6; color: #eb2f96; }
.md-event.established { background: #f6ffed; color: #52c41a; }
.me-dot { display: inline-block; width: 5px; height: 5px; border-radius: 50%; margin-right: 2px; vertical-align: middle; }
.md-event.visit .me-dot { background: #1677ff; }
.md-event.birthday .me-dot { background: #eb2f96; }
.md-event.established .me-dot { background: #52c41a; }
.md-more { font-size: 10px; color: #999; padding: 1px 4px; }

/* ============ 周视图 ============ */
.vc-week { border: 1px solid #f0f0f0; border-radius: 10px; overflow: hidden; }
.vc-day-headers { display: grid; grid-template-columns: repeat(7, 1fr); background: #fafafa; border-bottom: 1px solid #f0f0f0; }
.vc-day-header { padding: 10px 4px; text-align: center; }
.vc-day-header.today { background: #e6f4ff; }
.dh-name { font-size: 11px; color: #999; }
.dh-date { display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 50%; font-size: 14px; font-weight: 600; margin-top: 2px; }
.dh-date.active { background: #1677ff; color: #fff; }
.dh-count { font-size: 10px; color: #1677ff; display: block; }

.vc-day-cells { display: grid; grid-template-columns: repeat(7, 1fr); min-height: 280px; }
.vc-day-cell { padding: 6px; border-right: 1px solid #f5f5f5; overflow-y: auto; }
.vc-day-cell:nth-child(7n) { border-right: none; }
.vc-day-cell.today { background: #fafcff; }
.cell-empty { color: #e8e8e8; font-size: 20px; text-align: center; padding-top: 40px; }

.visit-item { background: #fff; border: 1px solid #eee; border-radius: 6px; padding: 5px 7px; margin-bottom: 4px; cursor: pointer; transition: all .15s; border-left: 3px solid #1677ff; }
.visit-item:hover { box-shadow: 0 1px 6px rgba(0,0,0,.08); }
.visit-item.birthday { border-left-color: #eb2f96; background: #fff0f620; }
.visit-item.established { border-left-color: #52c41a; background: #f6ffed20; }
.vi-time { font-size: 10px; color: #1677ff; font-weight: 600; margin-bottom: 1px; }
.visit-item.birthday .vi-time { color: #eb2f96; }
.visit-item.established .vi-time { color: #52c41a; }
.vi-body { display: flex; align-items: center; gap: 4px; margin-bottom: 1px; }
.vi-tag { font-size: 9px; padding: 0 4px; border-radius: 2px; line-height: 1.5; }
.vi-tag.visit { background: #e6f4ff; color: #1677ff; }
.vi-tag.birthday { background: #fff0f6; color: #eb2f96; }
.vi-tag.established { background: #f6ffed; color: #52c41a; }
.vi-name { font-size: 12px; font-weight: 600; }
.vi-plan { font-size: 11px; color: #555; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.vi-greet { font-size: 10px; color: #eb2f96; padding-top: 2px; font-style: italic; }

/* 详情面板 */
.vc-detail { margin-top: 4px; }
.detail-name { font-weight: 600; cursor: pointer; color: #1677ff; }
.detail-table { cursor: pointer; }

/* 图例 */
.vc-legend { display: flex; gap: 20px; padding: 12px 4px; }
.lg-item { font-size: 12px; color: #888; display: flex; align-items: center; gap: 4px; }
.lg-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
.lg-dot.visit { background: #1677ff; }
.lg-dot.birthday { background: #eb2f96; }
.lg-dot.established { background: #52c41a; }
</style>
