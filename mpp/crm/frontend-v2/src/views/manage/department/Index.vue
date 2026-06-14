<template>
  <a-card :bordered="false" title="部门管理">
    <div class="table-operator">
      <a-button type="primary" @click="handleAdd"><plus-outlined /> 新增部门</a-button>
      <a-button style="margin-left:8px" @click="loadData"><reload-outlined /> 刷新</a-button>
    </div>
    <a-table :columns="columns" :data-source="dataSource" :loading="loading" :pagination="false" :default-expand-all-rows="true" row-key="id">
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'status'">
          <a-badge :status="record.status === 1 ? 'success' : 'error'" :text="record.status === 1 ? '启用' : '禁用'" />
        </template>
        <template v-if="column.key === 'action'">
          <a @click="handleEdit(record)">编辑</a>
          <a-divider type="vertical" />
          <a-popconfirm title="确认删除?" @confirm="handleDelete(record)">
            <a style="color:#ff4d4f">删除</a>
          </a-popconfirm>
        </template>
      </template>
    </a-table>
    <DepartmentForm :visible="modalVisible" :record="currentRecord" :department-tree="dataSource" @ok="onFormOk" @cancel="modalVisible=false" />
  </a-card>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { message } from 'ant-design-vue'
import { PlusOutlined, ReloadOutlined } from '@ant-design/icons-vue'
import * as Api from '@/api/department'
import DepartmentForm from './modules/DepartmentForm.vue'

console.log('[manage/department] setup')

const loading = ref(false)
const dataSource = ref([])
const modalVisible = ref(false)
const currentRecord = ref(null)

const columns = [
  { title: '部门名称', dataIndex: 'department_name' },
  { title: '部门编码', dataIndex: 'department_code', width: 140 },
  { title: '排序', dataIndex: 'sort', width: 80 },
  { title: '状态', key: 'status', width: 80 },
  { title: '创建时间', dataIndex: 'create_time', width: 160 },
  { title: '操作', key: 'action', width: 120 },
]

onMounted(() => loadData())

async function loadData() {
  loading.value = true
  try {
    const res = await Api.getTreeList({})
    if (res?.status === 200) dataSource.value = res.data?.tree || res.data?.list || []
  } finally { loading.value = false }
}

function handleAdd() { currentRecord.value = null; modalVisible.value = true }
function handleEdit(r) { currentRecord.value = r; modalVisible.value = true }
async function handleDelete(r) {
  const res = await Api.deleteById(r.id)
  if (res?.status === 200) { message.success('删除成功'); loadData() }
}
function onFormOk() { modalVisible.value = false; loadData() }
</script>

<style lang="less" scoped>
.table-operator { margin-bottom: 16px; }
</style>
