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
        <a-button size="small" type="primary" @click="openEventForm(null)"><plus-outlined /> 事件</a-button>
        <a-radio-group v-model:value="viewMode" size="small" button-style="solid" style="margin-left:8px">
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
            @click="addEventForDay(d.key)"
          >
            <div class="md-num" :class="{ active: d.isToday }">{{ d.dayNum }}</div>
            <div class="md-events">
              <div v-for="e in d.events.slice(0, 3)" :key="e.id" class="md-event" :class="e._color ? '' : e.event_type"
                :style="e._color ? { background: e._color+'18', color: e._color } : {}"
                @click.stop="goEvent(e)" :title="e._color ? (e.customer_name ? e.follow_type + ' — ' + e.customer_name : e.follow_type) : (e.next_follow_content || e.customer_name || e.contact_name || e.follow_type)">
                <span class="me-dot" :style="e._color ? { background: e._color } : {}"></span>{{ eventLabel(e) }}
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
            <span class="dh-lunar">{{ d.lunar }}</span>
            <span class="dh-count" v-if="d.events.length">{{ d.events.length }}</span>
          </div>
        </div>
        <div class="vc-day-cells">
          <div v-for="d in weekDays" :key="d.key" class="vc-day-cell" :class="{ today: d.isToday }" @click="addEventForDay(d.key)">
            <div v-if="d.events.length" @click.stop>
              <div v-for="e in d.events" :key="e.id" class="visit-item" :class="e._color ? '' : e.event_type"
                :style="e._color ? { borderLeftColor: e._color, background: e._color+'10' } : {}">
                <div class="vi-time" :style="e._color ? { color: e._color } : {}">{{ e.next_follow_date ? dayjs(e.next_follow_date*1000).format('HH:mm') : '全天' }}</div>
                <div class="vi-body">
                  <span v-if="e.event_type === 'custom'" class="vi-tag" :style="{ background: e._color+'18', color: e._color, cursor:'pointer' }" @click.stop="openEventForm(e)">{{ e.follow_type }}</span>
                  <span v-else class="vi-tag" :class="e._color ? '' : e.event_type"
                    :style="e._color ? { background: e._color+'18', color: e._color } : {}">{{ e.follow_type || '生日' }}</span>
                  <a v-if="e.customer_id > 0" class="vi-name" @click.stop="router.push({ name:'customer-detail', query:{ id: e.customer_id } })">{{ e.customer_name || e.contact_name }}</a>
                  <a v-else-if="e.event_type === 'custom'" class="vi-name" @click.stop="openEventForm(e)">{{ e.follow_type }}</a>
                  <span v-else class="vi-name">{{ e.customer_name || e.contact_name }}</span>
                </div>
                <div v-if="e.next_follow_content" class="vi-plan">{{ e.next_follow_content }}</div>
                <div v-if="e._greeting" class="vi-greet" @click.stop="copyText(e._greeting)" title="点击复制">✨ {{ e._greeting }}</div>
                <a-button v-if="!e._greeting && !['visit','contract_expiry','workorder'].includes(e.event_type)" type="link" size="small" @click.stop="genGreeting(e)" :loading="e._greetingLoading" style="padding:0;font-size:10px;height:auto">✨ 祝福语</a-button>
              </div>
            </div>
            <div v-else class="cell-empty" @click="addEventForDay(d.key)">·</div>
          </div>
        </div>
      </div>

      <!-- 选中日详情 -->
      <div v-if="selectedDayDetail" class="vc-detail">
        <a-divider>{{ selectedDayDetail.label }} {{ selectedDayDetail.fullDate }}</a-divider>
        <a-table v-if="selectedDayDetail.events.length" :data-source="selectedDayDetail.events" :pagination="false"
          row-key="id" size="small" :columns="detailCols" class="detail-table">
          <template #bodyCell="{ column, record }">
            <template v-if="column.key === 'time'">
              {{ record.next_follow_date ? dayjs(record.next_follow_date*1000).format('HH:mm') : '全天' }}
            </template>
            <template v-if="column.key === 'type'">
              <a-tag :color="record.event_type === 'birthday' ? 'pink' : record.event_type === 'established' ? 'green' : record.event_type === 'contract_expiry' ? 'orange' : record.event_type === 'workorder' ? 'cyan' : 'blue'" size="small">
                {{ record.follow_type || '生日' }}
              </a-tag>
            </template>
            <template v-if="column.key === 'name'">
              <template v-if="record.event_type === 'custom'">
                <a-tag :color="record._color || 'blue'" size="small" style="margin-right:4px;cursor:pointer" @click.stop="openEventForm(record)">{{ record.follow_type }}</a-tag>
                <a v-if="record.customer_id > 0" @click="router.push({ name:'customer-detail', query:{ id: record.customer_id } })" class="detail-name">{{ record.customer_name || record.contact_name }}</a>
                <a v-else @click.stop="openEventForm(record)" class="detail-name" style="cursor:pointer">{{ record.follow_type }}</a>
              </template>
              <template v-else>
                <a v-if="record.customer_id > 0" @click="router.push({ name:'customer-detail', query:{ id: record.customer_id } })" class="detail-name">{{ record.customer_name || record.contact_name }}</a>
                <span v-else class="detail-name">{{ record.customer_name || record.contact_name }}</span>
              </template>
            </template>
            <template v-if="column.key === 'content'">{{ record.next_follow_content || cleanHtml(record.follow_content).substring(0, 60) || '-' }}</template>
            <template v-if="column.key === 'owner'">{{ record.owner_user_name || record.user_name || '-' }}</template>
            <template v-if="column.key === 'greet'">
              <span v-if="record._greeting" style="color:#eb2f96;font-size:12px;cursor:pointer" @click.stop="copyText(record._greeting)" title="点击复制">✨ {{ record._greeting }}</span>
              <a-button v-else-if="!['visit','contract_expiry','workorder'].includes(record.event_type)" type="link" size="small" :loading="record._greetingLoading" @click.stop="genGreeting(record)">✨ 祝福语</a-button>
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
        <span class="lg-item"><span class="lg-dot contract_expiry"></span> 合同到期</span>
        <span class="lg-item"><span class="lg-dot workorder"></span> 工单</span>
        <span class="lg-item"><span class="lg-dot demand"></span> 商机</span>
        <span class="lg-item"><span class="lg-dot custom"></span> 自定义</span>
        <span class="lg-item"><span class="lg-dot holiday"></span> 节日</span>
      </div>

      <!-- 待处理商机需求 -->
      <div v-if="demands.length" class="demand-list">
        <div class="demand-title">待处理商机 <span style="font-size:12px;color:#999;font-weight:normal">（{{ demands.length }} 条）</span></div>
        <div class="demand-item" v-for="d in demands" :key="'dm'+d.id" @click="router.push('/crm/customer/detail?id='+d.customer_id)">
          <a-tag :color="d.priority===1?'red':d.priority===2?'orange':'blue'" size="small">{{ d.priority===1?'高':d.priority===2?'中':'低' }}</a-tag>
          <span class="dm-name">{{ d.customer_name }}</span>
          <span class="dm-rule">{{ d.rule_name }}</span>
          <span class="dm-reason">{{ d.reason }}</span>
          <span class="dm-owner">{{ d.owner_user_name || '未分配' }}</span>
        </div>
      </div>
    </a-spin>

    <!-- 自定义事件弹窗 -->
    <a-modal :title="eventForm.id ? '编辑事件' : '添加事件'" :open="eventVisible" :width="520"
      :mask-closable="false" @ok="saveEvent" @cancel="eventVisible = false" :confirm-loading="eventSaving"
      :ok-text="eventForm.id ? '保存' : '添加'">
      <template v-if="eventForm.id" #footer>
        <a-button key="delete" danger @click="deleteEvent" style="float:left">删除</a-button>
        <a-button key="cancel" @click="eventVisible = false">取消</a-button>
        <a-button key="ok" type="primary" :loading="eventSaving" @click="saveEvent">保存</a-button>
      </template>
      <a-form :model="eventForm" :label-col="{ span: 6 }" :wrapper-col="{ span: 16 }">
        <a-form-item label="事件名称" required>
          <a-input v-model:value="eventForm.title" placeholder="如：季度复盘" />
        </a-form-item>
        <a-form-item label="日期" required>
          <a-date-picker v-model:value="eventForm.event_date" style="width:100%" />
        </a-form-item>
        <a-form-item label="历法">
          <a-radio-group v-model:value="eventForm.is_lunar">
            <a-radio :value="0">公历</a-radio>
            <a-radio :value="1">农历</a-radio>
          </a-radio-group>
        </a-form-item>
        <a-form-item label="关联客户">
          <a-select v-model:value="eventForm.customer_id" placeholder="输入关键词搜索客户" allow-clear show-search
            :filter-option="false" @search="searchCustomers">
            <a-select-option v-for="c in customers" :key="c.id" :value="c.id">{{ c.customer_name }}</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="重复类型">
          <a-radio-group v-model:value="eventForm.repeat_type" @change="onRepeatTypeChange">
            <a-radio-button value="none">不重复</a-radio-button>
            <a-radio-button value="daily">每天</a-radio-button>
            <a-radio-button value="weekly">每周</a-radio-button>
            <a-radio-button value="monthly">每月</a-radio-button>
            <a-radio-button value="yearly">每年</a-radio-button>
          </a-radio-group>
        </a-form-item>
        <!-- 重复规则 -->
        <a-form-item v-if="eventForm.repeat_type === 'daily'" label="间隔">
          <a-input-number v-model:value="eventForm.repeat_rule.interval" :min="1" :max="365" style="width:80px" /> 天
        </a-form-item>
        <a-form-item v-if="eventForm.repeat_type === 'weekly'" label="星期">
          <a-checkbox-group v-model:value="eventForm.repeat_rule.days">
            <a-checkbox :value="1">一</a-checkbox><a-checkbox :value="2">二</a-checkbox><a-checkbox :value="3">三</a-checkbox>
            <a-checkbox :value="4">四</a-checkbox><a-checkbox :value="5">五</a-checkbox><a-checkbox :value="6">六</a-checkbox><a-checkbox :value="7">日</a-checkbox>
          </a-checkbox-group>
        </a-form-item>
        <a-form-item v-if="eventForm.repeat_type === 'weekly'" label="间隔周数">
          <a-input-number v-model:value="eventForm.repeat_rule.interval" :min="1" :max="52" style="width:80px" /> 周
        </a-form-item>
        <a-form-item v-if="eventForm.repeat_type === 'monthly'" label="每月几号">
          <a-input-number v-model:value="eventForm.repeat_rule.day" :min="1" :max="31" style="width:80px" /> 号
          <span style="font-size:11px;color:#999;margin-left:4px">（超月底自动取最后一天）</span>
        </a-form-item>
        <a-form-item v-if="eventForm.repeat_type === 'yearly'" label="日期">
          <a-select v-model:value="eventForm.repeat_rule.month" style="width:80px">
            <a-select-option v-for="m in 12" :key="m" :value="m">{{ m }}月</a-select-option>
          </a-select>
          <a-input-number v-model:value="eventForm.repeat_rule.day" :min="1" :max="31" style="width:80px;margin-left:8px" /> 日
        </a-form-item>
        <a-form-item v-if="eventForm.repeat_type !== 'none'" label="截止日期">
          <a-date-picker v-model:value="eventForm.end_date" style="width:100%" placeholder="可选，不选=无限重复" />
        </a-form-item>
        <a-form-item label="颜色">
          <a-radio-group v-model:value="eventForm.color">
            <a-radio v-for="c in COLORS" :key="c" :value="c" :style="{ marginRight: 4 }">
              <span class="color-dot" :style="{ background: c }"></span>
            </a-radio>
          </a-radio-group>
        </a-form-item>
        <a-form-item label="备注">
          <a-textarea v-model:value="eventForm.content" :rows="2" />
        </a-form-item>
      </a-form>
    </a-modal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { getCalendarEvents, getGreeting, getGreetings, eventAdd, eventEdit, eventDelete } from '@/api/crm/followup'
