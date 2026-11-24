<template>
  <a-card :bordered="false" class="service-page">
    <a-alert
      type="info"
      show-icon
      class="tip"
      message="客服单用于咨询、投诉、建议、回访；建单、指派、状态变更都会进入客户日志。"
    />

    <!-- 筛选 -->
    <div class="action-bar">
      <a-input-search
        v-model:value="queryParam.keyword"
        placeholder="搜索单号 / 标题 / 客户"
        style="width: 240px"
        allow-clear
        @search="loadData(true)"
      />
      <a-select v-model:value="queryParam.status" placeholder="状态" allow-clear style="width: 110px" @change="loadData(true)">
        <a-select-option v-for="(label, val) in STATUS" :key="val" :value="Number(val)">{{ label }}</a-select-option>
      </a-select>
      <a-select v-model:value="queryParam.category" placeholder="类型" allow-clear style="width: 110px" @change="loadData(true)">
        <a-select-option v-for="(label, val) in CATEGORY" :key="val" :value="val">{{ label }}</a-select-option>
      </a-select>
      <a-select
        v-model:value="queryParam.assignee_user_id"
        placeholder="处理人"
        allow-clear
        show-search
        :filter-option="filterUserOption"
        style="width: 140px"
        @change="loadData(true)"
      >
        <a-select-option v-for="u in storeUsers" :key="u.store_user_id" :value="u.store_user_id">
          {{ u.real_name || u.user_name }}
        </a-select-option>
      </a-select>
      <a-button @click="resetSearch">重置</a-button>
      <div style="flex:1"></div>
      <a-button type="primary" @click="openAdd">
        <template #icon><plus-outlined /></template>
        新建客服单
      </a-button>
    </div>

    <a-table
      class="service-table"
      :columns="columns"
      :data-source="dataSource"
      :loading="loading"
      :pagination="pagination"
      :row-key="r => r.id"
      size="middle"
      :scroll="{ x: 1100 }"
      @change="onTableChange"
    >
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'ticket_no'">
          <a class="ticket-link" @click="openDetail(record)">{{ record.ticket_no }}</a>
        </template>
        <template v-else-if="column.key === 'customer'">
          <a @click="router.push({ path: '/crm/customer/detail', query: { id: record.customer_id, from: 'service' } })">
            {{ record.customer_name || ('客户#' + record.customer_id) }}
          </a>
        </template>
        <template v-else-if="column.key === 'priority'">
          <a-tag :color="PRIORITY_COLOR[record.priority] || 'default'">{{ record.priority_text }}</a-tag>
        </template>
        <template v-else-if="column.key === 'status'">
          <a-tag :color="STATUS_COLOR[record.status] || 'default'">{{ record.status_text }}</a-tag>
          <a-tag v-if="record.flow_instance_id" color="purple" style="margin-left:4px">流程中</a-tag>
        </template>
        <template v-else-if="column.key === 'expect_at'">
          <span v-if="!record.expect_at" class="muted">-</span>
          <template v-else>
            <span :class="{ 'overdue-text': record.overdue }">{{ record.expect_at_text }}</span>
            <a-tag v-if="record.overdue" color="red" style="margin-left:4px">已超时</a-tag>
            <a-tag v-else-if="record.due_soon" color="orange" style="margin-left:4px">即将到期</a-tag>
          </template>
        </template>
        <template v-else-if="column.key === 'create_time'">
          {{ formatTime(record.create_time) }}
        </template>
        <template v-else-if="column.key === 'action'">
          <a-space :size="0">
            <a-button type="link" size="small" @click="openDetail(record)">详情</a-button>
            <a-button v-if="record.can_operate" type="link" size="small" @click="openEdit(record)">编辑</a-button>
            <a-popconfirm v-if="record.can_delete" title="确认删除该客服单？" @confirm="handleDelete(record)">
              <a-button type="link" size="small" danger>删除</a-button>
            </a-popconfirm>
          </a-space>
        </template>
      </template>
    </a-table>

    <!-- 新建 / 编辑 -->
    <a-modal
      v-model:open="formOpen"
      :title="form.id ? '编辑客服单' : '新建客服单'"
      :confirm-loading="saving"
      ok-text="保存"
      :mask-closable="false"
      width="640px"
      @ok="submitForm"
    >
      <a-form layout="vertical">
        <a-form-item label="客户" required>
          <a-select
            v-model:value="form.customer_id"
            placeholder="搜索客户名称"
            show-search
            :filter-option="false"
            :disabled="!!form.id"
            :loading="customerSearching"
            style="width: 100%"
            @search="onCustomerSearch"
          >
            <a-select-option v-for="c in customerOptions" :key="c.id" :value="c.id">
              {{ c.customer_name }}<span class="muted"> {{ c.customer_code }}</span>
            </a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="标题" required>
          <a-input v-model:value="form.title" :maxlength="200" placeholder="例如：客户反馈发票抬头有误" />
        </a-form-item>
        <a-row :gutter="12">
          <a-col :span="8">
            <a-form-item label="类型">
              <a-select v-model:value="form.category" style="width:100%">
                <a-select-option v-for="(label, val) in CATEGORY" :key="val" :value="val">{{ label }}</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="8">
            <a-form-item label="渠道">
              <a-select v-model:value="form.channel" style="width:100%">
                <a-select-option v-for="(label, val) in CHANNEL" :key="val" :value="val">{{ label }}</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="8">
            <a-form-item label="优先级">
              <a-select v-model:value="form.priority" style="width:100%">
                <a-select-option :value="1">低</a-select-option>
                <a-select-option :value="2">中</a-select-option>
                <a-select-option :value="3">高</a-select-option>
                <a-select-option :value="4">紧急</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
        </a-row>
        <a-row :gutter="12">
          <a-col :span="12">
            <a-form-item label="处理人">
              <a-select
                v-model:value="form.assignee_user_id"
                allow-clear
                show-search
                :filter-option="filterUserOption"
                style="width:100%"
                placeholder="不选则进入待指派"
              >
                <a-select-option v-for="u in storeUsers" :key="u.store_user_id" :value="u.store_user_id">
                  {{ u.real_name || u.user_name }}
                </a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="期望解决时间">
              <a-date-picker v-model:value="form.expect_at" value-format="X" show-time style="width:100%" />
            </a-form-item>
          </a-col>
        </a-row>
        <a-form-item label="问题描述">
          <a-textarea v-model:value="form.content" :rows="4" :maxlength="2000" show-count />
        </a-form-item>
      </a-form>
    </a-modal>

    <!-- 详情 / 处理 -->
    <a-modal
      v-model:open="detailOpen"
      title="客服单详情"
      :footer="null"
      width="860px"
      :body-style="{ maxHeight: '74vh', overflowY: 'auto' }"
      :mask-closable="false"
    >
      <template v-if="detail.id">
        <div class="ticket-head">
          <span class="ticket-no">{{ detail.ticket_no }}</span>
          <a-tag :color="STATUS_COLOR[detail.status] || 'default'">{{ detail.status_text }}</a-tag>
          <span v-if="detail.assignee_name" class="muted">处理人：{{ detail.assignee_name }}</span>
          <a-tag v-if="detail.overdue" color="red">已超时</a-tag>
          <a-tag v-else-if="detail.due_soon" color="orange">即将到期</a-tag>
        </div>

        <a-tabs v-model:active-key="detailTab" size="small">
          <!-- 服务单：信息 + 处理 + 记录，一屏完成 -->
          <a-tab-pane key="ticket" tab="服务单">
            <div class="customer-brief">
              <a @click="goCustomer">{{ detail.customer?.name || detail.customer_name || ('客户#' + detail.customer_id) }}</a>
              <span v-if="detail.customer?.code" class="muted"> {{ detail.customer.code }}</span>
              <span v-if="detail.customer?.contact_name" class="muted"> · 联系人：{{ detail.customer.contact_name }}</span>
              <span v-if="detail.customer?.contact_mobile" class="muted"> {{ detail.customer.contact_mobile }}</span>
            </div>

            <a-descriptions :column="2" size="small" bordered>
              <a-descriptions-item label="类型">{{ detail.category_text }}</a-descriptions-item>
              <a-descriptions-item label="渠道">{{ detail.channel_text }}</a-descriptions-item>
              <a-descriptions-item label="优先级">{{ detail.priority_text }}</a-descriptions-item>
              <a-descriptions-item label="创建时间">{{ formatTime(detail.create_time) }}</a-descriptions-item>
              <a-descriptions-item label="期望完成">{{ detail.expect_at ? formatTime(detail.expect_at) : '-' }}</a-descriptions-item>
              <a-descriptions-item label="满意度">{{ detail.csat ? detail.csat + ' 分' : '-' }}</a-descriptions-item>
              <a-descriptions-item label="标题" :span="2">{{ detail.title }}</a-descriptions-item>
              <a-descriptions-item label="问题描述" :span="2">
                <div class="pre-wrap">{{ detail.content || '-' }}</div>
              </a-descriptions-item>
              <a-descriptions-item label="解决方案" :span="2">
                <div v-if="detail.can_operate" class="inline-edit" title="双击修改" @dblclick="startEdit('resolution')">
                  <a-textarea
                    v-if="editingField === 'resolution'"
                    v-model:value="editingValue"
                    :rows="3"
                    :maxlength="2000"
                    autofocus
                    @blur="saveEdit('resolution')"
                    @keydown.esc="cancelEdit"
                  />
                  <div v-else class="pre-wrap" :class="{ muted: !detail.resolution }">
                    {{ detail.resolution || '双击填写解决方案' }}
                  </div>
                </div>
                <div v-else class="pre-wrap">{{ detail.resolution || '-' }}</div>
              </a-descriptions-item>
              <a-descriptions-item label="根因" :span="2">
                <div v-if="detail.can_operate" class="inline-edit" title="双击修改" @dblclick="startEdit('root_cause')">
                  <a-input
                    v-if="editingField === 'root_cause'"
                    v-model:value="editingValue"
                    :maxlength="50"
                    autofocus
                    @blur="saveEdit('root_cause')"
                    @keydown.esc="cancelEdit"
                  />
                  <span v-else :class="{ muted: !detail.root_cause }">{{ detail.root_cause || '双击填写根因' }}</span>
                </div>
                <span v-else>{{ detail.root_cause || '-' }}</span>
              </a-descriptions-item>
            </a-descriptions>

            <!-- 流程（可选） -->
            <div v-if="detail.flow && detail.flow.active" class="flow-card">
              <div class="flow-head">
                <a-tag color="purple">流程进行中</a-tag>
                <span class="muted">发起于 {{ detail.flow.started_at_text }}</span>
                <a-button v-if="detail.flow.can_cancel" type="link" size="small" danger @click="openCancelFlow">撤回流程</a-button>
              </div>
              <div class="flow-node">
                当前节点：
                <template v-if="detail.flow.pending_tasks && detail.flow.pending_tasks.length">
                  <a-tag v-for="t in detail.flow.pending_tasks" :key="t.id" color="blue">
                    {{ t.node_name }}<template v-if="t.assignee_name"> · {{ t.assignee_name }}</template>
                  </a-tag>
                </template>
                <span v-else class="muted">等待流程推进</span>
              </div>
              <div class="muted" style="margin-top:6px">流程进行中，状态由流程驱动；需要手动改状态请先撤回流程。</div>
            </div>

            <!-- 处理卡片：即改即存 -->
            <div v-if="detail.can_operate && !(detail.flow && detail.flow.active)" class="process-card">
              <a-row :gutter="12" align="middle">
                <a-col :span="6">
                  <div class="process-label">状态</div>
                  <a-select v-model:value="process.status" :disabled="saving" style="width:100%" @change="onStatusChange">
                    <a-select-option v-for="(label, val) in STATUS" :key="val" :value="Number(val)">{{ label }}</a-select-option>
                  </a-select>
                </a-col>
                <a-col :span="6">
                  <div class="process-label">指派给</div>
                  <a-select
                    v-model:value="process.assignee_user_id"
                    allow-clear
                    show-search
                    :filter-option="filterUserOption"
                    :disabled="saving"
                    style="width:100%"
                    placeholder="选择处理人"
                    @change="onAssigneeChange"
                  >
                    <a-select-option v-for="u in storeUsers" :key="u.store_user_id" :value="u.store_user_id">
                      {{ u.real_name || u.user_name }}
                    </a-select-option>
                  </a-select>
                </a-col>
                <a-col :span="6">
                  <div class="process-label">满意度 <span class="muted">（点星星即保存）</span></div>
                  <a-rate v-model:value="process.csat" :count="5" :disabled="saving" @change="submitRating" />
                </a-col>
                <a-col :span="6" class="process-buttons">
                  <a-button v-if="detail.status !== 4 && detail.status !== 5" size="small" @click="openFlow">发起流程</a-button>
                  <a-button size="small" @click="openTransfer">转办</a-button>
                </a-col>
              </a-row>
              <div class="process-hint muted">状态下拉选择后立即生效；解决方案、根因双击修改，失焦保存。</div>
            </div>

            <!-- 处理记录 -->
            <div class="block-title" style="margin-top:16px">处理记录</div>
            <div v-if="detail.can_operate" class="comment-box">
              <a-textarea
                v-model:value="commentText"
                :rows="2"
                :maxlength="2000"
                show-count
                placeholder="记录一次沟通/处理过程，例如：已电话回复客户，确认发票重开时间"
              />
              <div class="comment-actions">
                <a-button type="primary" size="small" :loading="saving" :disabled="!commentText.trim()" @click="submitComment">
                  保存跟进
                </a-button>
              </div>
            </div>
            <a-timeline v-if="(detail.logs || []).length" class="ticket-logs">
              <a-timeline-item v-for="log in detail.logs" :key="log.id" :color="logColor(log.event_type)">
                <p style="margin:0 0 2px">
                  <strong>{{ log.title }}</strong>
                  <span class="muted" style="margin-left:8px">{{ log.time_text }}</span>
                </p>
                <p class="muted" style="margin:0">操作人：{{ log.actor }}</p>
                <div v-if="log.content" class="log-content">{{ log.content }}</div>
              </a-timeline-item>
            </a-timeline>
            <a-empty v-else description="暂无处理记录" style="padding:16px" />
          </a-tab-pane>

          <!-- 客户资料 -->
          <a-tab-pane key="customer" tab="客户资料">
            <a-descriptions :column="2" size="small" bordered>
              <a-descriptions-item label="公司名称" :span="2">
                <a @click="goCustomer">{{ detail.customer?.name || '-' }}</a>
                <span v-if="detail.customer?.code" class="muted"> {{ detail.customer.code }}</span>
              </a-descriptions-item>
              <a-descriptions-item label="简称">{{ detail.customer?.short_name || '-' }}</a-descriptions-item>
              <a-descriptions-item label="行业">{{ detail.customer?.industry || '-' }}</a-descriptions-item>
              <a-descriptions-item label="等级">{{ detail.customer?.level_name || '-' }}</a-descriptions-item>
              <a-descriptions-item label="分组">{{ detail.customer?.group || '-' }}</a-descriptions-item>
              <a-descriptions-item label="来源">{{ detail.customer?.source || '-' }}</a-descriptions-item>
              <a-descriptions-item label="客户负责人">{{ detail.customer?.owner_name || '-' }}</a-descriptions-item>
              <a-descriptions-item label="电话">{{ detail.customer?.telephone || '-' }}</a-descriptions-item>
              <a-descriptions-item label="地区">{{ detail.customer?.region || '-' }}</a-descriptions-item>
              <a-descriptions-item label="地址" :span="2">{{ detail.customer?.address || '-' }}</a-descriptions-item>
              <a-descriptions-item label="联系人" :span="2">
                {{ detail.customer?.contact_name || '-' }}
                <span v-if="detail.customer?.contact_mobile" class="muted"> {{ detail.customer.contact_mobile }}</span>
              </a-descriptions-item>
            </a-descriptions>
            <div style="margin-top:12px">
              <a-button size="small" @click="goCustomer">打开客户详情</a-button>
            </div>
          </a-tab-pane>
        </a-tabs>

        <a-alert
          v-if="!detail.can_operate"
          type="info"
          show-icon
          message="你可以查看这张客服单和客户资料，但只有处理人、创建人或管理员可以处理。"
          style="margin-top:8px"
        />
      </template>
    </a-modal>

    <!-- 发起流程 -->
    <a-modal
      v-model:open="flowOpen"
      title="发起流程"
      ok-text="发起"
      :confirm-loading="saving"
      :mask-closable="false"
      @ok="submitFlow"
    >
      <a-form layout="vertical">
        <a-form-item label="流程模板" required>
          <a-select v-model:value="flowForm.template_code" style="width:100%" placeholder="选择流程模板">
            <a-select-option v-for="t in flowList" :key="t.code" :value="t.code">
              {{ t.name }}<span v-if="t.description" class="muted"> {{ t.description }}</span>
            </a-select-option>
          </a-select>
        </a-form-item>
        <a-alert
          type="info"
          show-icon
          message="发起后状态由流程驱动，客服不能手动改状态；可以随时撤回流程。"
        />
      </a-form>
    </a-modal>

    <!-- 撤回流程 -->
    <a-modal
      v-model:open="cancelFlowOpen"
      title="撤回流程"
      ok-text="确认撤回"
      :confirm-loading="saving"
      :mask-closable="false"
      @ok="submitCancelFlow"
    >
      <div class="process-label">撤回原因</div>
      <a-textarea v-model:value="cancelReason" :rows="3" placeholder="例如：客户已直接联系技术解决" />
    </a-modal>

    <!-- 转办 -->
    <a-modal
      v-model:open="transferOpen"
      title="转办客服单"
      ok-text="确认转办"
      :confirm-loading="saving"
      :mask-closable="false"
      @ok="submitTransfer"
    >
      <a-form layout="vertical">
        <a-form-item label="转办给" required>
          <a-select
            v-model:value="transferForm.to_user_id"
            show-search
            :filter-option="filterUserOption"
            style="width:100%"
            placeholder="选择接收的同事"
          >
            <a-select-option
              v-for="u in storeUsers"
              :key="u.store_user_id"
              :value="u.store_user_id"
              :disabled="u.store_user_id === detail.assignee_user_id"
            >
              {{ u.real_name || u.user_name }}
            </a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="转办原因" required>
          <a-textarea
            v-model:value="transferForm.reason"
            :rows="3"
            :maxlength="500"
            show-count
            placeholder="例如：张三休假，客户交接给李四"
          />
        </a-form-item>
        <a-alert
          type="info"
          show-icon
          message="转办后整张单归新处理人，原处理人失去操作权；流程进行中不能转办。"
        />
      </a-form>
    </a-modal>
  </a-card>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { message } from 'ant-design-vue'
