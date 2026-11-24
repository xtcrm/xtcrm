<template>
  <div class="designer">
    <div class="designer-head">
      <div class="head-left">
        <a-button type="text" @click="goBack"><arrow-left-outlined /></a-button>
        <div>
          <div class="t-name">{{ template.name || '流程设计器' }} <a-tag v-if="template.version">v{{ template.version }}</a-tag></div>
          <div class="t-meta">{{ template.biz_label }} · {{ template.code }} · {{ template.status_text }}</div>
        </div>
      </div>
      <div class="head-actions">
        <a-button v-if="readonly" type="primary" :loading="copying" @click="handleCopyVersion">复制新版本并编辑</a-button>
        <a-button :disabled="readonly" :loading="saving" @click="handleSave">保存草稿</a-button>
        <a-button :disabled="readonly" @click="handleValidate">校验</a-button>
        <a-button type="primary" :disabled="readonly" :loading="publishing" @click="handlePublish">发布</a-button>
        <a-button @click="fitView({ padding: 0.2 })">适应画布</a-button>
      </div>
    </div>

    <div v-if="readonly" class="readonly-tip">
      已发布模板为只读版本。要修改流程，请点右上角「复制新版本并编辑」；新版本发布后，新单走新流程，在途单仍按原版本执行。
    </div>

    <div class="designer-body">
      <div class="palette">
        <div class="palette-title">节点</div>
        <div v-for="item in palette" :key="item.key" class="palette-item" draggable="true"
          @dragstart="onDragStart($event, item)">
          <span class="dot" :style="{ background: item.color }"></span>{{ item.label }}
        </div>
        <div class="palette-tip">拖到画布，或点击添加</div>
        <div class="palette-tip connect-tip">连线：从节点右侧圆点拖到目标节点左侧圆点</div>
        <div class="palette-legend">
          <span><i class="lg lg-error"></i>无人可派</span>
          <span><i class="lg lg-fallback"></i>靠兜底</span>
          <span><i class="lg lg-dynamic"></i>按单解析</span>
          <span><i class="lg lg-ok"></i>可解析</span>
        </div>
      </div>

      <div class="canvas" @drop="onDrop" @dragover.prevent>
        <VueFlow id="flow-designer" v-model:nodes="nodes" v-model:edges="edges" :fit-view-on-init="true" :min-zoom="0.2" :max-zoom="1.6"
          :nodes-draggable="!readonly" :nodes-connectable="!readonly" :edges-updatable="!readonly"
          @connect="onConnect" @nodes-initialized="onNodesInitialized" @node-click="onNodeClick" @edge-click="onEdgeClick" @pane-click="clearSelection"
          @node-context-menu="onNodeContextMenu" @edge-context-menu="onEdgeContextMenu" @pane-context-menu="onPaneContextMenu">
          <Background pattern-color="#d9e2ec" :gap="18" />
          <Controls />
          <MiniMap />
          <template #node-flow="{ data, selected }">
            <div class="flow-node" :class="['t-' + data.type, { selected }, 'a-' + (assignmentStatus(data.node_key)?.status || '')]"
              :title="assignmentTip(data.node_key)">
              <Handle v-if="data.type !== 'start'" type="target" :position="Position.Left" />
              <span v-if="assignmentStatus(data.node_key)" class="fn-badge" :class="'b-' + assignmentStatus(data.node_key).status">
                {{ assignmentBadge(data.node_key) }}
              </span>
              <div class="fn-top">
                <span class="fn-icon">{{ typeIcon(data.type) }}</span>
                <span class="fn-name">{{ data.name }}</span>
              </div>
              <div class="fn-sub">{{ nodeSub(data) }}</div>
              <Handle v-if="data.type !== 'end'" type="source" :position="Position.Right" />
            </div>
          </template>
        </VueFlow>
      </div>

    </div>

    <div v-if="ctx.visible" class="ctx-mask" @click="closeCtx" @contextmenu.prevent="closeCtx"></div>
    <div v-if="ctx.visible" class="ctx-menu" :style="{ left: ctx.x + 'px', top: ctx.y + 'px' }">
      <template v-if="ctx.type === 'node'">
        <div class="ctx-title">{{ ctx.nodeName || '节点' }}</div>
        <div class="ctx-item" @click="ctxEditNode">{{ readonly ? '查看属性' : '编辑属性' }}</div>
        <div v-if="canCopyNode" class="ctx-item" :class="{ disabled: readonly }" @click="ctxDuplicateNode">复制节点</div>
        <div class="ctx-item danger" :class="{ disabled: readonly }" @click="ctxDeleteNode">删除节点</div>
      </template>

      <template v-else-if="ctx.type === 'edge'">
        <div class="ctx-title">连线</div>
        <div class="ctx-item" @click="ctxEditEdge">{{ readonly ? '查看条件' : '编辑条件' }}</div>
        <div class="ctx-item danger" :class="{ disabled: readonly }" @click="ctxDeleteEdge">删除连线</div>
      </template>

      <template v-else>
        <div class="ctx-title">画布</div>
        <div class="ctx-sub">添加节点</div>
        <div class="ctx-item" v-for="item in palette" :key="item.key" :class="{ disabled: readonly }"
          @click="ctxAddNode(item.type)">
          <span class="ctx-dot" :style="{ background: item.color }"></span>{{ item.label }}
        </div>
        <div class="ctx-sep"></div>
        <div class="ctx-item" @click="fitView({ padding: 0.15 }); closeCtx()">适应画布</div>
        <div class="ctx-item" :class="{ disabled: readonly }" @click="handleSave()">保存草稿</div>
      </template>
    </div>

    <a-drawer v-model:open="panelOpen" :title="panelTitle" placement="right" :width="400" :body-style="{ padding: '12px' }">
        <template v-if="selectedNode">
          <div class="panel-head">
            <span>节点设置</span>
            <a @click="removeNode">删除节点</a>
          </div>
          <a-tabs v-model:activeKey="nodeTab" size="small">
            <a-tab-pane key="basic" tab="基本">
              <a-form layout="vertical" size="small">
                <a-form-item label="节点名称">
                  <a-input v-model:value="selectedNode.data.name" :disabled="readonly" />
                </a-form-item>
                <a-form-item label="节点 Key">
                  <a-input :value="selectedNode.id" disabled />
                </a-form-item>
                <a-form-item label="节点类型">
                  <a-select v-model:value="selectedNode.data.type" :disabled="readonly" @change="syncNodeVisual">
                    <a-select-option v-for="(v, k) in meta.node_types" :key="k" :value="k">{{ v }}</a-select-option>
                  </a-select>
                </a-form-item>

                <template v-if="selectedNode.data.type === 'gateway'">
                  <a-form-item label="网关类型">
                    <a-select v-model:value="selectedNode.data.gateway_type" :disabled="readonly">
                      <a-select-option v-for="(v, k) in meta.gateway_types" :key="k" :value="k">{{ v }}</a-select-option>
                    </a-select>
                  </a-form-item>
                  <a-form-item label="合并方式">
                    <a-radio-group v-model:value="selectedNode.data.join_mode" :disabled="readonly">
                      <a-radio value="all">等待全部分支</a-radio>
                      <a-radio value="any">任一分支</a-radio>
                    </a-radio-group>
                  </a-form-item>
                </template>

                <template v-if="isTaskNode(selectedNode.data.type)">
                  <a-form-item label="完成规则">
                    <a-select v-model:value="selectedNode.data.complete_mode" :disabled="readonly">
                      <a-select-option v-for="(v, k) in meta.complete_modes" :key="k" :value="k">{{ v }}</a-select-option>
                    </a-select>
                  </a-form-item>
                  <a-form-item v-if="selectedNode.data.complete_mode === 'ratio'" label="通过比例（%）">
                    <a-input-number v-model:value="selectedNode.data.complete_ratio" :min="1" :max="100" style="width: 100%" :disabled="readonly" />
                  </a-form-item>
                  <a-form-item label="允许动作">
                    <a-checkbox-group v-model:value="selectedNode.data.actions" :disabled="readonly">
                      <a-checkbox value="approve">通过</a-checkbox>
                      <a-checkbox value="complete">完成</a-checkbox>
                      <a-checkbox value="sign">签字</a-checkbox>
                      <a-checkbox value="pay">收款</a-checkbox>
                      <a-checkbox value="reject">驳回</a-checkbox>
                    </a-checkbox-group>
                  </a-form-item>
                  <a-row :gutter="8">
                    <a-col :span="12">
                      <a-form-item label="时限（小时）">
                        <a-input-number v-model:value="selectedNode.data.sla_hours" :min="0" style="width: 100%" :disabled="readonly" />
                      </a-form-item>
                    </a-col>
                    <a-col :span="12">
                      <a-form-item label="提前提醒（小时）">
                        <a-input-number v-model:value="selectedNode.data.remind_before_hours" :min="0" style="width: 100%" :disabled="readonly" />
                      </a-form-item>
                    </a-col>
                  </a-row>
                </template>
              </a-form>
            </a-tab-pane>

            <a-tab-pane key="assignee" tab="指派" v-if="isTaskNode(selectedNode.data.type)">
              <div v-for="(rule, index) in selectedNode.data.assignee_rules" :key="index" class="rule-row">
                <a-select v-model:value="rule.type" size="small" style="width: 96px" :disabled="readonly" @change="onRuleTypeChange(rule)">
                  <a-select-option value="role">角色</a-select-option>
                  <a-select-option value="role_name">角色名</a-select-option>
                  <a-select-option value="user">指定人</a-select-option>
                  <a-select-option value="dept">部门</a-select-option>
                  <a-select-option value="field">业务字段</a-select-option>
                  <a-select-option value="creator">发起人</a-select-option>
                  <a-select-option value="manager">直属上级</a-select-option>
                </a-select>
                <a-select v-if="rule.type === 'role'" v-model:value="rule.id" size="small" style="flex: 1" :disabled="readonly" placeholder="选择角色">
                  <a-select-option v-for="r in meta.roles" :key="r.id" :value="r.id">{{ r.name }}（{{ r.user_count ?? 0 }}人）</a-select-option>
                </a-select>
                <a-auto-complete v-else-if="rule.type === 'role_name'" v-model:value="rule.name" size="small" style="flex: 1" :disabled="readonly"
                  :options="roleNameOptions" placeholder="输入角色名称，如：负责人、财务" />
                <a-select v-else-if="rule.type === 'user'" v-model:value="rule.id" size="small" style="flex: 1" :disabled="readonly"
                  show-search :filter-option="filterUser" placeholder="选择人员">
                  <a-select-option v-for="u in meta.users" :key="u.id" :value="u.id">{{ u.name }}</a-select-option>
                </a-select>
                <a-select v-else-if="rule.type === 'dept'" v-model:value="rule.id" size="small" style="flex: 1" :disabled="readonly" placeholder="选择部门">
                  <a-select-option v-for="d in flatDepartments" :key="d.id" :value="d.id">{{ d.label }}</a-select-option>
                </a-select>
                <a-select v-else-if="rule.type === 'field'" v-model:value="rule.field" size="small" style="flex: 1" :disabled="readonly" placeholder="选择业务字段">
                  <a-select-option v-for="v in meta.allowed_variables" :key="v.value" :value="v.value">{{ v.label }}</a-select-option>
                </a-select>
                <a-select v-else-if="rule.type === 'manager'" v-model:value="rule.of_field" size="small" style="flex: 1" :disabled="readonly" placeholder="选择字段（取该人的部门负责人）">
                  <a-select-option v-for="v in meta.allowed_variables" :key="v.value" :value="v.value">{{ v.label }}</a-select-option>
                </a-select>
                <span v-else class="rule-empty">无需配置</span>
                <a @click="removeRule(index)" :class="{ disabled: readonly }">×</a>
              </div>
              <a-button size="small" block :disabled="readonly" @click="addRule">+ 添加指派规则</a-button>
              <div class="hint">
                多条规则取并集；会签时每个匹配用户各生成一条待办。无人可派时自动回落到发起人。<br />
                <b>业务字段</b>：直接取业务单上对应的人，比如销售、复核人、交付人。<br />
                <b>直属上级</b>：先取选中字段的那个人，再取他所属部门的负责人（部门管理里设置的「负责人」）；找不到部门负责人时继续按兜底规则处理。<br />
                <b>角色名</b>：不绑定具体角色 ID，运行时按名称找角色；角色改名或删除后会自动走兜底，推荐系统预置流程使用。
              </div>
            </a-tab-pane>

            <a-tab-pane key="condition" tab="条件">
              <div class="cond-head">
                <a-radio-group v-model:value="condMode" size="small" :disabled="readonly">
                  <a-radio-button value="all">全部满足</a-radio-button>
                  <a-radio-button value="any">任一满足</a-radio-button>
                </a-radio-group>
                <a v-if="condMode !== 'raw'" @click="addCondRow">+ 条件</a>
              </div>
              <template v-if="condMode !== 'raw'">
                <div v-for="(row, index) in condRows" :key="index" class="cond-row">
                  <a-select v-model:value="row.field" size="small" style="width: 120px" :disabled="readonly" placeholder="变量">
                    <a-select-option v-for="v in meta.allowed_variables" :key="v.value" :value="v.value">{{ v.label }}</a-select-option>
                  </a-select>
                  <a-select v-model:value="row.op" size="small" style="width: 78px" :disabled="readonly">
                    <a-select-option v-for="op in operators" :key="op.value" :value="op.value">{{ op.label }}</a-select-option>
                  </a-select>
                  <a-input v-model:value="row.value" size="small" style="flex: 1" :disabled="readonly" placeholder="值（多个用逗号）" @change="syncCondition" />
                  <a @click="removeCondRow(index)" :class="{ disabled: readonly }">×</a>
                </div>
                <a-button size="small" block :disabled="readonly" @click="addCondRow">+ 添加条件</a-button>
                <div class="hint">节点条件不满足时会自动跳过；用于按行业、金额、风险等级决定是否走这个节点。</div>
              </template>
              <a-textarea v-model:value="rawCondition" :rows="8" style="margin-top: 8px" :disabled="readonly"
                placeholder='高级：直接写 JSON，如 {"all":[{"field":"risk_level","op":"eq","value":"A"}]}' @blur="applyRawCondition" />
              <a class="raw-toggle" @click="toggleRaw">{{ condMode === 'raw' ? '使用表单条件' : '直接编辑 JSON' }}</a>
            </a-tab-pane>

            <a-tab-pane key="form" tab="表单" v-if="isTaskNode(selectedNode.data.type)">
              <div v-for="(field, index) in selectedNode.data.form_schema" :key="index" class="form-row">
                <a-input v-model:value="field.key" size="small" placeholder="key" style="width: 90px" :disabled="readonly" />
                <a-input v-model:value="field.label" size="small" placeholder="字段名" style="width: 90px" :disabled="readonly" />
                <a-select v-model:value="field.type" size="small" style="width: 88px" :disabled="readonly">
                  <a-select-option value="text">文本</a-select-option>
                  <a-select-option value="textarea">多行</a-select-option>
                  <a-select-option value="number">数字</a-select-option>
                  <a-select-option value="date">日期</a-select-option>
                  <a-select-option value="upload">附件</a-select-option>
                </a-select>
                <a-checkbox v-model:checked="field.required" :disabled="readonly">必填</a-checkbox>
                <a @click="removeField(index)" :class="{ disabled: readonly }">×</a>
              </div>
              <a-button size="small" block :disabled="readonly" @click="addField">+ 添加表单字段</a-button>
              <div class="hint">这些字段会在待办处理弹窗里出现，提交后传给业务 Hook。</div>
            </a-tab-pane>

            <a-tab-pane key="reject" tab="驳回">
              <a-form layout="vertical" size="small" v-if="isTaskNode(selectedNode.data.type)">
                <a-form-item label="驳回策略">
                  <a-select v-model:value="selectedNode.data.reject_policy.type" :disabled="readonly" @change="syncRejectTarget">
                    <a-select-option value="to_start">退回开始</a-select-option>
                    <a-select-option value="back_to">退回到指定节点</a-select-option>
                    <a-select-option value="back_prev">退回到上一节点</a-select-option>
                    <a-select-option value="terminate">直接终止流程</a-select-option>
                  </a-select>
                </a-form-item>
                <a-form-item v-if="selectedNode.data.reject_policy.type === 'back_to'" label="目标节点">
                  <a-select v-model:value="selectedNode.data.reject_policy.target" :disabled="readonly">
                    <a-select-option v-for="n in taskNodeOptions" :key="n.id" :value="n.id">{{ n.data.name }}</a-select-option>
                  </a-select>
                </a-form-item>
                <a-form-item label="说明">
                  <div class="hint">驳回必须填写意见；当前节点的其它待办会被取消，流程按策略退回。</div>
                </a-form-item>
              </a-form>
            </a-tab-pane>

            <a-tab-pane key="advanced" tab="高级">
              <a-form layout="vertical" size="small">
                <a-form-item label="超时升级对象（JSON）">
                  <a-textarea v-model:value="advanced.escalate_to" :rows="3" :disabled="readonly" @blur="applyAdvanced" />
                </a-form-item>
                <a-form-item label="扩展配置（JSON）">
                  <a-textarea v-model:value="advanced.config" :rows="6" :disabled="readonly" @blur="applyAdvanced" />
                </a-form-item>
              </a-form>
            </a-tab-pane>
          </a-tabs>
        </template>

        <template v-else-if="selectedEdge">
          <div class="panel-head">
            <span>连线设置</span>
            <a @click="removeEdge">删除连线</a>
          </div>
          <a-form layout="vertical" size="small">
            <a-form-item label="显示文字">
              <a-input v-model:value="selectedEdge.label" :disabled="readonly" @change="syncEdge" />
            </a-form-item>
            <a-form-item label="默认分支">
              <a-switch v-model:checked="selectedEdge.data.is_default" :disabled="readonly" @change="syncEdge" />
            </a-form-item>
            <a-form-item label="条件 JSON">
              <a-textarea v-model:value="edgeConditionText" :rows="8" :disabled="readonly" @blur="applyEdgeCondition"
                placeholder='{"all":[{"field":"risk_level","op":"eq","value":"A"}]}' />
            </a-form-item>
            <div class="hint">排他网关必须有一条默认分支；普通节点可不填条件。</div>
          </a-form>
        </template>

        <template v-else>
          <a-empty description="选择节点或连线进行设置" />
        </template>
    </a-drawer>

    <PublishConfirm ref="publishConfirm" @published="onPublished" />
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { message, Modal } from 'ant-design-vue'
import { ArrowLeftOutlined } from '@ant-design/icons-vue'
import { VueFlow, useVueFlow, MarkerType, Handle, Position } from '@vue-flow/core'
import { Background } from '@vue-flow/background'
import { Controls } from '@vue-flow/controls'
import { MiniMap } from '@vue-flow/minimap'
import '@vue-flow/core/dist/style.css'
import '@vue-flow/core/dist/theme-default.css'
import '@vue-flow/controls/dist/style.css'
import '@vue-flow/minimap/dist/style.css'
import { getTemplateDetail, saveTemplateDraft, validateTemplate, getTemplateMeta, copyTemplateVersion, checkTemplateAssignment } from '../api'
import PublishConfirm from '../components/PublishConfirm.vue'

