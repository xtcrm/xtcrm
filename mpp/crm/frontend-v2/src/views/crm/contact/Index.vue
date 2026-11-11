<template>
  <a-card :bordered="false" title="联系人管理">
    <div style="margin-bottom:16px">
      <a-input-search v-model:value="keyword" placeholder="搜索联系人/公司名/手机号" style="width:280px" allow-clear @search="onSearch" />
    </div>
    <a-table :columns="cols" :data-source="list" :loading="loading" :pagination="pagination"
      row-key="id" size="small" @change="handleTableChange">
      <template #bodyCell="{ column, record }">
        <template v-if="column.dataIndex === 'contact_name'">
          <a @click="openDetail(record)">{{ record.contact_name }}</a>
        </template>
        <template v-else-if="column.dataIndex === 'gender'">
          {{ ['未知','男','女'][record.gender] || record.gender }}
        </template>
        <template v-else-if="column.dataIndex === 'company_names'">
          <template v-if="record.company_names?.length">
            <a-tag v-for="(name, i) in record.company_names" :key="i" size="small" style="margin:1px">{{ name }}</a-tag>
          </template>
          <span v-else class="text-gray">-</span>
        </template>
        <template v-else-if="column.dataIndex === 'action'">
          <a @click="openDetail(record)">详情</a>
        </template>
      </template>
    </a-table>
  </a-card>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { getList } from '@/api/crm/contact'

const router = useRouter()
const list = ref([])
const loading = ref(false)
const keyword = ref('')
const pagination = reactive({ current: 1, pageSize: 20, total: 0 })

const cols = [
  { title: '姓名', dataIndex: 'contact_name' },
  { title: '手机', dataIndex: 'mobile', width: 130 },
  { title: '性别', dataIndex: 'gender', width: 60 },
  { title: '生日', dataIndex: 'birthday', width: 100 },
  { title: '籍贯', dataIndex: 'hometown', width: 80 },
  { title: '关联公司', dataIndex: 'company_names', width: 200 },
  { title: '操作', dataIndex: 'action', width: 80 },
]

function loadData() {
  loading.value = true
  const params = { page: pagination.current, pageSize: pagination.pageSize }
  if (keyword.value) params.keyword = keyword.value
  getList(params).then(res => {
    const d = res?.data || res
    list.value = d?.list || d?.data || []
    pagination.total = d?.total || 0
  }).finally(() => { loading.value = false })
}

function onSearch() { pagination.current = 1; loadData() }
function handleTableChange(p) { pagination.current = p.current; loadData() }
function openDetail(record) { router.push({ name: 'contact-detail', query: { id: record.id } }) }

onMounted(loadData)
</script>