import { listAll } from '@/api/crm/demand'
import { getSelect as getCustomerSelect } from '@/api/crm/customer'
import dayjs from 'dayjs'
import { LeftOutlined, RightOutlined, PlusOutlined } from '@ant-design/icons-vue'

// ========== 农历数据(1900-2100) ==========
const LUNAR_DATA = [
  0x04bd8,0x04ae0,0x0a570,0x054d5,0x0d260,0x0d950,0x16554,0x056a0,0x09ad0,0x055d2,
  0x04ae0,0x0a5b6,0x0a4d0,0x0d250,0x1d255,0x0b540,0x0d6a0,0x0ada2,0x095b0,0x14977,
  0x04970,0x0a4b0,0x0b4b5,0x06a50,0x06d40,0x1ab54,0x02b60,0x09570,0x052f2,0x04970,
  0x06566,0x0d4a0,0x0ea50,0x06e95,0x05ad0,0x02b60,0x186e3,0x092e0,0x1c8d7,0x0c950,
  0x0d4a0,0x1d8a6,0x0b550,0x056a0,0x1a5b4,0x025d0,0x092d0,0x0d2b2,0x0a950,0x0b557,
  0x06ca0,0x0b550,0x15355,0x04da0,0x0a5b0,0x14573,0x052b0,0x0a9a8,0x0e950,0x06aa0,
  0x0aea6,0x0ab50,0x04b60,0x0aae4,0x0a570,0x05260,0x0f263,0x0d950,0x05b57,0x056a0,
  0x096d0,0x04dd5,0x04ad0,0x0a4d0,0x0d4d4,0x0d250,0x0d558,0x0b540,0x0b6a0,0x195a6,
  0x095b0,0x049b0,0x0a974,0x0a4b0,0x0b27a,0x06a50,0x06d40,0x0af46,0x0ab60,0x09570,
  0x04af5,0x04970,0x064b0,0x074a3,0x0ea50,0x06b58,0x055c0,0x0ab60,0x096d5,0x092e0,
  0x0c960,0x0d954,0x0d4a0,0x0da50,0x07552,0x056a0,0x0abb7,0x025d0,0x092d0,0x0cab5,
  0x0a950,0x0b4a0,0x0baa4,0x0ad50,0x055d9,0x04ba0,0x0a5b0,0x15176,0x052b0,0x0a930,
  0x07954,0x06aa0,0x0ad50,0x05b52,0x04b60,0x0a6e6,0x0a4e0,0x0d260,0x0ea65,0x0d530,
  0x05aa0,0x076a3,0x096d0,0x04afb,0x04ad0,0x0a4d0,0x1d0b6,0x0d250,0x0d520,0x0dd45,
  0x0b5a0,0x056d0,0x055b2,0x049b0,0x0a577,0x0a4b0,0x0aa50,0x1b255,0x06d20,0x0ada0,
  0x14b63,0x09370,0x049f8,0x04970,0x064b0,0x168a6,0x0ea50,0x06b20,0x1a6c4,0x0aae0,
  0x0a2e0,0x0d2e3,0x0c960,0x0d557,0x0d4a0,0x0da50,0x05d55,0x056a0,0x0a6d0,0x055d4,
  0x052d0,0x0a9b8,0x0a950,0x0b4a0,0x0b6a6,0x0ad50,0x055a0,0x0aba4,0x0a5b0,0x052b0,
  0x0b273,0x06930,0x07337,0x06aa0,0x0ad50,0x14b55,0x04b60,0x0a570,0x054e4,0x0d160,
  0x0e968,0x0d520,0x0daa0,0x16aa6,0x056d0,0x04ae0,0x0a9d4,0x0a4d0,0x0d150,0x0f252,0x0d520
]
const LUNAR_MONTHS = ['正','二','三','四','五','六','七','八','九','十','冬','腊']
const LUNAR_DAYS = ['','初一','初二','初三','初四','初五','初六','初七','初八','初九','初十','十一','十二','十三','十四','十五','十六','十七','十八','十九','二十','廿一','廿二','廿三','廿四','廿五','廿六','廿七','廿八','廿九','三十']

