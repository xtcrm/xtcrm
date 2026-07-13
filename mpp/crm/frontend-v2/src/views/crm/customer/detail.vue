<template>
  <a-card :bordered="false">
    <a-page-header :title="detail.customer_name || '客户详情'" @back="() => router.go(-1)">
      <template #tags>
        <a-tag :color="detail.status === 1 ? 'green' : 'default'">{{ detail.status === 1 ? '正常' : '停用' }}</a-tag>
        <a-tag :color="detail.owner_user_id ? 'blue' : 'orange'">{{ detail.owner_user_id ? '归属：' + detail.owner_user_name : '公海客户' }}</a-tag>
      </template>
      <template #extra>
        <a-button v-if="canEdit" @click="formVisible = true">编辑</a-button>
        <a-button v-if="canEdit" :loading="aiAnalyzing" @click="handleAiAnalyze" style="margin-left:8px">🤖 AI分析</a-button>
        <a-button v-if="canEdit" :loading="portraitLoading" @click="handlePortrait" style="margin-left:8px">🪪 画像</a-button>
        <a-button v-if="canRelease" @click="handleRelease" style="margin-left:8px">释放到公海</a-button>
        <a-button v-if="showClaim" type="primary" @click="handleClaim" style="margin-left:8px">认领</a-button>
      </template>
    </a-page-header>

    <!-- AI 客户摘要 -->
    <a-alert v-if="aiSummary" type="info" :show-icon="false" closable style="margin-bottom:16px">
      <template #message>
        <div v-html="aiSummary" style="line-height:1.8;font-size:13px"></div>
      </template>
    </a-alert>

    <!-- 基本信息 — 永远可见，可编辑字段点击即可修改 -->
    <a-descriptions bordered :column="2" style="margin-bottom:16px" size="small">
      <a-descriptions-item label="客户编码">{{ detail.customer_code }}</a-descriptions-item>
      <a-descriptions-item label="简称">
        <span v-if="!canEdit || editingField !== 'short_name'" @click="startEdit('short_name')" :class="{ 'field-editable': canEdit }">{{ detail.short_name || '-' }}</span>
        <a-input v-else :value="detail.short_name" @press-enter="(e) => saveField('short_name', e.target.value)" @blur="(e) => saveField('short_name', e.target.value)" size="small" style="width:160px" />
      </a-descriptions-item>
      <a-descriptions-item label="行业">
        <span v-if="!canEdit || editingField !== 'industry'" @click="startEdit('industry')" :class="{ 'field-editable': canEdit }">{{ detail.industry_name || detail.industry || '-' }}</span>
        <a-select v-else :value="detail.industry_id" @change="(v) => saveField('industry_id', v, 'industry', dictOpts.industry.find(o => o.config_value == v)?.config_name || '')" @blur="editingField = ''" size="small" style="width:160px" auto-focus allow-clear>
          <a-select-option v-for="op in dictOpts.industry" :key="op.config_value" :value="parseInt(op.config_value)">{{ op.config_name }}</a-select-option>
        </a-select>
      </a-descriptions-item>
      <a-descriptions-item label="客户等级">
        <span v-if="!canEdit || editingField !== 'level'" @click="startEdit('level')" :class="{ 'field-editable': canEdit }">{{ detail.level_name || '-' }}</span>
        <a-select v-else :value="detail.level_id" @change="(v) => saveField('level_id', v, 'level_name', dictOpts.customer_level.find(o => o.config_value == v)?.config_name || '')" @blur="editingField = ''" size="small" style="width:160px" auto-focus allow-clear>
          <a-select-option v-for="op in dictOpts.customer_level" :key="op.config_value" :value="parseInt(op.config_value)">{{ op.config_name }}</a-select-option>
        </a-select>
      </a-descriptions-item>
      <a-descriptions-item label="客户来源">
        <span v-if="!canEdit || editingField !== 'source'" @click="startEdit('source')" :class="{ 'field-editable': canEdit }">{{ detail.source_name || detail.source || '-' }}</span>
        <a-select v-else :value="detail.source_id" @change="(v) => saveField('source_id', v, 'source', dictOpts.customer_source.find(o => o.config_value == v)?.config_name || '')" @blur="editingField = ''" size="small" style="width:160px" auto-focus allow-clear>
          <a-select-option v-for="op in dictOpts.customer_source" :key="op.config_value" :value="parseInt(op.config_value)">{{ op.config_name }}</a-select-option>
        </a-select>
      </a-descriptions-item>
      <a-descriptions-item label="分组">
        <span v-if="!canEdit || editingField !== 'group'" @click="startEdit('group')" :class="{ 'field-editable': canEdit }">{{ detail.group_name || detail.customer_group || '-' }}</span>
        <a-select v-else :value="detail.group_id" @change="(v) => saveField('group_id', v, 'customer_group', dictOpts.customer_group.find(o => o.config_value == v)?.config_name || '')" @blur="editingField = ''" size="small" style="width:160px" auto-focus allow-clear>
          <a-select-option v-for="op in dictOpts.customer_group" :key="op.config_value" :value="parseInt(op.config_value)">{{ op.config_name }}</a-select-option>
        </a-select>
      </a-descriptions-item>
      <a-descriptions-item label="税号">
        <span v-if="!canEdit || editingField !== 'tax_number'">{{ detail.tax_number || '-' }}</span>
        <a-input v-else :value="detail.tax_number" @press-enter="(e) => saveField('tax_number', e.target.value)" @blur="(e) => saveField('tax_number', e.target.value)" size="small" style="width:160px" />
      </a-descriptions-item>
      <a-descriptions-item label="信用额度">
        <span v-if="!canEdit || editingField !== 'credit_limit'" @click="startEdit('credit_limit')" :class="{ 'field-editable': canEdit }">{{ detail.credit_limit ? detail.credit_limit + ' 元' : '-' }}</span>
        <a-input-number v-else :value="detail.credit_limit" @press-enter="(e) => saveField('credit_limit', e.target.value)" @blur="(e) => saveField('credit_limit', e.target.value)" size="small" style="width:160px" :precision="2" />
      </a-descriptions-item>
      <a-descriptions-item label="结算方式">
        <span v-if="!canEdit || editingField !== 'payment_terms'" @click="startEdit('payment_terms')" :class="{ 'field-editable': canEdit }">{{ detail.payment_terms || '-' }}</span>
        <a-input v-else :value="detail.payment_terms" @press-enter="(e) => saveField('payment_terms', e.target.value)" @blur="(e) => saveField('payment_terms', e.target.value)" size="small" style="width:160px" />
      </a-descriptions-item>
      <a-descriptions-item label="网址">
        <span v-if="!canEdit || editingField !== 'website'" @click="startEdit('website')" :class="{ 'field-editable': canEdit }">{{ detail.website || '-' }}</span>
        <a-input v-else :value="detail.website" @press-enter="(e) => saveField('website', e.target.value)" @blur="(e) => saveField('website', e.target.value)" size="small" style="width:160px" />
      </a-descriptions-item>
      <a-descriptions-item label="所在地区">
        <span v-if="!canEdit || editingField !== 'region'" @click="startEditRegion()" :class="{ 'field-editable': canEdit }">{{ [detail.province, detail.city, detail.area].filter(Boolean).join(' / ') || '-' }}</span>
        <a-cascader v-else :value="regionCascader" :options="regionOptions" @change="saveRegion" placeholder="省市区" style="width:220px" />
      </a-descriptions-item>
      <a-descriptions-item label="地址">
        <span v-if="!canEdit || editingField !== 'address'" @click="startEdit('address')" :class="{ 'field-editable': canEdit }">{{ detail.address || '-' }}</span>
        <a-input v-else :value="detail.address" @press-enter="(e) => saveField('address', e.target.value)" @blur="(e) => saveField('address', e.target.value)" size="small" style="width:160px" />
      </a-descriptions-item>
      <a-descriptions-item label="漏斗阶段">
        <span v-if="!canEdit || editingField !== 'funnel_stage'" @click="startEdit('funnel_stage')" :class="{ 'field-editable': canEdit }">{{ detail.funnel_stage_text || '-' }}</span>
        <a-select v-else :value="detail.funnel_stage" @change="(v) => saveField('funnel_stage', v)" @blur="editingField = ''" size="small" style="width:160px" auto-focus allow-clear>
          <a-select-option v-for="op in dictOpts.funnel_stages" :key="op.config_value" :value="parseInt(op.config_value)">{{ op.config_name }}</a-select-option>
        </a-select>
      </a-descriptions-item>
      <a-descriptions-item label="最后跟进">{{ detail.last_followup_time ? new Date(detail.last_followup_time * 1000).toLocaleString() : '暂无' }}</a-descriptions-item>
      <a-descriptions-item label="负责人">{{ detail.owner_user_name || '-' }}</a-descriptions-item>
      <a-descriptions-item label="录入员">{{ detail.creator_user_name || '-' }}</a-descriptions-item>
      <a-descriptions-item v-if="detail.ai_score > 0" label="AI评分">
        <a-tag :color="aiScoreColor">{{ detail.ai_score }} 分</a-tag>
      </a-descriptions-item>
      <a-descriptions-item v-if="detail.ai_tags" label="AI标签">
        <a-tag v-for="tag in aiTagList" :key="tag" style="margin-right:4px">{{ tag }}</a-tag>
      </a-descriptions-item>
      <a-descriptions-item v-if="detail.ai_suggestion" label="AI建议" :span="2">{{ detail.ai_suggestion }}</a-descriptions-item>
      <a-descriptions-item label="备注">
        <span v-if="!canEdit || editingField !== 'remark'" @click="startEdit('remark')" :class="{ 'field-editable': canEdit }">{{ detail.remark || '-' }}</span>
        <a-textarea v-else :value="detail.remark" @press-enter="(e) => saveField('remark', e.target.value)" @blur="(e) => saveField('remark', e.target.value)" size="small" :rows="3" style="width:160px" />
      </a-descriptions-item>
    </a-descriptions>

    <!-- AI客户画像 -->
    <!-- 工商信息 -->
    <a-card size="small" title="工商信息" style="margin-bottom:16px">
      <a-descriptions :column="3" size="small" bordered>
        <a-descriptions-item label="统一社会信用代码">
          <span v-if="!canEdit || editingField !== 'tax_number'" @click="startEdit('tax_number')" :class="{ 'field-editable': canEdit }">{{ detail.tax_number || '-' }}</span>
          <a-input v-else :value="detail.tax_number" @press-enter="(e) => saveField('tax_number', e.target.value)" @blur="(e) => saveField('tax_number', e.target.value)" size="small" style="width:180px" />
        </a-descriptions-item>
        <a-descriptions-item label="工商注册号">
          <span v-if="!canEdit || editingField !== 'business_registration_no'" @click="startEdit('business_registration_no')" :class="{ 'field-editable': canEdit }">{{ detail.business_registration_no || '-' }}</span>
          <a-input v-else :value="detail.business_registration_no" @press-enter="(e) => saveField('business_registration_no', e.target.value)" @blur="(e) => saveField('business_registration_no', e.target.value)" size="small" style="width:180px" />
        </a-descriptions-item>
        <a-descriptions-item label="法定代表人">
          <span v-if="!canEdit || editingField !== 'legal_representative'" @click="startEdit('legal_representative')" :class="{ 'field-editable': canEdit }">{{ detail.legal_representative || '-' }}</span>
          <a-input v-else :value="detail.legal_representative" @press-enter="(e) => saveField('legal_representative', e.target.value)" @blur="(e) => saveField('legal_representative', e.target.value)" size="small" style="width:180px" />
        </a-descriptions-item>
        <a-descriptions-item label="注册资本">
          <span v-if="!canEdit || editingField !== 'registered_capital'" @click="startEdit('registered_capital')" :class="{ 'field-editable': canEdit }">{{ detail.registered_capital || '-' }}</span>
          <a-input v-else :value="detail.registered_capital" @press-enter="(e) => saveField('registered_capital', e.target.value)" @blur="(e) => saveField('registered_capital', e.target.value)" size="small" style="width:180px" />
        </a-descriptions-item>
        <a-descriptions-item label="实缴资本">
          <span v-if="!canEdit || editingField !== 'paid_in_capital'" @click="startEdit('paid_in_capital')" :class="{ 'field-editable': canEdit }">{{ detail.paid_in_capital || '-' }}</span>
          <a-input v-else :value="detail.paid_in_capital" @press-enter="(e) => saveField('paid_in_capital', e.target.value)" @blur="(e) => saveField('paid_in_capital', e.target.value)" size="small" style="width:180px" />
        </a-descriptions-item>
        <a-descriptions-item label="成立日期">
          <span v-if="!canEdit || editingField !== 'established_date'" @click="startEdit('established_date')" :class="{ 'field-editable': canEdit }">{{ detail.established_date || '-' }}</span>
          <a-date-picker v-else :value="detail.established_date ? dayjs(detail.established_date) : null" @change="(d) => saveField('established_date', d)" :open="true" value-format="YYYY-MM-DD" size="small" style="width:180px" />
        </a-descriptions-item>
        <a-descriptions-item label="登记状态">
          <span v-if="!canEdit || editingField !== 'registration_status'" @click="startEdit('registration_status')" :class="{ 'field-editable': canEdit }">{{ detail.registration_status || '-' }}</span>
          <a-select v-else :value="detail.registration_status" @change="(v) => saveField('registration_status', v)" @blur="editingField = ''" size="small" style="width:180px" auto-focus allow-clear>
            <a-select-option v-for="s in ['存续','在业','吊销','注销','迁入','迁出','停业','清算']" :key="s" :value="s">{{ s }}</a-select-option>
          </a-select>
        </a-descriptions-item>
        <a-descriptions-item label="纳税人资质">
          <span v-if="!canEdit || editingField !== 'taxpayer_qualification'" @click="startEdit('taxpayer_qualification')" :class="{ 'field-editable': canEdit }">{{ detail.taxpayer_qualification || '-' }}</span>
          <a-select v-else :value="detail.taxpayer_qualification" @change="(v) => saveField('taxpayer_qualification', v)" @blur="editingField = ''" size="small" style="width:180px" auto-focus allow-clear>
            <a-select-option value="一般纳税人">一般纳税人</a-select-option><a-select-option value="小规模纳税人">小规模纳税人</a-select-option>
          </a-select>
        </a-descriptions-item>
        <a-descriptions-item label="组织机构代码">
          <span v-if="!canEdit || editingField !== 'organization_code'" @click="startEdit('organization_code')" :class="{ 'field-editable': canEdit }">{{ detail.organization_code || '-' }}</span>
          <a-input v-else :value="detail.organization_code" @press-enter="(e) => saveField('organization_code', e.target.value)" @blur="(e) => saveField('organization_code', e.target.value)" size="small" style="width:180px" />
        </a-descriptions-item>
        <a-descriptions-item label="参保人数">
          <span v-if="!canEdit || editingField !== 'insured_count'" @click="startEdit('insured_count')" :class="{ 'field-editable': canEdit }">{{ detail.insured_count || '-' }}</span>
          <a-input v-else :value="detail.insured_count" @press-enter="(e) => saveField('insured_count', e.target.value)" @blur="(e) => saveField('insured_count', e.target.value)" size="small" style="width:180px" />
        </a-descriptions-item>
        <a-descriptions-item label="营业期限">
          <span v-if="!canEdit || editingField !== 'business_term'" @click="startEdit('business_term')" :class="{ 'field-editable': canEdit }">{{ detail.business_term || '-' }}</span>
          <a-input v-else :value="detail.business_term" @press-enter="(e) => saveField('business_term', e.target.value)" @blur="(e) => saveField('business_term', e.target.value)" size="small" style="width:180px" />
        </a-descriptions-item>
        <a-descriptions-item label="核准日期">
          <span v-if="!canEdit || editingField !== 'approval_date'" @click="startEdit('approval_date')" :class="{ 'field-editable': canEdit }">{{ detail.approval_date || '-' }}</span>
          <a-date-picker v-else :value="detail.approval_date ? dayjs(detail.approval_date) : null" @change="(d) => saveField('approval_date', d)" :open="true" value-format="YYYY-MM-DD" size="small" style="width:180px" />
        </a-descriptions-item>
        <a-descriptions-item label="登记机关" :span="2">
          <span v-if="!canEdit || editingField !== 'registration_authority'" @click="startEdit('registration_authority')" :class="{ 'field-editable': canEdit }">{{ detail.registration_authority || '-' }}</span>
          <a-input v-else :value="detail.registration_authority" @press-enter="(e) => saveField('registration_authority', e.target.value)" @blur="(e) => saveField('registration_authority', e.target.value)" size="small" style="width:300px" />
        </a-descriptions-item>
        <a-descriptions-item label="国标行业">
          <span v-if="!canEdit || editingField !== 'national_industry'" @click="startEdit('national_industry')" :class="{ 'field-editable': canEdit }">{{ detail.national_industry || '-' }}</span>
          <a-input v-else :value="detail.national_industry" @press-enter="(e) => saveField('national_industry', e.target.value)" @blur="(e) => saveField('national_industry', e.target.value)" size="small" style="width:180px" />
        </a-descriptions-item>
        <a-descriptions-item label="公司电话">
          <span v-if="!canEdit || editingField !== 'telephone'" @click="startEdit('telephone')" :class="{ 'field-editable': canEdit }">{{ detail.telephone || '-' }}</span>
          <a-input v-else :value="detail.telephone" @press-enter="(e) => saveField('telephone', e.target.value)" @blur="(e) => saveField('telephone', e.target.value)" size="small" style="width:180px" />
        </a-descriptions-item>
        <a-descriptions-item label="公司邮箱">
          <span v-if="!canEdit || editingField !== 'email'" @click="startEdit('email')" :class="{ 'field-editable': canEdit }">{{ detail.email || '-' }}</span>
          <a-input v-else :value="detail.email" @press-enter="(e) => saveField('email', e.target.value)" @blur="(e) => saveField('email', e.target.value)" size="small" style="width:180px" />
        </a-descriptions-item>
        <a-descriptions-item label="注册地址" :span="3">
          <span v-if="!canEdit || editingField !== 'registered_address'" @click="startEdit('registered_address')" :class="{ 'field-editable': canEdit }">{{ detail.registered_address || '-' }}</span>
          <a-input v-else :value="detail.registered_address" @press-enter="(e) => saveField('registered_address', e.target.value)" @blur="(e) => saveField('registered_address', e.target.value)" size="small" style="width:400px" />
        </a-descriptions-item>
        <a-descriptions-item label="经营范围" :span="3">
          <span v-if="!canEdit || editingField !== 'business_scope'" @click="startEdit('business_scope')" :class="{ 'field-editable': canEdit }">{{ detail.business_scope || '-' }}</span>
          <a-textarea v-else :value="detail.business_scope" @press-enter="(e) => saveField('business_scope', e.target.value)" @blur="(e) => saveField('business_scope', e.target.value)" size="small" :rows="3" style="width:400px" />
        </a-descriptions-item>
        <a-descriptions-item label="公司简介" :span="3">
          <span v-if="!canEdit || editingField !== 'introduction'" @click="startEdit('introduction')" :class="{ 'field-editable': canEdit }">{{ detail.introduction || '-' }}</span>
          <a-textarea v-else :value="detail.introduction" @press-enter="(e) => saveField('introduction', e.target.value)" @blur="(e) => saveField('introduction', e.target.value)" size="small" :rows="3" style="width:400px" />
        </a-descriptions-item>
      </a-descriptions>
    </a-card>

    <a-card v-if="portraitData" size="small" title="客户画像" style="margin-bottom:16px">
      <a-descriptions :column="2" size="small">
        <a-descriptions-item label="概况" :span="2">{{ portraitData.summary }}</a-descriptions-item>
        <a-descriptions-item label="核心需求">{{ portraitData.needs }}</a-descriptions-item>
        <a-descriptions-item label="痛点">{{ portraitData.pain_points }}</a-descriptions-item>
        <a-descriptions-item label="建议策略" :span="2">{{ portraitData.approach }}</a-descriptions-item>
      </a-descriptions>
    </a-card>

    <!-- 快速跟进 — 永远可见 -->
    <a-card v-if="canEditRelated" size="small" title="快速添加跟进" style="margin-bottom:16px">
      <a-form layout="inline">
        <a-form-item label="跟进方式">
          <a-select v-model:value="followupForm.follow_type" style="width:120px" allow-clear>
            <a-select-option v-for="op in dictOpts.follow_types" :key="op.config_value" :value="op.config_name">{{ op.config_name }}</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="跟进结果">
          <a-select v-model:value="followupForm.result" style="width:100px" allow-clear>
            <a-select-option v-for="op in dictOpts.follow_results" :key="op.config_value" :value="op.config_name">{{ op.config_name }}</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="下次跟进">
          <a-date-picker v-model:value="followupForm.next_follow_date" style="width:140px" />
        </a-form-item>
        <a-form-item>
          <a-button type="primary" @click="handleAddFollow" :loading="followupLoading">添加</a-button>
        </a-form-item>
      </a-form>
      <a-upload :show-upload-list="false" :before-upload="handleUpload" accept="*" style="margin-top:6px">
        <a-button size="small"><paper-clip-outlined /> {{ uploadFileName || '附件' }}</a-button>
      </a-upload>
      <RichEditor v-model="followupForm.follow_content" :height="200" style="margin-top:8px" />
    </a-card>

    <!-- Tab 切换区 -->
    <a-tabs default-active-key="followups">
      <a-tab-pane key="followups" tab="跟进记录">
        <a-timeline>
          <a-timeline-item v-for="item in followups" :key="item.id" :color="item.result === '有效' ? 'green' : item.result === '无效' ? 'red' : 'blue'">
            <p style="margin-bottom:4px">
              <strong>{{ item.owner_user_name }}</strong>
              <a-tag v-if="item.follow_type" style="margin-left:8px">{{ item.follow_type }}</a-tag>
              <a-tag v-if="item.result" :color="item.result === '有效' ? 'green' : item.result === '无效' ? 'red' : 'default'">{{ item.result }}</a-tag>
              <span style="color:#999;margin-left:8px">{{ item.follow_date ? new Date(item.follow_date * 1000).toLocaleString() : '' }}</span>
            </p>
            <div v-html="decodeHtml(item.follow_content)" class="follow-content"></div>
            <p v-if="item.attachment" style="margin-top:4px"><paper-clip-outlined /> <a :href="uploadBaseUrl + item.attachment" target="_blank">附件</a></p>
            <p v-if="item.next_follow_date" style="color:#999">下次跟进：{{ new Date(item.next_follow_date * 1000).toLocaleDateString() }}</p>
            <p v-if="item.ai_suggestion" style="color:#1890ff;font-style:italic;margin-top:4px">🤖 {{ item.ai_suggestion }}</p>
            <a-popconfirm v-if="canEditRelated" title="确认删除?" @confirm="handleDeleteFollow(item)">
              <a style="color:#ff4d4f">删除</a>
            </a-popconfirm>
          </a-timeline-item>
          <a-timeline-item v-if="followups.length === 0" color="gray">暂无跟进记录</a-timeline-item>
        </a-timeline>
      </a-tab-pane>
      <a-tab-pane key="contacts" tab="联系人">
        <a-button v-if="canEditRelated" type="primary" @click="handleAddContact" style="margin-bottom:12px"><plus-outlined /> 新增联系人</a-button>
        <a-table :data-source="contacts" :pagination="false" bordered row-key="id" :loading="contactsLoading" size="small">
          <a-table-column title="姓名" data-index="contact_name" key="contact_name" />
          <a-table-column title="职位" data-index="position" key="position" />
          <a-table-column title="手机" data-index="mobile" key="mobile" width="120" />
          <a-table-column title="生日" data-index="birthday" key="birthday" width="100" />
          <a-table-column title="籍贯" data-index="hometown" key="hometown" width="80" />
          <a-table-column title="邮箱" data-index="email" key="email" />
          <a-table-column title="首要" data-index="is_primary" key="is_primary" width="60">
            <template #default="{ text }"><a-tag v-if="text" color="blue">是</a-tag></template>
          </a-table-column>
          <a-table-column v-if="canEditRelated" title="操作" key="action" width="160">
            <template #default="{ record }">
              <a @click="handleEditContact(record)">编辑</a>
              <a-divider type="vertical" />
              <a-popconfirm title="解除与该客户的绑定?" @confirm="handleUnbindContact(record)">
                <a>解绑</a>
              </a-popconfirm>
              <a-divider type="vertical" />
              <a-popconfirm title="确认删除?" @confirm="handleDeleteContact(record)">
                <a style="color:#ff4d4f">删除</a>
              </a-popconfirm>
            </template>
          </a-table-column>
        </a-table>
      </a-tab-pane>
      <a-tab-pane v-if="canManageCollab" key="collabs" tab="协作成员">
        <div style="margin-bottom:12px">
          <a-select v-model:value="collabUserId" placeholder="选择要共享的同事" show-search :filter-option="filterUser" allow-clear style="width:200px">
            <a-select-option v-for="u in collabCandidates" :key="u.store_user_id" :value="u.store_user_id">{{ u.real_name || u.user_name }}</a-select-option>
          </a-select>
          <a-select v-model:value="collabPerm" style="width:100px;margin-left:8px">
            <a-select-option :value="1">只读</a-select-option>
            <a-select-option :value="2">可编辑</a-select-option>
          </a-select>
          <a-button type="primary" style="margin-left:8px" @click="handleAddCollab" :loading="collabLoading">添加</a-button>
        </div>
        <a-table :data-source="collabs" :pagination="false" row-key="id" size="small">
          <a-table-column title="姓名" data-index="real_name" key="real_name" />
          <a-table-column title="用户名" data-index="user_name" key="user_name" width="100" />
          <a-table-column title="权限" data-index="permission" key="permission" width="80">
            <template #default="{ text }"><a-tag>{{ text === 2 ? '可编辑' : '只读' }}</a-tag></template>
          </a-table-column>
          <a-table-column title="原因" data-index="remark" key="remark" width="120" />
          <a-table-column title="操作" key="action" width="60">
            <template #default="{ record }">
              <a-popconfirm title="移除?" @confirm="handleRemoveCollab(record)"><a style="color:#ff4d4f">移除</a></a-popconfirm>
            </template>
          </a-table-column>
        </a-table>
      </a-tab-pane>
      <a-tab-pane key="quotations" tab="报价单">
        <a-table :data-source="quotations" :pagination="false" row-key="id" size="small">
          <a-table-column title="报价单号" data-index="quotation_no" key="quotation_no" width="180">
            <template #default="{ text, record }"><a @click="router.push({path:'/crm/quotation/detail',query:{id:record.id}})">{{ text }}</a></template>
          </a-table-column>
          <a-table-column title="币种" data-index="currency" key="currency" width="60" />
          <a-table-column title="折后金额" data-index="final_amount" key="final_amount" width="120" align="right">
            <template #default="{ text }">{{ (text || 0).toLocaleString('zh-CN', { minimumFractionDigits: 2 }) }}</template>
          </a-table-column>
          <a-table-column title="状态" data-index="status" key="status" width="80">
            <template #default="{ text }"><a-tag :color="['','','blue','green','red','purple'][text]">{{ ['','草稿','已发送','已确认','已拒绝','已转订单'][text] }}</a-tag></template>
          </a-table-column>
          <a-table-column title="日期" data-index="quotation_date" key="quotation_date" width="110">
            <template #default="{ text }">{{ text ? new Date(text * 1000).toLocaleDateString() : '' }}</template>
          </a-table-column>
        </a-table>
      </a-tab-pane>
      <a-tab-pane key="orders" tab="销售订单">
        <a-table :data-source="orders" :pagination="false" row-key="id" size="small">
          <a-table-column title="订单号" data-index="order_no" key="order_no" width="180">
            <template #default="{ text, record }"><a @click="router.push({path:'/crm/order/detail',query:{id:record.id}})">{{ text }}</a></template>
          </a-table-column>
          <a-table-column title="币种" data-index="currency" key="currency" width="60" />
          <a-table-column title="金额" data-index="final_amount" key="final_amount" width="120" align="right">
            <template #default="{ text }">{{ (text || 0).toLocaleString('zh-CN', { minimumFractionDigits: 2 }) }}</template>
          </a-table-column>
          <a-table-column title="未付" data-index="unpaid_amount" key="unpaid_amount" width="120" align="right">
            <template #default="{ text }">{{ (text || 0).toLocaleString('zh-CN', { minimumFractionDigits: 2 }) }}</template>
          </a-table-column>
          <a-table-column title="状态" data-index="status" key="status" width="80">
            <template #default="{ text }"><a-tag>{{ ['','待确认','生产中','待发货','已发货','已完成','已取消'][text] }}</a-tag></template>
          </a-table-column>
          <a-table-column title="付款" data-index="payment_status" key="payment_status" width="80">
            <template #default="{ text }"><a-tag :color="text===3?'green':text===2?'orange':''">{{ ['','未付','部分','已付'][text] }}</a-tag></template>
          </a-table-column>
        </a-table>
      </a-tab-pane>
      <a-tab-pane key="contracts" tab="合同">
        <a-table :data-source="contracts" :pagination="false" row-key="id" size="small">
          <a-table-column title="合同号" data-index="contract_no" key="contract_no" width="180">
            <template #default="{ text, record }"><a @click="router.push({path:'/crm/contract/detail',query:{id:record.id}})">{{ text }}</a></template>
          </a-table-column>
          <a-table-column title="合同名称" data-index="contract_name" key="contract_name" width="150" />
          <a-table-column title="币种" data-index="currency" key="currency" width="60" />
          <a-table-column title="金额" data-index="contract_amount" key="contract_amount" width="120" align="right">
            <template #default="{ text }">{{ (text || 0).toLocaleString('zh-CN', { minimumFractionDigits: 2 }) }}</template>
          </a-table-column>
          <a-table-column title="状态" data-index="status" key="status" width="80">
            <template #default="{ text }"><a-tag>{{ ['','草稿','已签订','履行中','已完成','已终止'][text] }}</a-tag></template>
          </a-table-column>
          <a-table-column title="签订日期" data-index="sign_date" key="sign_date" width="110">
            <template #default="{ text }">{{ text ? new Date(text * 1000).toLocaleDateString() : '' }}</template>
          </a-table-column>
        </a-table>
      </a-tab-pane>
    </a-tabs>

    <CustomerForm v-model:open="formVisible" :mdl="detail" @ok="loadDetail" />
    <ContactForm v-model:open="contactFormVisible" :mdl="contactSelected" :customer-id="detail.id" @ok="loadContacts" />
  </a-card>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { PlusOutlined, PaperClipOutlined } from '@ant-design/icons-vue'
