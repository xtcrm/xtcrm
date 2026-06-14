<template>
  <a-modal v-model:open="visible" :title="title" :confirm-loading="loading" @ok="onOk" @cancel="onCancel" width="560px">
    <a-form ref="formRef" :model="form" :rules="rules" :label-col="{ span: 6 }" :wrapper-col="{ span: 16 }">
      <a-form-item label="真实姓名" name="real_name">
        <a-input v-model:value="form.real_name" />
      </a-form-item>
      <a-form-item label="登录用户名" name="user_name">
        <a-input v-model:value="form.user_name" disabled />
      </a-form-item>
      <a-form-item v-if="!record.is_super" label="所属角色" name="roles">
        <a-tree-select
          v-model:value="form.roles"
          :tree-data="roleTree"
          tree-checkable
          :tree-check-strictly="true"
          tree-default-expand-all
          placeholder="请选择角色"
        />
      </a-form-item>
      <a-form-item label="登录密码" name="password">
        <a-input-password v-model:value="form.password" placeholder="留空则不修改" />
      </a-form-item>
      <a-form-item label="确认密码" name="password_confirm">
        <a-input-password v-model:value="form.password_confirm" placeholder="留空则不修改" />
      </a-form-item>
      <a-form-item label="排序" name="sort">
        <a-input-number v-model:value="form.sort" :min="0" style="width:100%" />
      </a-form-item>
    </a-form>
  </a-modal>
</template>

<script setup>
import { ref, reactive, computed } from 'vue'
import { message } from 'ant-design-vue'
import * as UserApi from '@/api/user'

const props = defineProps({ roleList: { type: Array, default: () => [] } })
const emit = defineEmits(['ok'])

const visible = ref(false)
const loading = ref(false)
const title = ref('编辑管理员')
const formRef = ref()
const record = reactive({})
const form = reactive({ real_name: '', user_name: '', roles: [], password: '', password_confirm: '', sort: 100 })

const rules = computed(() => ({
  real_name: [{ required: true, min: 2, message: '请输入至少2个字符' }],
  roles: record.is_super ? [] : [{ required: true, type: 'array', min: 1, message: '请至少选择一个角色' }],
  password: [{ min: 6, message: '请输入至少6个字符' }],
  password_confirm: [{ validator: validatePwdConfirm }],
  sort: [{ required: true, type: 'number', message: '请输入排序' }],
}))

const roleTree = computed(() => {
  const toTree = (list) => list.map((r) => ({ title: r.role_name, key: r.role_id, value: r.role_id, children: r.children ? toTree(r.children) : undefined }))
  return toTree(props.roleList)
})

function validatePwdConfirm(_rule, value) {
  if (form.password && value !== form.password) return Promise.reject('两次输入的密码不一致')
  return Promise.resolve()
}

function open(row) {
  Object.assign(record, row)
  form.real_name = row.real_name
  form.user_name = row.user_name
  form.sort = row.sort
  form.password = ''
  form.password_confirm = ''
  form.roles = row.roleIds || []
  visible.value = true
}

async function onOk() {
  try { await formRef.value.validate() } catch { return }
  loading.value = true
  const data = { user_name: form.user_name, real_name: form.real_name, sort: form.sort }
  if (!record.is_super) data.roles = form.roles
  if (form.password) { data.password = form.password; data.password_confirm = form.password_confirm }
  try {
    const res = await UserApi.edit(record.store_user_id, data)
    if (res.status === 200) { message.success('修改成功'); visible.value = false; emit('ok') }
  } finally { loading.value = false }
}

function onCancel() { visible.value = false }
defineExpose({ open })
</script>
