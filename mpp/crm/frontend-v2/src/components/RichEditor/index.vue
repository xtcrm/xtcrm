<template>
  <div class="rich-editor" :style="{ minHeight: height + 'px' }">
    <div ref="editorRef" class="editor-box" :style="{ height: height + 'px' }"></div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, watch } from 'vue'
import { createEditor } from '@wangeditor/editor'
import '@wangeditor/editor/dist/css/style.css'

const props = defineProps({
  modelValue: { type: String, default: '' },
  height: { type: Number, default: 300 },
})
const emit = defineEmits(['update:modelValue'])

const editorRef = ref()
let editor = null

onMounted(() => {
  if (!editorRef.value) return
  editor = createEditor({
    selector: editorRef.value,
    html: props.modelValue || '',
    config: {
      placeholder: '请输入内容...',
      autoFocus: false,
    },
  })
  editor.on('change', () => {
    emit('update:modelValue', editor.getHtml())
  })
})

watch(() => props.modelValue, (val) => {
  if (editor && val !== editor.getHtml()) {
    editor.setHtml(val || '')
  }
})

onBeforeUnmount(() => {
  if (editor) { editor.destroy(); editor = null }
})
</script>

<style scoped>
.rich-editor {
  border: 1px solid #d9d9d9;
  border-radius: 4px;
  overflow: hidden;
}
.editor-box {
  overflow-y: auto;
}
</style>
