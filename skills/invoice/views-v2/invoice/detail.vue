<template>
  <a-card :bordered="false">
    <a-page-header title="发票详情" @back="() => router.go(-1)">
      <template #extra>
        <template v-if="detail?.status === 0">
          <a-button type="primary" @click="auditVisible = true">审核通过</a-button>
          <a-button danger style="margin-left:8px" @click="rejectVisible = true">驳回</a-button>
        </template>
        <template v-if="detail?.status === 1">
          <a-button type="primary" @click="completeVisible = true">完成开票</a-button>
        </template>
        <template v-if="detail?.status === 2">
          <a-button @click="handleNotify">发送短信通知</a-button>
        </template>
      </template>
    </a-page-header>
    <a-divider />

    <a-spin :spinning="loading">
      <a-descriptions title="基本信息" :column="2" bordered size="small">
        <a-descriptions-item label="状态">
          <a-tag :color="statusColor[detail?.status]">{{ statusText[detail?.status] }}</a-tag>
        </a-descriptions-item>
        <a-descriptions-item label="发票类型">{{ invoiceTypeText[detail?.invoice_type] }}</a-descriptions-item>
        <a-descriptions-item label="不含税金额">{{ detail?.amount }}</a-descriptions-item>
        <a-descriptions-item label="税率">{{ detail?.tax_rate }}</a-descriptions-item>
        <a-descriptions-item label="税额">{{ detail?.tax_amount }}</a-descriptions-item>
        <a-descriptions-item label="价税合计">{{ detail?.total_amount }}</a-descriptions-item>
        <a-descriptions-item label="备注">{{ detail?.remark || '-' }}</a-descriptions-item>
        <a-descriptions-item label="申请时间">{{ detail?.create_time }}</a-descriptions-item>
      </a-descriptions>

      <!-- 客户原始凭证 -->
      <div v-if="detail?.ocr_raw_text || ocrImages.length" style="background:#fffbe6;border-radius:8px;padding:16px;margin-top:16px;border:1px solid #ffe58f">
        <div style="font-weight:600;font-size:14px;margin-bottom:12px;color:#ad6800">📎 客户原始凭证</div>
        <div v-if="detail?.ocr_raw_text" style="background:#fff;border-radius:6px;padding:12px;font-size:13px;color:#555;white-space:pre-wrap;max-height:200px;overflow-y:auto;margin-bottom:12px;line-height:1.6">{{ detail.ocr_raw_text }}</div>
        <div v-if="ocrImages.length" style="display:flex;gap:8px;flex-wrap:wrap">
          <a-image v-for="(img, i) in ocrImages" :key="i" :src="img" :width="100" :height="100" style="object-fit:cover;border-radius:4px" />
        </div>
      </div>

      <!-- 商品明细 -->
      <div v-if="goodsList.length" style="background:#fff;border-radius:8px;padding:16px;margin-top:16px;border:1px solid #f0f0f0">
        <div style="font-weight:600;font-size:14px;margin-bottom:12px;color:#333">商品明细</div>
        <a-table :columns="goodsColumns" :data-source="goodsList" :pagination="false" size="small" :row-key="(r,i) => i">
          <template #bodyCell="{ column, record }">
            <template v-if="column.dataIndex === 'amount'">¥{{ record.amount }}</template>
          </template>
        </a-table>
      </div>

      <a-descriptions title="销方信息" :column="2" bordered size="small" style="margin-top:16px">
        <a-descriptions-item label="公司名称">{{ detail?.seller_name }}</a-descriptions-item>
        <a-descriptions-item label="税号">{{ detail?.seller_tax_number }}</a-descriptions-item>
        <a-descriptions-item label="地址">{{ detail?.seller_address || '-' }}</a-descriptions-item>
        <a-descriptions-item label="银行">{{ detail?.seller_bank || '-' }}</a-descriptions-item>
      </a-descriptions>

      <a-descriptions title="购方信息" :column="2" bordered size="small" style="margin-top:16px">
        <a-descriptions-item label="公司名称">{{ detail?.buyer_name }}</a-descriptions-item>
        <a-descriptions-item label="税号">{{ detail?.buyer_tax_number }}</a-descriptions-item>
        <a-descriptions-item label="地址">{{ detail?.buyer_address || '-' }}</a-descriptions-item>
        <a-descriptions-item label="银行">{{ detail?.buyer_bank || '-' }}</a-descriptions-item>
        <a-descriptions-item label="邮箱">{{ detail?.buyer_email || '-' }}</a-descriptions-item>
        <a-descriptions-item label="手机">{{ detail?.buyer_phone || '-' }}</a-descriptions-item>
      </a-descriptions>

      <template v-if="detail?.status >= 2">
        <a-descriptions title="开票结果" :column="2" bordered size="small" style="margin-top:16px">
          <a-descriptions-item label="发票代码">{{ detail?.invoice_code || '-' }}</a-descriptions-item>
          <a-descriptions-item label="发票号码">{{ detail?.invoice_number || '-' }}</a-descriptions-item>
          <a-descriptions-item label="开票日期">{{ detail?.invoice_date || '-' }}</a-descriptions-item>
          <a-descriptions-item label="票图">
            <a v-if="detail?.invoice_image_url" :href="detail.invoice_image_url" target="_blank">查看</a>
            <span v-else>-</span>
          </a-descriptions-item>
          <a-descriptions-item label="PDF">
            <a v-if="detail?.invoice_pdf_url" :href="detail.invoice_pdf_url" target="_blank">下载</a>
            <span v-else>-</span>
          </a-descriptions-item>
          <a-descriptions-item label="开票文字" :span="2">{{ detail?.invoice_text || '-' }}</a-descriptions-item>
        </a-descriptions>
      </template>

      <template v-if="detail?.status === 4">
        <a-descriptions title="驳回信息" :column="1" bordered size="small" style="margin-top:16px">
          <a-descriptions-item label="驳回原因">{{ detail?.audit_remark }}</a-descriptions-item>
        </a-descriptions>
      </template>
    </a-spin>

    <audit-modal v-model:visible="auditVisible" :record="detail" status="1" title="审核通过" @ok="onAudited(1)" />
    <audit-modal v-model:visible="rejectVisible" :record="detail" status="4" title="驳回申请" @ok="onAudited(4)" />
    <complete-modal v-model:visible="completeVisible" :record="detail" @ok="onCompleted" />
  </a-card>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { message } from 'ant-design-vue'