const route = useRoute()
const router = useRouter()
const { screenToFlowCoordinate, fitView } = useVueFlow('flow-designer')

const templateId = ref(Number(route.query.id || 0))
const template = reactive({})
const meta = reactive({ biz_types: [], node_types: {}, gateway_types: {}, complete_modes: {}, allowed_variables: [], users: [], roles: [], departments: [] })
const nodes = ref([])
const edges = ref([])
const saving = ref(false)
const publishing = ref(false)
const copying = ref(false)
const publishConfirm = ref(null)
const readonly = computed(() => template.status !== 'draft')

const selectedNodeId = ref('')
const selectedEdgeId = ref('')
const panelOpen = ref(false)
const assignmentMap = ref({})
const panelTitle = computed(() => selectedNode.value ? '节点设置' : (selectedEdge.value ? '连线设置' : '流程设置'))
const nodeTab = ref('basic')
const condMode = ref('all')
const condRows = ref([])
const rawCondition = ref('')
const edgeConditionText = ref('')
const advanced = reactive({ escalate_to: '', config: '' })

const palette = [
  { key: 'start', label: '开始', type: 'start', color: '#52c41a', icon: '▶' },
  { key: 'task', label: '任务', type: 'task', color: '#1677ff', icon: '□' },
  { key: 'approval', label: '审批', type: 'approval', color: '#722ed1', icon: '✓' },
  { key: 'sign', label: '签字', type: 'sign', color: '#fa8c16', icon: '✎' },
  { key: 'payment', label: '收款', type: 'payment', color: '#13c2c2', icon: '¥' },
  { key: 'gateway', label: '网关', type: 'gateway', color: '#8c8c8c', icon: '◇' },
  { key: 'end', label: '结束', type: 'end', color: '#ff4d4f', icon: '■' },
]

