<template>
  <a-card :bordered="false">
    <a-page-header title="中文报价单" @back="() => $router.go(-1)">
      <template slot="extra">
        <a-button-group style="margin-right:8px">
          <a-button type="primary" icon="file-pdf" :loading="downloading" @click="handleDownload('pdf')">PDF</a-button>
          <a-button type="primary" icon="file-word" :loading="downloading" @click="handleDownload('word')">Word</a-button>
          <a-button type="primary" icon="file-excel" :loading="downloading" @click="handleDownload('excel')">Excel</a-button>
        </a-button-group>
        <a-button icon="printer" @click="handlePrint">打印</a-button>
        <a-button style="margin-left:8px" @click="showSettings = !showSettings">{{ showSettings ? '关闭设置' : '模板设置' }}</a-button>
      </template>
    </a-page-header>
    <a-divider />

    <!-- 模板设置表单 -->
    <a-card v-if="showSettings" title="公司信息设置" style="margin-bottom:16px">
      <a-form :form="settingsForm" layout="inline" @submit="handleSaveSettings">
        <!-- Logo 选择 -->
        <a-row :gutter="16" style="margin-bottom:12px">
          <a-col :span="24">
            <a-form-item label="公司Logo" extra="建议尺寸：200×60像素">
              <SelectImage :multiple="false" :defaultList="logoDefaultList" @change="onLogoChange" :width="80" />
            </a-form-item>
          </a-col>
        </a-row>
        <a-row :gutter="16">
          <a-col :md="8" :sm="24">
            <a-form-item label="公司名称">
              <a-input v-decorator="['company_name']" placeholder="公司全称" />
            </a-form-item>
          </a-col>
          <a-col :md="8" :sm="24">
            <a-form-item label="地址">
              <a-input v-decorator="['company_address']" placeholder="公司地址" />
            </a-form-item>
          </a-col>
          <a-col :md="8" :sm="24">
            <a-form-item label="电话">
              <a-input v-decorator="['company_phone']" placeholder="联系电话" />
            </a-form-item>
          </a-col>
        </a-row>
        <a-row :gutter="16" style="margin-top:8px">
          <a-col :md="6" :sm="24">
            <a-form-item label="开户行">
              <a-input v-decorator="['bank_name']" placeholder="开户银行" />
            </a-form-item>
          </a-col>
          <a-col :md="6" :sm="24">
            <a-form-item label="账号">
              <a-input v-decorator="['bank_account']" placeholder="银行账号" />
            </a-form-item>
          </a-col>
          <a-col :md="6" :sm="24">
            <a-form-item label="税号">
              <a-input v-decorator="['tax_no']" placeholder="纳税识别号" />
            </a-form-item>
          </a-col>
          <a-col :md="6" :sm="24" style="padding-top:40px">
            <a-button type="primary" html-type="submit" :loading="saving">保存</a-button>
          </a-col>
        </a-row>
        <a-form-item label="条款" :labelCol="{span:2}" :wrapperCol="{span:22}" style="margin-top:8px">
          <a-textarea v-decorator="['terms_text']" :rows="4" placeholder="报价条款，每条一行" />
        </a-form-item>
        <a-form-item label="页脚" :labelCol="{span:2}" :wrapperCol="{span:22}">
          <a-input v-decorator="['footer_text']" placeholder="页脚声明文字" />
        </a-form-item>
      </a-form>
    </a-card>

    <!-- 报价预览 -->
    <a-spin :spinning="loading">
      <div v-if="previewHtml" class="preview-wrapper" v-html="previewHtml"></div>
      <a-empty v-else-if="!loading" description="暂无数据" />
    </a-spin>
  </a-card>
</template>

<script>
import { getPreview, exportPdf, exportWord, exportExcel, getTemplate, saveTemplate } from '@skills/quotecn/views/quoteCn'
import { SelectImage } from '@/components'

