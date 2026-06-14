<template>
  <a-card :bordered="false" style="min-height:calc(100vh - 140px)">
    <a-tabs v-model="activeTab" :animated="false" style="margin-top:-12px">
      <a-tab-pane v-for="t in tabs" :key="t.key" :tab="t.label">
        <component :is="t.component" />
      </a-tab-pane>
    </a-tabs>
  </a-card>
</template>
<script>
import Calendar from './Calendar.vue'
import Profile from './Profile.vue'
import Keywords from './Keywords.vue'
import Solutions from './Solutions.vue'
import Styles from './Styles.vue'
import Agents from './Agents.vue'
import Analytics from './Analytics.vue'
export default { name:'ContentConfig', components:{Calendar,Profile,Keywords, Solutions,Styles,Agents,Analytics}, data(){return{activeTab:'calendar',tabs:[{key:'calendar',label:'内容日历',component:'Calendar'},{key:'profile',label:'公司档案',component:'Profile'},{key:'keywords',label:'关键词',component:'Keywords'},{key:'solutions',label:'解决方案',component:'Solutions'},{key:'styles',label:'写作风格',component:'Styles'},{key:'agents',label:'智能体',component:'Agents'},{key:'analytics',label:'数据看板',component:'Analytics'}]}} }
</script>