const operators = [
  { value: 'eq', label: '等于' },
  { value: 'neq', label: '不等于' },
  { value: 'in', label: '属于' },
  { value: 'not_in', label: '不属于' },
  { value: 'gt', label: '大于' },
  { value: 'gte', label: '大于等于' },
  { value: 'lt', label: '小于' },
  { value: 'lte', label: '小于等于' },
  { value: 'contains', label: '包含' },
  { value: 'empty', label: '为空' },
  { value: 'not_empty', label: '不为空' },
]

const selectedNode = computed(() => nodes.value.find(n => n.id === selectedNodeId.value) || null)
const selectedEdge = computed(() => edges.value.find(e => e.id === selectedEdgeId.value) || null)
const taskNodeOptions = computed(() => nodes.value.filter(n => isTaskNode(n.data?.type)))
const roleNameOptions = computed(() => {
  const names = (meta.roles || []).map(r => r.name).filter(Boolean)
  return Array.from(new Set(names)).map(name => ({ value: name }))
})

const flatDepartments = computed(() => {
  const out = []
  const walk = (list, prefix = '') => {
    for (const item of list || []) {
      out.push({ id: item.id, label: prefix + item.name })
      walk(item.children, prefix + item.name + ' / ')
    }
  }
  walk(meta.departments)
  return out
})

