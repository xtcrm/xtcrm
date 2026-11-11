<template>
  <a-card :bordered="false" class="customer-detail">
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
      <a-descriptions-item label="AI评分">
        <template v-if="detail.ai_score > 0">
          <a-tag :color="aiScoreColor">{{ detail.ai_score }} 分</a-tag>
        </template>
        <template v-else>-</template>
      </a-descriptions-item>
      <a-descriptions-item label="AI标签">
        <template v-if="detail.ai_tags">
          <a-tag v-for="tag in aiTagList" :key="tag" style="margin-right:4px">{{ tag }}</a-tag>
        </template>
        <template v-else>-</template>
      </a-descriptions-item>
      <a-descriptions-item label="标签分类" :span="2">
        <template v-if="customerTags.length">
          <div class="detail-tags">
            <div v-for="g in groupedTags" :key="g.group_name" class="detail-tag-group">
              <span class="detail-tag-group-name">{{ g.group_name }}</span>
              <a-tag v-for="t in g.tags" :key="t.id" :color="t.color" style="margin-right:4px;margin-bottom:4px">{{ t.name }}</a-tag>
            </div>
          </div>
        </template>
        <template v-else>{{ canEdit ? '暂无标签' : '-' }}</template>
        <a-button v-if="canEdit" type="link" size="small" style="padding:0;height:auto;margin-left:4px" @click="tagPickerOpen = true">
          {{ customerTags.length ? '编辑标签' : '添加标签' }}
        </a-button>
      </a-descriptions-item>
      <a-descriptions-item label="AI建议" :span="2">
        <template v-if="detail.ai_suggestion">{{ detail.ai_suggestion }}</template>
        <template v-else>-</template>
      </a-descriptions-item>
      <a-descriptions-item label="备注">
        <span v-if="!canEdit || editingField !== 'remark'" @click="startEdit('remark')" :class="{ 'field-editable': canEdit }">{{ detail.remark || '-' }}</span>
        <a-textarea v-else :value="detail.remark" @press-enter="(e) => saveField('remark', e.target.value)" @blur="(e) => saveField('remark', e.target.value)" size="small" :rows="3" style="width:160px" />
      </a-descriptions-item>
    </a-descriptions>

    <!-- 工商档案（entquery skill）— 租户开关默认关闭，避免未配数据源时每条客户都弹查询失败 -->
    <ArchivePanel
      v-if="entqueryEnabled"
      :credit-code="detail.tax_number || ''"
      :company-name="detail.customer_name || ''"
      title="工商档案"
      style="margin-bottom:16px"
    />

    <!-- 自定义字段（扩展信息） -->
    <a-card v-if="customFields.length > 0" size="small" title="扩展信息" style="margin-bottom:16px">
      <a-descriptions :column="2" size="small" bordered>
        <a-descriptions-item v-for="(f, idx) in customFields" :key="f.field_key" :label="f.field_label"
          :span="idx === customFields.length - 1 && customFields.length % 2 === 1 ? 2 : 1">
          <span v-if="!canEdit || editingField !== 'extra_'+f.field_key"
                @click="startEdit('extra_'+f.field_key)"
                :class="{ 'field-editable': canEdit }">
            {{ extraData[f.field_key] || '-' }}
          </span>
          <a-input v-else-if="f.field_type==='text'"
                   :value="extraData[f.field_key]"
                   @press-enter="(e) => saveExtraField(f.field_key, e.target.value)"
                   @blur="(e) => saveExtraField(f.field_key, e.target.value)"
                   size="small" style="width:200px" />
          <a-select v-else-if="f.field_type==='select'"
                   :value="extraData[f.field_key]"
                   @change="(v) => saveExtraField(f.field_key, v)"
                   size="small" style="width:200px" allow-clear>
            <a-select-option v-for="opt in (f.options||[])" :key="opt" :value="opt">{{ opt }}</a-select-option>
          </a-select>
          <a-input v-else
                   :value="extraData[f.field_key]"
                   @press-enter="(e) => saveExtraField(f.field_key, e.target.value)"
                   @blur="(e) => saveExtraField(f.field_key, e.target.value)"
                   size="small" style="width:200px" />
        </a-descriptions-item>
      </a-descriptions>
    </a-card>

    <!-- 商机需求 -->
    <a-card v-if="demands.length > 0" size="small" title="商机需求" style="margin-bottom:16px">
      <div v-for="d in demands" :key="d.id" style="display:flex;align-items:center;padding:8px 0;border-bottom:1px solid #f5f5f5">
        <a-tag :color="d.priority===1?'red':d.priority===2?'orange':'blue'" style="margin-right:8px;flex-shrink:0">
          {{ d.priority===1?'高':d.priority===2?'中':'低' }}
        </a-tag>
        <span style="flex:1;min-width:0"><strong>{{ d.rule_name }}</strong></span>
        <span style="color:#999;font-size:12px;margin:0 12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px">{{ d.reason }}</span>
        <a-button size="small" type="primary" ghost @click="handleDemandFollow(d)">跟进</a-button>
        <a-button size="small" style="margin-left:4px" @click="handleDemandDismiss(d)">忽略</a-button>
      </div>
    </a-card>

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
    <a-card v-if="canEditRelated" size="small" style="margin-bottom:16px" @keydown="onFollowShortcut">
      <template #title>
        快速添加跟进
        <span class="followup-hint">@ 可提及公司/联系人 · Ctrl + Enter 提交</span>
      </template>
      <div class="followup-toolbar">
        <a-select v-model:value="followupForm.follow_type" style="width:100px" size="small" allow-clear placeholder="方式">
          <a-select-option v-for="op in dictOpts.follow_types" :key="op.config_value" :value="op.config_name">{{ op.config_name }}</a-select-option>
        </a-select>
        <a-select v-model:value="followupForm.result" style="width:90px" size="small" allow-clear placeholder="结果">
          <a-select-option v-for="op in dictOpts.follow_results" :key="op.config_value" :value="op.config_name">{{ op.config_name }}</a-select-option>
        </a-select>
        <a-date-picker v-model:value="followupForm.next_follow_date" size="small" style="width:130px" placeholder="下次跟进" />
        <a-upload :show-upload-list="false" :before-upload="handleUpload" accept="*">
          <a-button size="small"><paper-clip-outlined /> 附件</a-button>
        </a-upload>
        <a-tag v-if="followupForm.attachment" color="blue" closable style="max-width:220px" @close.prevent="clearFollowAttachment">
          <a style="cursor:pointer" @click="previewAttachment(followupForm.attachment)">{{ uploadFileName || '已上传附件' }}</a>
        </a-tag>
        <span class="followup-spacer"></span>
        <a-auto-complete
          v-model:value="mentionSearch"
          :options="mentionOptions"
          style="width:220px"
          size="small"
          placeholder="@搜索公司/联系人"
          :filter-option="false"
          @search="handleMentionSearch"
          @select="onMentionSelect"
          allow-clear
        >
          <!-- 空/失败状态直接在界面上显示，不用开控制台 -->
          <template #notFoundContent>
            <span v-if="mentionError" style="color:#ff4d4f">{{ mentionError }}</span>
            <span v-else style="color:#999">{{ mentionSearch ? '没有匹配的公司/联系人' : '输入关键词搜索公司 / 联系人' }}</span>
          </template>
          <template #option="opt">
            <div style="display:flex;align-items:center;justify-content:space-between">
              <span><span style="margin-right:4px">{{ opt.type === 'customer' ? '🏢' : '👤' }}</span>{{ opt.label }}</span>
              <span style="color:#999;font-size:11px">{{ opt.subtitle }}</span>
            </div>
          </template>
        </a-auto-complete>
      </div>
      <RichEditor ref="richEditorRef" v-model="followupForm.follow_content" :height="160" />
      <div class="followup-actions">
        <a-button type="primary" size="small" :disabled="!hasFollowContent" :loading="followupLoading" @click="handleAddFollow">添加跟进</a-button>
      </div>
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
            <p v-if="item.attachment" style="margin-top:4px"><paper-clip-outlined /> <a style="cursor:pointer" @click="previewAttachment(item.attachment)">附件</a></p>
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
          <a-table-column title="身份证号" data-index="id_card" key="id_card" width="170" ellipsis />
          <a-table-column title="籍贯" data-index="hometown" key="hometown" width="130" ellipsis />
          <a-table-column title="邮箱" data-index="email" key="email" />
          <a-table-column title="标签" key="tags" width="180">
            <template #default="{ record }">
              <template v-if="(record.tags || []).length">
                <a-tag v-for="t in record.tags" :key="t.id" :color="t.color" style="margin-right:4px;margin-bottom:2px">{{ t.name }}</a-tag>
              </template>
              <template v-else>-</template>
            </template>
          </a-table-column>
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
      <a-tab-pane key="events" tab="事件">
        <a-table :data-source="customerEvents" :pagination="false" row-key="id" size="small" :loading="eventsLoading">
          <a-table-column title="日期" key="date" width="130">
            <template #default="{ record }">
              {{ record._date }}
              <a-tag v-if="record.is_lunar" color="purple" style="margin-left:4px;font-size:10px">农历</a-tag>
            </template>
          </a-table-column>
          <a-table-column title="类型" key="type" width="80">
            <template #default="{ record }">
              <a-tag :color="eventColor(record)">{{ record.follow_type }}</a-tag>
            </template>
          </a-table-column>
          <a-table-column title="内容" key="content">
            <template #default="{ record }">
              {{ record.next_follow_content || record.content || '-' }}
            </template>
          </a-table-column>
        </a-table>
        <a-empty v-if="customerEvents.length === 0 && !eventsLoading" description="暂无事件" style="padding:24px" />
      </a-tab-pane>
      <a-tab-pane key="documents" tab="文件袋">
        <div style="margin-bottom:12px;display:flex;gap:8px;align-items:center">
          <a-button v-if="canEditRelated" type="primary" size="small" @click="openDocUpload"><plus-outlined /> 上传文件</a-button>
          <a-select v-model:value="docCategoryFilter" size="small" style="width:100px" allow-clear placeholder="分类筛选" @change="loadDocuments">
            <a-select-option value="">全部</a-select-option>
            <a-select-option value="合同">合同</a-select-option>
            <a-select-option value="发票">发票</a-select-option>
            <a-select-option value="方案">方案</a-select-option>
            <a-select-option value="资质">资质</a-select-option>
            <a-select-option value="沟通附件">沟通附件</a-select-option>
            <a-select-option value="其他">其他</a-select-option>
          </a-select>
        </div>
        <a-table :data-source="documents" :pagination="false" row-key="id" :loading="documentsLoading" size="small">
          <a-table-column title="文件名" key="file_name">
            <template #default="{ record }">
              <a @click.prevent="previewDoc(record)" style="margin-right:8px">{{ record.title || record.file_name }}</a>
              <a-tag v-if="record.category" color="processing" style="font-size:10px">{{ record.category }}</a-tag>
            </template>
          </a-table-column>
          <a-table-column title="大小" key="file_size" width="90">
            <template #default="{ record }">{{ record.file_size ? (record.file_size >= 1024 ? (record.file_size / 1024).toFixed(0) + ' KB' : (record.file_size / 1024).toFixed(1) + ' KB') : '-' }}</template>
          </a-table-column>
          <a-table-column title="关联" key="relation" width="140">
            <template #default="{ record }">
              <template v-if="record.contract_id">
                <a @click="router.push({path:'/crm/contract/detail',query:{id:record.contract_id}})">合同#{{ record.contract_id }}</a>
              </template>
              <template v-else-if="record.order_id">
                <a @click="router.push({path:'/crm/order/detail',query:{id:record.order_id}})">订单#{{ record.order_id }}</a>
              </template>
              <template v-else-if="record.quotation_id">
                <a @click="router.push({path:'/crm/quotation/detail',query:{id:record.quotation_id}})">报价#{{ record.quotation_id }}</a>
              </template>
              <span v-else style="color:#ccc">-</span>
            </template>
          </a-table-column>
          <a-table-column title="上传者" key="owner" width="90">
            <template #default="{ record }">{{ record.owner_user_name || '-' }}</template>
          </a-table-column>
          <a-table-column title="时间" key="create_time" width="110">
            <template #default="{ record }">{{ record.create_time ? new Date(record.create_time * 1000).toLocaleDateString() : '' }}</template>
          </a-table-column>
          <a-table-column v-if="canEditRelated" title="操作" key="action" width="120">
            <template #default="{ record }">
              <a @click="openDocEdit(record)">编辑</a>
              <a-divider type="vertical" />
              <a-popconfirm title="确认删除?" @confirm="handleDeleteDoc(record)">
                <a style="color:#ff4d4f">删除</a>
              </a-popconfirm>
            </template>
          </a-table-column>
        </a-table>
        <a-empty v-if="documents.length === 0 && !documentsLoading" description="暂无文件" style="padding:24px" />
      </a-tab-pane>
      <a-tab-pane key="workorders" tab="工单">
        <div style="margin-bottom:12px">
          <a-button type="primary" size="small" @click="router.push({ path: '/app/workorder/list' })">
            <plus-outlined /> 去工单页新建
          </a-button>
        </div>
        <a-table :data-source="workOrders" :pagination="false" row-key="id" :loading="workOrdersLoading" size="small">
          <a-table-column title="工单号" key="ticket_no" width="160">
            <template #default="{ record }">
              <a @click="router.push({ path: '/app/workorder/detail', query: { id: record.id } })">{{ record.ticket_no }}</a>
            </template>
          </a-table-column>
          <a-table-column title="标题" dataIndex="title" key="title" ellipsis />
          <a-table-column title="类型" dataIndex="type_name" key="type_name" width="100" />
          <a-table-column title="状态" key="status" width="90">
            <template #default="{ record }">
              <a-tag :color="workOrderStatusColor(record.status)">{{ record.status_text }}</a-tag>
            </template>
          </a-table-column>
          <a-table-column title="处理人" dataIndex="assignee_name" key="assignee_name" width="90">
            <template #default="{ record }">{{ record.assignee_name || '-' }}</template>
          </a-table-column>
          <a-table-column title="创建时间" key="create_time" width="110">
            <template #default="{ record }">{{ record.create_time ? new Date(record.create_time * 1000).toLocaleDateString() : '' }}</template>
          </a-table-column>
        </a-table>
        <a-empty v-if="workOrders.length === 0 && !workOrdersLoading" description="暂无工单" style="padding:24px" />
      </a-tab-pane>
    </a-tabs>

    <!-- 文件袋 — 上传/编辑弹窗 -->
    <a-modal v-model:open="docModalVisible" :title="docEditing ? '编辑文件' : '上传文件'" @ok="handleDocSubmit" :confirm-loading="docSaving" width="480px">
      <a-form layout="vertical">
        <a-form-item v-if="!docEditing" label="选择文件" required>
          <a-upload :custom-request="handleDocUpload" :show-upload-list="false" accept="*">
            <a-button><upload-outlined /> {{ docFile ? docFile.name : '点击选择文件' }}</a-button>
          </a-upload>
        </a-form-item>
        <a-form-item label="标题">
          <a-input v-model:value="docForm.title" placeholder="文件标题，默认使用文件名" />
        </a-form-item>
        <a-form-item label="分类">
          <a-select v-model:value="docForm.category" style="width:100%">
            <a-select-option value="合同">合同</a-select-option>
            <a-select-option value="发票">发票</a-select-option>
            <a-select-option value="方案">方案</a-select-option>
            <a-select-option value="资质">资质</a-select-option>
            <a-select-option value="其他">其他</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="关联业务（可选）">
          <a-select v-model:value="docForm.linkType" style="width:120px;margin-right:8px" allow-clear placeholder="类型">
            <a-select-option value="">不关联</a-select-option>
            <a-select-option value="quotation">报价单</a-select-option>
            <a-select-option value="order">订单</a-select-option>
            <a-select-option value="contract">合同</a-select-option>
          </a-select>
          <a-input v-model:value="docForm.linkId" style="width:120px" placeholder="业务ID" :disabled="!docForm.linkType" />
        </a-form-item>
        <a-form-item label="备注">
          <a-textarea v-model:value="docForm.remark" :rows="2" placeholder="可选备注" />
        </a-form-item>
      </a-form>
    </a-modal>

    <!-- 文件预览弹窗 -->
    <a-modal v-model:open="previewVisible" :title="previewTitle" :footer="null" width="80%" wrap-class-name="preview-modal" :body-style="{ padding: '12px', textAlign: 'center' }" @cancel="previewVisible = false">
      <img v-if="previewType === 'image'" :src="previewUrl" style="max-width:100%;max-height:70vh" />
      <iframe v-else-if="previewType === 'pdf'" :src="previewUrl" style="width:100%;height:70vh;border:none" />
      <div v-else style="padding:40px;text-align:center">
        <p style="font-size:48px;margin-bottom:16px">📄</p>
        <p>{{ previewTitle }}</p>
        <a-button type="primary" :href="previewUrl" target="_blank">下载文件</a-button>
      </div>
    </a-modal>

    <CustomerForm v-model:open="formVisible" :mdl="detail" @ok="loadDetail" />
    <ContactForm v-model:open="contactFormVisible" :mdl="contactSelected" :customer-id="detail.id" @ok="loadContacts" />
    <TagPicker v-model:open="tagPickerOpen" target-type="customer" :model-value="customerTagNames" @update:model-value="handleTagChange" />
  </a-card>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onActivated, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { PlusOutlined, PaperClipOutlined, UploadOutlined } from '@ant-design/icons-vue'
