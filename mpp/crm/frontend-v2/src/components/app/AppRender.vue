<template>
  <div class="app-render">
    <template v-for="node in spec" :key="node._key">
      <!-- 数据表格 -->
      <a-card v-if="node.type === 'table'" size="small" :bordered="false" :title="node.props.title">
        <a-row :gutter="12" style="margin-bottom:12px">
          <a-col :span="8">
            <a-input v-model:value="searchKw" :placeholder="node.props.searchPlaceholder || '搜索...'" allow-clear @press-enter="loadData(true)" />
          </a-col>
          <a-col :span="16" style="text-align:right">
            <a-button type="primary" @click="loadData(true)">查询</a-button>
            <a-button v-if="node.props.showCreate !== false" type="primary" style="margin-left:8px" @click="openCreate(node)">
              {{ node.props.createLabel || '新增' }}
            </a-button>
          </a-col>
        </a-row>
        <!-- 筛选栏 -->
        <a-row v-if="node.props.filters?.length" :gutter="12" style="margin-bottom:12px">
          <a-col v-for="flt in node.props.filters" :key="flt.field" :span="4">
            <a-select
              v-model:value="filters[flt.field]"
              :placeholder="fltLabel(flt, node)"
              allow-clear
              style="width:100%"
              size="small"
              @change="loadData(true)"
            >
              <a-select-option v-for="v in dictOptions(flt)" :key="v" :value="v">{{ v }}</a-select-option>
            </a-select>
          </a-col>
        </a-row>
        <a-table
          :columns="columns"
          :data-source="list"
          :loading="loading"
          :pagination="pagination"
          @change="onTableChange"
          row-key="id"
          size="small"
        >
          <template #bodyCell="{ column, record }">
            <!-- 关联字段 → 选择器只读显示 -->
            <template v-if="isRefField(column.dataIndex)">
              <AppEntitySelector
                v-if="isEntityRefField(column.dataIndex)"
                :model-value="record[column.dataIndex]"
                :app-id="node.props.app_id || appId"
                :entity="entityRefName(column.dataIndex)"
                :label-field="entityRefLabel(column.dataIndex)"
                disabled
              />
              <component
                v-else
                :is="refComponent(column.dataIndex)"
                :model-value="record[column.dataIndex]"
                disabled
              />
            </template>
            <!-- 枚举字段 → 标签 -->
            <template v-else-if="isEnumField(column.dataIndex)">
              <a-tag>{{ record[column.dataIndex] }}</a-tag>
            </template>
            <!-- 日期字段 -->
            <template v-else-if="isDateField(column.dataIndex)">
              {{ formatDate(record[column.dataIndex]) }}
            </template>
            <!-- 库存字段 → 低库存红色预警 -->
            <template v-else-if="isStockCol(column.dataIndex) && isLowStock(record)">
              <span style="color:#ff4d4f;font-weight:600">{{ record[column.dataIndex] }}</span>
              <a-tag color="red" style="margin-left:6px">不足</a-tag>
            </template>
            <!-- 操作列 -->
            <template v-else-if="column.key === 'action'">
              <a @click="openEdit(node, record)">编辑</a>
              <a-divider type="vertical" />
              <a-popconfirm title="确认删除?" @confirm="handleDelete(record)">
                <a style="color:#ff4d4f">删除</a>
              </a-popconfirm>
            </template>
          </template>
        </a-table>
      </a-card>

      <!-- 统计卡片 -->
      <StatCards
        v-else-if="node.type === 'stats'"
        :app-id="node.props.app_id || appId"
        :entity="node.props.entity"
        :items="node.props.items || []"
        style="margin-bottom:16px"
      />

      <!-- 布局行 -->
      <a-row
        v-else-if="node.type === 'row'"
        :gutter="node.props.gutter || 16"
      >
        <a-col
          v-for="col in node.children"
          :key="col._key"
          :span="col.span || col.props?.span || 24"
        >
          <AppRender :spec="[col]" :app-id="appId" />
        </a-col>
      </a-row>

      <!-- 卡片容器 -->
      <a-card
        v-else-if="node.type === 'card'"
        :title="node.props.title"
        size="small"
        style="margin-bottom:16px"
      >
        <AppRender :spec="node.children || []" :app-id="appId" />
      </a-card>

      <!-- 独立表单页 -->
      <a-card v-else-if="node.type === 'form'" size="small" :bordered="false" :title="node.props.title || '表单'">
        <EntityForm
          :app-id="node.props.app_id || appId"
          :entity="node.props.entity"
          :fields="node.props.fields || []"
          ref="pageFormRef"
          @submit="onPageFormOk"
        />
      </a-card>

      <!-- 标题文本 -->
      <h3 v-else-if="node.type === 'heading'" :style="node.props.style">{{ node.props.text }}</h3>

      <!-- 纯文本 -->
      <p v-else-if="node.type === 'text'" :style="node.props.style">{{ node.props.text }}</p>

      <!-- 未知类型 -->
      <a-alert v-else type="warning" :message="'未知组件类型: ' + node.type" banner style="margin:4px 0" />
    </template>

    <!-- 新增/编辑弹窗 -->
    <a-modal
      v-model:open="formVisible"
      :title="editing ? '编辑' : formTitle"
      width="560px"
      @ok="handleModalSubmit"
      :confirm-loading="saving"
    >
      <EntityForm
        ref="modalFormRef"
        :app-id="formAppId"
        :entity="formEntity"
        :fields="formFields"
        :record="editing"
        @submit="onModalOk"
      />
    </a-modal>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch, onErrorCaptured } from 'vue'
