<template>
  <div class="select-image">
    <a-tooltip v-if="tips" :title="tips">
      <div v-if="imgUrl" class="img-box" :style="{ width: width + 'px', height: (height || width) + 'px' }" @click="filesModal?.show()">
        <img :src="imgUrl" alt="" />
        <div class="img-mask"></div>
        <div class="img-replace">替换</div>
      </div>
      <div v-else class="upload-box" :style="{ width: width + 'px', height: width + 'px' }" @click="filesModal?.show()">
        <plus-outlined :style="{ fontSize: width * 0.4 + 'px', color: '#d9d9d9' }" />
      </div>
    </a-tooltip>
    <template v-else>
      <div v-if="imgUrl" class="img-box" :style="{ width: width + 'px', height: (height || width) + 'px' }" @click="filesModal?.show()">
        <img :src="imgUrl" alt="" />
        <div class="img-mask"></div>
        <div class="img-replace">替换</div>
      </div>
      <div v-else class="upload-box" :style="{ width: width + 'px', height: width + 'px' }" @click="filesModal?.show()">
        <plus-outlined :style="{ fontSize: width * 0.4 + 'px', color: '#d9d9d9' }" />
      </div>
    </template>

    <FilesModal ref="filesModal" :multiple="false" @handle-submit="handleSelect" />
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { PlusOutlined } from '@ant-design/icons-vue'
import FilesModal from '@/components/Modal/FilesModal/index.vue'

const props = defineProps({
  modelValue: { type: String, default: '' },
  width: { type: Number, default: 80 },
  height: { type: Number, default: 0 },
  tips: { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue', 'change', 'update'])

const filesModal = ref()
const imgUrl = ref('')

watch(() => props.modelValue, (val) => {
  if (val && String(val).trim()) {
    const base = (window.publicConfig?.BASE_API || '').replace(/\/index\.php.*$/, '') + '/uploads/'
    imgUrl.value = String(val).startsWith('http') ? String(val) : base + String(val)
  } else {
    imgUrl.value = ''
  }
}, { immediate: true })

function handleSelect(items) {
  if (!items.length) return
  const file = items[0]
  imgUrl.value = file.preview_url || ''
  const path = file.file_path || ''
  emit('update:modelValue', file.preview_url) // v-model 绑定 preview_url
  emit('change', imgUrl.value)                 // 兼容 v1 的 change 事件
  emit('update', file)                          // 兼容 v1 的 update 事件
}
</script>

<style scoped>
.select-image { display: inline-block; }
.upload-box {
  border: 1px dashed #e2e2e2; border-radius: 4px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; transition: all .2s;
}
.upload-box:hover { border-color: #40a9ff; color: #40a9ff; }
.img-box {
  position: relative; cursor: pointer; border-radius: 4px; overflow: hidden;
  border: 1px solid #e8e8e8;
}
.img-box img { width: 100%; height: 100%; object-fit: cover; }
.img-mask {
  position: absolute; inset: 0; background: rgba(0,0,0,.5);
  display: none; z-index: 1; border-radius: 4px;
}
.img-replace {
  position: absolute; top: 0; left: 0; right: 0; bottom: 0; margin: auto;
  width: 60px; height: 30px; font-size: 12px; text-align: center;
  display: none; z-index: 2; background: #fff; border-radius: 4px;
  font-weight: 600; color: #595961; line-height: 30px;
}
.img-box:hover .img-mask, .img-box:hover .img-replace { display: block; }
</style>
