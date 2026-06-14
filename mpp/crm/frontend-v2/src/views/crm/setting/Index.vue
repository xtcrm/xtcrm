<template>
  <a-card :bordered="false" title="CRM 设置">
    <a-tabs default-active-key="dict" @change="onTabChange">
      <!-- 公海规则 -->
      <a-tab-pane key="pool" tab="公海规则">
        <a-spin :spinning="settingLoading">
          <a-form ref="poolFormRef" :model="settingForm" style="max-width:500px">
            <a-form-item label="掉入公海天数" extra="客户超过此天数未跟进，自动掉入公海" name="pool_days">
              <a-input-number v-model:value="settingForm.pool_days" :min="30" :max="730" style="width:200px" /> 天
            </a-form-item>
          </a-form>
          <a-button type="primary" :loading="saving" @click="handleSave('pool')">保存</a-button>
        </a-spin>
      </a-tab-pane>

      <!-- 审批规则 -->
      <a-tab-pane key="approval" tab="审批规则">
        <a-spin :spinning="settingLoading">
          <a-form ref="approvalFormRef" :model="settingForm" style="max-width:500px">
            <a-form-item label="触发审批金额" extra="报价折后金额 ≥ 此值，提交后自动触发审批" name="approval_trigger_amount">
              <a-input-number v-model:value="settingForm.approval_trigger_amount" :min="0" :precision="2" style="width:200px" /> 元
            </a-form-item>
            <a-form-item label="审批层级" name="approval_levels">
              <a-radio-group v-model:value="settingForm.approval_levels">
                <a-radio :value="1">1级（经理）</a-radio>
                <a-radio :value="2">2级（经理→总经理）</a-radio>
              </a-radio-group>
            </a-form-item>
          </a-form>
          <a-button type="primary" :loading="saving" @click="handleSave('approval')">保存</a-button>
        </a-spin>
      </a-tab-pane>

      <!-- AI配置 -->
      <a-tab-pane key="ai" tab="AI配置">
        <a-spin :spinning="settingLoading">
          <a-form ref="aiFormRef" :model="settingForm" style="max-width:500px">
            <a-form-item label="API地址" name="ai_api_url" :rules="[{ required: true, message: '请输入API地址' }]">
              <a-input v-model:value="settingForm.ai_api_url" />
            </a-form-item>
            <a-form-item label="API Key" name="ai_api_key" :rules="[{ required: true, message: '请输入API Key' }]">
              <a-input-password v-model:value="settingForm.ai_api_key" />
            </a-form-item>
            <a-form-item label="模型" name="ai_model">
              <a-input v-model:value="settingForm.ai_model" />
            </a-form-item>
            <a-form-item label="温度" name="ai_temperature">
              <a-input-number v-model:value="settingForm.ai_temperature" :min="0" :max="2" :step="0.1" style="width:200px" />
            </a-form-item>
            <a-form-item label="最大Token数" name="ai_max_tokens">
              <a-input-number v-model:value="settingForm.ai_max_tokens" :min="100" :max="8192" style="width:200px" />
            </a-form-item>
          </a-form>
          <a-button type="primary" :loading="saving" @click="handleSave('ai')">保存</a-button>
          <a-button :loading="aiTesting" style="margin-left:8px" @click="handleTestAi">测试连接</a-button>
          <span v-if="aiTestResult" :style="{ color: aiTestResult.startsWith('成功') ? '#52c41a' : '#f5222d', marginLeft: '12px' }">{{ aiTestResult }}</span>
        </a-spin>
      </a-tab-pane>

      <!-- AI洞察配置 -->
      <a-tab-pane key="insight" tab="AI洞察">
        <a-spin :spinning="settingLoading">
          <a-form ref="insightFormRef" :model="settingForm" style="max-width:520px">
            <a-form-item label="报价逾期天数" extra="报价已发送超过此天数未回复，生成提醒" name="insight_quotation_overdue_days">
              <a-input-number v-model:value="settingForm.insight_quotation_overdue_days" :min="1" :max="60" style="width:160px" /> 天
            </a-form-item>
            <a-form-item label="复购窗口阈值" extra="距上次采购天数 ≥ 历史平均间隔 × 此值，触发复购提醒" name="insight_repurchase_threshold">
              <a-input-number v-model:value="settingForm.insight_repurchase_threshold" :min="0.3" :max="1.5" :step="0.1" style="width:160px" />
            </a-form-item>
            <a-form-item label="公海预警提前天数" extra="距掉入公海还剩此天数时预警" name="insight_pool_warning_days">
              <a-input-number v-model:value="settingForm.insight_pool_warning_days" :min="1" :max="60" style="width:160px" /> 天
            </a-form-item>
            <a-form-item label="流失判断倍数" extra="距上次采购 > 历史间隔 × 此值，且无跟进，判定流失风险" name="insight_churn_multiplier">
              <a-input-number v-model:value="settingForm.insight_churn_multiplier" :min="1" :max="5" :step="0.5" style="width:160px" />
            </a-form-item>
            <a-form-item label="流失判断无跟进天数" extra="流失评估时要求至少此天数无跟进" name="insight_churn_no_followup_days">
              <a-input-number v-model:value="settingForm.insight_churn_no_followup_days" :min="7" :max="180" style="width:160px" /> 天
            </a-form-item>
            <a-form-item label="沉睡客户天数" extra="曾成交客户超过此天数未跟进标记为沉睡" name="insight_dormant_days">
              <a-input-number v-model:value="settingForm.insight_dormant_days" :min="30" :max="730" style="width:160px" /> 天
            </a-form-item>
            <a-form-item label="每人每日最大洞察数" extra="限制每个业务员每天最多显示的洞察卡片数" name="insight_max_per_user">
              <a-input-number v-model:value="settingForm.insight_max_per_user" :min="1" :max="50" style="width:160px" />
            </a-form-item>
          </a-form>
          <a-button type="primary" :loading="saving" @click="handleSave('insight')">保存</a-button>
        </a-spin>
      </a-tab-pane>

      <!-- 字典配置 -->
      <a-tab-pane key="dict" tab="字典配置">
        <a-tabs v-model:active-key="dictType" tab-position="left" @change="loadDictList">
          <a-tab-pane v-for="dt in dictTypes" :key="dt.key" :tab="dt.label">
            <div style="margin-bottom:12px">
              <a-button type="primary" size="small" @click="handleDictAdd"><plus-outlined /> 新增</a-button>
              <a-button size="small" style="margin-left:8px" @click="handleDictInit">初始化默认数据</a-button>
            </div>
            <a-table :columns="dictColumns" :data-source="dictList" :pagination="dictPagination" size="small" bordered row-key="id" :loading="dictLoading" @change="handleDictPageChange">
              <template #bodyCell="{ column, record }">
                <template v-if="column.key === 'action'">
                  <a @click="handleDictEdit(record)">编辑</a>
                  <a-divider type="vertical" />
                  <a-popconfirm title="确认删除?" @confirm="handleDictDelete(record)">
                    <a style="color:#ff4d4f">删除</a>
                  </a-popconfirm>
                </template>
              </template>
            </a-table>
          </a-tab-pane>
        </a-tabs>
      </a-tab-pane>
    </a-tabs>

    <!-- 字典编辑弹窗 -->
    <a-modal :title="dictEditMode === 'add' ? '新增字典' : '编辑字典'" :open="dictModalVisible" :confirm-loading="dictSaving" @ok="handleDictSubmit" @cancel="dictModalVisible = false" :width="320">
      <a-form ref="dictFormRef" :model="dictForm" :label-col="{ span: 8 }" :wrapper-col="{ span: 14 }">
        <a-form-item label="名称" name="config_name" :rules="[{ required: true, message: '请输入名称' }]">
          <a-input v-model:value="dictForm.config_name" />
        </a-form-item>
        <a-form-item label="编号" name="config_value" :rules="[{ required: true, message: '请输入编号' }]">
          <a-input-number v-model:value="dictForm.config_value" :min="1" :precision="0" style="width:100%" placeholder="数字编号" />
        </a-form-item>
        <a-form-item label="排序" name="sort_order">
          <a-input-number v-model:value="dictForm.sort_order" :min="0" style="width:100%" />
        </a-form-item>
      </a-form>
    </a-modal>
  </a-card>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { message } from 'ant-design-vue'
