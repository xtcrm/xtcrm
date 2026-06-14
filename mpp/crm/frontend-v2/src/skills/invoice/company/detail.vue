<template>
  <a-card :bordered="false">
    <a-page-header title="公司详情" @back="() => router.go(-1)" />

    <a-tabs v-model:activeKey="activeTab">
      <a-tab-pane key="info" tab="基本信息">
        <a-spin :spinning="loading">
          <a-descriptions :column="2" bordered size="small" style="margin-top:16px">
            <a-descriptions-item label="公司名称">{{ company?.name }}</a-descriptions-item>
            <a-descriptions-item label="税号">{{ company?.tax_number }}</a-descriptions-item>
            <a-descriptions-item label="状态">
              <a-tag :color="company?.status === 1 ? 'green' : 'default'">{{ company?.status === 1 ? '正常' : '已禁用' }}</a-tag>
            </a-descriptions-item>
            <a-descriptions-item label="来源">
              <a-tag>{{ company?.source === 1 ? '小程序自助' : '后台建档' }}</a-tag>
            </a-descriptions-item>
            <a-descriptions-item label="地址">{{ company?.address || '-' }}</a-descriptions-item>
            <a-descriptions-item label="电话">{{ company?.phone || '-' }}</a-descriptions-item>
            <a-descriptions-item label="开户银行">{{ company?.bank_name || '-' }}</a-descriptions-item>
            <a-descriptions-item label="银行账号">{{ company?.bank_account || '-' }}</a-descriptions-item>
          </a-descriptions>

          <a-descriptions title="本月统计" :column="4" bordered size="small" style="margin-top:16px">
            <a-descriptions-item label="本月开票数">{{ stats?.month_count ?? 0 }}</a-descriptions-item>
            <a-descriptions-item label="本月金额">{{ stats?.month_amount ?? 0 }}</a-descriptions-item>
            <a-descriptions-item label="累计开票数">{{ stats?.total_count ?? 0 }}</a-descriptions-item>
            <a-descriptions-item label="累计金额">{{ stats?.total_amount ?? 0 }}</a-descriptions-item>
          </a-descriptions>
        </a-spin>
      </a-tab-pane>

      <a-tab-pane key="members" tab="绑定会员">
        <div style="margin-bottom:12px">
          <a-button type="primary" @click="bindVisible = true">添加会员</a-button>
        </div>
        <a-table :columns="memberColumns" :dataSource="members" :rowKey="r => r.member_id" size="small">
          <template #bodyCell="{ column, record }">
            <template v-if="column.dataIndex === 'action'">
              <a-popconfirm title="确认解绑?" @confirm="handleUnbind(record)">
                <a class="danger">解绑</a>
              </a-popconfirm>
            </template>
          </template>
        </a-table>

        <a-modal title="绑定会员" :visible="bindVisible" :confirm-loading="binding" @ok="handleBind" @cancel="bindVisible = false">
          <a-form-item label="会员ID"><a-input v-model:value="bindMemberId" placeholder="请输入会员ID" /></a-form-item>
        </a-modal>
      </a-tab-pane>

      <a-tab-pane key="invoices" tab="发票记录">
        <a-table :columns="invColumns" :data-source="invList" :loading="invLoading" :pagination="invPagination" :row-key="r => r.id" size="small" @change="handleInvChange">
          <template #bodyCell="{ column, record }">
            <template v-if="column.dataIndex === 'status'">
              <a-tag :color="invStatusColor[record.status]">{{ invStatusText[record.status] }}</a-tag>
            </template>
          </template>
        </a-table>
      </a-tab-pane>
    </a-tabs>
  </a-card>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { message } from 'ant-design-vue'
import { getCompanyDetail, bindMember, unbindMember } from '../api'
import { getInvoiceList } from '../api'

const router = useRouter()
const route = useRoute()
const activeTab = ref('info')
const loading = ref(false)
const company = ref(null)
const stats = ref({})
const members = ref([])
const bindVisible = ref(false)
const bindMemberId = ref('')
const binding = ref(false)
const invList = ref([])
const invLoading = ref(false)
const invPagination = reactive({ current: 1, pageSize: 10, total: 0 })

const invStatusText = { 0: '待审核', 1: '待开票', 2: '已开票', 3: '已作废', 4: '已驳回' }
const invStatusColor = { 0: 'orange', 1: 'blue', 2: 'green', 3: 'default', 4: 'red' }

const memberColumns = [
  { title: '会员ID', dataIndex: 'member_id', width: 80 },
  { title: '昵称', dataIndex: 'nickname' },
  { title: '手机', dataIndex: 'mobile', width: 130 },
  { title: '绑定时间', dataIndex: 'bind_time', width: 110 },
  { title: '操作', dataIndex: 'action', width: 80 },
]
const invColumns = [
  { title: '发票号码', dataIndex: 'invoice_number', width: 130 },
  { title: '购方', dataIndex: 'buyer_name' },
  { title: '金额', dataIndex: 'total_amount', width: 100 },
  { title: '状态', dataIndex: 'status', width: 80 },
  { title: '日期', dataIndex: 'create_time', width: 110 },
]

function load() {
  loading.value = true
  getCompanyDetail(route.query.id).then(r => {
    const d = r?.data
    company.value = d?.company ?? d
    stats.value = d?.stats ?? {}
    members.value = d?.members ?? []
  }).finally(() => { loading.value = false })
}
function loadInvoices() {
  invLoading.value = true
  getInvoiceList({ page: invPagination.current, pageSize: invPagination.pageSize, company_id: route.query.id }).then(r => {
    const res = r?.data?.list || r?.data || {}
    invList.value = res.data || res.list || []
    invPagination.total = res.total || 0
  }).finally(() => { invLoading.value = false })
}
function handleInvChange(p) { invPagination.current = p.current; loadInvoices() }

function handleBind() {
  if (!bindMemberId.value) { message.warning('请输入会员ID'); return }
  binding.value = true
  bindMember({ company_id: route.query.id, member_id: bindMemberId.value })
    .then(() => { message.success('绑定成功'); bindVisible.value = false; bindMemberId.value = ''; load() })
    .finally(() => { binding.value = false })
}
function handleUnbind(record) {
  unbindMember({ company_id: route.query.id, member_id: record.member_id })
    .then(() => { message.success('解绑成功'); load() })
}

onMounted(load)
</script>