function yearDays(y) { let d=0; const v=LUNAR_DATA[y-1900]; for(let i=0;i<12;i++) d+=((v>>(4+i))&1)?30:29; const l=v&0xf; return l>0 ? d+(((v>>16)&1)?30:29) : d }
function lunarMonthDays(y,m) { return ((LUNAR_DATA[y-1900]>>(4+m-1))&1)?30:29 }

function solarToLunar(dateStr) {
  const [y,m,d] = dateStr.split('-').map(Number)
  const baseTs = Date.UTC(1900, 0, 30) // 1900-01-31
  const targetTs = Date.UTC(y, m-1, d)
  let offset = Math.round((targetTs - baseTs) / 86400000)

  for (let ly = 1900; ly < y; ly++) offset -= yearDays(ly)

  const info = LUNAR_DATA[y-1900], leap = info & 0xf
  let lm = 1, isLeap = false
  while (lm <= 12 && offset >= lunarMonthDays(y, lm)) { offset -= lunarMonthDays(y, lm); lm++ }
  if (leap > 0 && lm > leap) {
    const ld = ((info>>16)&1)?30:29
    if (offset >= ld) { offset -= ld; lm++ }
    else if (offset >= 0) { isLeap = true; lm = leap }
  }
  return { month: isLeap ? '闰'+LUNAR_MONTHS[lm-1] : LUNAR_MONTHS[lm-1], day: LUNAR_DAYS[offset+1] || '?' }
}