import dayjs from 'dayjs'
import { getDetail, release, edit, analyze, portrait, claim as claimApi } from '@/api/crm/customer'
import { getByCustomer as getContacts, unbind as unbindContact, deleteById as deleteContact } from '@/api/crm/contact'
import { getList as getFollowups, add as addFollowup, deleteById as deleteFollowup } from '@/api/crm/followUp'
import { getList as getQuotations } from '@/api/crm/quotation'
import { getList as getOrders } from '@/api/crm/order'
import { getList as getContracts } from '@/api/crm/contract'
import { getList as getCollabs, add as addCollab, remove as removeCollab } from '@/api/crm/collaborator'
import { list as getStoreUsers } from '@/api/user'
import { getAll as getConfigs } from '@/api/crm/config'
import { tree as getRegionTree } from '@/api/region'
import request from '@/utils/request'
import RichEditor from '@/components/RichEditor/index.vue'
import CustomerForm from './components/CustomerForm.vue'
import ContactForm from './components/ContactForm.vue'

const route = useRoute()
const router = useRouter()

// ---- State ----
const loading = ref(false)
const detail = reactive({})
const from = ref('')

// Follow-up
const followups = ref([])
const followupLoading = ref(false)
const followupForm = reactive({ follow_type: '', result: '有效', follow_content: '', next_follow_date: null, attachment: '' })
const uploadFileName = ref('')