import { message } from 'ant-design-vue'
import dayjs from 'dayjs'
import request from '@/utils/request'
import StatCards from '@/components/app/StatCards.vue'
import EntityForm from '@/components/app/EntityForm.vue'
import CustomerSelector from '@/components/app/CustomerSelector.vue'
import UserSelector from '@/components/app/UserSelector.vue'
import ContactSelector from '@/components/app/ContactSelector.vue'
import AppEntitySelector from '@/components/app/AppEntitySelector.vue'
import { getDicts } from '@/api/crm/appBuilder'

const props = defineProps({
  spec: { type: Array, default: () => [] },
  appId: { type: String, default: '' },
})

// 捕获渲染错误，定位问题
onErrorCaptured((err, instance, info) => {
  console.error('[AppRender] 渲染错误:', err, info)
  return false
})

// 关联选择器注册表
const refRegistry = { crm_customer: CustomerSelector, user: UserSelector, crm_contact: ContactSelector }

// 收集 spec 中所有 table/form 的字段信息，供弹窗表单使用
const allColumnsMap = reactive({})
const allFieldsMap = reactive({})
const searchKw = ref('')
const filters = reactive({})
const dicts = reactive({})  // { dict_key: [label,...] }
const list = ref([])
const loading = ref(false)
const pagination = reactive({ current: 1, pageSize: 20, total: 0 })
const currentTableNode = ref(null)
const currentEntity = ref('')

const formVisible = ref(false)
const editing = ref(null)
const saving = ref(false)
const formAppId = ref('')
const formEntity = ref('')
const formTitle = ref('')
const formFields = ref([])
const modalFormRef = ref(null)
const pageFormRef = ref(null)

// 构建字段索引
function buildIndexes() {
  props.spec.forEach((node, idx) => {
    try {
      if (node.type === 'table') {
        allColumnsMap[node.props.entity] = node.props.columns || []
        if (node.props.fields) allFieldsMap[node.props.entity] = node.props.fields
        ;(node.props.filters || []).forEach(flt => { if (filters[flt.field] === undefined) filters[flt.field] = undefined })
      } else if (node.type === 'form') {
        allFieldsMap[node.props.entity] = node.props.fields || []
      }
    } catch (e) {
      console.error('[AppRender] buildIndexes 节点异常', idx, node, e)
    }
  })
}

const columns = computed(() => {
  const cols = (currentTableNode.value?.props?.columns || []).map(c => ({ ...c }))
  cols.push({ title: '操作', key: 'action', width: 140 })
  return cols
})

function isRefField(field) {
  const c = (currentTableNode.value?.props?.columns || []).find(col => col.dataIndex === field)
  return c?.type === 'ref'
}
function isEnumField(field) {
  const c = (currentTableNode.value?.props?.columns || []).find(col => col.dataIndex === field)
  return c?.type === 'enum'
}
function isDateField(field) {
  const c = (currentTableNode.value?.props?.columns || []).find(col => col.dataIndex === field)
  return c?.type === 'date'
}
function isStockCol(field) {
  return currentEntity.value === 'product' && field === 'stock'
}
function isLowStock(record) {
  const stock = parseFloat(record.stock)
  const minStock = parseFloat(record.min_stock)
  return !isNaN(stock) && !isNaN(minStock) && stock <= minStock
}
function refComponent(field) {
  const c = (currentTableNode.value?.props?.columns || []).find(col => col.dataIndex === field)
  return refRegistry[c?.ref?.source] || CustomerSelector
}
function isEntityRefField(field) {
  const c = (currentTableNode.value?.props?.columns || []).find(col => col.dataIndex === field)
  const src = c?.ref?.source || ''
  return String(src).startsWith('entity:')
}
function entityRefName(field) {
  const c = (currentTableNode.value?.props?.columns || []).find(col => col.dataIndex === field)
  return String(c?.ref?.source || '').replace('entity:', '')
}
function entityRefLabel(field) {
  const c = (currentTableNode.value?.props?.columns || []).find(col => col.dataIndex === field)
  return c?.ref?.labelField || 'name'
}
function formatDate(v) {
  if (!v) return '-'
  if (typeof v === 'number') return dayjs(v * 1000).format('YYYY-MM-DD')
  return dayjs(v).format('YYYY-MM-DD')
}

