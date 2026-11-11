<template>
  <a-modal v-model:open="visible" :title="title" :confirm-loading="loading" @ok="onOk" @cancel="onCancel" width="560px">
    <a-form ref="formRef" :model="form" :rules="rules" :label-col="{ span: 6 }" :wrapper-col="{ span: 16 }">
      <a-form-item label="真实姓名" name="real_name">
        <a-input v-model:value="form.real_name" placeholder="请输入真实姓名" />
      </a-form-item>
      <a-form-item label="登录用户名" name="user_name" extra="用于后台登录">
        <a-input v-model:value="form.user_name" placeholder="请输入登录用户名" />
      </a-form-item>
      <a-form-item label="所属角色" name="roles" v-if="!form.is_super">
        <a-tree-select
          v-model:value="form.roles"
          :tree-data="roleTree"
          tree-checkable
          :tree-check-strictly="true"
          tree-default-expand-all
          placeholder="请选择角色"
        />
      </a-form-item>
      <a-form-item label="所属部门" name="department_id" extra="用于数据归属与经理可见范围">
        <a-tree-select
          v-model:value="form.department_id"
          :tree-data="deptTree"
          allow-clear
          tree-default-expand-all
          placeholder="请选择所属部门"
        />
      </a-form-item>
      <a-form-item label="超级管理员" name="is_super" extra="超管拥有全部权限，无需分配角色">
        <a-switch v-model:checked="form.is_super" />
      </a-form-item>
      <a-form-item label="登录密码" name="password">
        <a-input-password v-model:value="form.password" placeholder="请输入登录密码" />
      </a-form-item>
      <a-form-item label="确认密码" name="password_confirm">
        <a-input-password v-model:value="form.password_confirm" placeholder="请再次输入密码" />
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
import { getTreeList as getDepts } from '@/api/department'

const props = defineProps({ roleList: { type: Array, default: () => [] } })
const emit = defineEmits(['ok'])

const visible = ref(false)
const loading = ref(false)
const title = ref('新增管理员')
const formRef = ref()
const form = reactive({ real_name: '', user_name: '', roles: [], password: '', password_confirm: '', sort: 100, is_super: false, department_id: undefined })
const deptTree = ref([])

const rules = computed(() => ({
  real_name: [{ required: true, min: 2, message: '请输入至少2个字符' }],
  user_name: [{ required: true, min: 4, message: '请输入至少4个字符' }],
  roles: form.is_super ? [] : [{ required: true, type: 'array', min: 1, message: '请至少选择一个角色', trigger: 'change' }],
  password: [{ required: true, min: 6, message: '请输入至少6个字符' }],
  password_confirm: [{ required: true, message: '请确认密码' }, { validator: validatePwdConfirm }],
  sort: [{ required: true, type: 'number', message: '请输入排序' }],
}))

const roleTree = computed(() => {
  const toTree = (list) => list.map((r) => ({ title: r.role_name, key: r.role_id, value: r.role_id, children: r.children ? toTree(r.children) : undefined }))
  return toTree(props.roleList)
})

function validatePwdConfirm(_rule, value) {
  if (value !== form.password) return Promise.reject('两次输入的密码不一致')
  return Promise.resolve()
}

function open() { resetForm(); loadDepts(); visible.value = true }

function loadDepts() {
  getDepts({}).then(res => {
    // 后端 /manage.department/tree 返回 { tree: [...] }，已是嵌套结构
    const tree = res?.data?.tree || []
    deptTree.value = toTreeData(tree)
  }).catch(() => {})
}
function toTreeData(list) {
  if (!Array.isArray(list)) return []
  return list.map(d => ({
    title: d.department_name,
    value: d.id,
    key: d.id,
    children: d.children && d.children.length ? toTreeData(d.children) : undefined,
  }))
}
function resetForm() {
  Object.assign(form, { real_name: '', user_name: '', roles: [], password: '', password_confirm: '', sort: 100, is_super: false, department_id: undefined })
}

async function onOk() {
  try { await formRef.value.validate() } catch { return }
  loading.value = true
  try {
    const res = await UserApi.add({ ...form, department_id: form.department_id || 0 })
    if (res.status === 200) { message.success('添加成功'); visible.value = false; emit('ok') }
  } finally { loading.value = false }
}

function onCancel() { visible.value = false }
defineExpose({ open })
</script>
