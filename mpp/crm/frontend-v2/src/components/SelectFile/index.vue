<template>
  <div class="select-file">
    <!-- 真正的文件输入藏起来，由 pick() 或默认方块触发 -->
    <input ref="inputRef" type="file" class="sf-input" :multiple="multiple"
      :accept="accept" :disabled="disabled" @change="onChange" />

    <a-tooltip :title="tips">
      <span class="sf-trigger" @click="pick">
        <slot>
          <div v-if="!noTrigger" class="sf-add" :style="{ width: width + 'px', height: height + 'px' }">
            <file-add-outlined :style="{ fontSize: Math.round(width * 0.28) + 'px' }" />
            <span class="sf-add-text">{{ label }}</span>
          </div>
        </slot>
      </span>
    </a-tooltip>

    <!-- 已选文件列表；调用方自己画附件区时可 showList=false 关掉 -->
    <div v-if="showList && files.length" class="sf-list">
      <div v-for="(f, i) in files" :key="f.file_id || i" class="sf-item">
        <span class="sf-ext">{{ (f.file_ext || '?').toUpperCase() }}</span>
        <a :href="f.file_url || f.preview_url" target="_blank" class="sf-name" :title="f.file_name">{{ f.file_name }}</a>
        <a v-if="!disabled" class="sf-del" @click.prevent="remove(i)">删除</a>
      </div>
    </div>
  </div>
</template>

<script setup>
/**
 * 文件选择 / 上传组件 —— 与 SelectImage 并列的一套：
 *   图片用 SelectImage（图片库，可复用已上传的图）
 *   文件用 SelectFile（任意类型，直接上传到 /upload/file）
 *
 * v-model 绑定的是**文件对象数组**（不是 URL 字符串，这点与 SelectImage 不同）：
 *   [{ file_id, file_name, file_path, file_url, file_ext, file_size }]
 * 拿到 file_id 后即可挂到业务表上（后端只存 file_id，文件统一在 yoshop_upload_file 里管理）。
 *
 * 触发器可以是默认方块、默认插槽，或 noTrigger + 外部调 pick()（例如放进下拉菜单里）。
 */
import { ref, watch } from 'vue'
import { message } from 'ant-design-vue'
import { FileAddOutlined } from '@ant-design/icons-vue'
import { uploadFile } from '@/api/files'

const props = defineProps({
  /** 文件对象数组 */
  modelValue: { type: Array, default: () => [] },
  /** 是否允许多选 */
  multiple: { type: Boolean, default: false },
  /** 允许的扩展名，如 '.pdf,.docx'；留空不限制 */
  accept: { type: String, default: '' },
  tips: { type: String, default: '' },
  width: { type: Number, default: 72 },
  height: { type: Number, default: 72 },
  disabled: { type: Boolean, default: false },
  /** 是否自渲染已选列表（调用方自己画附件区就传 false） */
  showList: { type: Boolean, default: true },
  /** 不渲染默认方块 —— 由外部调 pick() 触发（放进菜单里时用） */
  noTrigger: { type: Boolean, default: false },
  label: { type: String, default: '上传文件' },
})
const emit = defineEmits(['update:modelValue', 'change', 'update'])

const inputRef = ref()
const files = ref([])
const uploading = ref(false)

watch(() => props.modelValue, (val) => {
  files.value = Array.isArray(val) ? val.slice() : []
}, { immediate: true, deep: true })

/** 打开系统文件选择框 */
function pick() {
  if (props.disabled) return
  inputRef.value?.click()
}

function onChange(e) {
  const picked = Array.from(e.target.files || [])
  e.target.value = '' // 允许再次选同一个文件
  if (!picked.length) return

  uploading.value = true
  const jobs = picked.map(file => {
    const fd = new FormData()
    fd.append('iFile', file)   // 平台上传约定的文件域名
    return uploadFile(fd).catch(() => null)
  })

  Promise.all(jobs).then(results => {
    uploading.value = false
    const okItems = results.filter(r => r && r.status === 200).map(r => r.data)
    const failed = results.length - okItems.length

    if (okItems.length) {
      const next = props.multiple ? files.value.concat(okItems) : [okItems[okItems.length - 1]]
      files.value = next
      emit('update:modelValue', next)
      emit('change', next)
      // 逐条发 update，方便调用方「上传一个就挂一个」
      okItems.forEach(f => emit('update', f))
    }

    if (failed === results.length) message.error('上传失败')
    else if (failed > 0) message.warning(`已上传 ${okItems.length} 个，${failed} 个失败`)
    else if (results.length > 1) message.success(`已上传 ${okItems.length} 个文件`)
  })
}

function remove(i) {
  const next = files.value.slice()
  next.splice(i, 1)
  files.value = next
  emit('update:modelValue', next)
  emit('change', next)
}

defineExpose({ pick })
</script>

<style scoped>
.select-file { display: inline-block; }
.sf-input { display: none; }
.sf-trigger { display: inline-block; }

.sf-add {
  border: 1px dashed #d9d9d9;
  border-radius: 4px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #999;
  background: #fff;
  transition: all .2s;
}
.sf-add:hover { border-color: #d6893b; color: #d6893b; }
.sf-add-text { font-size: 11px; margin-top: 2px; }

.sf-list { margin-top: 6px; }
.sf-item {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  line-height: 22px;
}
.sf-ext {
  flex: none;
  font-size: 10px;
  font-weight: 600;
  color: #d6893b;
  border: 1px solid #f0d9bd;
  border-radius: 2px;
  padding: 0 3px;
}
.sf-name {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.sf-del { flex: none; color: #ff4d4f; }
</style>