function assignmentStatus(nodeKey) {
  return assignmentMap.value[nodeKey] || null
}

function assignmentTip(nodeKey) {
  const info = assignmentMap.value[nodeKey]
  if (!info) return ''
  const people = (info.assignees || []).join('、')
  return `${info.rule_text}
${people ? '当前解析：' + people : (info.message || '无人可派')}`
}

function assignmentBadge(nodeKey) {
  const status = assignmentMap.value[nodeKey]?.status
  return { error: '无人可派', fallback: '靠兜底', dynamic: '按单解析', ok: '可解析' }[status] || ''
}

async function refreshAssignmentStatus() {
  if (!templateId.value) return
  try {
    const res = await checkTemplateAssignment(templateId.value)
    if (res?.status === 200) {
      const map = {}
      for (const node of res.data?.nodes || []) map[node.node_key] = node
      assignmentMap.value = map
    }
  } catch (e) {
    // 指派检查失败不影响设计器使用
  }
}

function isTaskNode(type) {
  return ['task', 'approval', 'sign', 'payment'].includes(type)
}
function typeIcon(type) {
  return palette.find(p => p.type === type)?.icon || '□'
}
function nodeSub(data) {
  if (data.type === 'gateway') {
    return { exclusive: '排他网关', parallel: '并行网关', inclusive: '包容网关' }[data.gateway_type] || '网关'
  }
  if (data.type === 'start') return '流程入口'
  if (data.type === 'end') return '流程出口'
  const mode = { any: '或签', all: '会签', ratio: '比例', sequential: '顺序' }[data.complete_mode] || ''
  return mode + (data.sla_hours ? ` · ${data.sla_hours}h` : '')
}
function filterUser(input, option) {
  const u = meta.users.find(x => x.id === option.value)
  return (u?.name || '').toLowerCase().includes(input.toLowerCase())
}