// Contacts
const contacts = ref([])
const contactsLoading = ref(false)

// Related
const quotations = ref([])
const orders = ref([])
const contracts = ref([])

// Collab
const collabs = ref([])
const collabUserId = ref(null)
const collabPerm = ref(1)
const collabLoading = ref(false)
const storeUsers = ref([])

// Dict & region
const dictOpts = reactive({ industry: [], customer_level: [], customer_source: [], customer_group: [], follow_types: [], follow_results: [], funnel_stages: [
  { config_value: '1', config_name: '初步接触' }, { config_value: '2', config_name: '需求确认' },
  { config_value: '3', config_name: '报价' }, { config_value: '4', config_name: '谈判' }, { config_value: '5', config_name: '成交' },
] })
const regionTree = ref([])
const regionCascader = ref([])

// Edit
const editingField = ref('')
const fieldSaving = ref(false)
const aiAnalyzing = ref(false)
const portraitData = ref(null)
const portraitLoading = ref(false)

// Modals
const formVisible = ref(false)
const contactFormVisible = ref(false)
const contactSelected = ref(null)

// ---- Computed ----
const isPool = computed(() => from.value === 'pool')
const isCollabReadonly = computed(() => from.value === 'collab' && detail.collab_permission === 1)
const isCollabEdit = computed(() => from.value === 'collab' && detail.collab_permission === 2)
const canEdit = computed(() => !from.value || from.value === 'index' || isCollabEdit.value)
const canEditRelated = computed(() => !from.value || from.value === 'index' || isCollabEdit.value)
const canRelease = computed(() => (!from.value || from.value === 'index') && !!detail.owner_user_id)
const showClaim = computed(() => !detail.owner_user_id)
const canManageCollab = computed(() => !from.value || from.value === 'index')
const uploadBaseUrl = computed(() => (window.publicConfig?.BASE_API || '').replace(/\/index\.php.*$/, '') + '/uploads/')
const aiTagList = computed(() => (detail.ai_tags || '').split(',').filter(t => t))
const aiScoreColor = computed(() => { const s = detail.ai_score || 0; return s >= 80 ? 'red' : s >= 50 ? 'orange' : 'gray' })

