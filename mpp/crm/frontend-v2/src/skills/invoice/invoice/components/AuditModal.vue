<template>
  <a-modal :title="title" :open="visible" :confirm-loading="submitting" @ok="handleOk" @cancel="$emit('update:visible', false)">
    <a-form :model="form">
      <a-form-item label="审核备注">
        <a-textarea v-model:value="form.audit_remark" :rows="3" placeholder="驳回时请填写原因" />
      </a-form-item>
    </a-form>
  </a-modal>
</template>

<script setup>
import { ref, watch } from 'vue'
import { message } from 'ant-design-vue'
import { auditInvoice } from '../../api'

const props = defineProps({ visible: Boolean, record: Object, status: Number, title: String })
const emit = defineEmits(['update:open', 'ok'])
const submitting = ref(false)
const form = ref({ audit_remark: '' })

watch(() => props.visible, v => { if (v) form.value.audit_remark = '' })

function handleOk() {
  if (props.status === 4 && !form.value.audit_remark) {
    message.warning('驳回请填写原因')
    return
  }
  submitting.value = true
  auditInvoice({ id: props.record?.id, status: props.status, audit_remark: form.value.audit_remark })
    .then(() => { message.success('操作成功'); emit('ok'); emit('update:open', false) })
    .finally(() => { submitting.value = false })
}
</script>
