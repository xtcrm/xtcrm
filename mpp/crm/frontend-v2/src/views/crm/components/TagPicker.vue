<template>
  <a-modal
    :open="open"
    :title="title"
    :width="820"
    wrap-class-name="tag-picker-modal"
    :body-style="{ padding: '16px 20px' }"
    @cancel="handleCancel"
  >
    <div class="tag-picker">
      <!-- 顶部：搜索 + 已选计数 -->
      <div class="picker-top">
        <a-input v-model:value="keyword" placeholder="搜索标签..." allow-clear style="width: 240px">
          <template #prefix><SearchOutlined style="color:#bbb" /></template>
        </a-input>
        <span class="pick-count">已选 {{ draft.length }} 个</span>
        <a-button size="small" type="link" :disabled="!draft.length" @click="draft = []">清空</a-button>
      </div>

      <!-- 已选 chips（可移除） -->
      <div v-if="draft.length" class="selected-row">
        <span class="selected-label">已选：</span>
        <a-tag
          v-for="name in draft"
          :key="name"
          :color="findColorName(name) || undefined"
          closable
          style="margin-right:0"
          @close="remove(name)"
        >{{ name }}</a-tag>
      </div>

      <!-- 分组列棋盘 -->
      <div class="board">
        <div v-for="g in filteredGroups" :key="g.id" class="group-col">
          <div class="group-header">{{ g.name }}<span class="group-count">{{ g.tags.length }}</span></div>
          <div class="group-tags">
            <a-checkable-tag
              v-for="t in g.tags"
              :key="t.id"
              :checked="isChecked(t.name)"
              :style="chipStyle(t)"
              @change="toggle(t.name)"
            >{{ t.name }}</a-checkable-tag>
          </div>
        </div>
        <div v-if="!filteredGroups.length" class="empty-board">没有匹配的标签</div>
      </div>

      <!-- 自定义标签 -->
      <div class="custom-add">
        <a-input
          v-model:value="customInput"
          placeholder="输入新标签，回车添加"
          @press-enter="addCustom"
          style="width: 280px"
        />
      </div>
    </div>

    <template #footer>
      <a-button @click="handleCancel">取消</a-button>
      <a-button type="primary" @click="handleOk">保存</a-button>
    </template>
  </a-modal>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { SearchOutlined } from '@ant-design/icons-vue'
import { getAllByTarget } from '@/api/crm/tag'

const props = defineProps({
  open: { type: Boolean, default: false },
  targetType: { type: String, default: 'customer' },
  modelValue: { type: Array, default: () => [] },
  disabled: { type: Boolean, default: false },
})
const emit = defineEmits(['update:open', 'update:modelValue'])

const groups = ref([])
const draft = ref([])
const keyword = ref('')
const customInput = ref('')

const title = computed(() => (props.targetType === 'contact' ? '选择联系人标签' : '选择客户标签'))

// 每次打开：重新拉分组树 + 用 modelValue 初始化草稿
watch(() => props.open, (v) => {
  if (v) {
    draft.value = [...(props.modelValue || [])]
    keyword.value = ''
    customInput.value = ''
    loadGroups()
  }
})

function loadGroups() {
  getAllByTarget(props.targetType).then(res => {
    groups.value = res?.data?.list || []
  }).catch(() => {})
}

const kw = computed(() => (keyword.value || '').trim().toLowerCase())

// 按关键字过滤组和标签，空组自动隐藏
const filteredGroups = computed(() => {
  if (!kw.value) return groups.value
  return groups.value
    .map(g => ({ ...g, tags: g.tags.filter(t => t.name.toLowerCase().includes(kw.value)) }))
    .filter(g => g.tags.length)
})

const isChecked = (name) => draft.value.includes(name)

function toggle(name) {
  const i = draft.value.indexOf(name)
  if (i >= 0) draft.value.splice(i, 1)
  else draft.value.push(name)
}

function remove(name) {
  const i = draft.value.indexOf(name)
  if (i >= 0) draft.value.splice(i, 1)
}

// 自定义新标签：回车加入选中集（后端保存时自动创建为未分组标签）
function addCustom() {
  const name = (customInput.value || '').trim()
  if (!name) return
  if (!draft.value.includes(name)) draft.value.push(name)
  customInput.value = ''
}

function handleOk() {
  emit('update:modelValue', [...draft.value])
  emit('update:open', false)
}

function handleCancel() {
  emit('update:open', false)
}

// ---- 颜色 ----
const colorMap = {
  blue: '#1677ff', green: '#52c41a', orange: '#fa8c16',
  red: '#f5222d', purple: '#722ed1', cyan: '#13c2c2',
}

function findTag(name) {
  for (const g of groups.value) {
    const t = g.tags.find(x => x.name === name)
    if (t) return t
  }
  return null
}

function findColorName(name) {
  return findTag(name)?.color || ''
}

// chip 样式：选中填标签自身色（白字），未选中灰描边
function chipStyle(t) {
  const checked = isChecked(t.name)
  if (checked) {
    const c = colorMap[t.color] || '#8c8c8c'
    return { borderColor: c, backgroundColor: c, color: '#fff' }
  }
  return { borderColor: '#d9d9d9', backgroundColor: '#fff', color: '#666' }
}
</script>

<style scoped>
.tag-picker .picker-top { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; }
.tag-picker .pick-count { color: #888; font-size: 13px; }

.selected-row {
  display: flex; flex-wrap: wrap; align-items: center; gap: 6px;
  padding: 8px 10px; margin-bottom: 12px;
  background: #fafafa; border: 1px solid #f0f0f0; border-radius: 6px;
}
.selected-label { color: #888; font-size: 12px; }

/* 分组列棋盘：每组一列，列多自动换行 */
.board {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(170px, 1fr));
  gap: 12px;
  max-height: 460px;
  overflow-y: auto;
  padding: 2px 2px 8px;
}
.group-col { border: 1px solid #f0f0f0; border-radius: 8px; padding: 10px; background: #fff; }
.group-header { font-weight: 600; font-size: 13px; color: #333; margin-bottom: 8px; }
.group-count { color: #bbb; font-weight: 400; font-size: 12px; margin-left: 4px; }
.group-tags { display: flex; flex-direction: column; align-items: flex-start; gap: 6px; }
.empty-board { color: #999; padding: 24px; text-align: center; grid-column: 1 / -1; }

.custom-add { margin-top: 12px; }
</style>