function lunarText(date) {
  try { const l = solarToLunar(typeof date === 'string' ? date : date.format('YYYY-MM-DD')); return l.day === '初一' ? l.month+'月' : l.month + '月' + l.day }
  catch(e) { return '' }
}

// ========== 节日数据 ==========
const SOLAR_HOLIDAYS = {
  '01-01': '元旦', '02-14': '情人节', '03-08': '妇女节',
  '04-01': '愚人节', '05-01': '劳动节', '06-01': '儿童节',
  '07-01': '建党节', '08-01': '建军节', '09-10': '教师节',
  '10-01': '国庆节', '10-31': '万圣节', '12-24': '平安夜', '12-25': '圣诞节', '12-31': '跨年',
}
const LUNAR_HOLIDAYS = [
  [1,1,'春节'],[1,15,'元宵'],[5,5,'端午'],[7,7,'七夕'],[8,15,'中秋'],[9,9,'重阳'],[12,30,'除夕'],
]

function getHolidays(start, end) {
  const list = []
  const s = dayjs(start), e = dayjs(end)
  // Solar holidays
  for (const [md, name] of Object.entries(SOLAR_HOLIDAYS)) {
    for (let y = s.year(); y <= e.year(); y++) {
      const d = dayjs(y + '-' + md)
      if (d.isAfter(s.subtract(1,'day')) && d.isBefore(e.add(1,'day'))) {
        list.push({ id: 'sh_'+y+md, _date: d.format('YYYY-MM-DD'), event_type: 'holiday', follow_type: name, next_follow_date: d.unix(), next_follow_content: name, customer_id: 0, customer_name: '', owner_user_name: '' })
      }
    }
  }
  // Lunar holidays
  for (const [m, day, name] of LUNAR_HOLIDAYS) {
    for (let y = s.year(); y <= e.year(); y++) {
      const solar = solarToLunar_day(y, m, day)
      if (solar) {
        const d = dayjs(solar)
        if (d.isAfter(s.subtract(1,'day')) && d.isBefore(e.add(1,'day'))) {
          list.push({ id: 'lh_'+y+'_'+m+'_'+day, _date: d.format('YYYY-MM-DD'), event_type: 'holiday', follow_type: name, next_follow_date: d.unix(), next_follow_content: name, customer_id: 0, customer_name: '', owner_user_name: '' })
        }
      }
    }
  }
  // 除夕 = 腊月最后一天，需要特殊处理
  for (let y = s.year(); y <= e.year(); y++) {
    const info = LUNAR_DATA[y - 1900]
    const lastDay = ((info >> 15) & 1) ? 30 : 29
    const solar = solarToLunar_day(y, 12, lastDay)
    if (solar) {
      const d = dayjs(solar)
      if (d.isAfter(s.subtract(1,'day')) && d.isBefore(e.add(1,'day'))) {
        list.push({ id: 'lh_'+y+'_12_'+lastDay+'_eve', _date: d.format('YYYY-MM-DD'), event_type: 'holiday', follow_type: '除夕', next_follow_date: d.unix(), next_follow_content: '除夕', customer_id: 0, customer_name: '', owner_user_name: '' })
      }
    }
  }
  return list
}