import dayjs from 'dayjs'
import { getDetail, release, edit, analyze, portrait, claim as claimApi, getSelect as searchCustomers } from '@/api/crm/customer'
import { getByCustomer as getContacts, unbind as unbindContact, deleteById as deleteContact, getList as searchContacts } from '@/api/crm/contact'
import { getList as getFollowups, add as addFollowup, deleteById as deleteFollowup, getCalendarEvents } from '@/api/crm/followUp'
import { getList as getQuotations } from '@/api/crm/quotation'
import { getList as getOrders } from '@/api/crm/order'
import { getList as getContracts } from '@/api/crm/contract'
import { list as getDocuments, upload as uploadDocument, edit as editDocument, deleteById as deleteDocument } from '@/api/crm/document'
import { getList as getCollabs, add as addCollab, remove as removeCollab } from '@/api/crm/collaborator'
import { list as getStoreUsers } from '@/api/user'
// 工单是独立 skill，这里只读它的列表接口
import { getList as getWorkOrders } from '@skills/workorder/views-v2/api'
import { getAll as getConfigs } from '@/api/crm/config'
import { getActive } from '@/api/crm/customField'
import { getCustomerDemands, dismissDemand } from '@/api/crm/demand'
import { tree as getRegionTree } from '@/api/region'
import { useAiStore } from '@/stores/ai'
import { useTabsStore, tabKeyOf } from '@/stores/tabs'
import request from '@/utils/request'
import RichEditor from '@/components/RichEditor/index.vue'
import CustomerForm from './components/CustomerForm.vue'
import ArchivePanel from '@skills/entquery/views-v2/components/ArchivePanel.vue'
import { getStatus as getEntqueryStatus } from '@skills/entquery/views-v2/api'
import ContactForm from './components/ContactForm.vue'
import TagPicker from '@/views/crm/components/TagPicker.vue'
import { getCustomerTags, setCustomerTags } from '@/api/crm/tag'