function blankNode(id, type, x, y, extra = {}) {
  return {
    id,
    type: 'flow',
    position: { x, y },
    data: {
      node_key: id,
      name: meta.node_types[type] || type,
      type,
      gateway_type: type === 'gateway' ? 'exclusive' : '',
      join_mode: 'all',
      assignee_mode: 'any',
      assignee_rules: isTaskNode(type) ? [{ type: 'creator' }] : [],
      complete_mode: 'any',
      complete_ratio: 0,
      actions: type === 'approval' ? ['approve', 'reject'] : type === 'sign' ? ['sign'] : type === 'payment' ? ['pay'] : isTaskNode(type) ? ['complete'] : [],
      form_schema: [],
      condition_json: null,
      reject_policy: isTaskNode(type) ? { type: 'to_start' } : null,
      sync_status: '',
      sync_fields: null,
      sla_hours: isTaskNode(type) ? 24 : 0,
      remind_before_hours: 4,
      escalate_to: null,
      config: {},
      ...extra,
    },
  }
}

function nextKey(type) {
  let i = 1
  while (nodes.value.some(n => n.id === `${type}_${i}`)) i++
  return `${type}_${i}`
}

function addNode(type, position) {
  if (type === 'start' && nodes.value.some(n => n.data.type === 'start')) {
    return message.warning('只能有一个开始节点')
  }
  if (type === 'end' && nodes.value.some(n => n.data.type === 'end')) {
    return message.warning('只能有一个结束节点（v1）')
  }
  const key = nextKey(type)
  const node = blankNode(key, type, position?.x ?? 260, position?.y ?? 220)
  nodes.value.push(node)
  selectedNodeId.value = key
  selectedEdgeId.value = ''
  nodeTab.value = 'basic'
}

function onDragStart(event, item) {
  event.dataTransfer.setData('flow-node-type', item.type)
  event.dataTransfer.effectAllowed = 'move'
}
function onDrop(event) {
  if (readonly.value) return
  const type = event.dataTransfer.getData('flow-node-type')
  if (!type) return
  const position = screenToFlowCoordinate({ x: event.clientX, y: event.clientY })
  addNode(type, position)
}

function onConnect(connection) {
  if (readonly.value) return
  if (!connection.source || !connection.target || connection.source === connection.target) return
  const id = `${connection.source}-->${connection.target}`
  if (edges.value.some(e => e.id === id)) return
  edges.value.push({
    id,
    source: connection.source,
    target: connection.target,
    type: 'smoothstep',
    markerEnd: MarkerType.ArrowClosed,
    label: '',
    data: { condition_json: null, is_default: 0, label: '' },
  })
}

function onNodeClick({ node }) {
  selectedNodeId.value = node.id
  selectedEdgeId.value = ''
  panelOpen.value = true
  loadNodeEditors()
}
function onEdgeClick({ edge }) {
  selectedEdgeId.value = edge.id
  selectedNodeId.value = ''
  panelOpen.value = true
  edgeConditionText.value = edge.data?.condition_json ? JSON.stringify(edge.data.condition_json, null, 2) : ''
}
function clearSelection() {
  selectedNodeId.value = ''
  selectedEdgeId.value = ''
}

function loadNodeEditors() {
  const node = selectedNode.value
  if (!node) return
  const cond = node.data.condition_json
  condRows.value = []
  if (!cond) {
    condMode.value = 'all'
    rawCondition.value = ''
  } else if (cond.all) {
    condMode.value = 'all'
    condRows.value = cond.all.map(r => ({ field: r.field || '', op: r.op || 'eq', value: fmtValue(r.value) }))
    rawCondition.value = JSON.stringify(cond, null, 2)
  } else if (cond.any) {
    condMode.value = 'any'
    condRows.value = cond.any.map(r => ({ field: r.field || '', op: r.op || 'eq', value: fmtValue(r.value) }))
    rawCondition.value = JSON.stringify(cond, null, 2)
  } else {
    condMode.value = 'raw'
    rawCondition.value = JSON.stringify(cond, null, 2)
  }
  if (!node.data.reject_policy) node.data.reject_policy = { type: 'to_start' }
  advanced.escalate_to = node.data.escalate_to ? JSON.stringify(node.data.escalate_to, null, 2) : ''
  advanced.config = node.data.config && Object.keys(node.data.config).length ? JSON.stringify(node.data.config, null, 2) : ''
}

function fmtValue(v) {
  if (Array.isArray(v)) return v.join(', ')
  return v === null || v === undefined ? '' : String(v)
}
function parseValue(op, value) {
  const raw = String(value ?? '').trim()
  if (['in', 'not_in'].includes(op)) return raw.split(',').map(s => s.trim()).filter(Boolean)
  if (['gt', 'gte', 'lt', 'lte'].includes(op)) return Number(raw)
  return raw
}

function syncCondition() {
  const node = selectedNode.value
  if (!node) return
  if (!condRows.value.length) {
    node.data.condition_json = null
    rawCondition.value = ''
    return
  }
  const list = condRows.value
    .filter(r => r.field)
    .map(r => ({ field: r.field, op: r.op, value: parseValue(r.op, r.value) }))
  node.data.condition_json = { [condMode.value]: list }
  rawCondition.value = JSON.stringify(node.data.condition_json, null, 2)
}

function addCondRow() {
  condRows.value.push({ field: '', op: 'eq', value: '' })
}
function removeCondRow(index) {
  condRows.value.splice(index, 1)
  syncCondition()
}
function applyRawCondition() {
  const text = rawCondition.value.trim()
  if (!text) { selectedNode.value.data.condition_json = null; condRows.value = []; return }
  try {
    const parsed = JSON.parse(text)
    selectedNode.value.data.condition_json = parsed
    if (parsed.all || parsed.any) {
      condMode.value = parsed.all ? 'all' : 'any'
      condRows.value = (parsed.all || parsed.any).map(r => ({ field: r.field || '', op: r.op || 'eq', value: fmtValue(r.value) }))
    } else {
      condMode.value = 'raw'
    }
  } catch {
    message.error('条件 JSON 格式不正确')
  }
}
function toggleRaw() {
  if (condMode.value === 'raw') {
    loadNodeEditors()
  } else {
    condMode.value = 'raw'
    rawCondition.value = selectedNode.value.data.condition_json
      ? JSON.stringify(selectedNode.value.data.condition_json, null, 2) : ''
  }
}