// 打开新增
function openCreate(node) {
  currentTableNode.value = node
  currentEntity.value = node.props.entity
  editing.value = null
  formAppId.value = node.props.app_id || props.appId
  formEntity.value = node.props.entity
  formFields.value = allFieldsMap[node.props.entity] || []
  formTitle.value = node.props.createLabel || '新增'
  formVisible.value = true
}

// 打开编辑
function openEdit(node, record) {
  currentTableNode.value = node
  currentEntity.value = node.props.entity
  editing.value = record
  formAppId.value = node.props.app_id || props.appId
  formEntity.value = node.props.entity
  formFields.value = allFieldsMap[node.props.entity] || []
  formVisible.value = true
}

async function loadData(reset = false) {
  if (!currentTableNode.value) { console.warn('[AppRender] 无 currentTableNode'); return }
  if (reset) pagination.current = 1
  loading.value = true
  try {
    const params = {
      app_id: currentTableNode.value.props.app_id || props.appId,
      entity_key: currentTableNode.value.props.entity,
      page: pagination.current, pageSize: pagination.pageSize,
      search: searchKw.value || undefined,
      filter: JSON.stringify(buildFilterParams()),
    }
    const res = await request({ url: '/crm.appEngine/dataList', method: 'get', params })
    const d = res?.data || {}
    list.value = d.list || []
    pagination.total = d.total || 0
  } catch (e) { console.error('[AppRender] 加载失败', e) }
  loading.value = false
}

// 构建筛选参数（只包含有值的）
function buildFilterParams() {
  const arr = []
  Object.entries(filters).forEach(([k, v]) => {
    if (v !== undefined && v !== null && v !== '') arr.push({ field: k, op: 'eq', value: v })
  })
  return arr
}

function fltLabel(flt, node) {
  const col = (node.props.columns || []).find(c => c.dataIndex === flt.field)
  return col?.title || flt.field
}

// 返回筛选/字段的选项：优先固定 values，其次字典
function dictOptions(flt) {
  if (flt.values?.length) return flt.values
  const key = flt.dict || flt.field
  return dicts[key] || []
}

// 加载应用字典
async function loadDicts() {
  const appId = props.appId || currentTableNode.value?.props?.app_id
  if (!appId) return
  try {
    const res = await getDicts(appId)
    const grouped = res?.data?.dicts || {}
    Object.entries(grouped).forEach(([k, items]) => {
      dicts[k] = items.map(i => i.label)
    })
  } catch {}
}

function onTableChange(pag) { pagination.current = pag.current; pagination.pageSize = pag.pageSize; loadData() }

async function handleDelete(record) {
  await request({ url: '/crm.appEngine/dataDelete', method: 'post', data: { app_id: currentTableNode.value.props.app_id || props.appId, entity_key: currentTableNode.value.props.entity, id: record.id } })
  message.success('已删除')
  loadData()
}

async function handleModalSubmit() {
  saving.value = true
  const ok = await modalFormRef.value?.submit()
  if (ok) { formVisible.value = false; message.success('保存成功'); loadData() }
  saving.value = false
}
function onModalOk() { formVisible.value = false; loadData() }

function onPageFormOk() { message.success('保存成功') }

onMounted(() => {
  console.log('[AppRender] 挂载，spec条数=', props.spec.length, 'appId=', props.appId, '首节点=', props.spec[0]?.type)
  buildIndexes()
  loadDicts()
  const firstTable = props.spec.find(n => n.type === 'table')
  if (firstTable) {
    currentTableNode.value = firstTable
    currentEntity.value = firstTable.props.entity
    loadData()
  } else {
    console.log('[AppRender] 无 table 节点，spec=', JSON.stringify(props.spec).slice(0,200))
  }
})

// spec 变化时重置状态（切换页面）
watch(() => props.spec, () => {
  buildIndexes()
  loadDicts()
  const firstTable = props.spec.find(n => n.type === 'table')
  if (firstTable) {
    currentTableNode.value = firstTable
    currentEntity.value = firstTable.props.entity
    searchKw.value = ''
    Object.keys(filters).forEach(k => delete filters[k])
    loadData()
  } else {
    currentTableNode.value = null
  }
})
</script>
