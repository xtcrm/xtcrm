<template>
  <div>
    <a-card :bordered="false">
      <a-page-header title="会员列表" />
      <a-row :gutter="16" style="margin-bottom:16px">
        <a-col :span="6">
          <a-input v-model:value="keyword" placeholder="搜索 会员ID / 手机号 / 昵称" @pressEnter="refresh" allow-clear />
        </a-col>
        <a-col :span="4">
          <a-button type="primary" :loading="loading" @click="refresh">查询</a-button>
          <a-button style="margin-left:8px" @click="keyword='';refresh()">重置</a-button>
        </a-col>
      </a-row>
      <a-table :columns="columns" :data-source="list" :loading="loading" :pagination="pagination" :row-key="r => r.user_id" size="small" @change="handleTableChange">
        <template #bodyCell="{ column, record }">
          <template v-if="column.dataIndex === 'avatar_url'">
            <a-avatar v-if="record.avatar_url" :src="record.avatar_url" size="small" />
            <span v-else>-</span>
          </template>
          <template v-else-if="column.dataIndex === 'user_id'">
            <a-tag color="blue" style="cursor:pointer" @click="copyId(record.user_id)">{{ record.user_id }}</a-tag>
          </template>
        </template>
      </a-table>
    </a-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { message } from 'ant-design-vue'
import request from '@/utils/request'

const keyword = ref('')
const list = ref([])
const loading = ref(false)
const pagination = reactive({ current: 1, pageSize: 20, total: 0 })

const columns = [
  { title: '会员ID', dataIndex: 'user_id', width: 90 },
  { title: '头像', dataIndex: 'avatar_url', width: 60 },
  { title: '昵称', dataIndex: 'name', width: 150 },
  { title: '手机号', dataIndex: 'mobile', width: 140 },
  { title: '注册时间', dataIndex: 'create_time', width: 160 },
]

function loadData() {
  loading.value = true
  request({ url: '/invoice.config/memberList', method: 'get', params: { keyword: keyword.value, page: pagination.current, pageSize: pagination.pageSize } })
    .then(r => {
      const d = r?.data || {}
      list.value = d.list || []
      pagination.total = d.total || 0
    })
    .finally(() => { loading.value = false })
}

function handleTableChange(p) { pagination.current = p.current; loadData() }
function refresh() { pagination.current = 1; loadData() }
function copyId(id) {
  navigator.clipboard?.writeText(String(id))
    .then(() => message.success('已复制会员ID: ' + id))
    .catch(() => message.info('会员ID: ' + id))
}

onMounted(loadData)
</script>
