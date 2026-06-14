<template>
  <a-card :bordered="false" title="角色管理">
    <div class="table-operator">
      <a-button type="primary" @click="handleAdd"><plus-outlined /> 新增</a-button>
    </div>
    <a-table :columns="columns" :data-source="dataSource" :loading="loading" :pagination="false" :default-expand-all-rows="true" row-key="role_id">
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'action'">
          <a @click="handleEdit(record)">编辑</a>
          <a-divider type="vertical" />
          <a-popconfirm title="确认删除?" @confirm="handleDelete(record)">
            <a style="color:#ff4d4f">删除</a>
          </a-popconfirm>
        </template>
      </template>
    </a-table>
    <AddForm ref="addRef" :role-list="dataSource" :menu-list="menuList" @ok="loadData" />
    <EditForm ref="editRef" :role-list="dataSource" :menu-list="menuList" @ok="loadData" />
  </a-card>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { message } from 'ant-design-vue'
import { PlusOutlined } from '@ant-design/icons-vue'
import * as RoleApi from '@/api/role'
import * as MenuApi from '@/api/menu'
import AddForm from './modules/AddForm.vue'
import EditForm from './modules/EditForm.vue'

console.log('[manage/role] setup')

const loading = ref(false)
const dataSource = ref([])
const menuList = ref([])
const addRef = ref()
const editRef = ref()

const columns = [
  { title: '角色ID', dataIndex: 'role_id' },
  { title: '角色名称', dataIndex: 'role_name' },
  { title: '排序', dataIndex: 'sort' },
  { title: '添加时间', dataIndex: 'create_time' },
  { title: '操作', key: 'action', width: 120 },
]

onMounted(() => loadData())

async function loadData() {
  loading.value = true
  try {
    const res = await RoleApi.list({})
    if (res?.status === 200) dataSource.value = res.data?.list || []
  } finally { loading.value = false }
}

async function loadMenuList() {
  if (menuList.value.length > 0) return
  const res = await MenuApi.list({})
  if (res?.status === 200) menuList.value = res.data?.list || []
}

async function handleAdd() { await loadMenuList(); addRef.value?.open() }
async function handleEdit(r) { await loadMenuList(); editRef.value?.open(r) }

async function handleDelete(r) {
  const res = await RoleApi.deleted(r.role_id)
  if (res?.status === 200) { message.success('删除成功'); loadData() }
}
</script>

<style lang="less" scoped>
.table-operator { margin-bottom: 16px; }
</style>