function addRule() {
  selectedNode.value.data.assignee_rules.push({ type: 'role', id: undefined, field: '', of_field: '' })
}
function onRuleTypeChange(rule) {
  // 切换指派方式时清空旧值，避免把角色ID/人员ID带到新的下拉里显示成数字
  rule.id = undefined
  rule.field = ''
  rule.of_field = ''
  rule.name = ''
}
function removeRule(index) {
  selectedNode.value.data.assignee_rules.splice(index, 1)
}
function addField() {
  selectedNode.value.data.form_schema.push({ key: 'field_' + (selectedNode.value.data.form_schema.length + 1), label: '新字段', type: 'text', required: false })
}
function removeField(index) {
  selectedNode.value.data.form_schema.splice(index, 1)
}
function syncRejectTarget() {
  const policy = selectedNode.value.data.reject_policy
  if (policy.type !== 'back_to') policy.target = ''
}
function applyAdvanced() {
  const node = selectedNode.value
  try {
    node.data.escalate_to = advanced.escalate_to.trim() ? JSON.parse(advanced.escalate_to) : null
  } catch { message.error('升级对象 JSON 不正确'); return }
  try {
    node.data.config = advanced.config.trim() ? JSON.parse(advanced.config) : {}
  } catch { message.error('扩展配置 JSON 不正确'); return }
}
function syncEdge() {
  const edge = selectedEdge.value
  edge.data.label = edge.label || ''
  edge.data.is_default = edge.data.is_default ? 1 : 0
}
function applyEdgeCondition() {
  const text = edgeConditionText.value.trim()
  try {
    selectedEdge.value.data.condition_json = text ? JSON.parse(text) : null
  } catch {
    message.error('连线条件 JSON 不正确')
  }
}
function syncNodeVisual() {
  const node = selectedNode.value
  if (node.data.type === 'gateway' && !node.data.gateway_type) node.data.gateway_type = 'exclusive'
  if (isTaskNode(node.data.type) && !node.data.assignee_rules.length) node.data.assignee_rules = [{ type: 'creator' }]
  if (!isTaskNode(node.data.type)) {
    node.data.assignee_rules = []
    node.data.form_schema = []
    node.data.reject_policy = null
    if (['assignee', 'form', 'reject'].includes(nodeTab.value)) nodeTab.value = 'basic'
  } else if (!node.data.reject_policy) {
    node.data.reject_policy = { type: 'to_start' }
  }
}

function removeNode() {
  const id = selectedNodeId.value
  const incoming = edges.value.filter(e => e.target === id)
  const outgoing = edges.value.filter(e => e.source === id)

  edges.value = edges.value.filter(e => e.source !== id && e.target !== id)
  nodes.value = nodes.value.filter(n => n.id !== id)

  // 删除中间节点时自动把前后接上，避免画布断链
  for (const inc of incoming) {
    for (const out of outgoing) {
      if (inc.source === out.target) continue
      const edgeId = `${inc.source}-->${out.target}`
      if (edges.value.some(e => e.id === edgeId)) continue
      edges.value.push({
        id: edgeId,
        source: inc.source,
        target: out.target,
        type: 'smoothstep',
        markerEnd: MarkerType.ArrowClosed,
        label: '',
        data: { condition_json: null, is_default: 0, label: '' },
      })
    }
  }

  clearSelection()
  message.success(incoming.length && outgoing.length ? '已删除节点，前后节点已自动接通' : '已删除节点')
}
function removeEdge() {
  const id = selectedEdgeId.value
  edges.value = edges.value.filter(e => e.id !== id)
  clearSelection()
}

function buildPayload() {
  const payloadNodes = nodes.value.map(n => ({
    node_key: n.id,
    name: n.data.name,
    type: n.data.type,
    gateway_type: n.data.gateway_type || '',
    join_mode: n.data.join_mode || 'all',
    assignee_mode: n.data.assignee_mode || 'any',
    assignee_rules: n.data.assignee_rules || [],
    complete_mode: n.data.complete_mode || 'any',
    complete_ratio: Number(n.data.complete_ratio || 0),
    actions: n.data.actions || [],
    form_schema: n.data.form_schema || [],
    condition_json: n.data.condition_json || null,
    reject_policy: n.data.reject_policy || null,
    sync_status: n.data.sync_status || '',
    sync_fields: n.data.sync_fields || null,
    sla_hours: Number(n.data.sla_hours || 0),
    remind_before_hours: Number(n.data.remind_before_hours || 0),
    escalate_to: n.data.escalate_to || null,
    position_x: Math.round(n.position.x),
    position_y: Math.round(n.position.y),
    config: n.data.config || {},
  }))
  const payloadEdges = edges.value.map((e, index) => ({
    from_key: e.source,
    to_key: e.target,
    label: e.label || e.data?.label || '',
    is_default: e.data?.is_default ? 1 : 0,
    condition_json: e.data?.condition_json || null,
    sort: (index + 1) * 10,
  }))
  return { nodes: payloadNodes, edges: payloadEdges }
}

async function handleSave(silent = false) {
  if (readonly.value) return message.warning('已发布模板不可编辑，请复制新版本')
  saving.value = true
  try {
    const payload = buildPayload()
    const res = await saveTemplateDraft({ id: templateId.value, ...payload })
    if (res?.status === 200) {
      if (!silent) message.success('草稿已保存')
      applyDetail(res.data)
      refreshAssignmentStatus()
      return true
    }
    return false
  } finally {
    saving.value = false
  }
}