const route = useRoute()
const router = useRouter()

// ---- State ----
const loading = ref(false)
const detail = reactive({})
const from = ref('')
const aiStore = useAiStore()
const tabsStore = useTabsStore()

// 事件列表（从日历事件 API 按 customer_id 加载，取前后 6 个月）
const customerEvents = ref([])
const eventsLoading = ref(false)
function loadCustomerEvents() {
  const id = route.query.id; if (!id) return
  eventsLoading.value = true
  const now = dayjs()
  getCalendarEvents({
    start: now.subtract(6, 'month').format('YYYY-MM-DD'),
    end: now.add(6, 'month').format('YYYY-MM-DD'),
    customer_id: parseInt(id)
  }).then(res => {
    customerEvents.value = (res?.data?.list || []).map(e => ({
      ...e,
      _date: e.next_follow_date ? dayjs(e.next_follow_date * 1000).format('YYYY-MM-DD') : ''
    }))
  }).catch(() => {}).finally(() => { eventsLoading.value = false })
}

function eventColor(e) {
  if (e.event_type === 'birthday') return 'pink'
  if (e.event_type === 'established') return 'green'
  if (e.event_type === 'custom') return e._color || 'blue'
  return 'blue'
}

// Follow-up
const followups = ref([])
const followupLoading = ref(false)
const richEditorRef = ref(null)
const followupForm = reactive({ follow_type: '', result: '有效', follow_content: '', next_follow_date: null, attachment: '' })
const uploadFileName = ref('')