const collabCandidates = computed(() => {
  const excludeIds = collabs.value.map(c => c.user_id)
  if (detail.owner_user_id) excludeIds.push(detail.owner_user_id)
  return storeUsers.value.filter(u => !excludeIds.includes(u.store_user_id))
})

const regionOptions = computed(() => {
  const toArr = (v) => Array.isArray(v) ? v : Object.values(v || {})
  const build = (nodes) => toArr(nodes).map(n => ({
    value: n.id, label: n.name, children: build(n.city || n.region || {}),
  }))
  return build(regionTree.value)
})

const aiSummary = computed(() => {
  const d = detail
  const p = portraitData.value || {}
  if (!d.id) return ''
  let html = '<b>📊 客户速览</b><br>客户：<b>' + (d.customer_name || '未知') + '</b>'
  if (d.industry) html += ' · ' + d.industry
  if (d.level_name) html += ' · ' + d.level_name
  if (d.funnel_stage) html += ' · ' + ['','初步接触','需求确认','报价','谈判','成交'][d.funnel_stage]
  const ords = orders.value; const qts = quotations.value; const fws = followups.value
  const sixMonthAgo = Date.now() - 180 * 86400000
  const recentOrders = ords.filter(o => (o.create_time || 0) * 1000 > sixMonthAgo)
  const recentFollowups = fws.filter(f => (f.follow_date || 0) * 1000 > sixMonthAgo)
  const activeQuotations = qts.filter(q => q.status === 1 || q.status === 2)
  const lastFollowup = fws.length ? fws[0] : null
  html += '<br>近6个月：<b>' + recentOrders.length + '</b> 笔订单'
  if (recentOrders.length) html += '（合计 ¥' + recentOrders.reduce((s, o) => s + parseFloat(o.final_amount || 0), 0).toLocaleString() + '）'
  html += ' · <b>' + recentFollowups.length + '</b> 次跟进'
  if (ords.length) html += ' · 历史累计 ' + ords.length + ' 笔'
  if (activeQuotations.length) html += ' · <b style=color:#faad14>' + activeQuotations.length + '</b> 个报价进行中'
  if (lastFollowup) {
    const days = Math.floor((Date.now() - lastFollowup.follow_date * 1000) / 86400000)
    html += '<br>最近跟进：' + days + '天前 [' + (lastFollowup.follow_type || '') + '] '
    html += decodeHtml(lastFollowup.follow_content || '').replace(/<[^>]*>/g, '').substring(0, 60)
  }
  if (p.summary) html += '<br><b>🔍 画像：</b>' + p.summary
  if (p.needs) html += ' · ' + p.needs
  if (p.pain_points) html += '<br><b>⚠ 痛点：</b>' + p.pain_points
  if (p.approach) html += '<br><b>💡 建议：</b>' + p.approach
  if (d.ai_score !== undefined && d.ai_score !== null && d.ai_score > 0) {
    html += '<br><b>📈 AI评分：</b>' + d.ai_score + '分'
    if (aiTagList.value.length) html += ' · 标签：' + aiTagList.value.join('、')
  }
  return html
})