async function handleValidate() {
  if (!(await handleSave(true))) return
  const res = await validateTemplate(templateId.value)
  if (res?.status === 200) {
    if (res.data.passed) {
      message.success('校验通过，可以发布')
    } else {
      Modal.warning({
        title: '模板校验未通过',
        content: res.data.errors.map(e => `• ${e}`).join('\n'),
        width: 640,
      })
    }
  }
}

async function handlePublish() {
  if (readonly.value) return message.warning('该版本已发布')
  if (!(await handleSave(true))) return
  publishConfirm.value?.open(templateId.value)
}

function onPublished(data) {
  message.success('发布成功')
  applyDetail(data)
  refreshAssignmentStatus()
}

function applyDetail(data) {
  if (!data) return
  Object.assign(template, data.template || {})
  meta.allowed_variables = data.allowed_variables || meta.allowed_variables
  nodes.value = (data.nodes || []).map(n => ({
    id: n.node_key,
    type: 'flow',
    position: { x: Number(n.position_x || 100), y: Number(n.position_y || 100) },
    data: {
      ...n,
      condition_json: n.condition_json || null,
      reject_policy: n.reject_policy || (isTaskNode(n.type) ? { type: 'to_start' } : null),
      assignee_rules: n.assignee_rules || [],
      actions: n.actions || [],
      form_schema: n.form_schema || [],
      config: n.config || {},
    },
  }))
  edges.value = (data.edges || []).map(e => ({
    id: `${e.from_key}-->${e.to_key}`,
    source: e.from_key,
    target: e.to_key,
    type: 'smoothstep',
    markerEnd: MarkerType.ArrowClosed,
    label: e.label || '',
    data: {
      condition_json: e.condition_json || null,
      is_default: Number(e.is_default) || 0,
      label: e.label || '',
    },
  }))
}

async function handleCopyVersion() {
  copying.value = true
  try {
    const res = await copyTemplateVersion(templateId.value)
    if (res?.status === 200) {
      const newId = res.data?.template?.id
      message.success('已复制为新草稿，可以编辑了')
      if (newId) {
        router.replace({ path: '/app/flow/designer', query: { id: newId } })
        templateId.value = newId
        applyDetail(res.data)
        refreshAssignmentStatus()
        await nextTick()
        setTimeout(() => fitView({ padding: 0.15 }), 300)
      }
    }
  } finally {
    copying.value = false
  }
}

const ctx = reactive({ visible: false, x: 0, y: 0, type: '', nodeId: '', edgeId: '', nodeName: '', flowX: 0, flowY: 0 })

const canCopyNode = computed(() => {
  const node = nodes.value.find(n => n.id === ctx.nodeId)
  return !!node && !['start', 'end'].includes(node.data?.type)
})

function placeCtx(event, type, extra = {}) {
  event.preventDefault()
  event.stopPropagation()
  const menuHeight = type === 'pane' ? 360 : 150
  ctx.visible = true
  ctx.type = type
  ctx.x = Math.min(event.clientX, Math.max(8, window.innerWidth - 200))
  ctx.y = Math.min(event.clientY, Math.max(8, window.innerHeight - menuHeight))
  ctx.nodeId = ''
  ctx.edgeId = ''
  ctx.nodeName = ''
  Object.assign(ctx, extra)
}

function closeCtx() {
  ctx.visible = false
}

function onNodeContextMenu({ event, node }) {
  const pos = screenToFlowCoordinate({ x: event.clientX, y: event.clientY })
  placeCtx(event, 'node', { nodeId: node.id, nodeName: node.data?.name || node.id, flowX: pos.x, flowY: pos.y })
}

function onEdgeContextMenu({ event, edge }) {
  placeCtx(event, 'edge', { edgeId: edge.id })
}

function onPaneContextMenu(payload) {
  // Vue Flow 的 paneContextMenu 直接抛 MouseEvent，节点/连线则包了一层 { event, node/edge }
  const event = payload?.event || payload
  if (!event || typeof event.clientX !== 'number') return
  const pos = screenToFlowCoordinate({ x: event.clientX, y: event.clientY })
  placeCtx(event, 'pane', { flowX: pos.x, flowY: pos.y })
}

function ctxEditNode() {
  const node = nodes.value.find(n => n.id === ctx.nodeId)
  closeCtx()
  if (!node) return
  selectedNodeId.value = node.id
  selectedEdgeId.value = ''
  panelOpen.value = true
  loadNodeEditors()
}

function ctxDuplicateNode() {
  if (readonly.value) return
  const node = nodes.value.find(n => n.id === ctx.nodeId)
  closeCtx()
  if (!node) return
  const key = nextKey(node.data.type)
  const data = JSON.parse(JSON.stringify(node.data))
  data.node_key = key
  data.name = (data.name || '节点') + '副本'
  nodes.value.push({
    id: key,
    type: 'flow',
    position: { x: node.position.x + 40, y: node.position.y + 40 },
    data,
  })
  selectedNodeId.value = key
  selectedEdgeId.value = ''
  panelOpen.value = true
  loadNodeEditors()
}

function ctxDeleteNode() {
  if (readonly.value) return
  selectedNodeId.value = ctx.nodeId
  closeCtx()
  removeNode()
}

function ctxEditEdge() {
  const edge = edges.value.find(e => e.id === ctx.edgeId)
  closeCtx()
  if (!edge) return
  selectedEdgeId.value = edge.id
  selectedNodeId.value = ''
  panelOpen.value = true
  edgeConditionText.value = edge.data?.condition_json ? JSON.stringify(edge.data.condition_json, null, 2) : ''
}

function ctxDeleteEdge() {
  if (readonly.value) return
  const id = ctx.edgeId
  closeCtx()
  edges.value = edges.value.filter(e => e.id !== id)
  if (selectedEdgeId.value === id) clearSelection()
}

function ctxAddNode(type) {
  if (readonly.value) return
  const pos = { x: ctx.flowX, y: ctx.flowY }
  closeCtx()
  addNode(type, pos)
}

function onNodesInitialized() {
  setTimeout(() => fitView({ padding: 0.15 }), 120)
}

function goBack() {
  router.push('/app/flow/template')
}

