<template>
  <a-modal title="新增角色" :width="720" :open="visible" :confirm-loading="loading" :mask-closable="false" @ok="handleSubmit" @cancel="handleCancel">
    <a-spin :spinning="loading">
      <a-form ref="formRef" :model="form" :rules="rules" :label-col="{ span: 7 }" :wrapper-col="{ span: 13 }">
        <a-form-item label="角色名称" name="role_name">
          <a-input v-model:value="form.role_name" placeholder="请输入角色名称" />
        </a-form-item>
        <a-form-item label="上级角色" name="parent_id">
          <a-tree-select v-model:value="form.parent_id" :tree-data="roleTree" allow-clear tree-default-expand-all placeholder="顶级角色" />
        </a-form-item>
        <a-form-item label="菜单权限" name="menus" extra="设置该角色有权操作的功能">
          <a-tree ref="menuTreeRef" v-model:checkedKeys="checkedRaw" :tree-data="menuTree" checkable check-strictly :auto-expand-parent="false" @check="onCheckedMenu" />
        </a-form-item>
        <a-form-item label="排序" name="sort" extra="数字越小越靠前">
          <a-input-number v-model:value="form.sort" :min="0" style="width:100%" />
        </a-form-item>
      </a-form>
    </a-spin>
  </a-modal>
</template>

<script setup>
import { ref, reactive, computed, watch } from 'vue'
import { message } from 'ant-design-vue'
import * as RoleApi from '@/api/role'

const props = defineProps({ roleList: { type: Array, default: () => [] }, menuList: { type: Array, default: () => [] } })
const emit = defineEmits(['ok'])

const visible = ref(false)
const loading = ref(false)
const formRef = ref()
const menuTreeRef = ref()
const form = reactive({ role_name: '', parent_id: undefined, sort: 100 })
const checkedKeys = ref([])
const checkedRaw = ref([])

const rules = {
  role_name: [{ required: true, min: 2, message: '请输入至少2个字符' }],
  sort: [{ required: true, type: 'number', message: '请输入排序' }],
}

const roleTree = computed(() => {
  const build = list => list.map(r => ({ title: r.role_name, key: r.role_id, value: r.role_id, children: r.children?.length ? build(r.children) : undefined }))
  return [{ title: '顶级角色', key: 0, value: 0, children: build(props.roleList) }]
})

const menuTree = computed(() => {
  const map = {}, roots = []
  props.menuList.forEach(m => { map[m.menu_id] = { title: m.name, key: m.menu_id, parentKey: m.parent_id, children: [] } })
  props.menuList.forEach(m => { const n = map[m.menu_id]; if (n) { if (m.parent_id && map[m.parent_id]) map[m.parent_id].children.push(n); else roots.push(n) } })
  const clean = nodes => nodes.forEach(n => { if (!n.children.length) delete n.children; else clean(n.children) })
  clean(roots)
  return roots
})

function getAllKeys(nodes) { const ks = []; const w = n => { ks.push(n.key); n.children?.forEach(w) }; nodes.forEach(w); return ks }

function findNode(key, nodes) { for (const n of nodes) { if (n.key === key) return n; const f = findNode(key, n.children || []); if (f) return f }; return null }

function toArr(v) { if (Array.isArray(v)) return v; if (v && Array.isArray(v.checked)) return v.checked; return [] }

function onCheckedMenu(keys, { checked, node }) {
  const nd = findNode(node.eventKey, menuTree.value)
  if (!nd) return
  let arr = toArr(keys)
  if (checked) {
    if (nd.children) { const cks = getAllKeys(nd.children); arr = [...new Set([...arr, ...cks])] }
    const pks = getParentKeys(nd.parentKey)
    if (pks.length) arr = [...new Set([...arr, ...pks])]
  } else {
    if (nd.children) { const cks = getAllKeys(nd.children); arr = arr.filter(k => !cks.includes(k)) }
  }
  checkedKeys.value = arr; checkedRaw.value = arr
}

function getParentKeys(pk) { if (!pk) return []; const p = findNode(pk, menuTree.value); if (!p) return []; const a = [p.key]; if (p.parentKey) a.push(...getParentKeys(p.parentKey)); return a }

function open() { resetForm(); visible.value = true }
function resetForm() { form.role_name = ''; form.parent_id = undefined; form.sort = 100; checkedKeys.value = []; checkedRaw.value = [] }

async function handleSubmit() {
  try { await formRef.value.validate() } catch { return }
  loading.value = true
  try {
    const tree = menuTreeRef.value
    const menus = checkedKeys.value.map(Number).filter(Boolean)
    const res = await RoleApi.add({ role_name: form.role_name, parent_id: form.parent_id, sort: form.sort, menus })
    if (res.status === 200) { message.success('添加成功'); visible.value = false; emit('ok') }
  } finally { loading.value = false }
}

function handleCancel() { visible.value = false; formRef.value?.resetFields(); checked.value = [] }
defineExpose({ open })
</script>