import { PlusOutlined } from '@ant-design/icons-vue'
import { getSettings, saveSettings, testAi } from '@/api/crm/setting'
import { getList, add, edit, deleteById, initType } from '@/api/crm/config'

// ---- Settings ----
const settingLoading = ref(false)
const saving = ref(false)
const settingForm = reactive({
  pool_days: 180,
  approval_trigger_amount: 50000, approval_levels: 2,
  ai_api_url: 'https://api.deepseek.com/v1/chat/completions', ai_api_key: '',
  ai_model: 'deepseek-chat', ai_temperature: 0.7, ai_max_tokens: 1024,
  insight_quotation_overdue_days: 7, insight_repurchase_threshold: 0.8,
  insight_pool_warning_days: 7, insight_churn_multiplier: 1.5,
  insight_churn_no_followup_days: 30, insight_dormant_days: 90, insight_max_per_user: 10,
})

const aiTesting = ref(false)
const aiTestResult = ref('')

const defaults = { ...settingForm }

function loadSettings() {
  settingLoading.value = true
  getSettings().then(res => {
    const s = res?.data || {}
    Object.keys(defaults).forEach(k => { if (s[k] !== undefined) settingForm[k] = s[k] })
    settingLoading.value = false
  }).catch(() => { settingLoading.value = false })
}