// lunar holiday helper: convert (year, lunarMonth, lunarDay) → solar date string
function solarToLunar_day(y, m, d) {
  const baseTs = Date.UTC(1900, 0, 30)
  let offset = 0
  for (let ly = 1900; ly < y; ly++) offset += yearDays(ly)
  for (let i = 1; i < m; i++) offset += lunarMonthDays(y, i)
  const info = LUNAR_DATA[y-1900], leap = info & 0xf
  if (leap > 0 && m > leap) offset += ((info>>16)&1) ? 30 : 29
  offset += d - 1
  return dayjs(new Date(baseTs + offset * 86400000)).format('YYYY-MM-DD')
}

const router = useRouter()
const loading = ref(false)
const viewMode = ref('week')
const currentDate = ref(dayjs())
const allEvents = ref([])
const selectedDayKey = ref('')
const demands = ref([])

const weekNames = ['周一', '周二', '周三', '周四', '周五', '周六', '周日']
const COLORS = ['#1677ff', '#52c41a', '#fa8c16', '#722ed1', '#eb2f96', '#13c2c2', '#ff4d4f']
const defaultRepeatRule = () => ({ interval: 1, days: [], day: 1, month: 1 })

// ============ 自定义事件 ============
const eventVisible = ref(false)
const eventSaving = ref(false)
const customers = ref([])
let searchTimer = null
function searchCustomers(keyword) {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    getCustomerSelect(keyword).then(r => { customers.value = r?.data?.list || [] })
  }, 300)
}
function loadCustomerList() {
  getCustomerSelect('').then(r => { customers.value = r?.data?.list || [] })
}
const eventForm = reactive({
  id: 0, title: '', event_date: null, is_lunar: 0, customer_id: undefined,
  repeat_type: 'none', repeat_rule: defaultRepeatRule(), end_date: null,
  color: '#1677ff', content: '',
})