// ---- Load ----
function loadDetail() {
  const id = route.query.id
  if (!id) return
  loading.value = true
  const params = from.value ? { from: from.value } : {}
  getDetail(id, params).then(res => {
    Object.assign(detail, res?.data?.detail || res?.data || {})
    loading.value = false
    try { if (detail.ai_portrait) portraitData.value = JSON.parse(detail.ai_portrait) } catch (e) {}
  }).catch(() => { loading.value = false })
}

function loadContacts() {
  const id = route.query.id; if (!id) return
  contactsLoading.value = true
  getContacts(id).then(res => { contacts.value = (res?.data?.list || []); contactsLoading.value = false }).catch(() => { contactsLoading.value = false })
}
function loadFollowups() { const id = route.query.id; if (!id) return; getFollowups(id).then(res => { followups.value = (res?.data?.list?.data || res?.data?.list || []) }).catch(() => {}) }
function loadQuotations() { const id = route.query.id; if (!id) return; getQuotations({ customer_id: id }).then(res => { quotations.value = (res?.data?.list?.data || res?.data?.list || []) }).catch(() => {}) }
function loadOrders() { const id = route.query.id; if (!id) return; getOrders({ customer_id: id }).then(res => { orders.value = (res?.data?.list?.data || res?.data?.list || []) }).catch(() => {}) }
function loadContracts() { const id = route.query.id; if (!id) return; getContracts({ customer_id: id }).then(res => { contracts.value = (res?.data?.list?.data || res?.data?.list || []) }).catch(() => {}) }
function loadCollabs() { const id = route.query.id; if (!id) return; getCollabs(id).then(r => { collabs.value = r?.data?.list || [] }) }
function loadStoreUsers() { getStoreUsers({}).then(r => { const l = r?.data?.list?.data || r?.data?.list || []; storeUsers.value = l.filter(u => u.is_supplier !== 1) }) }
function loadDicts() {
  getConfigs().then(res => {
    const data = res?.data || {}
    const toOpts = (arr) => (arr || []).filter(i => i.config_value)
    dictOpts.industry = toOpts(data.industry)
    dictOpts.customer_level = toOpts(data.customer_level)
    dictOpts.customer_source = toOpts(data.customer_source)
    dictOpts.customer_group = toOpts(data.customer_group)
    dictOpts.follow_types = toOpts(data.follow_type)
    dictOpts.follow_results = toOpts(data.follow_result)
    if (dictOpts.follow_types.length > 0) followupForm.follow_type = dictOpts.follow_types[0].config_name
  })
}

