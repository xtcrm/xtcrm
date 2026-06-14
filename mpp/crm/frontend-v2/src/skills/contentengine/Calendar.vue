<template>
  <a-card :bordered="false">
    <div style="display:flex;justify-content:space-between;margin-bottom:16px">
      <a-month-picker v-model:value="month" format="YYYY-MM" @change="loadCalendar" />
      <span style="color:#999">{{ days.length }}天有排期</span>
    </div>
    <a-spin :spinning="loading">
      <div class="cal-grid">
        <div v-for="d in days" :key="d.date" class="cal-day">
          <div class="cal-date">{{ d.day }}</div>
          <div v-for="item in d.items" :key="item.id" :class="['cal-item', item.format]" @click="router.push({ path: '/crm/content/editor', query: { topic_id: item.topic_id } })">
            <a-tag :color="fmtColor[item.format]" size="small">{{ fmtLabel[item.format] }}</a-tag>{{ item.title }}
          </div>
        </div>
        <a-empty v-if="!days.length" description="本月无排期" />
      </div>
    </a-spin>
  </a-card>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import dayjs from 'dayjs'
import { getCalendar } from './api'

const router = useRouter()
const loading = ref(false)
const month = ref(dayjs())
const days = ref([])

const fmtLabel = { long: '长文', video: '视频', social: '朋友圈', seo: 'SEO' }
const fmtColor = { long: 'blue', video: 'purple', social: 'green', seo: 'orange' }

function loadCalendar() {
  loading.value = true
  getCalendar(month.value.format('YYYY-MM')).then(res => {
    const s = (res?.data || {}).schedule || {}
    days.value = Object.entries(s)
      .map(([date, items]) => ({ date, day: parseInt(date.split('-')[2]), items }))
      .sort((a, b) => a.date.localeCompare(b.date))
  }).finally(() => { loading.value = false })
}

onMounted(() => { loadCalendar() })
</script>

<style scoped>
.cal-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px; }
.cal-day { background: #fafafa; border-radius: 4px; padding: 8px 12px; min-height: 60px; }
.cal-date { font-weight: bold; font-size: 15px; margin-bottom: 6px; color: #d6893b; }
.cal-item { padding: 3px 0; font-size: 12px; cursor: pointer; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.cal-item:hover { color: #d6893b; }
</style>
