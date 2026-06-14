<template>
  <a-modal :open="visible" title="图片库" :width="780" :mask-closable="false" @ok="handleSubmit" @cancel="visible = false">
    <div class="files-modal">
      <div class="fm-left">
        <a-tree
          :tree-data="groupTree"
          :block-node="true"
          :show-icon="false"
          @select="onSelectGroup"
        />
        <a-button type="dashed" size="small" block style="margin-top:8px" @click="showAddGroup = true"><plus-outlined /> 新增分组</a-button>
      </div>

      <div class="fm-right">
        <div class="fm-toolbar">
          <a-input-search v-model:value="queryParam.fileName" placeholder="搜索文件名称" style="width:200px" @search="loadList" />
          <a-upload name="iFile" accept="image/*" :multiple="true" :show-upload-list="false" :custom-request="onUpload">
            <a-button><cloud-upload-outlined /> 上传</a-button>
          </a-upload>
        </div>

        <div v-if="list.length" class="file-grid">
          <div
            v-for="(item, index) in list"
            :key="item.file_id"
            :class="['file-card', { active: selected.includes(index) }]"
            @click="onSelect(index)"
          >
            <div class="img-cover" :style="{ backgroundImage: `url('${item.preview_url}')` }"></div>
            <div class="file-name">{{ item.file_name }}</div>
            <div class="select-overlay"><check-outlined /></div>
          </div>
        </div>
        <a-empty v-else-if="!loading" style="padding:40px 0" />

        <div class="fm-footer">
          <span v-if="selected.length" style="color:#d6893b">已选择 {{ selected.length }} 项</span>
          <span v-else></span>
          <a-pagination v-model:current="queryParam.page" :total="total" :page-size="15" size="small" @change="loadList" />
        </div>
      </div>
    </div>

    <!-- 新增分组弹窗 -->
    <a-modal :open="showAddGroup" title="新增分组" :width="360" @ok="handleAddGroup" @cancel="showAddGroup = false" :confirm-loading="groupSaving">
      <a-form :label-col="{ span: 5 }">
        <a-form-item label="名称"><a-input v-model:value="newGroupName" placeholder="分组名称" /></a-form-item>
      </a-form>
    </a-modal>
  </a-modal>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { message } from 'ant-design-vue'
import { CloudUploadOutlined, CheckOutlined, PlusOutlined } from '@ant-design/icons-vue'
import * as FileApi from '@/api/files'
import * as GroupApi from '@/api/files-group'

const props = defineProps({
  multiple: { type: Boolean, default: false },
  maxNum: { type: Number, default: 100 },
})
const emit = defineEmits(['handleSubmit'])

const visible = ref(false)
const loading = ref(false)
const list = ref([])
const total = ref(0)
const selected = ref([])
const groups = ref([])
const showAddGroup = ref(false)
const newGroupName = ref('')
const groupSaving = ref(false)
const queryParam = reactive({ page: 1, fileName: '', fileType: 10, channel: 10, groupId: 0 })

const groupTree = computed(() => {
  const tree = [
    { title: '全部', key: -1 },
    { title: '未分组', key: 0 },
  ]
  const format = (arr) => arr.map(g => ({
    title: g.name, key: g.group_id,
    children: g.children ? format(g.children) : undefined,
  }))
  return [...tree, ...format(groups.value)]
})

function show() { visible.value = true; loadGroups(); loadList() }

function loadGroups() {
  GroupApi.list({}).then(res => {
    const d = res?.data?.list || res?.data || []
    groups.value = Array.isArray(d) ? d : (d?.data || [])
  }).catch(() => {})
}

function loadList() {
  loading.value = true
  FileApi.list({ ...queryParam }).then(res => {
    const d = res?.data?.list || res?.data || {}
    list.value = Array.isArray(d) ? d : (d?.data || [])
    total.value = d?.total || 0
    loading.value = false
  }).catch(() => { loading.value = false })
}

function onSelectGroup(keys) {
  queryParam.groupId = keys?.[0] ?? 0
  queryParam.page = 1
  loadList()
}

function onSelect(index) {
  if (!props.multiple) { selected.value = [index]; return }
  const idx = selected.value.indexOf(index)
  if (idx > -1) { selected.value.splice(idx, 1) }
  else if (selected.value.length < props.maxNum) { selected.value.push(index) }
  else { message.warning(`最多可选${props.maxNum}个文件`) }
}

function handleSubmit() {
  const items = selected.value.map(i => list.value[i]).filter(Boolean)
  emit('handleSubmit', items)
  visible.value = false
  selected.value = []
}

async function onUpload(info) {
  const formData = new FormData()
  formData.append('iFile', info.file)
  formData.append('groupId', queryParam.groupId)
  try {
    const res = await FileApi.uploadImage(formData)
    if (res?.status === 200) { message.success('上传成功'); loadList() }
  } catch { message.error('上传失败') }
  info.onSuccess?.()
}

function handleAddGroup() {
  if (!newGroupName.value.trim()) { message.warning('请输入分组名称'); return }
  groupSaving.value = true
  GroupApi.add({ name: newGroupName.value.trim() }).then(() => {
    message.success('添加成功'); showAddGroup.value = false; newGroupName.value = ''; loadGroups()
  }).finally(() => { groupSaving.value = false })
}

defineExpose({ show })
</script>

<style scoped>
.files-modal { display: flex; gap: 16px; min-height: 400px; }
.fm-left { width: 140px; flex-shrink: 0; border-right: 1px solid #f0f0f0; padding-right: 8px; overflow-y: auto; display: flex; flex-direction: column; }
.fm-right { flex: 1; display: flex; flex-direction: column; }
.fm-toolbar { display: flex; gap: 10px; align-items: center; margin-bottom: 12px; }
.file-grid { flex: 1; overflow: hidden; }
.file-card {
  width: 100px; height: 120px; float: left; margin: 6px;
  cursor: pointer; border: 1px solid rgba(0,0,0,.05); border-radius: 2px;
  padding: 4px; transition: all .2s; position: relative;
}
.file-card:hover { border-color: #d6893b; }
.file-card.active { border-color: #d6893b; }
.file-card .img-cover {
  margin: 0 auto; width: 90px; height: 90px;
  background: no-repeat center center / 100%;
}
.file-name { font-size: 12px; text-align: center; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.select-overlay {
  position: absolute; top: 0; left: 0; right: 0; bottom: 0;
  background: rgba(214,137,59,.25); display: none; align-items: center; justify-content: center;
  border-radius: 6px; font-size: 20px; color: #fff;
}
.file-card.active .select-overlay { display: flex; }
.fm-footer { margin-top: 12px; display: flex; justify-content: space-between; align-items: center; }
</style>