import { PlusOutlined } from '@ant-design/icons-vue'
import { getList, getDetail, add, edit, assign, transfer, flowTemplates, startFlow, cancelFlow, changeStatus, rate, comment, deleteById } from '@/api/crm/serviceTicket'
import { getSelect as searchCustomers } from '@/api/crm/customer'
import { list as getStoreUsers } from '@/api/user'

const router = useRouter()
const route = useRoute()

const STATUS = { 1: '新建', 2: '处理中', 3: '待确认', 4: '已解决', 5: '已关闭' }
const CATEGORY = { consult: '咨询', complaint: '投诉', suggestion: '建议', visit: '回访' }
const CHANNEL = { phone: '电话', wechat: '微信', email: '邮件', visit: '上门', other: '其他' }
const STATUS_COLOR = { 1: 'default', 2: 'processing', 3: 'warning', 4: 'success', 5: 'default' }
const PRIORITY_COLOR = { 1: 'default', 2: 'blue', 3: 'orange', 4: 'red' }

const loading = ref(false)
const saving = ref(false)
const dataSource = ref([])
const storeUsers = ref([])
const customerOptions = ref([])
const customerSearching = ref(false)
const queryParam = reactive({})
const formOpen = ref(false)
const detailOpen = ref(false)
const form = reactive({})
const detail = reactive({})
const process = reactive({ status: 2, assignee_user_id: undefined, resolution: '', root_cause: '', csat: 0 })
const commentText = ref('')
const detailTab = ref('ticket')
const editingField = ref(null)
const editingValue = ref('')
const transferOpen = ref(false)
const transferForm = reactive({ to_user_id: undefined, reason: '' })
const flowOpen = ref(false)
const flowLoading = ref(false)
const flowList = ref([])
const flowForm = reactive({ template_code: undefined })
const cancelFlowOpen = ref(false)
const cancelReason = ref('')