import { getInvoiceDetail, notifyInvoice } from '../api'
import AuditModal from './components/AuditModal.vue'
import CompleteModal from './components/CompleteModal.vue'

const router = useRouter()
const route = useRoute()
const detail = ref(null)
const loading = ref(false)
const auditVisible = ref(false)
const rejectVisible = ref(false)
const completeVisible = ref(false)

const ocrImages = ref([])
const goodsList = ref([])
const goodsColumns = [
  { title: '名称', dataIndex: 'name' },
  { title: '规格', dataIndex: 'quantity' },
  { title: '单价', dataIndex: 'price' },
  { title: '金额', dataIndex: 'amount', width: 100 },
]

const statusText = { 0: '待审核', 1: '待开票', 2: '已开票', 3: '已作废', 4: '已驳回' }
const statusColor = { 0: 'orange', 1: 'blue', 2: 'green', 3: 'default', 4: 'red' }
const invoiceTypeText = { 1: '增值税专票', 2: '增值税普票', 3: '电子发票', 4: '数电票' }

function load() {
  loading.value = true
  getInvoiceDetail(route.query.id).then(r => {
    const d = r?.data?.detail ?? r?.data
    detail.value = d
    if (d?.goods_info) {
      try { goodsList.value = typeof d.goods_info === 'string' ? JSON.parse(d.goods_info) : d.goods_info } catch (e) { goodsList.value = [] }
    }
    if (d?.ocr_images) {
      try { ocrImages.value = typeof d.ocr_images === 'string' ? JSON.parse(d.ocr_images) : d.ocr_images } catch (e) { ocrImages.value = [] }
    }
  }).finally(() => { loading.value = false })
}
function handleNotify() {
  notifyInvoice(detail.value.id).then(() => message.success('短信已发送'))
}
function onAudited() { auditVisible.value = false; rejectVisible.value = false; load() }
function onCompleted() { completeVisible.value = false; load() }

onMounted(load)
</script>