function onTabChange(key) {
  if (['pool', 'approval', 'ai', 'insight'].includes(key)) {
    // re-apply settings if needed
  }
}

function handleSave() {
  saving.value = true
  saveSettings({ ...settingForm }).then(() => { message.success('保存成功'); saving.value = false }).catch(() => { saving.value = false })
}

function handleTestAi() {
  aiTesting.value = true; aiTestResult.value = ''
  testAi().then(res => {
    aiTestResult.value = '成功: ' + (res?.data?.reply || 'AI已连接')
    aiTesting.value = false
  }).catch(() => {
    aiTestResult.value = '连接失败，请检查API Key和网络'
    aiTesting.value = false
  })
}

// ---- Dictionary ----
const dictTypes = [
  { key: 'industry', label: '行业' },
  { key: 'customer_level', label: '客户等级' },
  { key: 'customer_source', label: '客户来源' },
  { key: 'customer_group', label: '客户分组' },
  { key: 'follow_type', label: '跟进方式' },
  { key: 'follow_result', label: '跟进结果' },
  { key: 'product_category', label: '产品分类' },
  { key: 'currency', label: '币种' },
]

const dictType = ref('customer_level')
const dictList = ref([])
const dictLoading = ref(false)
const dictPagination = reactive({ current: 1, pageSize: 20, total: 0, showSizeChanger: false })
const dictColumns = [
  { title: '名称', dataIndex: 'config_name', key: 'config_name', width: 150 },
  { title: '值', dataIndex: 'config_value', key: 'config_value', width: 100 },
  { title: '排序', dataIndex: 'sort_order', key: 'sort_order', width: 60 },
  { title: '操作', key: 'action', width: 120 },
]

const dictFormRef = ref()
const dictModalVisible = ref(false)
const dictEditMode = ref('add')
const dictEditId = ref(null)
const dictSaving = ref(false)
const dictForm = reactive({ config_name: '', config_value: undefined, sort_order: 100 })

function loadDictList() {
  dictLoading.value = true
  getList(dictType.value).then(res => {
    const list = res?.data?.list || {}
    dictList.value = list.data || []
    dictPagination.total = list.total || 0
    dictLoading.value = false
  }).catch(() => { dictLoading.value = false })
}

function handleDictPageChange(p) { dictPagination.current = p.current; loadDictList() }

function handleDictAdd() {
  dictEditMode.value = 'add'; dictEditId.value = null
  dictFormRef.value?.resetFields()
  dictForm.config_name = ''; dictForm.config_value = undefined; dictForm.sort_order = 100
  dictModalVisible.value = true
}

function handleDictEdit(record) {
  dictEditMode.value = 'edit'; dictEditId.value = record.id
  dictForm.config_name = record.config_name; dictForm.config_value = record.config_value; dictForm.sort_order = record.sort_order
  dictModalVisible.value = true
}

function handleDictDelete(record) { deleteById(record.id).then(() => { message.success('已删除'); loadDictList() }) }

function handleDictInit() { initType(dictType.value).then(() => { message.success('初始化成功'); loadDictList() }) }

function handleDictSubmit() {
  dictFormRef.value.validate().then((vals) => {
    dictSaving.value = true
    vals.config_type = dictType.value
    const fn = dictEditMode.value === 'edit' ? edit : add
    if (dictEditId.value) vals.id = dictEditId.value
    fn(vals).then(() => { message.success('保存成功'); dictSaving.value = false; dictModalVisible.value = false; loadDictList() }).catch(() => { dictSaving.value = false })
  }).catch(() => {})
}

onMounted(() => { loadSettings(); loadDictList() })
</script>
