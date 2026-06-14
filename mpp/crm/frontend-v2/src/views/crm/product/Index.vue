<template>
  <a-card :bordered="false">
    <div class="table-operator">
      <a-button type="primary" @click="handleAdd"><plus-outlined /> 新增产品</a-button>
    </div>

    <div class="table-page-search-wrapper">
      <a-form layout="inline">
        <a-row :gutter="24">
          <a-col :md="8">
            <a-form-item label="产品名称">
              <a-input v-model:value="queryParam.product_name" placeholder="产品名称" allow-clear />
            </a-form-item>
          </a-col>
          <a-col :md="8">
            <a-form-item label="状态">
              <a-select v-model:value="queryParam.status" placeholder="全部" allow-clear>
                <a-select-option value="">全部</a-select-option>
                <a-select-option :value="1">上架</a-select-option>
                <a-select-option :value="0">下架</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :md="8">
            <a-button type="primary" @click="loadData(true)">查询</a-button>
            <a-button style="margin-left:8px" @click="resetSearch">重置</a-button>
          </a-col>
        </a-row>
      </a-form>
    </div>

    <a-table :columns="columns" :data-source="dataSource" :loading="loading" :pagination="pagination"
      :row-key="r => r.id" size="middle" @change="onTableChange">
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'image'">
          <img v-if="record.image_url" :src="record.image_url" style="width:40px;height:40px;object-fit:cover" />
        </template>
        <template v-else-if="column.key === 'status'">
          <a-badge :status="record.status === 1 ? 'success' : 'default'" :text="record.status === 1 ? '上架' : '下架'" />
        </template>
        <template v-else-if="column.key === 'action'">
          <a @click="handleEdit(record)">编辑</a>
          <a-divider type="vertical" />
          <a-popconfirm title="确认删除?" @confirm="handleDelete(record)">
            <a style="color:#ff4d4f">删除</a>
          </a-popconfirm>
        </template>
      </template>
    </a-table>

    <ProductForm v-model:open="formVisible" :mdl="selected" @ok="onFormOk" />
  </a-card>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { message } from 'ant-design-vue'
import { PlusOutlined } from '@ant-design/icons-vue'
import { getList, deleteById } from '@/api/crm/product'
import ProductForm from './components/ProductForm.vue'

const loading = ref(false)
const dataSource = ref([])
const queryParam = reactive({})
const formVisible = ref(false)
const selected = ref(null)
const pagination = reactive({ current: 1, pageSize: 15, total: 0, showSizeChanger: true, showTotal: t => `共 ${t} 条` })

const columns = [
  { title: '图片', dataIndex: 'image_url', key: 'image', width: 60 },
  { title: '产品编码', dataIndex: 'product_code', key: 'product_code', width: 120 },
  { title: '产品名称', dataIndex: 'product_name', key: 'product_name', width: 180 },
  { title: '规格型号', dataIndex: 'specification', key: 'specification', width: 140 },
  { title: '单位', dataIndex: 'unit', key: 'unit', width: 60 },
  { title: '分类', dataIndex: 'category_name', key: 'category_name', width: 100 },
  { title: '参考单价', dataIndex: 'reference_price', key: 'reference_price', width: 100, align: 'right' },
  { title: '状态', dataIndex: 'status', key: 'status', width: 80 },
  { title: '操作', key: 'action', width: 120 },
]

async function loadData(reset = false) {
  if (reset) pagination.current = 1
  loading.value = true
  try {
    const params = { page: pagination.current, limit: pagination.pageSize, ...queryParam }
    const res = await getList(params)
    if (res?.status === 200) {
      const list = res.data?.list
      dataSource.value = list?.data || []
      pagination.total = list?.total || 0
    }
  } finally { loading.value = false }
}

function onTableChange(pag) { pagination.current = pag.current; pagination.pageSize = pag.pageSize; loadData() }
function handleAdd() { selected.value = null; formVisible.value = true }
function handleEdit(r) { selected.value = r; formVisible.value = true }
async function handleDelete(r) { await deleteById(r.id); message.success('删除成功'); loadData() }
function onFormOk() { formVisible.value = false; loadData() }
function resetSearch() { Object.keys(queryParam).forEach(k => delete queryParam[k]); loadData(true) }

onMounted(() => { loadData() })
</script>
