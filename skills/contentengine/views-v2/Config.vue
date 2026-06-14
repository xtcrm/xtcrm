<template>
  <a-card :bordered="false" style="min-height:calc(100vh - 140px)">
    <a-tabs v-model:active-key="activeTab" :animated="false" style="margin-top:-12px" @change="onTabChange">
      <a-tab-pane v-for="t in tabs" :key="t.key" :tab="t.label">
        <component :is="t.component" :key="activeTab" />
      </a-tab-pane>
    </a-tabs>
  </a-card>
</template>

<script setup>
import { ref, markRaw } from 'vue'
import Calendar from './Calendar.vue'
import Profile from './Profile.vue'
import Keywords from './Keywords.vue'
import Solutions from './Solutions.vue'
import Styles from './Styles.vue'
import Agents from './Agents.vue'
import Analytics from './Analytics.vue'

const activeTab = ref('calendar')

const tabs = [
  { key: 'calendar', label: '内容日历', component: markRaw(Calendar) },
  { key: 'profile', label: '公司档案', component: markRaw(Profile) },
  { key: 'keywords', label: '关键词', component: markRaw(Keywords) },
  { key: 'solutions', label: '解决方案', component: markRaw(Solutions) },
  { key: 'styles', label: '写作风格', component: markRaw(Styles) },
  { key: 'agents', label: '智能体', component: markRaw(Agents) },
  { key: 'analytics', label: '数据看板', component: markRaw(Analytics) },
]

function onTabChange(key) { /* keep alive via :key */ }
</script>
