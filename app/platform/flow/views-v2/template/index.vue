<template>
  <a-card :bordered="false">
    <div class="toolbar">
      <div class="filters">
        <a-input-search v-model:value="query.keyword" placeholder="模板名称/编码" style="width: 220px" allow-clear @search="loadData(true)" />
        <a-select v-model:value="query.biz_type" placeholder="业务类型" allow-clear style="width: 160px" @change="loadData(true)">
          <a-select-option v-for="item in meta.biz_types" :key="item.value" :value="item.value">{{ item.label }}</a-select-option>
        </a-select>
        <a-select v-model:value="query.status" placeholder="状态" allow-clear style="width: 120px" @change="loadData(true)">
          <a-select-option value="draft">草稿</a-select-option>
          <a-select-option value="published">已发布</a-select-option>
          <a-select-option value="archived">已归档</a-select-option>
        </a-select>
      </div>
      <a-button type="primary" @click="openCreate"><plus-outlined /> 新建模板</a-button>
    </div>

    <a-table :columns="columns" :data-source="list" :loading="loading" :pagination="pagination"
      :row-key="r => r.id" size="middle" @change="onTableChange">
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'name'">
          <div class="title">{{ record.name }}</div>
          <div class="muted">{{ record.code }} · v{{ record.version }}</div>
        </template>
        <template v-else-if="column.key === 'biz_type'">{{ record.biz_label }}</template>
        <template v-else-if="column.key === 'status'">
          <a-tag :color="statusColor(record.status)">{{ record.status_text }}</a-tag>
          <a-tag v-if="Number(record.is_default) === 1" color="gold">默认</a-tag>
        </template>
        <template v-else-if="column.key === 'update_time'">{{ formatTime(record.update_time) }}</template>
        <template v-else-if="column.key === 'action'">
          <a @click="openDesigner(record)">{{ record.status === 'draft' ? '设计' : '查看' }}</a>
          <template v-if="record.status === 'draft'">
            <a-divider type="vertical" />
            <a @click="handlePublish(record)">发布</a>
          </template>
          <template v-if="record.status === 'published'">
            <a-divider type="vertical" />
            <a v-if="Number(record.is_default) !== 1" @click="handleSetDefault(record)">设为默认</a>
            <a-divider type="vertical" />
            <a @click="handleCopy(record)">复制新版本并编辑</a>
            <a-divider type="vertical" />
            <a @click="handleArchive(record)">归档</a>
          </template>
          <template v-if="record.status !== 'published'">
            <a-divider type="vertical" />
            <a-popconfirm title="删除该模板版本?" @confirm="() => handleRemove(record)">
              <a style="color: #ff4d4f">删除</a>
            </a-popconfirm>
          </template>
        </template>
      </template>
    </a-table>

    <a-modal v-model:open="createOpen" title="新建流程模板" :confirm-loading="creating" @ok="handleCreate">
      <a-form layout="vertical">
        <a-form-item label="业务类型" required>
          <a-select v-model:value="form.biz_type" placeholder="选择已注册业务" @change="onBizTypeChange">
            <a-select-option v-for="item in meta.biz_types" :key="item.value" :value="item.value">{{ item.label }}</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="初始化方式">
          <a-radio-group v-model:value="form.init_mode" @change="onInitModeChange">
            <a-radio v-if="presetOptions.length" value="preset">系统默认模板（推荐）</a-radio>
            <a-radio v-if="sourceOptions.length" value="default">现有模板</a-radio>
            <a-radio value="blank">空白模板</a-radio>
          </a-radio-group>
          <div v-if="!presetOptions.length && !sourceOptions.length" class="hint">该业务暂无可用模板，将创建空白流程。</div>
        </a-form-item>

        <a-form-item v-if="form.init_mode === 'preset'" label="选择系统默认模板" required>
          <a-select v-model:value="form.preset_key" @change="onPresetChange">
            <a-select-option v-for="p in presetOptions" :key="p.key" :value="p.key">
              {{ p.name }}（系统预置）
            </a-select-option>
          </a-select>
          <div class="hint">系统内置流程，选中后直接生成完整节点，不需要从空白设计。</div>
        </a-form-item>

        <a-form-item v-if="form.init_mode === 'default'" label="选择要复制的模板" required>
          <a-select v-model:value="form.source_template_id" @change="onSourceChange">
            <a-select-option v-for="t in sourceOptions" :key="t.id" :value="t.id">
              {{ t.name }} v{{ t.version }}{{ Number(t.is_default) === 1 ? '（默认）' : '' }}
            </a-select-option>
          </a-select>
          <div class="hint">会复制该模板的节点、指派、条件和表单，创建后可继续微调。</div>
        </a-form-item>

        <div v-if="sourcePreview && form.init_mode !== 'blank'" class="source-preview">
          <div class="sp-head">
            <a-tag v-if="sourcePreview.kind === 'preset'" color="blue">系统预置</a-tag>
            <a-tag v-else color="green">已发布</a-tag>
            <a-tag v-if="Number(sourcePreview.template.is_default) === 1" color="gold">默认</a-tag>
            <span class="sp-name">{{ sourcePreview.template.name }} {{ sourcePreview.template.version_text }}</span>
            <a v-if="sourcePreview.template.id" :href="'/app/flow/designer?id=' + sourcePreview.template.id" target="_blank">打开设计器看图</a>
          </div>
          <div class="sp-desc">{{ sourcePreview.template.description || '暂无说明' }}</div>
          <div class="sp-chain">
            <div class="sp-label">流程节点</div>
            <div class="sp-nodes">{{ sourceChain }}</div>
          </div>
        </div>
        <a-spin v-else-if="previewLoading" size="small" style="margin-top: 8px" />

        <a-form-item label="模板名称" required>
          <a-input v-model:value="form.name" placeholder="如：财税业务单默认流程" />
        </a-form-item>
        <a-form-item label="模板编码">
          <a-input v-model:value="form.code" placeholder="留空则使用业务默认编码" />
        </a-form-item>
        <a-form-item label="说明">
          <a-textarea v-model:value="form.description" :rows="3" />
        </a-form-item>
        <a-form-item label="创建后立即发布">
          <a-switch v-model:checked="form.publish_now" />
          <span class="hint" style="margin-left: 8px">发布后新单立即使用；之后修改需复制新版本，在途单不受影响</span>
        </a-form-item>
      </a-form>
    </a-modal>

    <PublishConfirm ref="publishConfirm" @published="onPublished" />
  </a-card>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { PlusOutlined } from '@ant-design/icons-vue'
