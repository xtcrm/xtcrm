<template>
  <a-form ref="formRef" :model="formState" :label-col="{ span: 6 }" :wrapper-col="{ span: 18 }">
    <a-divider orientation="left">基本信息</a-divider>
    <a-row :gutter="16">
      <a-col :span="12"><a-form-item label="客户名称" name="customer_name" :rules="rules"><a-input v-model:value="formState.customer_name" placeholder="公司全称" /></a-form-item></a-col>
      <a-col :span="12"><a-form-item label="简称" name="short_name"><a-input v-model:value="formState.short_name" placeholder="简称" /></a-form-item></a-col>
      <a-col :span="12"><a-form-item label="行业" name="industry_id" :rules="[{ required: true, message: '请选择行业' }]"><a-select v-model:value="formState.industry_id" placeholder="选择行业" show-search :filter-option="filterOption" @change="(v) => setDictName('industry', v, 'industry')"><a-select-option v-for="op in dicts.industry" :key="op.value" :value="op.value">{{ op.name }}</a-select-option></a-select></a-form-item></a-col>
      <a-col :span="12"><a-form-item label="客户分组" name="group_id" :rules="[{ required: true, message: '请选择客户分组' }]"><a-select v-model:value="formState.group_id" placeholder="选择分组" show-search :filter-option="filterOption" @change="(v) => setDictName('customer_group', v, 'customer_group')"><a-select-option v-for="op in dicts.customer_group" :key="op.value" :value="op.value">{{ op.name }}</a-select-option></a-select></a-form-item></a-col>
      <a-col :span="12"><a-form-item label="客户等级" name="level_id" :rules="[{ required: true, message: '请选择客户等级' }]"><a-select v-model:value="formState.level_id" placeholder="选择等级" @change="(v) => setDictName('customer_level', v, 'level_name')"><a-select-option v-for="op in dicts.customer_level" :key="op.value" :value="op.value">{{ op.name }}</a-select-option></a-select></a-form-item></a-col>
      <a-col :span="12"><a-form-item label="客户来源" name="source_id" :rules="[{ required: true, message: '请选择客户来源' }]"><a-select v-model:value="formState.source_id" placeholder="选择来源" show-search :filter-option="filterOption" @change="(v) => setDictName('customer_source', v, 'source')"><a-select-option v-for="op in dicts.customer_source" :key="op.value" :value="op.value">{{ op.name }}</a-select-option></a-select></a-form-item></a-col>
      <a-col :span="12"><a-form-item label="信用额度" name="credit_limit"><a-input-number v-model:value="formState.credit_limit" :min="0" :precision="2" style="width:100%" /></a-form-item></a-col>
      <a-col :span="12"><a-form-item label="结算方式" name="payment_terms"><a-input v-model:value="formState.payment_terms" placeholder="如：月结30天、款到发货" /></a-form-item></a-col>
      <a-col :span="12"><a-form-item label="网址" name="website"><a-input v-model:value="formState.website" placeholder="https://" /></a-form-item></a-col>
    </a-row>

    <a-divider orientation="left">地址信息</a-divider>
    <a-row :gutter="16">
      <a-col :span="12"><a-form-item label="所在地区" name="region_cascader"><SelectRegion ref="selectRegionRef" v-model:value="formState.region_cascader" placeholder="省市区" /></a-form-item></a-col>
      <a-col :span="12"><a-form-item label="详细地址" name="address"><a-input v-model:value="formState.address" placeholder="详细地址" /></a-form-item></a-col>
    </a-row>

    <a-divider orientation="left">工商信息</a-divider>
    <a-row :gutter="16">
      <a-col :span="12"><a-form-item label="统一社会信用代码" name="tax_number"><a-input v-model:value="formState.tax_number" placeholder="18位信用代码" :maxlength="18" /></a-form-item></a-col>
      <a-col :span="12"><a-form-item label="工商注册号" name="business_registration_no"><a-input v-model:value="formState.business_registration_no" placeholder="工商注册号" /></a-form-item></a-col>
      <a-col :span="12"><a-form-item label="法定代表人" name="legal_representative"><a-input v-model:value="formState.legal_representative" placeholder="法定代表人" /></a-form-item></a-col>
      <a-col :span="12"><a-form-item label="注册资本" name="registered_capital"><a-input v-model:value="formState.registered_capital" placeholder="如：1000万元" /></a-form-item></a-col>
      <a-col :span="12"><a-form-item label="实缴资本" name="paid_in_capital"><a-input v-model:value="formState.paid_in_capital" placeholder="实缴资本" /></a-form-item></a-col>
      <a-col :span="12"><a-form-item label="成立日期" name="established_date"><a-date-picker v-model:value="formState.established_date" value-format="YYYY-MM-DD" placeholder="成立日期" style="width:100%" /></a-form-item></a-col>
      <a-col :span="12"><a-form-item label="登记状态" name="registration_status"><a-select v-model:value="formState.registration_status" placeholder="选择状态" allow-clear><a-select-option value="存续">存续</a-select-option><a-select-option value="在业">在业</a-select-option><a-select-option value="吊销">吊销</a-select-option><a-select-option value="注销">注销</a-select-option><a-select-option value="迁入">迁入</a-select-option><a-select-option value="迁出">迁出</a-select-option><a-select-option value="停业">停业</a-select-option><a-select-option value="清算">清算</a-select-option></a-select></a-form-item></a-col>
      <a-col :span="12"><a-form-item label="纳税人资质" name="taxpayer_qualification"><a-select v-model:value="formState.taxpayer_qualification" placeholder="选择资质" allow-clear><a-select-option value="一般纳税人">一般纳税人</a-select-option><a-select-option value="小规模纳税人">小规模纳税人</a-select-option></a-select></a-form-item></a-col>
      <a-col :span="12"><a-form-item label="组织机构代码" name="organization_code"><a-input v-model:value="formState.organization_code" placeholder="组织机构代码" /></a-form-item></a-col>
      <a-col :span="12"><a-form-item label="参保人数" name="insured_count"><a-input v-model:value="formState.insured_count" placeholder="参保人数" /></a-form-item></a-col>
      <a-col :span="12"><a-form-item label="营业期限"><a-row :gutter="8"><a-col :span="11"><a-date-picker v-model:value="formState.business_term_start" value-format="YYYY-MM-DD" placeholder="起始日期" style="width:100%" /></a-col><a-col :span="2" style="text-align:center;line-height:32px">至</a-col><a-col :span="11"><a-date-picker v-model:value="formState.business_term_end" value-format="YYYY-MM-DD" placeholder="截止日期" style="width:100%" /></a-col></a-row></a-form-item></a-col>
      <a-col :span="12"><a-form-item label="核准日期" name="approval_date"><a-date-picker v-model:value="formState.approval_date" value-format="YYYY-MM-DD" placeholder="核准日期" style="width:100%" /></a-form-item></a-col>
      <a-col :span="12"><a-form-item label="登记机关" name="registration_authority"><a-input v-model:value="formState.registration_authority" placeholder="登记机关" /></a-form-item></a-col>
      <a-col :span="12"><a-form-item label="国标行业" name="national_industry"><a-input v-model:value="formState.national_industry" placeholder="国标行业分类" /></a-form-item></a-col>
      <a-col :span="12"><a-form-item label="公司电话" name="telephone"><a-input v-model:value="formState.telephone" placeholder="公司总机" /></a-form-item></a-col>
      <a-col :span="12"><a-form-item label="公司邮箱" name="email"><a-input v-model:value="formState.email" placeholder="公司邮箱" /></a-form-item></a-col>
      <a-col :span="24"><a-form-item label="注册地址" name="registered_address" :label-col="{ span: 3 }" :wrapper-col="{ span: 20 }"><a-input v-model:value="formState.registered_address" placeholder="工商注册地址" /></a-form-item></a-col>
      <a-col :span="24"><a-form-item label="经营范围" name="business_scope" :label-col="{ span: 3 }" :wrapper-col="{ span: 20 }"><a-textarea v-model:value="formState.business_scope" placeholder="经营范围" :rows="3" /></a-form-item></a-col>
      <a-col :span="24"><a-form-item label="公司简介" name="introduction" :label-col="{ span: 3 }" :wrapper-col="{ span: 20 }"><a-textarea v-model:value="formState.introduction" placeholder="公司简介" :rows="3" /></a-form-item></a-col>
    </a-row>

    <a-divider orientation="left">归属信息</a-divider>
    <a-row :gutter="16">
      <a-col :span="12"><a-form-item label="负责人" name="owner_user_id"><a-select v-model:value="formState.owner_user_id" placeholder="选择负责人" show-search :filter-option="filterOption" @change="onOwnerChange"><a-select-option v-for="u in storeUsers" :key="u.store_user_id" :value="u.store_user_id">{{ u.real_name || u.user_name }}</a-select-option></a-select></a-form-item></a-col>
      <a-col :span="12"><a-form-item label="负责人部门" name="owner_department_id"><a-tree-select v-model:value="formState.owner_department_id" placeholder="选负责人后自动填入，也可手动选" allow-clear tree-default-expand-all :tree-data="deptTree" :dropdown-style="{ maxHeight: '300px', overflow: 'auto' }" /></a-form-item></a-col>
    </a-row>

    <a-divider orientation="left">备注</a-divider>
    <a-row :gutter="16">
      <a-col :span="24"><a-form-item label="备注" name="remark" :label-col="{ span: 2 }" :wrapper-col="{ span: 21 }"><a-textarea v-model:value="formState.remark" placeholder="备注信息" :rows="2" /></a-form-item></a-col>
    </a-row>
  </a-form>
</template>

<script setup>
import { ref } from 'vue'
import SelectRegion from '@/components/SelectRegion/index.vue'

defineProps({
  formState: Object,
  rules: Array,
  dicts: Object,
  storeUsers: Array,
  deptTree: Array,
})
const emit = defineEmits(['setDictName', 'onOwnerChange'])

const formRef = ref()
const selectRegionRef = ref()

function setDictName(dictType, idVal, nameField) { emit('setDictName', dictType, idVal, nameField) }
function onOwnerChange(userId) { emit('onOwnerChange', userId) }
function filterOption(input, option) {
  const label = option.children?.default?.() || option.label || ''
  return String(label).toLowerCase().indexOf(input.toLowerCase()) >= 0
}
defineExpose({
  validate: () => formRef.value?.validate(),
  resetFields: () => formRef.value?.resetFields(),
  getRegionNames: (cascader) => selectRegionRef.value?.getNames(cascader) || {},
  getRegionIds: (names) => selectRegionRef.value?.getIds(names) || [],
})
</script>