export default {
  name: 'QuoteCnPreview',
  components: { SelectImage },
  data() {
    return {
      loading: false,
      downloading: false,
      saving: false,
      previewHtml: '',
      showSettings: false,
      settingsForm: this.$form.createForm(this, { name: 'quoteCnSettings' }),
      quotationId: null,
      logoFileId: 0,
      logoDefaultList: []
    }
  },
  created() {
    this.quotationId = this.$route.query.id
    if (this.quotationId) this.loadPreview()
  },
  methods: {
    onLogoChange(fileId, selectedItems) {
      this.logoFileId = fileId
      // 从选中项提取 file_path 保存
      const filePath = (selectedItems && selectedItems.length) ? (selectedItems[0].file_path || '') : ''
      const previewUrl = (selectedItems && selectedItems.length) ? (selectedItems[0].preview_url || '') : ''
      saveTemplate({
        company_logo: filePath,
        logo_file_id: fileId
      }).then(() => {
        this.$message.success('Logo已更新')
        // 同步更新 defaultList 以显示预览
        if (fileId > 0) {
          this.logoDefaultList = [{ file_id: fileId, preview_url: previewUrl, file_path: filePath }]
        } else {
          this.logoDefaultList = []
        }
        this.loadPreview()
      })
    },
    loadPreview() {
      this.loading = true
      getPreview(this.quotationId).then(res => {
        this.previewHtml = (res.data && res.data.html) || ''
      }).finally(() => { this.loading = false })
    },
    handleDownload(type) {
      this.downloading = true
      const apiMap = { pdf: exportPdf, word: exportWord, excel: exportExcel }
      const mimeMap = { pdf: 'application/pdf', word: 'application/msword', excel: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }
      const fn = apiMap[type]
      if (!fn) { this.downloading = false; return }
      fn(this.quotationId).then(res => {
        const data = res.data || {}
        if (data.pdf_base64 || data.file_base64) {
          const b64 = data.pdf_base64 || data.file_base64
          const byteChars = atob(b64)
          const byteNums = new Array(byteChars.length)
          for (let i = 0; i < byteChars.length; i++) byteNums[i] = byteChars.charCodeAt(i)
          const blob = new Blob([new Uint8Array(byteNums)], { type: mimeMap[type] })
          const url = URL.createObjectURL(blob)
          const a = document.createElement('a')
          a.href = url; a.download = data.filename || ('quotation.' + type); document.body.appendChild(a); a.click()
          document.body.removeChild(a); URL.revokeObjectURL(url)
          this.$message.success('已导出 ' + type.toUpperCase())
        } else if (data.html) {
          const blob = new Blob(['﻿' + data.html], { type: mimeMap[type] + ';charset=utf-8' })
          const url = URL.createObjectURL(blob)
          const a = document.createElement('a')
          a.href = url; a.download = data.filename || ('quotation.doc'); document.body.appendChild(a); a.click()
          document.body.removeChild(a); URL.revokeObjectURL(url)
          this.$message.success('已导出 ' + type.toUpperCase())
        } else {
          this.$message.warning(type === 'pdf' ? 'PDF引擎未配置，请安装wkhtmltopdf' : '导出失败')
        }
      }).finally(() => { this.downloading = false })
    },
    handlePrint() {
      const printWin = window.open('', '_blank')
      printWin.document.write(this.previewHtml)
      printWin.document.close()
      printWin.focus()
      printWin.print()
    },
    handleSaveSettings(e) {
      e.preventDefault()
      this.settingsForm.validateFields((err, values) => {
        if (err) return
        this.saving = true
        saveTemplate(values).then(() => {
          this.$message.success('保存成功')
          this.loadPreview()
        }).finally(() => { this.saving = false })
      })
    }
  },
  watch: {
    showSettings(v) {
      if (v) {
        getTemplate().then(res => {
          const serverData = res.data || {}
          // 前端默认值（CODE，非数据库）
          const defaults = {
            company_name: '',
            company_address: '',
            company_phone: '',
            bank_name: '',
            bank_account: '',
            tax_no: '',
            terms_text: '1. 本报价单有效期30天。\n2. 付款方式：合同签订后预付30%，发货前付清余款。\n3. 交货期：收到预付款后15个工作日内。\n4. 本报价不含运输费、安装费，如需另行协商。',
            footer_text: '本报价单一式两份，供需双方各执一份，具有同等法律效力。'
          }
          // 合并：服务器有值用服务器的，没有就用默认值
          const data = { ...defaults, ...Object.fromEntries(
            Object.entries(serverData).filter(([,v]) => v !== '' && v !== null && v !== undefined)
          )}
          // 构造 Logo defaultList
          const fid = parseInt(data.logo_file_id) || 0
          if (fid > 0 && data.company_logo) {
            const base = (window.publicConfig?.BASE_API || '').replace(/\/index\.php.*$/, '') + '/uploads/'
            this.logoDefaultList = [{
              file_id: fid, file_path: data.company_logo, preview_url: base + data.company_logo
            }]
          } else {
            this.logoDefaultList = []
          }
          this.$nextTick(() => { this.settingsForm.setFieldsValue(data) })
        })
      }
    }
  }
}
</script>

<style scoped>
.preview-wrapper {
  background: #fff;
  border: 1px solid #e8e8e8;
  border-radius: 4px;
  overflow: auto;
  max-height: calc(100vh - 200px);
  padding: 24px 0;
  display: flex;
  justify-content: center;
}
.preview-wrapper >>> .quote-cn-wrapper {
  margin: 0;
}
</style>