onMounted(async () => {
  if (!templateId.value) {
    message.error('缺少模板 ID')
    return goBack()
  }
  const metaRes = await getTemplateMeta()
  if (metaRes?.status === 200) Object.assign(meta, metaRes.data)
  const res = await getTemplateDetail(templateId.value)
  if (res?.status === 200) {
    applyDetail(res.data)
    refreshAssignmentStatus()
    await nextTick()
    setTimeout(() => fitView({ padding: 0.15 }), 400)
  }
})
</script>

<style scoped>
.designer { background: #fff; border-radius: 8px; overflow: hidden; }
.designer-head { display: flex; justify-content: space-between; align-items: center; padding: 10px 16px; border-bottom: 1px solid #eef1f5; }
.head-left { display: flex; align-items: center; gap: 8px; }
.t-name { font-weight: 600; font-size: 15px; }
.t-meta { color: #8a949e; font-size: 12px; margin-top: 2px; }
.head-actions { display: flex; gap: 8px; }
.designer-body { display: flex; height: calc(100vh - 210px); min-height: 520px; }
.readonly-tip { padding: 8px 16px; background: #fffbe6; border-bottom: 1px solid #ffe58f; color: #8a6d3b; font-size: 12px; }
.palette { width: 140px; border-right: 1px solid #eef1f5; padding: 12px; }
.palette-title { font-size: 12px; color: #8a949e; margin-bottom: 8px; }
.palette-item { display: flex; align-items: center; gap: 8px; padding: 8px 10px; border: 1px solid #e6ebf1; border-radius: 6px; margin-bottom: 8px; cursor: grab; background: #fafbfc; font-size: 13px; }
.palette-item:hover { border-color: #1677ff; background: #f0f7ff; }
.dot { width: 8px; height: 8px; border-radius: 50%; }
.palette-tip { font-size: 11px; color: #a0aab4; line-height: 1.5; margin-top: 12px; }
.canvas { flex: 1; min-width: 0; background: #f8fafc; }
.panel-head { display: flex; justify-content: space-between; font-weight: 600; margin-bottom: 8px; }
.flow-node { position: relative; min-width: 132px; padding: 8px 12px; border-radius: 8px; background: #fff; border: 1px solid #d9e2ec; box-shadow: 0 1px 2px rgba(16, 24, 40, 0.06); border-left-width: 4px; }
.connect-tip { color: #1677ff; }
.palette-legend { margin-top: 12px; font-size: 11px; color: #6b7785; display: flex; flex-wrap: wrap; gap: 6px 10px; }
.palette-legend span { display: flex; align-items: center; gap: 4px; }
.lg { width: 8px; height: 8px; border-radius: 50%; display: inline-block; }
.lg-error { background: #ff4d4f; }
.lg-fallback { background: #fa8c16; }
.lg-dynamic { background: #1677ff; }
.lg-ok { background: #52c41a; }
.fn-badge { position: absolute; top: -9px; right: 8px; font-size: 10px; line-height: 1; padding: 3px 6px; border-radius: 8px; color: #fff; white-space: nowrap; }
.b-error { background: #ff4d4f; }
.b-fallback { background: #fa8c16; }
.b-dynamic { background: #1677ff; }
.b-ok { background: #52c41a; }
.flow-node.a-error { border-color: #ff4d4f; background: #fff1f0; }
.flow-node.a-fallback { border-color: #fa8c16; background: #fff7e6; }
.flow-node.a-dynamic { border-color: #1677ff; background: #f0f7ff; }
.ctx-mask { position: fixed; inset: 0; z-index: 1000; }
.ctx-menu { position: fixed; z-index: 1001; min-width: 170px; background: #fff; border: 1px solid #e6ebf1; border-radius: 8px; box-shadow: 0 8px 24px rgba(16,24,40,.16); padding: 6px; font-size: 13px; }
.ctx-title { padding: 4px 10px 6px; color: #8a949e; font-size: 12px; border-bottom: 1px solid #f0f2f5; margin-bottom: 4px; }
.ctx-sub { padding: 6px 10px 2px; color: #8a949e; font-size: 11px; }
.ctx-item { padding: 7px 10px; border-radius: 5px; cursor: pointer; display: flex; align-items: center; gap: 8px; }
.ctx-item:hover { background: #f0f7ff; color: #1677ff; }
.ctx-item.danger:hover { background: #fff1f0; color: #ff4d4f; }
.ctx-item.disabled { color: #c5ccd4; pointer-events: none; }
.ctx-sep { height: 1px; background: #f0f2f5; margin: 4px 0; }
.ctx-dot { width: 8px; height: 8px; border-radius: 50%; }
.flow-node :deep(.vue-flow__handle) { width: 10px; height: 10px; background: #1677ff; border: 2px solid #fff; }
.flow-node :deep(.vue-flow__handle-left) { left: -6px; }
.flow-node :deep(.vue-flow__handle-right) { right: -6px; }
.flow-node.selected { box-shadow: 0 0 0 2px #1677ff33; border-color: #1677ff; }
.flow-node.t-start { border-left-color: #52c41a; }
.flow-node.t-task { border-left-color: #1677ff; }
.flow-node.t-approval { border-left-color: #722ed1; }
.flow-node.t-sign { border-left-color: #fa8c16; }
.flow-node.t-payment { border-left-color: #13c2c2; }
.flow-node.t-gateway { border-left-color: #8c8c8c; }
.flow-node.t-end { border-left-color: #ff4d4f; }
.fn-top { display: flex; align-items: center; gap: 6px; font-weight: 600; font-size: 13px; }
.fn-icon { font-size: 12px; color: #6b7785; }
.fn-sub { font-size: 11px; color: #8a949e; margin-top: 2px; }
.rule-row, .cond-row, .form-row { display: flex; gap: 6px; align-items: center; margin-bottom: 8px; }
.rule-empty { flex: 1; color: #a0aab4; font-size: 12px; }
.cond-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
.hint { font-size: 11px; color: #8a949e; line-height: 1.6; margin-top: 10px; }
.raw-toggle { display: inline-block; margin-top: 8px; font-size: 12px; }
.disabled { color: #c5ccd4; pointer-events: none; }
</style>
