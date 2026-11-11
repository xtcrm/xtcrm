<template>
  <div>
    <div class="rich-editor" :style="{ minHeight: height + 'px' }">
      <div ref="toolbarRef" class="editor-toolbar"></div>
      <div ref="editorRef" class="editor-box" :style="{ height: (height - 44) + 'px' }"></div>
    </div>
    <FilesModal ref="filesModal" :multiple="false" @handle-submit="onSelectImage" />
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from 'vue'
import { createEditor, createToolbar } from '@wangeditor/editor'
import '@wangeditor/editor/dist/css/style.css'
import FilesModal from '@/components/Modal/FilesModal/index.vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  height: { type: Number, default: 400 },
})
const emit = defineEmits(['update:modelValue'])

const toolbarRef = ref()
const editorRef = ref()
const filesModal = ref()
let editor = null
let pendingInsertFn = null

function onSelectImage(rows) {
  const url = rows?.[0]?.preview_url || rows?.[0]?.file_path || ''
  if (url && pendingInsertFn) {
    pendingInsertFn(url, rows[0]?.file_name || '', '')
    pendingInsertFn = null
    filesModal.value?.hide?.()
  }
}

function insertMention(label, href) {
  if (!editor) return
  editor.restoreSelection()
  editor.focus()
  editor.dangerouslyInsertHtml(
    `<a href="${href}" class="mention-link" data-mention="true" target="_blank">@${label}</a>&nbsp;`
  )
}

onMounted(() => {
  const init = () => {
    if (!editorRef.value || !toolbarRef.value) { setTimeout(init, 50); return }
    editor = createEditor({
      selector: editorRef.value,
      html: props.modelValue || '',
      config: { placeholder: '请输入内容...', autoFocus: false },
    })
    createToolbar({
      editor,
      selector: toolbarRef.value,
      config: {
        toolbarKeys: [
          'headerSelect', '|', 'bold', 'italic', 'underline', 'color', 'bgColor', '|',
          'fontSize', 'fontFamily', 'lineHeight', '|', 'bulletedList', 'numberedList', '|',
          'insertImage', 'uploadImage', 'insertTable', '|', 'undo', 'redo', '|', 'fullScreen',
        ],
      },
    })
    // 挂载 uploadImage 的自定义图片选择
    const uploadCfg = editor.getMenuConfig('uploadImage') || {}
    uploadCfg.customBrowseAndUpload = function (insertFn) {
      pendingInsertFn = insertFn
      filesModal.value?.show?.()
    }
    // 空内容统一回传 ''，别把 '<p><br></p>' 当成有内容交给父组件
    editor.on('change', () => emit('update:modelValue', editor.isEmpty() ? '' : editor.getHtml()))
  }
  nextTick(() => init())
})

watch(() => props.modelValue, (val) => {
  if (!editor) return
  const next = val || ''
  // 和 model 一致就不动，避免每次输入都 setHtml 重建 DOM、丢光标
  if (next === (editor.isEmpty() ? '' : editor.getHtml())) return
  nextTick(() => {
    if (!editor) return
    // 之前只判断 val 为真就跳过，父组件置空时输入框不跟着清空
    if (!next) { editor.clear(); return }
    editor.setHtml(next)
  })
})

onBeforeUnmount(() => {
  if (editor) { editor.destroy(); editor = null }
})

defineExpose({
  insertMention,
  clear: () => editor && editor.clear(),
  focus: () => editor && editor.focus(),
})
</script>

<style scoped>
.rich-editor { border: 1px solid #d9d9d9; border-radius: 4px; }
.editor-toolbar { border-bottom: 1px solid #e8e8e8; }
.editor-box { overflow-y: auto; }
</style>