import PublishConfirm from '../components/PublishConfirm.vue'
import {
  getTemplateList, createTemplate, archiveTemplate,
  setDefaultTemplate, copyTemplateVersion, removeTemplate, getTemplateMeta, getTemplateDetail, getTemplatePresets,
} from '../api'

const router = useRouter()
const meta = ref({ biz_types: [] })
const query = reactive({ keyword: '', biz_type: undefined, status: undefined })
const list = ref([])
const loading = ref(false)
const pagination = reactive({ current: 1, pageSize: 20, total: 0, showSizeChanger: true, showTotal: t => `共 ${t} 个版本` })

const columns = [
  { title: '模板', key: 'name', ellipsis: true },
  { title: '业务', key: 'biz_type', width: 140 },
  { title: '状态', key: 'status', width: 150 },
  { title: '节点数', dataIndex: 'node_count', key: 'node_count', width: 90 },
  { title: '更新时间', key: 'update_time', width: 160 },
  { title: '操作', key: 'action', width: 260 },
]

function statusColor(status) {
  return { draft: 'default', published: 'green', archived: 'orange' }[status] || 'default'
}

function formatTime(ts) {
  if (!ts) return '—'
  const d = new Date(Number(ts) * 1000)
  const p = n => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${p(d.getMonth() + 1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}`
}

async function loadData(reset = false) {
  if (reset) pagination.current = 1
  loading.value = true
  try {
    const res = await getTemplateList({ page: pagination.current, limit: pagination.pageSize, ...query })
    if (res?.status === 200) {
      list.value = res.data?.list || []
      pagination.total = res.data?.total || 0
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

const createOpen = ref(false)
const publishConfirm = ref(null)
const creating = ref(false)
const form = reactive({ biz_type: undefined, name: '', code: '', description: '', init_mode: 'blank', source_template_id: undefined, preset_key: undefined, publish_now: false })
const sourceOptions = ref([])
const presetOptions = ref([])
const sourcePreview = ref(null)
const previewLoading = ref(false)

const sourceChain = computed(() => {
  const nodes = sourcePreview.value?.nodes || []
  return nodes
    .filter(n => n.type !== 'gateway')
    .map(n => n.name)
    .join(' → ')
})

function openCreate() {
  Object.assign(form, {
    biz_type: undefined, name: '', code: '', description: '',
    init_mode: 'blank', source_template_id: undefined, preset_key: undefined, publish_now: false,
  })
  sourceOptions.value = []
  presetOptions.value = []
  sourcePreview.value = null
  createOpen.value = true
}

function onBizTypeChange(bizType) {
  form.name = ''
  form.code = ''
  form.source_template_id = undefined
  form.preset_key = undefined
  sourcePreview.value = null
  loadCreateOptions(bizType)
}

async function loadCreateOptions(bizType) {
  sourceOptions.value = []
  presetOptions.value = []
  if (!bizType) return

  const [listRes, presetRes] = await Promise.all([
    getTemplateList({ biz_type: bizType, status: 'published', limit: 100 }),
    getTemplatePresets(bizType),
  ])
  if (listRes?.status === 200) sourceOptions.value = listRes.data?.list || []
  if (presetRes?.status === 200) presetOptions.value = presetRes.data || []

  if (sourceOptions.value.length) {
    const def = sourceOptions.value.find(t => Number(t.is_default) === 1) || sourceOptions.value[0]
    form.init_mode = 'default'
    form.source_template_id = def.id
    applySourceDefaults(def)
    loadSourcePreview(def.id)
  } else if (presetOptions.value.length) {
    form.init_mode = 'preset'
    form.preset_key = presetOptions.value[0].key
    applyPresetDefaults(presetOptions.value[0])
  } else {
    form.init_mode = 'blank'
  }
}

function onInitModeChange(mode) {
  if (mode === 'default' && sourceOptions.value.length) {
    const def = sourceOptions.value.find(t => Number(t.is_default) === 1) || sourceOptions.value[0]
    form.source_template_id = def.id
    applySourceDefaults(def)
    loadSourcePreview(def.id)
  } else if (mode === 'preset' && presetOptions.value.length) {
    form.preset_key = presetOptions.value[0].key
    applyPresetDefaults(presetOptions.value[0])
  } else {
    sourcePreview.value = null
  }
}

function applyPresetDefaults(preset) {
  if (!preset) return
  if (!form.name) form.name = preset.name
  form.code = ''
  previewPreset(preset)
}

function onPresetChange(key) {
  const preset = presetOptions.value.find(p => p.key === key)
  if (!preset) return
  if (!form.name) form.name = preset.name
  previewPreset(preset)
}

function previewPreset(preset) {
  sourcePreview.value = {
    kind: 'preset',
    template: {
      id: 0,
      name: preset.name,
      description: preset.description,
      is_default: 1,
      version_text: '系统预置',
      version: 0,
    },
    nodes: preset.nodes || [],
  }
}

function applySourceDefaults(template) {
  if (!template) return
  if (!form.name) form.name = template.name + '（副本）'
  if (!form.code) form.code = template.code
}

function onSourceChange(id) {
  const template = sourceOptions.value.find(t => t.id === id)
  applySourceDefaults(template)
  loadSourcePreview(id)
}

async function loadSourcePreview(id) {
  sourcePreview.value = null
  if (!id) return
  previewLoading.value = true
  try {
    const res = await getTemplateDetail(id)
    if (res?.status === 200) {
      sourcePreview.value = { ...res.data, kind: 'template' }
      sourcePreview.value.template.version_text = 'v' + sourcePreview.value.template.version
    }
  } finally {
    previewLoading.value = false
  }
}

async function handleCreate() {
  if (!form.biz_type || !form.name) return message.warning('请选择业务类型并填写模板名称')
  creating.value = true
  try {
    const payload = {
      biz_type: form.biz_type,
      name: form.name,
      code: form.code,
      description: form.description,
      source_template_id: form.init_mode === 'default' ? form.source_template_id : 0,
      preset_key: form.init_mode === 'preset' ? form.preset_key : '',
    }
    const res = await createTemplate(payload)
    if (res?.status === 200) {
      const id = res.data?.template?.id

      if (form.publish_now && id) {
        createOpen.value = false
        publishConfirm.value?.open(id)
        return
      }

      message.success('已创建草稿，可以继续设计')
      createOpen.value = false
      if (id) router.push({ path: '/app/flow/designer', query: { id } })
      else loadData(true)
    }
  } finally {
    creating.value = false
  }
}

function openDesigner(record) {
  router.push({ path: '/app/flow/designer', query: { id: record.id } })
}

function handlePublish(record) {
  publishConfirm.value?.open(record.id)
}

function onPublished() {
  loadData()
}

async function handleSetDefault(record) {
  const res = await setDefaultTemplate(record.id)
  if (res?.status === 200) {
    message.success('已设为默认模板')
    loadData()
  }
}

async function handleCopy(record) {
  const res = await copyTemplateVersion(record.id)
  if (res?.status === 200) {
    message.success('已复制为新草稿版本')
    loadData(true)
  }
}

async function handleArchive(record) {
  const res = await archiveTemplate(record.id)
  if (res?.status === 200) {
    message.success('已归档')
    loadData()
  }
}

async function handleRemove(record) {
  const res = await removeTemplate(record.id)
  if (res?.status === 200) {
    message.success('已删除')
    loadData()
  }
}

onMounted(async () => {
  const metaRes = await getTemplateMeta()
  if (metaRes?.status === 200) meta.value = metaRes.data
  loadData()
})
</script>

<style scoped>
.toolbar { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 16px; }
.filters { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
.title { font-weight: 600; }
.muted { color: #8a949e; font-size: 12px; margin-top: 2px; }
.hint { color: #8a949e; font-size: 12px; margin-top: 4px; line-height: 1.5; }
.source-preview { margin-top: 10px; border: 1px solid #e6ebf1; border-radius: 8px; padding: 10px 12px; background: #fafbfc; }
.sp-head { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.sp-name { font-weight: 600; }
.sp-desc { color: #5c6670; font-size: 12px; margin-top: 6px; line-height: 1.6; }
.sp-chain { margin-top: 8px; }
.sp-label { font-size: 12px; color: #8a949e; margin-bottom: 4px; }
.sp-nodes { font-size: 12px; color: #334155; line-height: 1.8; background: #fff; border: 1px dashed #d9e2ec; border-radius: 6px; padding: 8px 10px; }
</style>