let customerTimer = null

const pagination = reactive({
  current: 1, pageSize: 20, total: 0,
  showSizeChanger: true, showTotal: t => `共 ${t} 条`,
})

const columns = [
  { title: '单号', key: 'ticket_no', width: 170 },
  { title: '客户', key: 'customer', width: 180 },
  { title: '标题', dataIndex: 'title', key: 'title', ellipsis: true },
  { title: '类型', dataIndex: 'category_text', key: 'category_text', width: 80 },
  { title: '渠道', dataIndex: 'channel_text', key: 'channel_text', width: 80 },
  { title: '优先级', key: 'priority', width: 80, align: 'center' },
  { title: '处理人', dataIndex: 'assignee_name', key: 'assignee_name', width: 100 },
  { title: '状态', key: 'status', width: 90, align: 'center' },
  { title: '期望完成', key: 'expect_at', width: 160 },
  { title: '创建时间', key: 'create_time', width: 120 },
  { title: '操作', key: 'action', width: 160, fixed: 'right' },
]

function formatTime(ts) {
  if (!ts) return '-'
  const d = new Date(ts * 1000)
  const p = n => (n < 10 ? '0' + n : n)
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}`
}

function filterUserOption(input, option) {
  return (option.label || option.value || '').toLowerCase().includes(input.toLowerCase())
}

function baseParams() {
  const params = { ...queryParam }
  Object.keys(params).forEach(k => {
    if (params[k] === '' || params[k] === null || params[k] === undefined) delete params[k]
  })
  return params
}

async function loadData(reset = false) {
  if (reset) pagination.current = 1
  loading.value = true
  try {
    const res = await getList({ ...baseParams(), page: pagination.current, limit: pagination.pageSize })
    if (res?.status === 200) {
      const d = res.data || {}
      dataSource.value = d.list || []
      pagination.total = d.total || 0
    }
  } finally {
    loading.value = false
  }
}

function onTableChange(pag) {
  pagination.current = pag.current
  pagination.pageSize = pag.pageSize
  loadData()
}

function resetSearch() {
  Object.keys(queryParam).forEach(k => delete queryParam[k])
  loadData(true)
}

function loadUsers() {
  getStoreUsers({}).then(r => {
    const list = r?.data?.list?.data || r?.data?.list || []
    storeUsers.value = list.filter(u => u.is_supplier !== 1 && u.is_delete !== 1)
  }).catch(e => console.error('loadUsers failed:', e))
}

function onCustomerSearch(keyword) {
  if (customerTimer) clearTimeout(customerTimer)
  const kw = String(keyword || '').trim()
  if (!kw) return
  customerTimer = setTimeout(() => {
    customerSearching.value = true
    searchCustomers(kw).then(res => {
      customerOptions.value = res?.data?.list || []
    }).catch(e => console.error('searchCustomers failed:', e))
      .finally(() => { customerSearching.value = false })
  }, 300)
}

function resetForm() {
  Object.keys(form).forEach(k => delete form[k])
  form.category = 'consult'
  form.channel = 'phone'
  form.priority = 2
  form.assignee_user_id = undefined
  form.customer_id = route.query.customer_id ? Number(route.query.customer_id) : undefined
  if (form.customer_id) {
    customerOptions.value = [{ id: form.customer_id, customer_name: route.query.customer_name || ('客户#' + form.customer_id), customer_code: '' }]
  }
}

function openAdd() {
  resetForm()
  formOpen.value = true
}

function openEdit(record) {
  Object.keys(form).forEach(k => delete form[k])
  Object.assign(form, {
    id: record.id,
    customer_id: record.customer_id,
    title: record.title,
    category: record.category,
    channel: record.channel,
    priority: record.priority,
    assignee_user_id: record.assignee_user_id || undefined,
    expect_at: record.expect_at ? String(record.expect_at) : undefined,
    content: record.content,
    root_cause: record.root_cause,
  })
  customerOptions.value = [{ id: record.customer_id, customer_name: record.customer_name || ('客户#' + record.customer_id), customer_code: '' }]
  formOpen.value = true
}

async function submitForm() {
  if (!form.customer_id) return message.warning('请选择客户')
  if (!form.title) return message.warning('请填写标题')
  saving.value = true
  try {
    const payload = { ...form }
    if (payload.expect_at) payload.expect_at = parseInt(payload.expect_at)
    const res = form.id ? await edit(payload) : await add(payload)
    if (res?.status === 200) {
      message.success(res.message || '保存成功')
      formOpen.value = false
      loadData()
    }
  } catch (e) {
    console.error('save ticket failed:', e)
  } finally {
    saving.value = false
  }
}

function openDetail(record) {
  getDetail(record.id).then(res => {
    if (res?.status === 200) {
      const sameTicket = (detail.id || 0) === record.id
      Object.keys(detail).forEach(k => delete detail[k])
      Object.assign(detail, res.data?.detail || {})
      commentText.value = ''
      if (!sameTicket) detailTab.value = 'ticket'
      process.status = detail.status
      process.assignee_user_id = detail.assignee_user_id || undefined
      process.csat = detail.csat || 0
      editingField.value = null
      detailOpen.value = true
    }
  }).catch(e => console.error('getDetail failed:', e))
}

function goCustomer() {
  if (detail.customer_id) {
    router.push({ path: '/crm/customer/detail', query: { id: detail.customer_id, from: 'service' } })
  }
}
function logColor(eventType) {
  return {
    created: 'blue',
    assigned: 'blue',
    status_changed: 'green',
    rated: 'gold',
    comment: 'gray',
    updated: 'gray',
    deleted: 'red',
  }[eventType] || 'blue'
}
async function submitComment() {
  const content = commentText.value.trim()
  if (!content) return message.warning('请填写跟进内容')
  saving.value = true
  try {
    const res = await comment({ id: detail.id, content })
    if (res?.status === 200) {
      message.success('已记录')
      commentText.value = ''
      openDetail({ id: detail.id })
      loadData()
    }
  } catch (e) {
    console.error('comment failed:', e)
  } finally {
    saving.value = false
  }
}

function openFlow() {
  flowForm.template_code = undefined
  flowLoading.value = true
  flowTemplates().then(res => {
    flowList.value = res?.data?.list || []
    if (!flowList.value.length) {
      message.warning('暂无可用流程模板，请先在流程中心配置')
      return
    }
    flowForm.template_code = flowList.value[0].code
    flowOpen.value = true
  }).catch(e => console.error('flowTemplates failed:', e))
    .finally(() => { flowLoading.value = false })
}

async function submitFlow() {
  if (!flowForm.template_code) return message.warning('请选择流程模板')
  saving.value = true
  try {
    const res = await startFlow({ id: detail.id, template_code: flowForm.template_code })
    if (res?.status === 200) {
      message.success('流程已发起')
      flowOpen.value = false
      openDetail({ id: detail.id })
      loadData()
    }
  } catch (e) {
    console.error('startFlow failed:', e)
  } finally {
    saving.value = false
  }
}

function openCancelFlow() {
  cancelReason.value = ''
  cancelFlowOpen.value = true
}

async function submitCancelFlow() {
  saving.value = true
  try {
    const res = await cancelFlow({ id: detail.id, reason: cancelReason.value })
    if (res?.status === 200) {
      message.success('流程已撤回')
      cancelFlowOpen.value = false
      openDetail({ id: detail.id })
      loadData()
    }
  } catch (e) {
    console.error('cancelFlow failed:', e)
  } finally {
    saving.value = false
  }
}

function openTransfer() {
  transferForm.to_user_id = undefined
  transferForm.reason = ''
  transferOpen.value = true
}

async function submitTransfer() {
  if (!transferForm.to_user_id) return message.warning('请选择转办人')
  if (!transferForm.reason.trim()) return message.warning('请填写转办原因')
  saving.value = true
  try {
    const res = await transfer({
      id: detail.id,
      to_user_id: transferForm.to_user_id,
      reason: transferForm.reason,
    })
    if (res?.status === 200) {
      message.success(res.message || '已转办')
      transferOpen.value = false
      openDetail({ id: detail.id })
      loadData()
    }
  } catch (e) {
    console.error('transfer failed:', e)
  } finally {
    saving.value = false
  }
}

function startEdit(field) {
  if (!detail.can_operate) return
  editingField.value = field
  editingValue.value = field === 'resolution' ? (detail.resolution || '') : (detail.root_cause || '')
}

function cancelEdit() {
  editingField.value = null
  editingValue.value = ''
}

async function saveEdit(field) {
  if (editingField.value !== field) return
  const value = String(editingValue.value || '').trim()
  const oldValue = String(detail[field] || '').trim()
  editingField.value = null
  editingValue.value = ''
  if (value === oldValue) return

  saving.value = true
  try {
    const res = await edit({ id: detail.id, [field]: value })
    if (res?.status === 200) {
      message.success('已保存')
      openDetail({ id: detail.id })
      loadData()
    }
  } catch (e) {
    console.error('inline save failed:', e)
    openDetail({ id: detail.id })
  } finally {
    saving.value = false
  }
}

async function onStatusChange(value) {
  if (Number(value) === Number(detail.status)) return
  saving.value = true
  try {
    const res = await changeStatus({ id: detail.id, status: value })
    if (res?.status === 200) {
      message.success(res.message || '状态已更新')
      openDetail({ id: detail.id })
      loadData()
    } else {
      openDetail({ id: detail.id })
    }
  } catch (e) {
    console.error('status change failed:', e)
    openDetail({ id: detail.id })
  } finally {
    saving.value = false
  }
}

async function onAssigneeChange(value) {
  if ((value || 0) === (detail.assignee_user_id || 0)) return
  saving.value = true
  try {
    const res = await assign({ id: detail.id, assignee_user_id: value || 0 })
    if (res?.status === 200) {
      message.success('已指派')
      openDetail({ id: detail.id })
      loadData()
    } else {
      openDetail({ id: detail.id })
    }
  } catch (e) {
    console.error('assign failed:', e)
    openDetail({ id: detail.id })
  } finally {
    saving.value = false
  }
}

async function submitRating(value) {
  if (!detail.id || !value) return
  saving.value = true
  try {
    const res = await rate({ id: detail.id, csat: value })
    if (res?.status === 200) {
      message.success('评价已保存')
      detail.csat = value
      process.csat = value
    }
  } catch (e) {
    console.error('rate failed:', e)
  } finally {
    saving.value = false
  }
}



async function handleDelete(record) {
  try {
    const res = await deleteById(record.id)
    if (res?.status === 200) {
      message.success('已删除')
      loadData()
    }
  } catch (e) {
    console.error('delete ticket failed:', e)
  }
}

onMounted(() => {
  loadUsers()
  loadData()
  const tid = Number(route.query.id || 0)
  if (tid) openDetail({ id: tid })
  const cid = Number(route.query.customer_id || 0)
  if (cid) {
    queryParam.customer_id = cid
    loadData(true)
    openAdd()
  }
})
</script>

<style scoped>
.tip { margin-bottom: 12px; }

.action-bar {
  display: flex; gap: 10px; align-items: center; flex-wrap: wrap;
  margin-bottom: 16px; padding: 12px 16px;
  background: #fff; border-radius: 6px; border: 1px solid #f0f0f0;
}

.service-table { background: #fff; border-radius: 6px; }
.ticket-link { font-weight: 500; }
.muted { color: #999; font-size: 12px; }
.overdue-text { color: #cf1322; font-weight: 500; }
.flow-card { margin-top: 16px; padding: 12px 14px; background: #f9f0ff; border: 1px solid #d3adf7; border-radius: 6px; }
.flow-head { display: flex; align-items: center; gap: 10px; }
.flow-node { margin-top: 8px; font-size: 13px; }
.pre-wrap { white-space: pre-wrap; word-break: break-word; }
.process-label { font-size: 12px; color: #666; margin-bottom: 4px; }
.block-title { font-weight: 600; font-size: 14px; color: #1f1f1f; margin-bottom: 8px; }
.comment-box { margin-bottom: 12px; }
.comment-actions { display: flex; justify-content: flex-end; margin-top: 8px; }
.ticket-logs { margin-top: 4px; }
.log-content { margin-top: 4px; background: #fafafa; border-radius: 4px; padding: 6px 10px; white-space: pre-wrap; word-break: break-word; }
.ticket-head { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
.process-card { margin-top: 16px; padding: 12px 14px; background: #fafafa; border: 1px solid #f0f0f0; border-radius: 6px; }
.process-buttons { display: flex; justify-content: flex-end; gap: 8px; }
.process-hint { margin-top: 8px; font-size: 12px; }
.inline-edit { min-height: 22px; cursor: text; border-bottom: 1px dashed transparent; }
.inline-edit:hover { border-bottom-color: #d9d9d9; }
.ticket-head .ticket-no { font-weight: 600; font-size: 15px; }
.customer-brief { margin-bottom: 10px; font-size: 14px; }
</style>
