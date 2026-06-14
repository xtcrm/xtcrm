<template>
  <a-modal :open="props.visible" :title="isEdit ? '编辑部门' : '新增部门'" :confirm-loading="loading" @ok="onOk" @cancel="$emit('cancel')" width="560px">
    <a-form ref="formRef" :model="form" :rules="rules" :label-col="{ span: 6 }" :wrapper-col="{ span: 16 }">
      <a-form-item label="上级部门" name="parent_id">
        <a-tree-select v-model:value="form.parent_id" :tree-data="deptTree" allow-clear tree-default-expand-all placeholder="顶级部门" />
      </a-form-item>
      <a-form-item label="部门名称" name="department_name">
        <a-input v-model:value="form.department_name" placeholder="请输入部门名称" />
      </a-form-item>
      <a-form-item label="部门编码" name="department_code">
        <a-input v-model:value="form.department_code" placeholder="请输入部门编码" />
      </a-form-item>
      <a-form-item label="排序" name="sort">
        <a-input-number v-model:value="form.sort" :min="0" :max="999" style="width:100%" />
      </a-form-item>
      <a-form-item label="同步方向" name="sync_direction">
        <a-select v-model:value="form.sync_direction" :options="syncOptions" />
      </a-form-item>
      <a-form-item label="描述" name="description">
        <a-textarea v-model:value="form.description" :rows="3" placeholder="部门描述" />
      </a-form-item>
      <a-form-item label="状态" name="status">
        <a-switch v-model:checked="form.status" checked-children="启用" un-checked-children="禁用" />
      </a-form-item>
    </a-form>
  </a-modal>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { message } from 'ant-design-vue'
import * as Api from '@/api/department'

const props = defineProps({ visible: Boolean, record: Object, departmentTree: { type: Array, default: () => [] } })
const emit = defineEmits(['ok', 'cancel'])

const isEdit = computed(() => !!props.record)
const loading = ref(false)
const formRef = ref()
const form = reactive({ parent_id: 0, department_name: '', department_code: '', sort: 100, sync_direction: 0, description: '', status: true })

const syncOptions = [
  { value: 0, label: '不同步' },
  { value: 1, label: '系统→企业微信' },
  { value: 2, label: '企业微信→系统' },
  { value: 3, label: '双向同步' },
]

const rules = {
  department_name: [{ required: true, message: '请输入部门名称' }],
}

const deptTree = computed(() => {
  const exclId = props.record?.id
  const toTree = (list) => list
    .filter((d) => d.id !== exclId)
    .map((d) => ({ title: d.department_name, key: d.id, value: d.id, children: d.children ? toTree(d.children) : undefined }))
  return [{ title: '顶级部门', key: 0, value: 0, children: toTree(props.departmentTree) }]
})

watch(() => props.visible, (val) => {
  if (val) {
    if (props.record) {
      const r = props.record
      Object.assign(form, { parent_id: r.parent_id || 0, department_name: r.department_name, department_code: r.department_code || '', sort: r.sort || 100, sync_direction: r.sync_direction || 0, description: r.description || '', status: r.status !== 0 })
    } else {
      Object.assign(form, { parent_id: 0, department_name: '', department_code: '', sort: 100, sync_direction: 0, description: '', status: true })
    }
  }
})

async function onOk() {
  try { await formRef.value.validate() } catch { return }
  loading.value = true
  try {
    const data = { ...form, status: form.status ? 1 : 0 }
    let res
    if (isEdit.value) res = await Api.edit(props.record.id, data)
    else res = await Api.add(data)
    if (res.status === 200) { message.success(isEdit.value ? '修改成功' : '添加成功'); emit('ok') }
  } finally { loading.value = false }
}
</script>
