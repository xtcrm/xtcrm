<template>
  <a-card :bordered="false">
    <a-spin :spinning="loading">
      <a-row :gutter="16">
        <a-col :span="6"><a-card :body-style="{ padding: '16px' }"><div class="stat-num">{{ stats.total || 0 }}</div><div class="stat-label">总内容数</div></a-card></a-col>
        <a-col :span="6"><a-card :body-style="{ padding: '16px' }"><div class="stat-num">{{ stats.published || 0 }}</div><div class="stat-label">已发布</div></a-card></a-col>
        <a-col :span="6"><a-card :body-style="{ padding: '16px' }"><div class="stat-num">{{ (stats.views || 0).toLocaleString() }}</div><div class="stat-label">总阅读量</div></a-card></a-col>
        <a-col :span="6"><a-card :body-style="{ padding: '16px' }"><div class="stat-num">{{ stats.inquiries || 0 }}</div><div class="stat-label">带来询盘</div></a-card></a-col>
      </a-row>

      <a-row :gutter="16" style="margin-top:16px">
        <a-col :span="12">
          <a-card title="按格式统计" size="small">
            <a-table :data-source="stats.by_format || []" :pagination="false" size="small" row-key="format">
              <a-table-column title="格式" data-index="format" key="format">
                <template #default="{ text }">{{ ({ long: '长文', video: '视频', social: '朋友圈', seo: 'SEO' })[text] }}</template>
              </a-table-column>
              <a-table-column title="数量" data-index="cnt" key="cnt" width="80" />
              <a-table-column title="阅读" data-index="v" key="v" width="100" />
              <a-table-column title="询盘" data-index="iq" key="iq" width="80" />
            </a-table>
          </a-card>
        </a-col>
        <a-col :span="12">
          <a-card title="高阅读 TOP10" size="small">
            <a-table :data-source="stats.top_viewed || []" :pagination="false" size="small" row-key="id">
              <a-table-column title="标题" data-index="title" key="title" ellipsis />
              <a-table-column title="阅读" data-index="views" key="views" width="80" />
            </a-table>
          </a-card>
        </a-col>
      </a-row>

      <a-card title="近30天趋势" size="small" style="margin-top:16px">
        <div class="trend-bar">
          <div v-for="t in trend" :key="t.d" class="trend-col">
            <div class="trend-bar-inner" :style="{ height: barH(t.cnt) + 'px' }"><span>{{ t.cnt }}</span></div>
            <div class="trend-date">{{ t.d.slice(5) }}</div>
          </div>
        </div>
      </a-card>
    </a-spin>
  </a-card>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { getStats } from './api'

const loading = ref(false)
const stats = ref({})

const trend = computed(() => stats.value.trend || [])
const maxCnt = computed(() => Math.max(1, ...trend.value.map(t => t.cnt) || [1]))

function loadStats() {
  loading.value = true
  getStats().then(res => { stats.value = res?.data || {} }).finally(() => { loading.value = false })
}

function barH(cnt) { return Math.max(4, cnt / maxCnt.value * 120) }

onMounted(() => { loadStats() })
</script>

<style scoped>
.stat-num { font-size: 28px; font-weight: bold; color: #d6893b; }
.stat-label { font-size: 12px; color: #999; margin-top: 4px; }
.trend-bar { display: flex; align-items: flex-end; gap: 2px; height: 140px; padding-top: 10px; }
.trend-col { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: flex-end; height: 100%; }
.trend-bar-inner { width: 100%; background: #d6893b; border-radius: 2px 2px 0 0; min-width: 12px; display: flex; align-items: flex-end; justify-content: center; }
.trend-bar-inner span { font-size: 10px; color: #fff; padding: 1px 0; }
.trend-date { font-size: 10px; color: #999; margin-top: 2px; }
</style>