// ---- Follow-up actions ----
function handleUpload(file) {
  uploadFileName.value = file.name
  const formData = new FormData()
  formData.append('iFile', file)
  request({ url: '/upload/file', method: 'post', data: formData, headers: { 'Content-Type': 'multipart/form-data' } })
    .then(res => { followupForm.attachment = res?.data?.fileInfo?.file_path || ''; message.success('上传成功') }).catch(() => { message.error('上传失败') })
  return false
}

function handleAddFollow() {
  const id = route.query.id
  if (!followupForm.follow_content) { message.warning('请输入跟进内容'); return }
  followupLoading.value = true
  const data = { customer_id: parseInt(id), follow_type: followupForm.follow_type, follow_content: followupForm.follow_content, result: followupForm.result, attachment: followupForm.attachment }
  if (followupForm.next_follow_date) data.next_follow_date = dayjs(followupForm.next_follow_date).format('YYYY-MM-DD')
  addFollowup(data).then(() => {
    message.success('跟进记录已添加')
    followupForm.follow_content = ''; followupForm.next_follow_date = null; followupForm.attachment = ''; uploadFileName.value = ''
    followupLoading.value = false; loadFollowups(); loadDetail()
  }).catch(() => { followupLoading.value = false })
}

function handleDeleteFollow(item) { deleteFollowup(item.id).then(() => { message.success('已删除'); loadFollowups() }) }

