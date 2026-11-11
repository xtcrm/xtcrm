<template>
  <a-card :bordered="false" title="分账规则">
    <p style="color:#999;margin-bottom:16px">
      比例在签约时锁定：改这里只影响之后新签的单据，已签订与已确认的业绩不变。
    </p>

    <a-table :columns="columns" :data-source="rows" :pagination="false" size="middle" row-key="biz">
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'sales'">
          <a-input-number v-model:value="record.sales" :min="0" :max="100" :precision="2" style="width:110px" />
        </template>
        <template v-if="column.key === 'cs'">
          <a-input-number v-model:value="record.cs" :min="0" :max="100" :precision="2" style="width:110px" />
        </template>
        <template v-if="column.key === 'sum'">
          <a-tag :color="Number(record.sales) + Number(record.cs) === 100 ? 'green' : 'red'">
            {{ Number(record.sales) + Number(record.cs) }}%
          </a-tag>
        </template>
      </template>
    </a-table>

    <div style="margin-top:16px">
      <a-button type="primary" :loading="saving" @click="save">保存规则</a-button>
    </div>
  </a-card>
</template>

<script setup>
import { ref } from 'vue'
import { message } from 'ant-design-vue'
import { getRatios, saveRatios } from '@/api/crm/commission'

const saving = ref(false)
const rows = ref([
  { biz: 'new', name: '新签', sales: 100, cs: 0 },
  { biz: 'renew', name: '续费', sales: 30, cs: 70 },
  { biz: 'upgrade', name: '增购', sales: 70, cs: 30 },
])

const columns = [
  { title: '业务类型', dataIndex: 'name', key: 'name', width: 140 },
  { title: '销售 %', key: 'sales', width: 160 },
  { title: '客服 %', key: 'cs', width: 160 },
  { title: '合计', key: 'sum', width: 120 },
]

async function load() {
  try {
    const res = await getRatios()
    if (res?.status === 200 && res.data) {
      rows.value.forEach(r => {
        const biz = { new: 1, renew: 2, upgrade: 3 }[r.biz]
        const cfg = res.data[biz]
        if (cfg) { r.sales = Number(cfg.sales); r.cs = Number(cfg.cs) }
      })
    }
  } catch (e) {
    message.error('读取比例失败')
  }
}

async function save() {
  for (const r of rows.value) {
    if (Number(r.sales) + Number(r.cs) !== 100) {
      return message.warning(`${r.name}：销售与客服比例合计必须为 100%`)
    }
  }
  saving.value = true
  try {
    const payload = {}
    rows.value.forEach(r => { payload[r.biz] = { sales: Number(r.sales), cs: Number(r.cs) } })
    const res = await saveRatios(payload)
    if (res?.status === 200) {
      message.success('已保存')
    } else {
      message.error(res?.message || '保存失败')
    }
  } catch (e) {
    message.error('保存失败')
  } finally { saving.value = false }
}

load()
</script>