function filterCustomerOpt(input, option) {
  return String(option.label || '').toLowerCase().includes(input.toLowerCase())
}

function openEventForm(row) {
  // row 是已有的自定义事件（来自日历点击），可选
  if (row?._eventId) {
    const r = row
    eventForm.id = r._eventId
    eventForm.title = r.follow_type || ''
    eventForm.event_date = r._original_date ? dayjs(r._original_date) : dayjs(r._event_date || r._date)
    eventForm.is_lunar = parseInt(r._is_lunar) || 0
    eventForm.customer_id = r.customer_id || undefined
    eventForm.repeat_type = r._repeat_type || 'none'
    eventForm.repeat_rule = r._repeat_rule ? { ...defaultRepeatRule(), ...JSON.parse(r._repeat_rule) } : defaultRepeatRule()
    eventForm.end_date = r._end_date ? dayjs(r._end_date) : null
    eventForm.color = r._color || '#1677ff'
    eventForm.content = r.next_follow_content || ''
  } else {
    eventForm.id = 0
    eventForm.title = ''; eventForm.event_date = dayjs(); eventForm.is_lunar = 0; eventForm.customer_id = undefined
    eventForm.repeat_type = 'none'; eventForm.repeat_rule = defaultRepeatRule(); eventForm.end_date = null
    eventForm.color = '#1677ff'; eventForm.content = ''
  }
  eventVisible.value = true
  if (!customers.value.length) loadCustomerList()
}

function addEventForDay(dateStr) {
  eventForm.id = 0
  eventForm.title = ''; eventForm.event_date = dayjs(dateStr); eventForm.is_lunar = 0; eventForm.customer_id = undefined
  eventForm.repeat_type = 'none'; eventForm.repeat_rule = defaultRepeatRule(); eventForm.end_date = null
  eventForm.color = '#1677ff'; eventForm.content = ''
  eventVisible.value = true
  if (!customers.value.length) loadCustomerList()
}

function onRepeatTypeChange() {
  eventForm.repeat_rule = defaultRepeatRule()
}

function saveEvent() {
  if (!eventForm.title) { message.warning('请输入事件名称'); return }
  if (!eventForm.event_date) { message.warning('请选择日期'); return }
  eventSaving.value = true
  const body = {
    title: eventForm.title,
    event_date: dayjs(eventForm.event_date).format('YYYY-MM-DD'),
    is_lunar: eventForm.is_lunar,
    customer_id: eventForm.customer_id || 0,
    repeat_type: eventForm.repeat_type,
    repeat_rule: eventForm.repeat_type !== 'none' ? { ...eventForm.repeat_rule } : null,
    end_date: eventForm.end_date ? dayjs(eventForm.end_date).format('YYYY-MM-DD') : null,
    color: eventForm.color,
    content: eventForm.content,
  }
  if (eventForm.id) body.id = eventForm.id
  const fn = eventForm.id ? eventEdit : eventAdd
  fn(body).then(() => { message.success(eventForm.id ? '已更新' : '已添加'); eventVisible.value = false; eventSaving.value = false; loadAll() })
    .catch(() => { eventSaving.value = false })
}

