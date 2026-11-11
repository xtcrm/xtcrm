<template>
  <a-card :bordered="false" title="角色管理">
    <div class="table-operator">
      <a-button type="primary" @click="handleAdd"><plus-outlined /> 新增</a-button>
    </div>
    <a-table :columns="columns" :data-source="dataSource" :loading="loading" :pagination="false"
      :expanded-row-keys="expandedKeys" row-key="role_id" @expand="onExpand">
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
const expandedKeys = ref([])

/** 展开所有有下级的角色（数据异步加载，default-expand-all-rows 不生效，用受控 keys） */
function expandableIds(list, acc = []) {
  (list || []).forEach(r => {
    if (r.children?.length) { acc.push(r.role_id); expandableIds(r.children, acc) }
  })
  return acc
}
function onExpand(expanded, record) {
  expandedKeys.value = expanded
    ? [...new Set([...expandedKeys.value, record.role_id])]
    : expandedKeys.value.filter(k => k !== record.role_id)
}
const menuList = ref([])
const addRef = ref()
const editRef = ref()

// 树形表格：缩进/展开箭头落在第一列，所以角色名称放前面
const columns = [
  { title: '角色名称', dataIndex: 'role_name' },
  { title: '角色ID', dataIndex: 'role_id', width: 100 },
  { title: '排序', dataIndex: 'sort', width: 80 },
  { title: '添加时间', dataIndex: 'create_time', width: 180 },
  { title: '操作', key: 'action', width: 120 },
]

onMounted(() => loadData())

async function loadData() {
  loading.value = true
  try {
    const res = await RoleApi.list({})
    if (res?.status === 200) {
      dataSource.value = res.data?.list || []
      expandedKeys.value = expandableIds(dataSource.value)
    }
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
