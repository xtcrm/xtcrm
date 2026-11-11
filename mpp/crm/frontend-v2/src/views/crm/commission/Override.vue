<template>
  <a-card :bordered="false" title="覆盖审批">
    <p style="color:#999;margin-bottom:16px">
      单笔比例覆盖必须先申请、后锁定：只有审批通过的申请才在签约时生效，驳回会留档。
    </p>

    <a-form layout="inline" style="margin-bottom:16px">
      <a-form-item label="状态">
        <a-select v-model:value="queryParam.status" allow-clear placeholder="全部" style="width:140px">
          <a-select-option :value="0">待审批</a-select-option>
          <a-select-option :value="1">已通过</a-select-option>
          <a-select-option :value="2">已驳回</a-select-option>
          <a-select-option :value="3">已失效</a-select-option>
        </a-select>
      </a-form-item>
      <a-form-item>
        <a-button type="primary" @click="loadData(true)">查询</a-button>
      </a-form-item>
    </a-form>

    <a-table
      :columns="columns"
      :data-source="dataSource"
      :loading="loading"
      :pagination="pagination"
      :row-key="r => r.id"
      size="middle"
      @change="onTableChange"
    >
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'source'">
          <div>{{ record.source_no || ('#' + record.source_id) }}</div>
          <div style="color:#999;font-size:12px">{{ record.source_type === 2 ? '合同' : '订单' }}</div>
        </template>
        <template v-if="column.key === 'default_ratio'">
          {{ record.default_sales_ratio }} / {{ record.default_cs_ratio }}
        </template>
        <template v-if="column.key === 'apply_ratio'">
          <b>{{ record.sales_ratio }} / {{ record.cs_ratio }}</b>
        </template>
        <template v-if="column.key === 'status'">
          <a-tag v-if="record.status === 0" color="orange">待审批</a-tag>
          <a-tag v-else-if="record.status === 1" color="green">已通过</a-tag>
          <a-tag v-else-if="record.status === 2" color="red">已驳回</a-tag>
          <a-tag v-else>已失效</a-tag>
        </template>
        <template v-if="column.key === 'action'">
          <template v-if="record.status === 0">
            <a-button type="primary" size="small" @click="decide(record, true)">通过</a-button>
            <a-button danger size="small" style="margin-left:6px" @click="decide(record, false)">驳回</a-button>
          </template>
          <span v-else style="color:#999">{{ record.approve_remark || '—' }}</span>
        </template>
      </template>
    </a-table>
  </a-card>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { Modal, message } from 'ant-design-vue'
import { getOverrides, approveOverride } from '@/api/crm/commission'

const loading = ref(false)
const dataSource = ref([])
const queryParam = reactive({})
const pagination = reactive({ current: 1, pageSize: 20, total: 0, showSizeChanger: true, showTotal: t => `共 ${t} 条` })

const columns = [
  { title: '单据', key: 'source', width: 160 },
  { title: '业务', dataIndex: 'biz_type', key: 'biz_type', width: 80, customRender: ({ text }) => ({ 1: '新签', 2: '续费', 3: '增购' }[text] || text) },
  { title: '规则比例(销/客)', key: 'default_ratio', width: 140 },
  { title: '申请比例(销/客)', key: 'apply_ratio', width: 140 },
  { title: '理由', dataIndex: 'reason', key: 'reason' },
  { title: '申请人', dataIndex: 'apply_user_name', key: 'apply_user_name', width: 110 },
  { title: '状态', key: 'status', width: 100 },
  { title: '操作', key: 'action', width: 160 },
]

async function loadData(reset = false) {
  if (reset) pagination.current = 1
  loading.value = true
  try {
    const params = { page: pagination.current, list_rows: pagination.pageSize, ...queryParam }
    const res = await getOverrides(params)
    if (res?.status === 200) {
      dataSource.value = res.data?.list || []
      pagination.total = res.data?.total || 0
    }
  } finally { loading.value = false }
}

function onTableChange(pag) {
  pagination.current = pag.current
  pagination.pageSize = pag.pageSize
  loadData()
}

function decide(record, pass) {
  Modal.confirm({
    title: pass ? '确认通过该覆盖申请？' : '确认驳回该覆盖申请？',
    content: pass
      ? `该单据签约时将按 ${record.sales_ratio}% / ${record.cs_ratio}% 锁定分账比例。`
      : '驳回后不会产生任何分账，申请单留档可查。',
    onOk: async () => {
      try {
        const res = await approveOverride({ id: record.id, pass: pass ? 1 : 0, remark: '' })
        if (res?.status === 200) {
          message.success(pass ? '已通过' : '已驳回')
          loadData()
        } else {
          message.error(res?.message || '操作失败')
        }
      } catch (e) {
        message.error('操作失败')
      }
    },
  })
}

loadData()
</script>