function deleteEvent() {
  if (!eventForm.id) return
  eventDelete(eventForm.id).then(() => { message.success('已删除'); eventVisible.value = false; loadAll() })
}

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
    lunar: lunarDay(d),
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
      lunar: lunarText(d),
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
  loadAll()
}
function next() {
  currentDate.value = currentDate.value.add(1, viewMode.value === 'month' ? 'month' : 'week')
  loadAll()
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
  if (e.event_type === 'demand') return '💼' + (e.customer_name || '').substring(0, 6)
  if (e.event_type === 'contract_expiry') return '📋' + (e.customer_name || '').substring(0, 6)
  if (e.event_type === 'workorder') return '📝' + (e.customer_name || '工单').substring(0, 6)
  if (e.event_type === 'custom') {
    const name = e.customer_name || e.follow_type || ''
    return '📌' + name.substring(0, 6)
  }
  if (e.event_type === 'holiday') return '🎉' + e.follow_type
  // 自定义事件优先显示事件名
  if (e.event_type === 'custom') {
    return '📌' + (e.follow_type || e.customer_name || '').substring(0, 10)
  }
  const name = e.customer_name || e.contact_name || ''
  const type = e.event_type === 'birthday' ? '🎂' : e.event_type === 'established' ? '🏢' : ''
  return type + name.substring(0, 6)
}

function goEvent(e) {
  if (e.event_type === 'birthday') return
  if (e.event_type === 'custom') { openEventForm(e); return }
  // 工单跳过客户详情，直接去工单
  if (e.event_type === 'workorder' && e.workorder_id) {
    router.push({ path: '/app/workorder/detail', query: { id: e.workorder_id } })
    return
  }
  if (e.customer_id > 0) router.push({ name: 'customer-detail', query: { id: e.customer_id } })
}