// 富文本的“空”是 <p><br></p>，直接判空会把空内容当成有内容提交
function stripHtml(html) {
  return String(html || '')
    .replace(/<[^>]*>/g, '')
    .replace(/&nbsp;/g, ' ')
    .replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&amp;/g, '&')
    .trim()
}
const hasFollowContent = computed(() => stripHtml(followupForm.follow_content).length > 0)

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

// Documents
const documents = ref([])
const documentsLoading = ref(false)
const docCategoryFilter = ref('')
const docModalVisible = ref(false)
const docSaving = ref(false)
const docEditing = ref(null)
const docFile = ref(null)
const docForm = reactive({ title: '', category: '其他', linkType: '', linkId: '', remark: '' })

// Preview
// 企业查询（工商档案）开关：租户在「应用 → 企业查询 → 配置」里开启，默认关闭
const entqueryEnabled = ref(false)
function loadEntquerySetting() {
  getEntqueryStatus().then(on => {
    entqueryEnabled.value = on === true
  }).catch(err => console.error('[客户] 读取企业查询开关失败：', err)) // 读取失败按关闭处理
}

const previewVisible = ref(false)
const previewUrl = ref('')
const previewTitle = ref('')
const previewType = ref('')

const imageExts = ['jpg','jpeg','png','gif','bmp','webp','svg','ico']
const pdfExts = ['pdf']
const customFields = ref([])
const extraData = reactive({})
const demands = ref([])
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

