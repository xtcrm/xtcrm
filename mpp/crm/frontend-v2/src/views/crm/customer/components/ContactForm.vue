<template>
  <a-modal :title="mdl?.id ? '编辑联系人' : '新增联系人'" :width="560" :open="open" :confirm-loading="loading"
    :mask-closable="false" @ok="handleSubmit" @cancel="handleCancel">
    <a-spin :spinning="loading">
      <a-form ref="formRef" :model="formState" :label-col="{ span: 6 }" :wrapper-col="{ span: 18 }">
        <a-row :gutter="16">
          <!-- 手机号优先，作为唯一标识 -->
          <a-col :span="12">
            <a-form-item label="手机号" name="mobile" :rules="[{ required: true, message: '请输入手机号' }]">
              <a-input v-model:value="formState.mobile" placeholder="手机号（唯一标识）" @change="onPhoneChange" />
              <div v-if="phoneExisting" style="margin-top:4px">
                <a-alert type="info" show-icon>
                  <template #message>
                    该手机号已存在：<strong>{{ phoneExisting.contact.contact_name }}</strong>
                    <span v-if="phoneExisting.companies?.length">，已关联 {{ phoneExisting.companies.map(c=>c.customer_name).join('、') }}</span>
                    <br />提交后将直接绑定到当前客户
                  </template>
                </a-alert>
              </div>
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="姓名" name="contact_name" :rules="[{ required: true, message: '请输入姓名' }]">
              <a-input v-model:value="formState.contact_name" placeholder="联系人姓名" />
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
            <a-form-item label="生日" name="birthday">
              <a-date-picker v-model:value="formState.birthday" value-format="YYYY-MM-DD" placeholder="选择日期" style="width:100%" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="身份证号" name="id_card" extra="填写后自动识别生日/性别/籍贯">
              <a-input v-model:value="formState.id_card" placeholder="18位身份证号" :maxlength="18" @change="onIdCardChange" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="籍贯" name="hometown">
              <a-input v-model:value="formState.hometown" placeholder="自动识别或手动填写" />
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
            <a-form-item label="固话" name="telephone">
              <a-input v-model:value="formState.telephone" placeholder="固话" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="家庭住址" name="address">
              <a-input v-model:value="formState.address" placeholder="家庭住址" />
            </a-form-item>
          </a-col>

          <a-divider orientation="left" plain style="font-size:12px;color:#999">当前公司信息</a-divider>

          <a-col :span="12">
            <a-form-item label="职位" name="position">
              <a-input v-model:value="formState.position" placeholder="在该公司的职位" />
            </a-form-item>
          </a-col>
          <a-col :span="12">
            <a-form-item label="部门" name="department">
              <a-input v-model:value="formState.department" placeholder="在该公司的部门" />
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
import { add, edit, lookupByMobile } from '@/api/crm/contact'

const props = defineProps({ open: Boolean, mdl: Object, customerId: [Number, String] })
const emit = defineEmits(['update:open', 'ok'])

const formRef = ref()
const loading = ref(false)

const emptyForm = () => ({
  contact_name: '', mobile: '', gender: 0, birthday: '', id_card: '',
  hometown: '', email: '', wechat: '', telephone: '', address: '',
  position: '', department: '', is_primary: false, remark: '',
})

const formState = reactive(emptyForm())
const phoneExisting = ref(null) // 手机号查询结果：null=未查, false=不存在, object=已存在的联系人

let lookupTimer = null
function onPhoneChange() {
  phoneExisting.value = null
  const mobile = formState.mobile?.trim() || ''
  if (mobile.length < 11) return
  clearTimeout(lookupTimer)
  lookupTimer = setTimeout(() => {
    lookupByMobile(mobile).then(res => {
      const d = res?.data || res
      if (d?.contact) {
        phoneExisting.value = d
        // 自动回填已有信息
        if (!formState.contact_name) formState.contact_name = d.contact.contact_name
        if (!formState.gender) formState.gender = d.contact.gender || 0
        if (!formState.birthday) formState.birthday = d.contact.birthday || ''
        if (!formState.id_card) formState.id_card = d.contact.id_card || ''
        if (!formState.hometown) formState.hometown = d.contact.hometown || ''
        if (!formState.email) formState.email = d.contact.email || ''
        if (!formState.wechat) formState.wechat = d.contact.wechat || ''
        if (!formState.telephone) formState.telephone = d.contact.telephone || ''
        if (!formState.address) formState.address = d.contact.address || ''
      } else {
        phoneExisting.value = false
      }
    }).catch(() => { phoneExisting.value = false })
  }, 400)
}

function onIdCardChange() {
  const id = formState.id_card?.trim() || ''
  if (id.length !== 18 && id.length !== 15) return
  const birth = id.length === 18 ? id.substring(6, 14) : '19' + id.substring(6, 12)
  const y = parseInt(birth.substring(0, 4))
  const m = parseInt(birth.substring(4, 6))
  const d = parseInt(birth.substring(6, 8))
  if (m >= 1 && m <= 12 && d >= 1 && d <= 31) {
    formState.birthday = `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`
  }
  const genderIdx = id.length === 18 ? 16 : 14
  const genderCode = parseInt(id.charAt(genderIdx))
  formState.gender = genderCode % 2 === 1 ? 1 : 2
  const provMap = { '11':'北京','12':'天津','13':'河北','14':'山西','15':'内蒙古','21':'辽宁','22':'吉林','23':'黑龙江','31':'上海','32':'江苏','33':'浙江','34':'安徽','35':'福建','36':'江西','37':'山东','41':'河南','42':'湖北','43':'湖南','44':'广东','45':'广西','46':'海南','50':'重庆','51':'四川','52':'贵州','53':'云南','54':'西藏','61':'陕西','62':'甘肃','63':'青海','64':'宁夏','65':'新疆','71':'台湾','81':'香港','82':'澳门' }
  formState.hometown = provMap[id.substring(0, 2)] || ''
}

watch(() => props.open, (v) => {
  if (v && props.mdl?.id) {
    nextTick(() => {
      formRef.value?.resetFields()
      Object.assign(formState, {
        contact_name: props.mdl.contact_name || '',
        mobile: props.mdl.mobile || '',
        gender: props.mdl.gender ?? 0,
        birthday: props.mdl.birthday || '',
        id_card: props.mdl.id_card || '',
        hometown: props.mdl.hometown || '',
        email: props.mdl.email || '',
        wechat: props.mdl.wechat || '',
        telephone: props.mdl.telephone || '',
        address: props.mdl.address || '',
        position: props.mdl.position || '',
        department: props.mdl.department || '',
        is_primary: !!props.mdl.is_primary,
        remark: props.mdl.remark || '',
      })
    })
  } else if (v) {
    nextTick(() => {
      formRef.value?.resetFields()
      Object.assign(formState, emptyForm())
    })
  }
})

function handleSubmit() {
  formRef.value.validate().then((values) => {
    loading.value = true
    values.customer_id = props.customerId
    values.is_primary = values.is_primary ? 1 : 0
    const fn = props.mdl?.id ? edit : add
    if (props.mdl?.id) values.id = props.mdl.id
    fn(values).then((res) => {
      const msg = res?.data?.msg || res?.msg || '保存成功'
      message.success(msg)
      loading.value = false
      emit('update:open', false)
      emit('ok')
    }).catch(() => { loading.value = false })
  }).catch(() => {})
}

function handleCancel() { emit('update:open', false) }
</script>