function copyText(text) {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(text).then(() => message.success('已复制')).catch(() => fallbackCopy(text))
  } else {
    fallbackCopy(text)
  }
}
function fallbackCopy(text) {
  const ta = document.createElement('textarea')
  ta.value = text; ta.style.position='fixed'; ta.style.left='-9999px'
  document.body.appendChild(ta); ta.select()
  try { document.execCommand('copy'); message.success('已复制') } catch { message.error('复制失败') }
  document.body.removeChild(ta)
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
watch(viewMode, () => { loadAll() })

function loadAll() {
  loading.value = true
  let start, end
  if (viewMode.value === 'month') {
    start = currentDate.value.startOf('month').subtract(7, 'day').format('YYYY-MM-DD')
    end = currentDate.value.endOf('month').add(7, 'day').format('YYYY-MM-DD')
  } else {
    start = weekStart.value.format('YYYY-MM-DD')
    end = weekStart.value.add(6, 'day').format('YYYY-MM-DD')
  }
  // 日历事件 + 需求 + 祝福语：三合一加载，未来新增事件类型只需加 Promise.all
  Promise.all([
    getCalendarEvents({ start, end }),
    listAll(),
    getGreetings({ start, end })
  ]).then(([calRes, demandRes, greetRes]) => {
    // 1. 日历事件（拜访/生日/周年）
    const list = (calRes?.data?.list || []).map(e => ({
      ...e,
      _date: e.next_follow_date ? dayjs(e.next_follow_date * 1000).format('YYYY-MM-DD') : '',
      _target_id: e._target_id || String(e.id || ''),
    }))

    // 2. 需求事件（显示在今天的日期格子里）
    demands.value = demandRes?.data?.list || []
    const today = dayjs().format('YYYY-MM-DD')
    const demandEvents = demands.value.map(d => ({
      id: 'dm' + d.id,
      customer_id: d.customer_id,
      customer_name: d.customer_name,
      _date: today,
      _event_date: today,
      _target_id: 'dm' + d.id,
      event_type: 'demand',
      follow_type: d.rule_name,
      next_follow_content: d.reason,
      owner_user_name: d.owner_user_name || '',
    }))

    // 3. 统一祝福语注入（key = event_type|event_date|target_id）
    const greetings = greetRes?.data?.list || []
    const greetMap = {}
    greetings.forEach(g => { greetMap[g.event_type + '|' + g.event_date + '|' + g.target_id] = g.greeting })

    const allEvts = [...list, ...demandEvents, ...getHolidays(start, end)]
    allEvts.forEach(ev => {
      const key = (ev.event_type || 'visit') + '|' + (ev._event_date || ev._date) + '|' + (ev._target_id || ev.id || '')
      if (greetMap[key]) ev._greeting = greetMap[key]
    })

    allEvents.value = allEvts
    selectedDayKey.value = today
  }).finally(() => { loading.value = false })
}

onMounted(loadAll)
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
.md-event.contract_expiry { background: #fff7e6; color: #fa8c16; }
.md-event.workorder { background: #e6fffb; color: #13c2c2; }
.md-event.demand { background: #fdf5eb; color: #d6893b; }
.md-event.holiday { background: #fff1f0; color: #cf1322; }
.me-dot { display: inline-block; width: 5px; height: 5px; border-radius: 50%; margin-right: 2px; vertical-align: middle; }
.md-event.visit .me-dot { background: #1677ff; }
.md-event.birthday .me-dot { background: #eb2f96; }
.md-event.established .me-dot { background: #52c41a; }
.md-event.contract_expiry .me-dot { background: #fa8c16; }
.md-event.workorder .me-dot { background: #13c2c2; }
.md-event.demand .me-dot { background: #d6893b; }
.md-event.holiday .me-dot { background: #cf1322; }
.md-more { font-size: 10px; color: #999; padding: 1px 4px; }

/* ============ 周视图 ============ */
.vc-week { border: 1px solid #f0f0f0; border-radius: 10px; overflow: hidden; }
.vc-day-headers { display: grid; grid-template-columns: repeat(7, 1fr); background: #fafafa; border-bottom: 1px solid #f0f0f0; }
.vc-day-header { padding: 10px 4px; text-align: center; }
.vc-day-header.today { background: #e6f4ff; }
.dh-name { font-size: 11px; color: #999; }
.dh-date { display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 50%; font-size: 14px; font-weight: 600; margin-top: 2px; }
.dh-date.active { background: #1677ff; color: #fff; }
.dh-lunar { font-size: 10px; color: #999; margin-top: 1px; white-space: nowrap; }
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
.visit-item.contract_expiry { border-left-color: #fa8c16; background: #fff7e620; }
.visit-item.workorder { border-left-color: #13c2c2; background: #e6fffb20; }
.visit-item.demand { border-left-color: #d6893b; background: #fdf5eb20; }
.visit-item.holiday { border-left-color: #cf1322; background: #fff1f020; }
.vi-time { font-size: 10px; color: #1677ff; font-weight: 600; margin-bottom: 1px; }
.visit-item.birthday .vi-time { color: #eb2f96; }
.visit-item.established .vi-time { color: #52c41a; }
.visit-item.contract_expiry .vi-time { color: #fa8c16; }
.visit-item.workorder .vi-time { color: #13c2c2; }
.visit-item.demand .vi-time { color: #d6893b; }
.vi-body { display: flex; align-items: center; gap: 4px; margin-bottom: 1px; }
.vi-tag { font-size: 9px; padding: 0 4px; border-radius: 2px; line-height: 1.5; }
.vi-tag.visit { background: #e6f4ff; color: #1677ff; }
.vi-tag.birthday { background: #fff0f6; color: #eb2f96; }
.vi-tag.established { background: #f6ffed; color: #52c41a; }
.vi-tag.contract_expiry { background: #fff7e6; color: #fa8c16; }
.vi-tag.workorder { background: #e6fffb; color: #13c2c2; }
.vi-tag.demand { background: #fdf5eb; color: #d6893b; }
.vi-tag.holiday { background: #fff1f0; color: #cf1322; }
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
.lg-dot.contract_expiry { background: #fa8c16; }
.lg-dot.workorder { background: #13c2c2; }
.lg-dot.custom { background: #722ed1; }
.lg-dot.demand { background: #d6893b; }
.lg-dot.holiday { background: #f5222d; }

/* 颜色选择器 */
.color-dot { display: inline-block; width: 16px; height: 16px; border-radius: 50%; vertical-align: middle; cursor: pointer; }

/* 商机需求列表 */
.demand-list { margin-top: 16px; border: 1px solid #f0f0f0; border-radius: 10px; overflow: hidden; }
.demand-title { padding: 10px 14px; font-size: 14px; font-weight: 600; background: #fafafa; border-bottom: 1px solid #f0f0f0; }
.demand-item { display: flex; align-items: center; gap: 8px; padding: 10px 14px; border-bottom: 1px solid #f5f5f5; cursor: pointer; transition: background .15s; }
.demand-item:hover { background: #fafcff; }
.demand-item:last-child { border-bottom: none; }
.dm-name { font-weight: 600; min-width: 120px; }
.dm-rule { color: #d6893b; font-size: 12px; min-width: 100px; }
.dm-reason { color: #999; font-size: 12px; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
</style>