// ---- Contact actions ----
function handleAddContact() { contactSelected.value = null; contactFormVisible.value = true }
function handleEditContact(record) { contactSelected.value = record; contactFormVisible.value = true }
function handleUnbindContact(record) { unbindContact(record.id, route.query.id).then(() => { message.success('已解除绑定'); loadContacts() }) }
function handleDeleteContact(record) { deleteContact(record.id).then(() => { message.success('删除成功'); loadContacts() }) }

// ---- Collab actions ----
function filterUser(input, option) { const label = option.children?.default?.() || option.label || ''; return String(label).toLowerCase().indexOf(input.toLowerCase()) >= 0 }
function handleAddCollab() {
  if (!collabUserId.value) return message.warning('请选择同事')
  collabLoading.value = true
  addCollab({ customer_id: parseInt(route.query.id), user_id: collabUserId.value, permission: collabPerm.value }).then(() => {
    message.success('已添加'); collabLoading.value = false; collabUserId.value = null; collabPerm.value = 1; loadCollabs()
  }).catch(() => { collabLoading.value = false })
}
function handleRemoveCollab(r) { removeCollab(r.id).then(() => { message.success('已移除'); loadCollabs() }) }

// ---- AI ----
function handleAiAnalyze() { aiAnalyzing.value = true; analyze(detail.id).then(() => { message.success('AI分析完成'); aiAnalyzing.value = false; loadDetail() }).catch(() => { aiAnalyzing.value = false }) }
function handlePortrait() { portraitLoading.value = true; portraitData.value = null; portrait(detail.id).then(res => { portraitData.value = res?.data?.portrait || {}; portraitLoading.value = false; message.success('画像已生成') }).catch(() => { portraitLoading.value = false }) }
function handleClaim() { claimApi(detail.id).then(() => { message.success('认领成功'); loadDetail() }) }
function handleRelease() { release(detail.id).then(() => { message.success('已释放到公海'); loadDetail() }) }

