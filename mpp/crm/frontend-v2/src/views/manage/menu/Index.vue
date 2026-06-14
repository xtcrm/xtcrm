<template>
  <a-card :bordered="false" title="菜单管理">
    <a-table :columns="columns" :data-source="menuList" :loading="loading" row-key="menu_id" :default-expand-all-rows="true" :pagination="false">
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'module'">
          <a-tag :color="record.module === 10 ? 'blue' : 'green'">{{ record.module === 10 ? '菜单' : '操作' }}</a-tag>
        </template>
        <template v-if="column.key === 'path'">
          {{ record.path || '-' }}
        </template>
        <template v-if="column.key === 'action_mark'">
          {{ record.action_mark || '-' }}
        </template>
      </template>
    </a-table>
  </a-card>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import * as MenuApi from '@/api/menu'

const loading = ref(false)
const menuList = ref([])

const columns = [
  { title: '名称', dataIndex: 'name', key: 'name' },
  { title: '类型', dataIndex: 'module', key: 'module', width: 80 },
  { title: '路径', dataIndex: 'path', key: 'path' },
  { title: '操作标识', dataIndex: 'action_mark', key: 'action_mark', width: 120 },
]

onMounted(async () => {
  loading.value = true
  try {
    const res = await MenuApi.list({})
    if (res?.status === 200) {
      const list = res.data?.list || []
      menuList.value = buildTree(list)
    }
  } finally { loading.value = false }
})

function buildTree(list) {
  const map = {}, roots = []
  list.forEach(m => { map[m.menu_id] = { ...m, children: [] } })
  list.forEach(m => {
    const node = map[m.menu_id]
    if (m.parent_id && map[m.parent_id]) map[m.parent_id].children.push(node)
    else roots.push(node)
  })
  roots.forEach(r => { if (!r.children.length) delete r.children })
  return roots
}
</script>
