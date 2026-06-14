<template>
  <a-card :bordered="false" title="管理员列表">
    <div class="table-operator">
      <div class="right-actions" style="margin-left:auto">
        <a-button type="primary" @click="handleAdd"><plus-outlined /> 新增</a-button>
        <a-input-search v-model:value="searchText" placeholder="搜索用户名/姓名" style="width: 220px" @search="onSearch" />
      </div>
    </div>

    <a-table :columns="columns" :data-source="dataSource" :loading="loading" :pagination="pagination" row-key="store_user_id" @change="onTableChange">
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'user_name'">
          {{ record.user_name }}
          <a-tag v-if="record.is_super" color="red">超管</a-tag>
          <a-tag v-if="record.is_supplier" color="blue">供应商</a-tag>
        </template>
        <template v-if="column.key === 'action'">
          <a @click="handleEdit(record)">编辑</a>
          <a-divider type="vertical" />
          <a-popconfirm title="确认删除?" @confirm="handleDelete(record)">
            <a v-if="!record.is_super" style="color:#ff4d4f">删除</a>
          </a-popconfirm>
        </template>
      </template>
    </a-table>

    <AddForm ref="addRef" :role-list="roleList" @ok="onRefresh" />
    <EditForm ref="editRef" :role-list="roleList" @ok="onRefresh" />
  </a-card>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { message } from 'ant-design-vue'
import { PlusOutlined } from '@ant-design/icons-vue'
import * as UserApi from '@/api/user'
import * as RoleApi from '@/api/role'
import AddForm from './modules/AddForm.vue'
import EditForm from './modules/EditForm.vue'

console.log('[manage/user] setup')

const loading = ref(false)
const searchText = ref('')
const dataSource = ref([])
const roleList = ref([])
const addRef = ref()
const editRef = ref()
const pagination = reactive({ current: 1, pageSize: 15, total: 0 })

const columns = [
  { title: '管理员ID', dataIndex: 'store_user_id', key: 'store_user_id' },
  { title: '用户名', dataIndex: 'user_name', key: 'user_name' },
  { title: '姓名', dataIndex: 'real_name', key: 'real_name' },
  { title: '排序', dataIndex: 'sort', key: 'sort' },
  { title: '添加时间', dataIndex: 'create_time', key: 'create_time' },
  { title: '操作', key: 'action', width: 120 },
]

onMounted(() => { loadData(); loadRoleList() })

async function loadData(reset = false) {
  if (reset) pagination.current = 1
  loading.value = true
  try {
    const res = await UserApi.list({ page: pagination.current, limit: pagination.pageSize, search: searchText.value, isSupplier: 0 })
    if (res?.status === 200) {
      const list = res.data?.list
      dataSource.value = list?.data || []
      pagination.total = list?.total || 0
    }
  } finally { loading.value = false }
}

async function loadRoleList() {
  try {
    const res = await RoleApi.list({})
    if (res?.status === 200) roleList.value = res.data?.list || []
  } catch {}
}

function onSearch() { loadData(true) }
function onTableChange(pag) { pagination.current = pag.current; pagination.pageSize = pag.pageSize; loadData() }
function onRefresh() { loadData() }

function handleAdd() { addRef.value?.open() }
function handleEdit(r) { editRef.value?.open(r) }

async function handleDelete(r) {
  const res = await UserApi.deleted(r.store_user_id)
  if (res?.status === 200) { message.success('删除成功'); loadData() }
}
</script>

<style lang="less" scoped>
.table-operator { display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;
  .right-actions { display: flex; gap: 12px; }
}
</style>