// ---- Customer Tags ----
const customerTagNames = ref([])
const customerTags = ref([])
const tagPickerOpen = ref(false)

// 按分组聚合已选标签，用于详情页按分组展示
const groupedTags = computed(() => {
  const map = {}
  customerTags.value.forEach(t => {
    const key = t.group_id || 0
    if (!map[key]) map[key] = { group_name: t.group_name || '未分组', tags: [] }
    map[key].tags.push(t)
  })
  return Object.values(map)
})

function loadCustomerTags() {
  const id = route.query.id; if (!id) return
  getCustomerTags(id).then(res => {
    customerTags.value = res?.data?.tags || []
    customerTagNames.value = res?.data?.names || []
  }).catch(() => {})
}

function handleTagChange(names) {
  const id = route.query.id; if (!id) return
  setCustomerTags(id, names).then(() => {
    loadCustomerTags()
  }).catch(() => {})
}

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
  if (!id) { message.warning('缺少客户ID参数'); router.replace('/crm/customer/index'); return }
  // 捕获当前页签 key。本页会被 keep-alive 缓存，异步响应回来时 route.fullPath
  // 可能已经指向别的页面了，用捕获值才不会把标题写到别的页签上。
  const tabKey = tabKeyOf(route)
  loading.value = true
  const params = from.value ? { from: from.value } : {}
  getDetail(id, params).then(res => {
    Object.assign(detail, res?.data?.detail || res?.data || {})

    // 页签标题回填实体名 —— 否则 5 个客户会显示 5 个"客户详情"，页签栏没法用。
    // 公海/协作进来的是不同视图（按钮不一样）会各占一个页签，加后缀才分得清。
    if (detail.customer_name) {
      const suffix = { pool: '（公海）', collab: '（协作）' }[from.value] || ''
      tabsStore.setTitle(tabKey, detail.customer_name + suffix)
    }

    // 补充 AI 上下文（客户名称等，BasicLayout 已设好基础 page + customer_id）
    aiStore.setContext({
      ...aiStore.currentContext,
      customer_id: parseInt(id),
      customer_name: detail.customer_name,
      industry: detail.industry,
      level_name: detail.level_name,
    })
    // 重新加载该客户的 AI 聊天历史（先清空避免残留）
    aiStore.messages = []
    aiStore.loadHistory()

    // Parse extra JSON
    try {
      const extra = (() => { try { const e = detail.extra; if (typeof e === 'string' && e) { const d = e.replace(/&quot;/g, '"').replace(/&#039;/g, "'").replace(/&amp;/g, '&'); return JSON.parse(d) } if (typeof e === 'object' && e !== null) return e; return {} } catch { return {} } })()
      Object.assign(extraData, extra)
    } catch (e) {}
    loading.value = false
    try { if (detail.ai_portrait) portraitData.value = JSON.parse(detail.ai_portrait) } catch (e) {}
    loadCustomerTags()
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
    .then(res => {
      // /upload/file 返回扁平结构，只有老的 /upload/image 才是 { fileInfo: {...} }
      const d = res?.data || {}
      const path = d.file_path || d.fileInfo?.file_path || ''
      if (!path) {
        console.error('[客户] 上传返回缺少 file_path：', res)
        message.error('上传失败：未获取到文件路径')
        return
      }
      followupForm.attachment = path
      message.success('上传成功')
    }).catch(() => { message.error('上传失败') })
  return false
}

// @提及搜索
const mentionSearch = ref('')
const mentionOptions = ref([])
/** 搜索失败时在下拉里显示出来 —— 之前 catch 静默吞掉，出问题只表现为"下拉永远空的" */
const mentionError = ref('')
let mentionTimer = null

function handleMentionSearch(keyword) {
  if (mentionTimer) clearTimeout(mentionTimer)
  // 占位符写着"@搜索公司/联系人"，用户很自然会先敲一个 @ 再输名字。
  // 不能把 @ 带进关键词，否则后端是 LIKE '%@伟业%'，永远搜不到。
  const kw = String(keyword || '').replace(/^[@＠\s]+/, '').trim()
  if (kw.length < 1) { mentionOptions.value = []; mentionError.value = ''; return }
  mentionTimer = setTimeout(async () => {
    // 用 allSettled：一个接口挂了另一个的结果照常显示，便于定位是哪个挂了
    const [custRes, contactRes] = await Promise.allSettled([
      searchCustomers(kw),
      searchContacts({ keyword: kw, list_rows: 20 })
    ])

    const opts = []
    const failed = []

    if (custRes.status === 'fulfilled') {
      const customers = custRes.value?.data?.list || []
      customers.forEach(c => {
        opts.push({
          value: `@${c.customer_name}`,
          label: c.customer_name,
          type: 'customer',
          subtitle: c.customer_code || '',
          href: `/crm/customer/detail?id=${c.id}`
        })
      })
    } else {
      failed.push('公司')
      console.error('[快速跟进] @搜索公司失败：', custRes.reason)
    }

    if (contactRes.status === 'fulfilled') {
      const contacts = contactRes.value?.data?.list || []
      contacts.forEach(ct => {
        const companies = ct.company_names || []
        const companyIds = ct.company_ids || []
        opts.push({
          value: `@${ct.contact_name}`,
          label: ct.contact_name,
          type: 'contact',
          subtitle: companies.length > 0 ? `🏢 ${companies[0]}` : '',
          // 联系人接口不返回 customer_id，只有 company_ids；没有关联公司时留空
          href: companyIds.length > 0 ? `/crm/customer/detail?id=${companyIds[0]}` : '',
          contactId: ct.id
        })
      })
    } else {
      failed.push('联系人')
      console.error('[快速跟进] @搜索联系人失败：', contactRes.reason)
    }

    mentionOptions.value = opts
    mentionError.value = failed.length ? (failed.join('、') + '搜索失败') : ''
  }, 300)
}

function onMentionSelect(value, option) {
  mentionSearch.value = ''
  mentionOptions.value = []
  const label = option.label || value.replace(/^@/, '')
  const href = option.href || ''
  richEditorRef.value?.insertMention(label, href)
}

function clearFollowAttachment() {
  followupForm.attachment = ''
  uploadFileName.value = ''
}

/** 提交后把输入区恢复干净：正文、下次跟进、附件、@搜索都清空；方式/结果保留，方便连续记录 */
function resetFollowForm() {
  followupForm.follow_content = ''
  followupForm.next_follow_date = null
  followupForm.attachment = ''
  uploadFileName.value = ''
  mentionSearch.value = ''
  mentionOptions.value = []
  mentionError.value = ''
}

/** Ctrl / Cmd + Enter 提交，省得鼠标再挪到按钮上 */
function onFollowShortcut(e) {
  if (!(e.ctrlKey || e.metaKey) || e.key !== 'Enter') return
  e.preventDefault()
  e.stopPropagation()
  handleAddFollow()
}

function handleAddFollow() {
  if (followupLoading.value) return
  const id = route.query.id
  if (!hasFollowContent.value) { message.warning('请输入跟进内容'); return }
  followupLoading.value = true
  const data = { customer_id: parseInt(id), follow_type: followupForm.follow_type, follow_content: followupForm.follow_content, result: followupForm.result, attachment: followupForm.attachment }
  if (followupForm.next_follow_date) data.next_follow_date = dayjs(followupForm.next_follow_date).format('YYYY-MM-DD')
  addFollowup(data).then(() => {
    message.success('跟进记录已添加')
    resetFollowForm()
    // 下次跟进日期会进客户事件列表，跟着一起刷新
    loadFollowups(); loadDetail(); loadCustomerEvents(); loadDocuments()
  }).catch(err => {
    console.error('[快速跟进] 添加失败：', err)
  }).finally(() => { followupLoading.value = false })
}

function handleDeleteFollow(item) { deleteFollowup(item.id).then(() => { message.success('已删除'); loadFollowups() }) }

// ---- Contact actions ----
function handleAddContact() { contactSelected.value = null; contactFormVisible.value = true }
function handleEditContact(record) { contactSelected.value = record; contactFormVisible.value = true }
function handleUnbindContact(record) { unbindContact(record.id, route.query.id).then(() => { message.success('已解除绑定'); loadContacts() }) }
function handleDeleteContact(record) { deleteContact(record.id).then(() => { message.success('删除成功'); loadContacts() }) }

// ---- Collab actions ----
function filterUser(input, option) { return String(option.label || '').toLowerCase().includes(input.toLowerCase()) }
function handleAddCollab() {
  if (!collabUserId.value) return message.warning('请选择同事')
  collabLoading.value = true
  addCollab({ customer_id: parseInt(route.query.id), user_id: collabUserId.value, permission: collabPerm.value }).then(() => {
    message.success('已添加'); collabLoading.value = false; collabUserId.value = null; collabPerm.value = 1; loadCollabs()
  }).catch(() => { collabLoading.value = false })
}
function handleRemoveCollab(r) { removeCollab(r.id).then(() => { message.success('已移除'); loadCollabs() }) }

// ---- 工单（独立 skill，只读） ----
const workOrders = ref([])
const workOrdersLoading = ref(false)

function workOrderStatusColor(s) {
  return { 1: 'blue', 2: 'processing', 3: 'orange', 4: 'green', 5: 'default' }[s] || ''
}

function loadWorkOrders() {
  const id = route.query.id
  if (!id) return
  workOrdersLoading.value = true
  getWorkOrders({ customer_id: id, page: 1, limit: 50 })
    .then(res => { workOrders.value = res?.data?.list?.data || [] })
    .catch(() => {})
    .finally(() => { workOrdersLoading.value = false })
}

// ---- Documents ----
function loadDocuments() {
  const id = route.query.id; if (!id) return
  documentsLoading.value = true
  const params = { customer_id: id, list_rows: 50 }
  if (docCategoryFilter.value) params.category = docCategoryFilter.value
  getDocuments(params).then(res => {
    documents.value = (res?.data?.list?.data || res?.data?.list || [])
    documentsLoading.value = false
  }).catch(() => { documentsLoading.value = false })
}
function openDocUpload() {
  docEditing.value = null; docFile.value = null
  Object.assign(docForm, { title: '', category: '其他', linkType: '', linkId: '', remark: '' })
  docModalVisible.value = true
}
function openDocEdit(record) {
  docEditing.value = record; docFile.value = null
  docForm.title = record.title || record.file_name || ''
  docForm.category = record.category || '其他'
  docForm.linkType = record.contract_id ? 'contract' : record.order_id ? 'order' : record.quotation_id ? 'quotation' : ''
  docForm.linkId = record.contract_id || record.order_id || record.quotation_id || ''
  docForm.remark = record.remark || ''
  docModalVisible.value = true
}
function handleDocUpload({ file }) { docFile.value = file; return false }
async function handleDocSubmit() {
  if (!docEditing.value && !docFile.value) { message.warning('请选择文件'); return }
  docSaving.value = true
  try {
    if (docEditing.value) {
      const data = { id: docEditing.value.id, title: docForm.title, category: docForm.category, remark: docForm.remark }
      // 关联业务
      if (docForm.linkType === 'quotation') data.quotation_id = parseInt(docForm.linkId) || 0
      else if (docForm.linkType === 'order') data.order_id = parseInt(docForm.linkId) || 0
      else if (docForm.linkType === 'contract') data.contract_id = parseInt(docForm.linkId) || 0
      await editDocument(data)
      message.success('已更新')
    } else {
      const fd = new FormData()
      fd.append('iFile', docFile.value)
      fd.append('customer_id', route.query.id)
      fd.append('title', docForm.title || docFile.value.name)
      fd.append('category', docForm.category)
      fd.append('remark', docForm.remark)
      if (docForm.linkType === 'quotation') fd.append('quotation_id', parseInt(docForm.linkId) || 0)
      else if (docForm.linkType === 'order') fd.append('order_id', parseInt(docForm.linkId) || 0)
      else if (docForm.linkType === 'contract') fd.append('contract_id', parseInt(docForm.linkId) || 0)
      await uploadDocument(fd)
      message.success('上传成功')
    }
    docModalVisible.value = false
    loadDocuments()
  } catch (e) { message.error(e?.message || '操作失败') }
  docSaving.value = false
}
function handleDeleteDoc(record) {
  deleteDocument(record.id).then(() => { message.success('已删除'); loadDocuments() }).catch(() => {})
}

/** 打开文件预览弹窗：url 为完整地址，title 为弹窗标题 */
function openFilePreview(url, title) {
  const ext = String(url).split(/[?#]/)[0].split('.').pop().toLowerCase()
  previewUrl.value = url
  previewTitle.value = title || '文件预览'
  if (imageExts.includes(ext)) {
    previewType.value = 'image'
  } else if (pdfExts.includes(ext)) {
    previewType.value = 'pdf'
  } else {
    previewType.value = 'other'
  }
  previewVisible.value = true
}
function previewDoc(record) {
  openFilePreview(uploadBaseUrl.value + (record.file_path || ''), record.title || record.file_name)
}
/** 跟进附件预览（attachment 存的是相对路径，如 10001/20261103/xxx.jpg） */
function previewAttachment(attachment) {
  if (!attachment) return
  // crm_followup 只存路径不存原始文件名，路径末段是哈希名，不适合当标题
  openFilePreview(uploadBaseUrl.value + attachment, '附件预览')
}

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

function saveExtraField(fieldKey, value) {
  if (fieldSaving.value) return
  const newExtra = { ...extraData, [fieldKey]: value || '' }
  fieldSaving.value = true
  edit({ id: detail.id, customer_name: detail.customer_name, extra: JSON.stringify(newExtra) }).then(() => {
    message.success('已更新')
    Object.assign(extraData, newExtra)
    editingField.value = ''
    fieldSaving.value = false
  }).catch(() => { fieldSaving.value = false })
}

function loadDemands() {
  const id = route.query.id; if (!id) return
  getCustomerDemands(id).then(res => { demands.value = res?.data?.list || [] })
}
function handleDemandFollow(d) { message.info('请根据需求联系客户：' + d.rule_name) }
function handleDemandDismiss(d) { dismissDemand(d.id).then(() => { message.success('已忽略'); loadDemands() }) }

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

// keep-alive 下 onMounted 只跑一次：从设置页开启后回到已打开的详情页也要生效
onActivated(() => loadEntquerySetting())

onMounted(() => {
  from.value = route.query.from || ''
  loadEntquerySetting()
  loadDicts(); loadRegions(); loadDetail(); loadFollowups(); loadContacts(); loadQuotations(); loadOrders(); loadContracts(); loadCollabs(); loadStoreUsers(); loadDemands(); loadCustomerEvents(); loadDocuments(); loadWorkOrders()
  // 加载自定义字段定义
  getActive('customer').then(res => {
    customFields.value = (res?.data?.fields || []).map(f => ({
      ...f,
      options: typeof f.options === 'string' ? JSON.parse(f.options || '[]') : (f.options || [])
    }))
  })
})

// AI 写入数据后自动刷新页面数据
watch(() => aiStore.refreshFlag, (n, o) => {
  if (n > 0 && n !== o) {
    loadDetail()
    loadFollowups()
    loadContacts()
    loadQuotations()
    loadOrders()
    loadCustomerEvents()
    loadDocuments()
    loadWorkOrders()
  }
})
</script>

<style scoped>
.field-editable { cursor: pointer; display: inline-block; min-width: 60px; padding: 2px 6px; margin: -2px -6px; border-radius: 3px; transition: background .2s; }
.field-editable:hover { background: #e6f7ff; }

/* 快速跟进 */
.followup-hint { color: #999; font-size: 12px; font-weight: 400; margin-left: 8px; }
.followup-toolbar { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 8px; }
.followup-spacer { flex: 1 1 auto; }
.followup-actions { display: flex; justify-content: flex-end; margin-top: 8px; }

/* 标签分类：按分组展示，组名 + 彩色标签内联换行 */
.detail-tags { display: flex; flex-direction: column; gap: 4px; }
.detail-tag-group { display: flex; flex-wrap: wrap; align-items: center; gap: 4px; }
.detail-tag-group-name { font-size: 12px; color: #888; margin-right: 4px; white-space: nowrap; }
.follow-content { line-height: 1.8; word-break: break-word; overflow-x: auto; }
.follow-content :deep(img) { max-width: 100% !important; height: auto !important; }
.follow-content :deep(table) { border-collapse: collapse; width: 100%; font-size: 12px; }
.follow-content :deep(td), .follow-content :deep(th) { border: 1px solid #e8e8e8; padding: 4px 8px; }
.follow-content :deep(p) { margin: 4px 0; }
.follow-content :deep(blockquote) { border-left: 3px solid #1890ff; padding: 4px 12px; margin: 8px 0; color: #666; background: #fafafa; }
.follow-content :deep(.mention-link) { display: inline-block; padding: 0 6px; margin: 0 2px; border-radius: 3px; background: #e6f7ff; color: #1890ff; font-size: 12px; text-decoration: none; border: 1px solid #91d5ff; line-height: 1.6; }
.follow-content :deep(.mention-link:hover) { background: #bae7ff; color: #096dd9; }
</style>

<style>
.preview-modal .ant-modal { max-width: 90vw; }
.preview-modal .ant-modal-body { padding: 12px; }
.customer-detail .ant-descriptions-table { table-layout: fixed; }
.customer-detail .ant-descriptions-item-label { white-space: nowrap; width: 88px; }
</style>
