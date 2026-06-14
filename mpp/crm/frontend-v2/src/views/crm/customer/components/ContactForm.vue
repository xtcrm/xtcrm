<template>
  <a-modal :title="mdl?.id ? '编辑联系人' : '新增联系人'" :width="520" :open="open" :confirm-loading="loading"
    :mask-closable="false" @ok="handleSubmit" @cancel="handleCancel">
    <a-spin :spinning="loading">
      <a-form ref="formRef" :model="formState" :label-col="{ span: 6 }" :wrapper-col="{ span: 18 }">
        <a-row :gutter="16">
          <a-col :span="12">
            <a-form-item label="姓名" name="contact_name" :rules="[{ required: true, message: '请输入姓名' }]">
              <a-input v-model:value="formState.contact_name" placeholder="联系人姓名" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="职位" name="position">
              <a-input v-model:value="formState.position" placeholder="职位" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="部门" name="department">
              <a-input v-model:value="formState.department" placeholder="部门" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="性别" name="gender">
              <a-select v-model:value="formState.gender">
                <a-select-option :value="0">未知</a-select-option>
                <a-select-option :value="1">男</a-select-option>
                <a-select-option :value="2">女</a-select-option>
              </a-select>
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="手机" name="mobile">
              <a-input v-model:value="formState.mobile" placeholder="手机号" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="固话" name="telephone">
              <a-input v-model:value="formState.telephone" placeholder="固话" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="邮箱" name="email">
              <a-input v-model:value="formState.email" placeholder="邮箱" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="微信" name="wechat">
              <a-input v-model:value="formState.wechat" placeholder="微信号" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="首要联系人" name="is_primary">
              <a-switch v-model:checked="formState.is_primary" />
            </a-form-item>
          </a-col>
          <a-col :span="24">
            <a-form-item label="备注" name="remark" :label-col="{ span: 3 }" :wrapper-col="{ span: 20 }">
              <a-textarea v-model:value="formState.remark" placeholder="备注" :rows="2" />
            </a-form-item>
          </a-col>
        </a-row>
      </a-form>
    </a-spin>
  </a-modal>
</template>

<script setup>
import { ref, reactive, watch, nextTick } from 'vue'
import { message } from 'ant-design-vue'
import { add, edit } from '@/api/crm/contact'

const props = defineProps({ open: Boolean, mdl: Object, customerId: [Number, String] })
const emit = defineEmits(['update:open', 'ok'])

const formRef = ref()
const loading = ref(false)

const formState = reactive({
  contact_name: '', position: '', department: '', gender: 0,
  mobile: '', telephone: '', email: '', wechat: '',
  is_primary: false, remark: '',
})

watch(() => props.open, (v) => {
  if (v && props.mdl?.id) {
    nextTick(() => {
      formRef.value?.resetFields()
      Object.assign(formState, {
        contact_name: props.mdl.contact_name || '',
        position: props.mdl.position || '',
        department: props.mdl.department || '',
        gender: props.mdl.gender ?? 0,
        mobile: props.mdl.mobile || '',
        telephone: props.mdl.telephone || '',
        email: props.mdl.email || '',
        wechat: props.mdl.wechat || '',
        is_primary: !!props.mdl.is_primary,
        remark: props.mdl.remark || '',
      })
    })
  } else if (v) {
    nextTick(() => { formRef.value?.resetFields(); formState.gender = 0; formState.is_primary = false })
  }
})

function handleSubmit() {
  formRef.value.validate().then((values) => {
    loading.value = true
    values.customer_id = props.customerId
    values.is_primary = values.is_primary ? 1 : 0
    const fn = props.mdl?.id ? edit : add
    if (props.mdl?.id) values.id = props.mdl.id
    fn(values).then(() => {
      message.success('保存成功')
      loading.value = false
      emit('update:open', false)
      emit('ok')
    }).catch(() => { loading.value = false })
  }).catch(() => {})
}

function handleCancel() { emit('update:open', false) }
</script>