// ---- Inline edit ----
function startEdit(field) { if (canEdit.value) editingField.value = field }
function startEditRegion() {
  if (!canEdit.value) return
  regionCascader.value = regionIds()
  editingField.value = 'region'
}
function saveField(field, value, nameField, nameValue) {
  if (fieldSaving.value) return
  if (value === undefined || value === null) { editingField.value = ''; return }
  fieldSaving.value = true
  const data = { id: detail.id, customer_name: detail.customer_name }
  data[field] = value
  if (nameField && nameValue !== undefined) data[nameField] = nameValue
  edit(data).then(() => { message.success('已更新'); loadDetail(); editingField.value = ''; fieldSaving.value = false }).catch(() => { fieldSaving.value = false })
}
function saveRegion(ids) {
  if (!Array.isArray(ids) || ids.length === 0) return
  const toArr = (v) => Array.isArray(v) ? v : Object.values(v || {})
  const find = (obj, id) => { for (const i of toArr(obj)) { if (i.id == id) return i } return null }
  const [provId, cityId, areaId] = ids
  const prov = find(regionTree.value, provId)
  if (!prov) return
  const city = find(prov.city || prov.region || {}, cityId)
  const area = city ? find(city.region || {}, areaId) : null
  editingField.value = ''; fieldSaving.value = true
  edit({ id: detail.id, customer_name: detail.customer_name, province: prov.name, city: city ? city.name : '', area: area ? area.name : '' }).then(() => {
    message.success('已更新'); loadDetail(); fieldSaving.value = false
  }).catch(() => { fieldSaving.value = false })
}

// ---- Helpers ----
function decodeHtml(html) { if (!html) return ''; return html.replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&').replace(/&quot;/g, '"') }
function toArray(list) { if (!list) return []; return Array.isArray(list) ? list : Object.values(list) }
function findRegionId(tree, name, skipId) {
  if (!name) return null
  for (const item of toArray(tree)) { if (item.name === name && item.id !== skipId) return item.id; const children = item.city || item.region || {}; const found = findRegionId(children, name); if (found) return found }
  return null
}
function regionIds() {
  if (!detail.province) return []
  const provId = findRegionId(regionTree.value, detail.province)
  if (!provId) return []
  const ids = [provId]
  if (detail.city) { const cityId = findRegionId(regionTree.value, detail.city, provId); if (cityId) { ids.push(cityId); if (detail.area && cityId) { const areaId = findRegionId(regionTree.value, detail.area, cityId); if (areaId) ids.push(areaId) } } }
  return ids
}

// ---- Init ----
function loadRegions() {
  getRegionTree().then(res => {
    regionTree.value = res?.data?.list || []
  }).catch(() => {})
}

onMounted(() => {
  from.value = route.query.from || ''
  loadDicts(); loadRegions(); loadDetail(); loadFollowups(); loadContacts(); loadQuotations(); loadOrders(); loadContracts(); loadCollabs(); loadStoreUsers()
})
</script>

<style scoped>
.field-editable { cursor: pointer; display: inline-block; min-width: 60px; padding: 2px 6px; margin: -2px -6px; border-radius: 3px; transition: background .2s; }
.field-editable:hover { background: #e6f7ff; }
.follow-content { line-height: 1.8; word-break: break-word; overflow-x: auto; }
.follow-content :deep(img) { max-width: 100% !important; height: auto !important; }
.follow-content :deep(table) { border-collapse: collapse; width: 100%; font-size: 12px; }
.follow-content :deep(td), .follow-content :deep(th) { border: 1px solid #e8e8e8; padding: 4px 8px; }
.follow-content :deep(p) { margin: 4px 0; }
.follow-content :deep(blockquote) { border-left: 3px solid #1890ff; padding: 4px 12px; margin: 8px 0; color: #666; background: #fafafa; }
</style>
