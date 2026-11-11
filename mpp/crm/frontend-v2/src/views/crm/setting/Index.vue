<template>
  <a-card :bordered="false" title="CRM 设置">
    <a-tabs v-model:activeKey="activeKey" @change="onTabChange">
      <!-- 公海规则 -->
      <a-tab-pane key="pool" tab="公海规则">
        <a-spin :spinning="settingLoading">
          <a-form ref="poolFormRef" :model="settingForm" style="max-width:500px">
            <a-form-item label="自动回收">
              <a-switch v-model:checked="poolAutoEnabled" />
              <span class="ai-hint-inline">默认关闭。开启后，超过设定天数未跟进的客户会自动掉入公海</span>
            </a-form-item>
            <a-form-item label="掉入公海天数" extra="客户超过此天数未跟进，自动掉入公海" name="pool_days">
              <a-input-number v-model:value="settingForm.pool_days" :min="30" :max="730" :disabled="!poolAutoEnabled" style="width:200px" /> 天
            </a-form-item>
          </a-form>
          <a-button type="primary" :loading="saving" @click="handleSave('pool')">保存</a-button>
        </a-spin>
      </a-tab-pane>

      <!-- AI配置 -->
      <a-tab-pane key="ai" tab="AI配置">
        <a-spin :spinning="settingLoading">
          <a-form :model="settingForm" style="max-width:580px">
            <a-form-item label="接入方式">
              <a-radio-group v-model:value="settingForm.ai_provider" @change="onAiProviderChange">
                <a-radio value="platform">平台配置</a-radio>
                <a-radio value="custom">自定义配置</a-radio>
              </a-radio-group>
              <div class="ai-hint">
                <template v-if="settingForm.ai_provider === 'platform'">
                  平台统一接入，无需填写接口地址。在 <a href="https://api.xtocn.com" target="_blank" rel="noopener noreferrer">api.xtocn.com</a> 领取 Key 后即可使用，模型按 Key 的可用范围选择。
                </template>
                <template v-else>
                  接入任意 OpenAI 兼容接口，例如 DeepSeek、自建网关。
                </template>
              </div>
            </a-form-item>

            <a-form-item v-if="settingForm.ai_provider === 'custom'" label="API地址">
              <a-input v-model:value="settingForm.ai_api_url" placeholder="例如 https://api.deepseek.com" />
              <div class="ai-hint">只填域名会自动补 /chat/completions，也可以直接粘贴完整地址。</div>
            </a-form-item>

            <a-form-item label="API Key">
              <div class="ai-key-row">
                <a-input-password
                  v-model:value="settingForm.ai_api_key"
                  :placeholder="aiHasKey ? '已配置，留空保持原值' : '填写 API Key'"
                  autocomplete="new-password"
                />
                <a
                  v-if="settingForm.ai_provider === 'platform'"
                  class="ai-key-link"
                  href="https://api.xtocn.com"
                  target="_blank"
                  rel="noopener noreferrer"
                  title="前往平台控制台创建或查看 API Key"
                >获取 Key <export-outlined /></a>
              </div>
              <div class="ai-hint">
                <template v-if="aiHasKey">已配置 {{ aiKeyMask }}，留空表示保持原值。</template>
                <template v-else>保存后不回传明文。</template>
              </div>
            </a-form-item>

            <a-form-item label="模型">
              <div class="ai-model-row">
                <a-select
                  v-if="!aiManualModel"
                  v-model:value="settingForm.ai_model"
                  show-search
                  allow-clear
                  :options="aiModelSelectOptions"
                  :placeholder="aiModelOptions.length ? '从可用模型中选择' : '先获取模型，或切换手动输入'"
                  :not-found-content="aiLoadingModels ? '正在读取…' : '暂无可用模型'"
                />
                <a-input v-else v-model:value="settingForm.ai_model" placeholder="手动填写模型名，例如 deepseek-chat" />
                <a-button size="small" :loading="aiLoadingModels" @click="handleLoadAiModels">获取模型</a-button>
              </div>
              <div class="ai-hint">
                列表来自当前 Key 可用的模型。
                <a @click="toggleAiManualModel">{{ aiManualModel ? '从列表选择' : '手动输入模型名' }}</a>
              </div>
            </a-form-item>

            <a-form-item label="温度">
              <a-input-number v-model:value="settingForm.ai_temperature" :min="0" :max="2" :step="0.1" style="width:200px" />
              <span class="ai-hint-inline">0 更稳定，1 更有创意</span>
            </a-form-item>

            <a-form-item label="最大Token数">
              <a-input-number v-model:value="settingForm.ai_max_tokens" :min="100" :max="8192" style="width:200px" />
            </a-form-item>
          </a-form>
          <a-button type="primary" :loading="saving" @click="handleSave('ai')">保存</a-button>
          <a-button :loading="aiTesting" style="margin-left:8px" @click="handleTestAi">测试连接</a-button>
          <span v-if="aiTestResult" :style="{ color: aiTestResult.startsWith('成功') ? '#52c41a' : '#f5222d', marginLeft: '12px' }">{{ aiTestResult }}</span>
        </a-spin>
      </a-tab-pane>

      <!-- AI洞察配置 -->
      <a-tab-pane key="insight" tab="AI洞察">
        <a-spin :spinning="settingLoading">
          <a-form ref="insightFormRef" :model="settingForm" style="max-width:520px">
            <a-form-item label="报价逾期天数" extra="报价已发送超过此天数未回复，生成提醒" name="insight_quotation_overdue_days">
              <a-input-number v-model:value="settingForm.insight_quotation_overdue_days" :min="1" :max="60" style="width:160px" /> 天
            </a-form-item>
            <a-form-item label="复购窗口阈值" extra="距上次采购天数 ≥ 历史平均间隔 × 此值，触发复购提醒" name="insight_repurchase_threshold">
              <a-input-number v-model:value="settingForm.insight_repurchase_threshold" :min="0.3" :max="1.5" :step="0.1" style="width:160px" />
            </a-form-item>
            <a-form-item label="公海预警提前天数" :extra="poolAutoEnabled ? '距掉入公海还剩此天数时预警' : '公海自动回收已关闭，此项不生效'" name="insight_pool_warning_days">
              <a-input-number v-model:value="settingForm.insight_pool_warning_days" :min="1" :max="60" :disabled="!poolAutoEnabled" style="width:160px" /> 天
            </a-form-item>
            <a-form-item label="流失判断倍数" extra="距上次采购 > 历史间隔 × 此值，且无跟进，判定流失风险" name="insight_churn_multiplier">
              <a-input-number v-model:value="settingForm.insight_churn_multiplier" :min="1" :max="5" :step="0.5" style="width:160px" />
            </a-form-item>
            <a-form-item label="流失判断无跟进天数" extra="流失评估时要求至少此天数无跟进" name="insight_churn_no_followup_days">
              <a-input-number v-model:value="settingForm.insight_churn_no_followup_days" :min="7" :max="180" style="width:160px" /> 天
            </a-form-item>
            <a-form-item label="沉睡客户天数" extra="曾成交客户超过此天数未跟进标记为沉睡" name="insight_dormant_days">
              <a-input-number v-model:value="settingForm.insight_dormant_days" :min="30" :max="730" style="width:160px" /> 天
            </a-form-item>
            <a-form-item label="每人每日最大洞察数" extra="限制每个业务员每天最多显示的洞察卡片数" name="insight_max_per_user">
              <a-input-number v-model:value="settingForm.insight_max_per_user" :min="1" :max="50" style="width:160px" />
            </a-form-item>
          </a-form>
          <a-button type="primary" :loading="saving" @click="handleSave('insight')">保存</a-button>
        </a-spin>
      </a-tab-pane>

      <!-- 字典配置 -->
      <a-tab-pane key="dict" tab="字典配置">
        <a-tabs v-model:active-key="dictType" tab-position="left" @change="loadDictList">
          <a-tab-pane v-for="dt in dictTypes" :key="dt.key" :tab="dt.label">
            <div style="margin-bottom:12px">
              <a-button type="primary" size="small" @click="handleDictAdd"><plus-outlined /> 新增</a-button>
              <a-button size="small" style="margin-left:8px" @click="handleDictInit">初始化默认数据</a-button>
            </div>
            <a-table :columns="dictColumns" :data-source="dictList" :pagination="dictPagination" size="small" bordered row-key="id" :loading="dictLoading" @change="handleDictPageChange">
              <template #bodyCell="{ column, record }">
                <template v-if="column.key === 'action'">
                  <a @click="handleDictEdit(record)">编辑</a>
                  <a-divider type="vertical" />
                  <a-popconfirm title="确认删除?" @confirm="handleDictDelete(record)">
                    <a style="color:#ff4d4f">删除</a>
                  </a-popconfirm>
                </template>
              </template>
            </a-table>
          </a-tab-pane>
        </a-tabs>
      </a-tab-pane>

      <!-- 自定义字段 -->
      <a-tab-pane key="customField" tab="自定义字段">
        <div style="margin-bottom:12px">
          <a-button type="primary" size="small" @click="handleCfAdd"><plus-outlined /> 新增字段</a-button>
        </div>
        <a-table :columns="cfColumns" :data-source="cfList" :pagination="cfPagination" size="small" bordered row-key="id" :loading="cfLoading" @change="handleCfPageChange">
          <template #bodyCell="{ column, record }">
            <template v-if="column.key === 'action'">
              <a @click="handleCfEdit(record)">编辑</a>
              <a-divider type="vertical" />
              <a-popconfirm title="确认删除?" @confirm="handleCfDelete(record)">
                <a style="color:#ff4d4f">删除</a>
              </a-popconfirm>
              <a-divider type="vertical" />
              <a @click="handleCfToggle(record)">{{ record.status === 1 ? '停用' : '启用' }}</a>
            </template>
          </template>
        </a-table>
      </a-tab-pane>

      <!-- 需求规则 -->
      <a-tab-pane key="demandRule" tab="需求规则">
        <div style="margin-bottom:12px">
          <a-button type="primary" size="small" @click="handleDrAiCreate"><plus-outlined /> AI 创建规则</a-button>
        </div>
        <a-table :columns="drColumns" :data-source="drList" :pagination="false" size="small" bordered row-key="id" :loading="drLoading">
          <template #bodyCell="{ column, record }">
            <template v-if="column.key === 'priority'">
              <a-tag :color="record.priority===1?'red':record.priority===2?'orange':'blue'">{{ record.priority===1?'高':record.priority===2?'中':'低' }}</a-tag>
            </template>
            <template v-if="column.key === 'status'">
              <a-switch :checked="record.status===1" size="small" @change="(v) => handleDrToggle(record, v)" />
            </template>
            <template v-if="column.key === 'action'">
              <a @click="handleDrEdit(record)">编辑</a>
              <a-divider type="vertical" />
              <a-popconfirm title="确认删除?" @confirm="handleDrDelete(record)">
                <a style="color:#ff4d4f">删除</a>
              </a-popconfirm>
            </template>
          </template>
        </a-table>

        <!-- AI创建规则弹窗 -->
        <a-modal :title="drAiPreview ? '确认保存规则' : 'AI 创建需求规则'" :open="drAiVisible" :confirm-loading="drAiLoading" :ok-text="drAiPreview ? '确认保存' : 'AI 生成'" @ok="handleDrAiSubmit" @cancel="drAiVisible = false" :width="600">
          <a-form :label-col="{ span: 4 }" :wrapper-col="{ span: 19 }">
            <a-form-item label="规则描述">
              <a-textarea v-model:value="drAiDesc" placeholder="用自然语言描述规则，例如：&#10;当法定代表人字段不为空，且扩展字段中的监事字段也不为空时，生成监事减除商机" :rows="4" />
            </a-form-item>
            <a-form-item label="AI 生成结果" v-if="drAiPreview">
              <a-descriptions size="small" bordered :column="1">
                <a-descriptions-item label="标识">{{ drAiPreview.rule_key }}</a-descriptions-item>
                <a-descriptions-item label="名称">{{ drAiPreview.rule_name }}</a-descriptions-item>
                <a-descriptions-item label="逻辑">{{ drAiPreview.logic === 'all' ? '所有条件都满足' : '任一条件满足' }}</a-descriptions-item>
                <a-descriptions-item label="优先级">{{ drAiPreview.priority === 1 ? '高' : drAiPreview.priority === 2 ? '中' : '低' }}</a-descriptions-item>
                <a-descriptions-item label="条件"><pre style="margin:0;font-size:12px">{{ JSON.stringify(drAiPreview.conditions, null, 2) }}</pre></a-descriptions-item>
                <a-descriptions-item v-if="drAiPreview.created_fields && drAiPreview.created_fields.length" label="自动创建字段">
                  <a-tag v-for="f in drAiPreview.created_fields" :key="f" color="green">{{ f }}</a-tag>
                  <span style="color:#999;font-size:12px">（已自动创建，在客户表单「扩展信息」中可见）</span>
                </a-descriptions-item>
              </a-descriptions>
            </a-form-item>
          </a-form>
        </a-modal>

        <!-- 编辑规则弹窗 -->
        <a-modal title="编辑需求规则" :open="drEditVisible" :confirm-loading="drEditSaving" @ok="handleDrEditSubmit" @cancel="drEditVisible = false" :width="640">
          <a-form :label-col="{ span: 4 }" :wrapper-col="{ span: 19 }">
            <a-form-item label="规则名称">
              <a-input v-model:value="drEditForm.rule_name" />
            </a-form-item>
            <a-form-item label="条件">
              <div v-for="(cond, idx) in drEditForm.conditions" :key="idx" style="display:flex;gap:6px;margin-bottom:6px;align-items:center">
                <a-input v-model:value="cond.field" placeholder="字段" style="width:160px" size="small" />
                <a-select v-model:value="cond.op" style="width:130px" size="small">
                  <a-select-option value="not_empty">非空</a-select-option>
                  <a-select-option value="empty">为空</a-select-option>
                  <a-select-option value="eq">等于</a-select-option>
                  <a-select-option value="contains">包含</a-select-option>
                  <a-select-option value="gt">大于</a-select-option>
                  <a-select-option value="split_count_gt">条目数&gt;</a-select-option>
                  <a-select-option value="days_since_gt">超过N天</a-select-option>
                </a-select>
                <a-input v-model:value="cond.value" placeholder="比较值" style="width:120px" size="small" />
                <a-button size="small" danger @click="drEditForm.conditions.splice(idx,1)">×</a-button>
              </div>
              <a-button size="small" dashed block @click="drEditForm.conditions.push({field:'',op:'not_empty',value:''})">+ 添加条件</a-button>
            </a-form-item>
            <a-form-item label="逻辑">
              <a-radio-group v-model:value="drEditForm.logic">
                <a-radio value="all">全部满足</a-radio>
                <a-radio value="any">任一满足</a-radio>
              </a-radio-group>
            </a-form-item>
            <a-form-item label="优先级">
              <a-radio-group v-model:value="drEditForm.priority">
                <a-radio :value="1">高</a-radio>
                <a-radio :value="2">中</a-radio>
                <a-radio :value="3">低</a-radio>
              </a-radio-group>
            </a-form-item>
          </a-form>
        </a-modal>
      </a-tab-pane>

      <!-- API 密钥 -->
      <a-tab-pane key="apiKey" tab="API 密钥">
        <div style="margin-bottom:12px">
          <a-input-group compact>
            <a-input v-model:value="apkName" placeholder="备注（如：官网对接）" style="width:200px" size="small" />
            <a-select v-model:value="apkScopes" style="width:90px" size="small">
              <a-select-option value="read">只读</a-select-option>
              <a-select-option value="write">读写</a-select-option>
            </a-select>
            <a-select v-model:value="apkUserId" show-search :filter-option="false"
              :options="apkUserOptions" :loading="apkUserLoading" allow-clear
              placeholder="绑定用户(可选)" style="width:160px" size="small"
              @search="handleApkUserSearch" />
            <a-button type="primary" size="small" :loading="apkGenerating" @click="handleGenerateKey"><plus-outlined /> 生成密钥</a-button>
          </a-input-group>
          <span v-if="apkNewKey" style="margin-left:12px;color:#d6893b;font-size:13px">
            ✅ 新密钥：<code>{{ apkNewKey }}</code>（请立即复制保存，关闭后无法找回）
          </span>
        </div>
        <a-table :columns="apkColumns" :data-source="apkList" :pagination="false" size="small" bordered row-key="id" :loading="apkLoading">
          <template #bodyCell="{ column, record }"> 
            <template v-if="column.key === 'api_key'" style="max-width:30px">
              <a-typography-text copyable :code="true" style="font-size:12px">{{ record.api_key }}</a-typography-text>
            </template>
            <template v-if="column.key === 'scopes'">
              <a-tag :color="record.scopes==='write'?'green':'blue'">{{ record.scopes==='write'?'读写':'只读' }}</a-tag>
            </template>
            <template v-if="column.key === 'bind_user'">
              <a-tag v-if="record.store_user_id > 0" color="blue">{{ record.bind_user_name || ('用户#' + record.store_user_id) }}</a-tag>
              <span v-else style="color:#999">未绑定(虚拟超管)</span>
            </template>
            <template v-if="column.key === 'last_used_at'">
              {{ record.last_used_at ? new Date(record.last_used_at*1000).toLocaleString() : '未使用' }}
            </template>
            <template v-if="column.key === 'action'">
              <a-popconfirm title="确认删除?" @confirm="handleDeleteKey(record)">
                <a style="color:#ff4d4f">删除</a>
              </a-popconfirm>
            </template>
          </template>
        </a-table>
      </a-tab-pane>
      <!-- 标签管理 -->
      <a-tab-pane key="tag" tab="标签管理">
        <a-tabs v-model:activeKey="tagTargetType" size="small" @change="onTagTargetChange">
          <a-tab-pane key="customer" tab="客户标签">
            <div style="margin-bottom:12px">
              <a-button type="default" size="small" @click="handleTagAdd(null)"><plus-outlined /> 新建标签</a-button>
              <a-divider type="vertical" />
              <a @click="showTagGroupModal = true" style="font-size:12px">管理分组</a>
            </div>
            <a-table :columns="tagColumns" :data-source="tagList" :pagination="false" size="small" bordered row-key="id" :loading="tagLoading">
              <template #bodyCell="{ column, record }">
                <template v-if="column.key === 'color'">
                  <a-tag :color="record.color">{{ record.name }}</a-tag>
                </template>
                <template v-if="column.key === 'group_name'">
                  <span>{{ record.group_name || '未分组' }}</span>
                </template>
                <template v-if="column.key === 'action'">
                  <a @click="handleTagEdit(record)">编辑</a>
                  <a-divider type="vertical" />
                  <a-popconfirm title="确认删除?" @confirm="handleTagDelete(record)">
                    <a style="color:#ff4d4f">删除</a>
                  </a-popconfirm>
                </template>
              </template>
            </a-table>
          </a-tab-pane>
          <a-tab-pane key="contact" tab="联系人标签">
            <div style="margin-bottom:12px">
              <a-button type="default" size="small" @click="handleTagAdd(null)"><plus-outlined /> 新建标签</a-button>
              <a-divider type="vertical" />
              <a @click="showTagGroupModal = true" style="font-size:12px">管理分组</a>
            </div>
            <a-table :columns="tagColumns" :data-source="tagList" :pagination="false" size="small" bordered row-key="id" :loading="tagLoading">
              <template #bodyCell="{ column, record }">
                <template v-if="column.key === 'color'">
                  <a-tag :color="record.color">{{ record.name }}</a-tag>
                </template>
                <template v-if="column.key === 'group_name'">
                  <span>{{ record.group_name || '未分组' }}</span>
                </template>
                <template v-if="column.key === 'action'">
                  <a @click="handleTagEdit(record)">编辑</a>
                  <a-divider type="vertical" />
                  <a-popconfirm title="确认删除?" @confirm="handleTagDelete(record)">
                    <a style="color:#ff4d4f">删除</a>
                  </a-popconfirm>
                </template>
              </template>
            </a-table>
          </a-tab-pane>
        </a-tabs>

        <!-- 标签编辑弹窗 -->
        <a-modal :title="tagEditMode==='add'?'新建标签':'编辑标签'" :open="showTagModal" :confirm-loading="tagSaving" @ok="handleTagSubmit" @cancel="showTagModal = false" :width="380">
          <a-form ref="tagFormRef" :model="tagForm" :label-col="{ span: 6 }" :wrapper-col="{ span: 16 }">
            <a-form-item label="名称" name="name" :rules="[{ required: true, message: '请输入标签名称' }]">
              <a-input v-model:value="tagForm.name" />
            </a-form-item>
            <a-form-item label="分组" name="group_id">
              <a-select v-model:value="tagForm.group_id" placeholder="选择分组（可选）" allow-clear>
                <a-select-option :value="0">未分组</a-select-option>
                <a-select-option v-for="g in tagGroupOptions" :key="g.id" :value="g.id">{{ g.name }}</a-select-option>
              </a-select>
            </a-form-item>
            <a-form-item label="颜色" name="color">
              <a-select v-model:value="tagForm.color">
                <a-select-option value="blue"><a-tag color="blue">蓝色</a-tag></a-select-option>
                <a-select-option value="green"><a-tag color="green">绿色</a-tag></a-select-option>
                <a-select-option value="orange"><a-tag color="orange">橙色</a-tag></a-select-option>
                <a-select-option value="red"><a-tag color="red">红色</a-tag></a-select-option>
                <a-select-option value="purple"><a-tag color="purple">紫色</a-tag></a-select-option>
                <a-select-option value="cyan"><a-tag color="cyan">青色</a-tag></a-select-option>
              </a-select>
            </a-form-item>
            <a-form-item label="排序" name="sort_order">
              <a-input-number v-model:value="tagForm.sort_order" :min="0" :precision="0" style="width:100%" />
            </a-form-item>
          </a-form>
        </a-modal>

        <!-- 分组管理弹窗 -->
        <a-modal title="管理分组" :open="showTagGroupModal" :footer="null" @cancel="showTagGroupModal = false" :width="420">
          <div style="margin-bottom:8px">
            <a-input-group compact>
              <a-input v-model:value="newGroupName" placeholder="新分组名称" style="width:160px" />
              <a-button type="primary" @click="handleGroupAdd"><plus-outlined /> 添加</a-button>
            </a-input-group>
          </div>
          <a-table :columns="groupTableColumns" :data-source="tagGroupOptions" :pagination="false" size="small" bordered row-key="id">
            <template #bodyCell="{ column, record }">
              <template v-if="column.key === 'action'">
                <a @click="handleGroupDelete(record)">删除</a>
              </template>
            </template>
          </a-table>
        </a-modal>
      </a-tab-pane>

      <!-- 操作日志 -->
      <a-tab-pane key="oplog" tab="操作日志">
        <div style="margin-bottom:12px;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
          <a-select v-model:value="oplogFilter.action" placeholder="操作类型" allow-clear style="width:120px" size="small" @change="loadOplog(true)">
            <a-select-option value="login">登录</a-select-option>
            <a-select-option value="login_fail">登录失败</a-select-option>
            <a-select-option value="logout">登出</a-select-option>
            <a-select-option value="add">新增</a-select-option>
            <a-select-option value="edit">编辑</a-select-option>
            <a-select-option value="delete">删除</a-select-option>
          </a-select>
          <a-input v-model:value="oplogFilter.keyword" placeholder="搜索摘要/操作人" size="small" style="width:180px" @press-enter="loadOplog(true)" />
          <a-button type="primary" size="small" @click="loadOplog(true)">查询</a-button>
        </div>
        <a-table :columns="oplogColumns" :data-source="oplogList" :pagination="oplogPagination" size="small" bordered row-key="id" :loading="oplogLoading" @change="handleOplogPage">
          <template #bodyCell="{ column, record }">
            <template v-if="column.key === 'action'">
              <a-tag :color="oplogActionColor(record.action)">{{ oplogActionLabel(record.action) }}</a-tag>
            </template>
            <template v-if="column.key === 'create_time'">
              {{ record.create_time || '-' }}
            </template>
            <template v-if="column.key === 'detail'">
              <a v-if="record.detail" @click="showOplogDetail(record)">查看</a>
              <span v-else>-</span>
            </template>
          </template>
        </a-table>
        <a-modal title="变更详情" :open="oplogDetailVisible" @cancel="oplogDetailVisible = false" :footer="null" :width="500">
          <pre style="background:#f5f5f5;padding:16px;border-radius:4px;font-size:12px;overflow-x:auto;max-height:400px">{{ oplogDetailText }}</pre>
        </a-modal>
      </a-tab-pane>
    </a-tabs>

    <!-- 字典编辑弹窗 -->
    <a-modal :title="dictEditMode === 'add' ? '新增字典' : '编辑字典'" :open="dictModalVisible" :confirm-loading="dictSaving" @ok="handleDictSubmit" @cancel="dictModalVisible = false" :width="320">
      <a-form ref="dictFormRef" :model="dictForm" :label-col="{ span: 8 }" :wrapper-col="{ span: 14 }">
        <a-form-item label="名称" name="config_name" :rules="[{ required: true, message: '请输入名称' }]">
          <a-input v-model:value="dictForm.config_name" />
        </a-form-item>
        <a-form-item label="编号" name="config_value" :rules="[{ required: true, message: '请输入编号' }]">
          <a-input-number v-model:value="dictForm.config_value" :min="1" :precision="0" style="width:100%" placeholder="数字编号" />
        </a-form-item>
        <a-form-item label="排序" name="sort_order">
          <a-input-number v-model:value="dictForm.sort_order" :min="0" style="width:100%" />
        </a-form-item>
      </a-form>
    </a-modal>
    <!-- 自定义字段编辑弹窗 -->
    <a-modal :title="cfEditMode === 'add' ? '新增自定义字段' : '编辑自定义字段'" :open="cfModalVisible" :confirm-loading="cfSaving" @ok="handleCfSubmit" @cancel="cfModalVisible = false" :width="420">
      <a-form ref="cfFormRef" :model="cfForm" :label-col="{ span: 6 }" :wrapper-col="{ span: 16 }">
        <a-form-item label="字段标识" name="field_key" :rules="[{ required: true, message: '请输入' }]">
          <a-input v-model:value="cfForm.field_key" placeholder="英文，如 supervisor" :disabled="cfEditMode === 'edit'" />
        </a-form-item>
        <a-form-item label="显示名称" name="field_label" :rules="[{ required: true }]">
          <a-input v-model:value="cfForm.field_label" placeholder="如 监事/理事" />
        </a-form-item>
        <a-form-item label="字段类型" name="field_type">
          <a-select v-model:value="cfForm.field_type">
            <a-select-option value="text">文本</a-select-option>
            <a-select-option value="number">数字</a-select-option>
            <a-select-option value="date">日期</a-select-option>
            <a-select-option value="select">下拉选择</a-select-option>
            <a-select-option value="switch">开关</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="选项" v-if="cfForm.field_type === 'select'">
          <a-select v-model:value="cfForm.options" mode="tags" placeholder="输入选项，回车添加" />
        </a-form-item>
        <a-form-item label="提示文字">
          <a-input v-model:value="cfForm.placeholder" />
        </a-form-item>
        <a-form-item label="排序">
          <a-input-number v-model:value="cfForm.sort" :min="0" :max="999" style="width:100%" />
        </a-form-item>
      </a-form>
    </a-modal>
  </a-card>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { message } from 'ant-design-vue'
import { PlusOutlined, ExportOutlined } from '@ant-design/icons-vue'
import { getSettings, saveSettings, testAi, getAiModels } from '@/api/crm/setting'
import { getList, add, edit, deleteById, initType } from '@/api/crm/config'
import { getList as getCfList, add as addCf, edit as editCf, deleteById as deleteCf, setStatus as setCfStatus } from '@/api/crm/customField'
import { getList as getDrList, setStatus as setDrStatus, aiGenerate, add as addDr, edit as editDr, deleteRule } from '@/api/crm/demandRule'
import { getList as getApkList, generate as genApk, deleteKey } from '@/api/crm/apiKey'
import { list as listUsers } from '@/api/user'
import { getGroups, addGroup, deleteGroup, getTags, addTag, editTag, deleteTag } from '@/api/crm/tag'
import { getList as getOplog } from '@/api/crm/oplog'

const route = useRoute()
const router = useRouter()
// 「审批规则」tab 已下掉（审批工作流没实现，配置是死数据），老链接落回公海规则
const activeKey = ref(route.query.tab === 'approval' ? 'pool' : (route.query.tab || 'dict'))

// ---- Settings ----
const settingLoading = ref(false)
const saving = ref(false)
const settingForm = reactive({
  pool_enabled: 0,
  pool_days: 180,
  // 审批规则 tab 已下掉。这两个值保留在这一层原样回传：
  // 后端 save() 对缺失字段会写默认值，不带着它们反而会把历史配置改掉。
  approval_trigger_amount: 50000, approval_levels: 2,
  ai_provider: 'platform',
  ai_api_url: 'https://api.deepseek.com/v1/chat/completions', ai_api_key: '',
  ai_model: 'deepseek-chat', ai_temperature: 0.7, ai_max_tokens: 4096,
  insight_quotation_overdue_days: 7, insight_repurchase_threshold: 0.8,
  insight_pool_warning_days: 7, insight_churn_multiplier: 1.5,
  insight_churn_no_followup_days: 30, insight_dormant_days: 90, insight_max_per_user: 10,
})

// 公海自动回收开关（后端存 0/1）
const poolAutoEnabled = computed({
  get: () => Number(settingForm.pool_enabled) === 1,
  set: (v) => { settingForm.pool_enabled = v ? 1 : 0 },
})

const aiTesting = ref(false)
const aiTestResult = ref('')

// 平台/自定义接入的额外状态
const aiHasKey = ref(false)
const aiKeyMask = ref('')
const aiModelOptions = ref([])
const aiLoadingModels = ref(false)
const aiManualModel = ref(false)
const aiModelSelectOptions = computed(() => aiModelOptions.value.map(m => ({ value: m, label: m })))

const defaults = { ...settingForm }

function loadSettings() {
  settingLoading.value = true
  getSettings().then(res => {
    const s = res?.data || {}
    Object.keys(defaults).forEach(k => { if (s[k] !== undefined) settingForm[k] = s[k] })
    aiHasKey.value = !!s.ai_has_key
    aiKeyMask.value = s.ai_api_key_mask || ''
    settingForm.ai_api_key = ''   // 后端只回传掩码，输入框永远从空开始
    settingLoading.value = false
    if (activeKey.value === 'ai' && aiHasKey.value) autoLoadAiModels()
  }).catch(err => {
    console.error('[设置] 读取失败：', err)
    settingLoading.value = false
  })
}

function onTabChange(key) {
  router.replace({ query: { tab: key } })
  if (key === 'ai' && aiHasKey.value) autoLoadAiModels()
}

// ---- AI 配置 ----

// 保存前校验：平台接入不用填地址，但必须已有 Key 或这次填了 Key
function validateAiForm() {
  if (settingForm.ai_provider === 'custom' && !String(settingForm.ai_api_url || '').trim()) {
    message.warning('请填写 API 地址'); return false
  }
  if (!String(settingForm.ai_model || '').trim()) {
    message.warning('请选择或填写模型'); return false
  }
  if (!aiHasKey.value && !String(settingForm.ai_api_key || '').trim()) {
    message.warning('请填写 API Key'); return false
  }
  return true
}

function onAiProviderChange() {
  // 两种接入的可用模型不同，切完重新取
  aiModelOptions.value = []
}

function handleSave(tab) {
  if (tab === 'ai' && !validateAiForm()) return
  saving.value = true
  saveSettings({ ...settingForm }).then(() => {
    message.success('保存成功')
    saving.value = false
    if (tab === 'ai') {
      aiModelOptions.value = []
      loadSettings()   // 重新读取：Key 掩码、接入方式以服务端为准
    }
  }).catch(err => {
    console.error('[设置] 保存失败：', err)
    saving.value = false
  })
}

function handleTestAi() {
  aiTesting.value = true; aiTestResult.value = ''
  // 带上未保存的表单值，填完可以先测再存
  testAi({
    provider: settingForm.ai_provider,
    ai_api_url: settingForm.ai_api_url,
    ai_api_key: settingForm.ai_api_key,
    ai_model: settingForm.ai_model,
    ai_temperature: settingForm.ai_temperature,
    ai_max_tokens: settingForm.ai_max_tokens,
  }).then(res => {
    aiTestResult.value = '成功: ' + (res?.data?.reply || 'AI已连接')
    aiTesting.value = false
  }).catch(err => {
    aiTestResult.value = '失败: ' + (err?.message || '请检查 API Key 和网络')
    console.error('[AI配置] 测试连接失败：', err)
    aiTesting.value = false
  })
}

// 模型列表会话缓存：同一套配置反复进出页面不重复请求
const AI_MODEL_CACHE = 'crm_ai_models_v1'
const AI_MODEL_CACHE_TTL = 10 * 60 * 1000

function aiModelCacheSig() {
  return [settingForm.ai_provider, aiKeyMask.value, settingForm.ai_api_url || ''].join('|')
}

function readAiModelCache() {
  try {
    const raw = window.sessionStorage.getItem(AI_MODEL_CACHE)
    if (!raw) return null
    const data = JSON.parse(raw)
    if (!data || data.sig !== aiModelCacheSig()) return null
    if (Date.now() - Number(data.time || 0) > AI_MODEL_CACHE_TTL) return null
    return Array.isArray(data.models) && data.models.length ? data.models : null
  } catch (e) { return null }
}

function writeAiModelCache(models) {
  try {
    window.sessionStorage.setItem(AI_MODEL_CACHE, JSON.stringify({
      sig: aiModelCacheSig(), time: Date.now(), models,
    }))
  } catch (e) { /* 隐私模式/超配额忽略 */ }
}

function toggleAiManualModel() {
  aiManualModel.value = !aiManualModel.value
}

function handleLoadAiModels() { loadAiModels(false) }

function autoLoadAiModels() {
  const cached = readAiModelCache()
  if (cached) { aiModelOptions.value = cached; ensureAiModel(cached); return }
  loadAiModels(true)
}

/**
 * 读取模型列表
 * @param {boolean} silent 进入页面时静默执行，不弹提示
 */
function loadAiModels(silent = false) {
  if (!aiHasKey.value && !String(settingForm.ai_api_key || '').trim()) {
    if (!silent) message.warning('请先填写 API Key')
    return
  }
  aiLoadingModels.value = true
  getAiModels({
    provider: settingForm.ai_provider,
    ai_api_url: settingForm.ai_api_url,
    ai_api_key: settingForm.ai_api_key,
  }).then(res => {
    const list = res?.data?.models || []
    aiModelOptions.value = list
    if (!list.length) return
    writeAiModelCache(list)
    if (!silent) message.success('已获取 ' + list.length + ' 个可用模型')
    ensureAiModel(list)
  }).catch(err => {
    aiModelOptions.value = []
    console.error('[AI配置] 获取模型失败：', err)
  }).finally(() => { aiLoadingModels.value = false })
}

// 平台接入只接受列表里的模型（网关按模型名路由）；自定义接入允许手填
function ensureAiModel(list) {
  const current = String(settingForm.ai_model || '').trim()
  if (current && list.indexOf(current) > -1) return
  if (settingForm.ai_provider === 'platform') {
    settingForm.ai_model = list[0]
    message.info('当前模型不可用，已选择 ' + list[0])
  } else if (current) {
    message.warning('当前模型不在可用列表中，请确认后再保存')
  }
}

// ---- Dictionary ----
const dictTypes = [
  { key: 'industry', label: '行业' },
  { key: 'customer_level', label: '客户等级' },
  { key: 'customer_source', label: '客户来源' },
  { key: 'customer_group', label: '客户分组' },
  { key: 'follow_type', label: '跟进方式' },
  { key: 'follow_result', label: '跟进结果' },
  { key: 'product_category', label: '产品分类' },
  { key: 'currency', label: '币种' },
]

const dictType = ref('customer_level')
const dictList = ref([])
const dictLoading = ref(false)
const dictPagination = reactive({ current: 1, pageSize: 20, total: 0, showSizeChanger: false })
const dictColumns = [
  { title: '名称', dataIndex: 'config_name', key: 'config_name', width: 150 },
  { title: '值', dataIndex: 'config_value', key: 'config_value', width: 100 },
  { title: '排序', dataIndex: 'sort_order', key: 'sort_order', width: 60 },
  { title: '操作', key: 'action', width: 120 },
]

const dictFormRef = ref()
const dictModalVisible = ref(false)
const dictEditMode = ref('add')
const dictEditId = ref(null)
const dictSaving = ref(false)
const dictForm = reactive({ config_name: '', config_value: undefined, sort_order: 100 })

function loadDictList() {
  dictLoading.value = true
  getList(dictType.value).then(res => {
    const list = res?.data?.list || {}
    dictList.value = list.data || []
    dictPagination.total = list.total || 0
    dictLoading.value = false
  }).catch(() => { dictLoading.value = false })
}

function handleDictPageChange(p) { dictPagination.current = p.current; loadDictList() }

function handleDictAdd() {
  dictEditMode.value = 'add'; dictEditId.value = null
  dictFormRef.value?.resetFields()
  dictForm.config_name = ''; dictForm.config_value = undefined; dictForm.sort_order = 100
  dictModalVisible.value = true
}

function handleDictEdit(record) {
  dictEditMode.value = 'edit'; dictEditId.value = record.id
  dictForm.config_name = record.config_name; dictForm.config_value = record.config_value; dictForm.sort_order = record.sort_order
  dictModalVisible.value = true
}

function handleDictDelete(record) { deleteById(record.id).then(() => { message.success('已删除'); loadDictList() }) }

function handleDictInit() { initType(dictType.value).then(() => { message.success('初始化成功'); loadDictList() }) }

function handleDictSubmit() {
  dictFormRef.value.validate().then((vals) => {
    dictSaving.value = true
    vals.config_type = dictType.value
    const fn = dictEditMode.value === 'edit' ? edit : add
    if (dictEditId.value) vals.id = dictEditId.value
    fn(vals).then(() => { message.success('保存成功'); dictSaving.value = false; dictModalVisible.value = false; loadDictList() }).catch(() => { dictSaving.value = false })
  }).catch(() => {})
}

// ---- Custom Fields ----
const cfList = ref([])
const cfLoading = ref(false)
const cfPagination = reactive({ current: 1, pageSize: 10, total: 0, showSizeChanger: false })
const cfColumns = [
  { title: '标识', dataIndex: 'field_key', width: 100 },
  { title: '名称', dataIndex: 'field_label', width: 100 },
  { title: '类型', dataIndex: 'field_type', width: 70 },
  { title: '排序', dataIndex: 'sort', width: 50 },
  { title: '状态', dataIndex: 'status', width: 60 },
  { title: '操作', key: 'action', width: 160 },
]
const cfModalVisible = ref(false)
const cfEditMode = ref('add')
const cfSaving = ref(false)
const cfFormRef = ref()
const cfForm = reactive({ id: 0, field_key: '', field_label: '', field_type: 'text', options: [], placeholder: '', sort: 100 })

function loadCfList(p = {}) {
  cfLoading.value = true
  getCfList({ ...p, entity: 'customer' }).then(res => {
    const data = res?.data?.list?.data || res?.data?.list || []
    cfList.value = data
    cfPagination.total = res?.data?.list?.total || data.length
  }).finally(() => { cfLoading.value = false })
}

function handleCfPageChange(pag) { cfPagination.current = pag.current; loadCfList({ page: pag.current, limit: pag.pageSize }) }

function handleCfAdd() {
  cfEditMode.value = 'add'
  cfFormRef.value?.resetFields()
  Object.assign(cfForm, { id: 0, field_key: '', field_label: '', field_type: 'text', options: [], placeholder: '', sort: 100 })
  cfModalVisible.value = true
}

function handleCfEdit(record) {
  cfEditMode.value = 'edit'
  const opts = typeof record.options === 'string' ? JSON.parse(record.options || '[]') : (record.options || [])
  Object.assign(cfForm, { ...record, options: opts })
  cfModalVisible.value = true
}

function handleCfDelete(record) { deleteCf(record.id).then(() => { message.success('已删除'); loadCfList() }) }

function handleCfToggle(record) {
  const newStatus = record.status === 1 ? 0 : 1
  setCfStatus(record.id, newStatus).then(() => { message.success('已更新'); loadCfList() })
}

function handleCfSubmit() {
  cfFormRef.value.validate().then(() => {
    cfSaving.value = true
    const data = { ...cfForm, entity: 'customer' }
    if (cfForm.field_type === 'select') { data.options = JSON.stringify(cfForm.options || []) } else { data.options = '' }
    const fn = cfEditMode.value === 'edit' ? editCf : addCf
    if (cfEditMode.value === 'edit') data.id = cfForm.id
    fn(data).then(() => {
      message.success(cfEditMode.value === 'add' ? '添加成功' : '保存成功')
      cfModalVisible.value = false
      loadCfList()
    }).finally(() => { cfSaving.value = false })
  }).catch(() => {})
}

// ---- Demand Rules ----
const drList = ref([])
const drLoading = ref(false)
const drColumns = [
  { title: '规则名称', dataIndex: 'rule_name', width: 150 },
  { title: '标识', dataIndex: 'rule_key', width: 140 },
  { title: '优先级', key: 'priority', width: 60 },
  { title: '启用', key: 'status', width: 60 },
  { title: '操作', key: 'action', width: 100 },
]
function loadDrList() {
  drLoading.value = true
  getDrList().then(res => { drList.value = res?.data?.list || []; drLoading.value = false }).catch(() => { drLoading.value = false })
}
function handleDrToggle(record, checked) {
  setDrStatus(record.id, checked ? 1 : 0).then(() => { message.success(checked ? '已启用' : '已停用'); loadDrList() })
}

// 编辑规则
const drEditVisible = ref(false)
const drEditSaving = ref(false)
const drEditForm = reactive({ id: 0, rule_name: '', conditions: [], logic: 'all', priority: 2 })

function handleDrEdit(record) {
  let conds = []
  try { conds = JSON.parse(record.conditions || '[]') } catch { conds = [] }
  if (!Array.isArray(conds) || conds.length === 0) conds = [{ field: '', op: 'not_empty', value: '' }]
  Object.assign(drEditForm, { id: record.id, rule_name: record.rule_name, conditions: conds, logic: record.logic || 'all', priority: record.priority || 2 })
  drEditVisible.value = true
}

function handleDrEditSubmit() {
  drEditSaving.value = true
  editDr({
    id: drEditForm.id,
    rule_name: drEditForm.rule_name,
    conditions: JSON.stringify(drEditForm.conditions.filter(c => c.field)),
    logic: drEditForm.logic,
    priority: drEditForm.priority,
  }).then(() => {
    message.success('规则已更新')
    drEditVisible.value = false
    loadDrList()
  }).finally(() => { drEditSaving.value = false })
}

function handleDrDelete(record) {
  deleteRule(record.id).then(() => { message.success('已删除'); loadDrList() })
}

// AI 创建规则
const drAiVisible = ref(false)
const drAiLoading = ref(false)
const drAiDesc = ref('')
const drAiPreview = ref(null)

function handleDrAiCreate() {
  drAiDesc.value = ''
  drAiPreview.value = null
  drAiVisible.value = true
}

function handleDrAiSubmit() {
  if (!drAiDesc.value.trim()) { message.warning('请输入规则描述'); return }
  if (drAiPreview.value) {
    // 已生成预览 → 确认保存
    drAiLoading.value = true
    const p = drAiPreview.value
    addDr({
      rule_key: p.rule_key,
      rule_name: p.rule_name,
      conditions: typeof p.conditions === 'string' ? p.conditions : JSON.stringify(p.conditions),
      logic: p.logic || 'all',
      priority: p.priority || 2,
    }).then(() => {
      message.success('规则已创建并扫描完成')
      drAiVisible.value = false
      loadDrList()
    }).finally(() => { drAiLoading.value = false })
  } else {
    // 第一步：AI 生成规则
    drAiLoading.value = true
    aiGenerate(drAiDesc.value).then(res => {
      drAiPreview.value = res?.data || null
      if (!drAiPreview.value) message.warning('AI没有返回有效结果，请重试')
    }).finally(() => { drAiLoading.value = false })
  }
}

// ---- API Keys ----
const apkList = ref([])
const apkLoading = ref(false)
const apkName = ref('')
const apkScopes = ref('read')
const apkGenerating = ref(false)
const apkNewKey = ref('')
const apkUserId = ref(undefined)
const apkUserOptions = ref([])
const apkUserLoading = ref(false)
const apkColumns = [
  { title: '备注', dataIndex: 'name', width: 90, ellipsis: true },
  { title: '密钥', key: 'api_key', width: 330 },
  { title: '权限', key: 'scopes', width: 60 },
  { title: '绑定用户', key: 'bind_user', width: 130 },
  { title: '最后使用', key: 'last_used_at', width: 140 },
  { title: '操作', key: 'action', width: 60 },
]
function loadApkList() {
  apkLoading.value = true
  getApkList().then(res => { apkList.value = res?.data?.list || []; apkLoading.value = false }).catch(() => { apkLoading.value = false })
}
function handleGenerateKey() {
  apkGenerating.value = true; apkNewKey.value = ''
  genApk(apkName.value || '未命名', apkScopes.value, apkUserId.value || 0).then(res => {
    apkNewKey.value = res?.data?.api_key || ''
    apkName.value = ''
    apkUserId.value = undefined
    loadApkList()
  }).finally(() => { apkGenerating.value = false })
}
function handleDeleteKey(record) {
  deleteKey(record.id).then(() => { message.success('已删除'); loadApkList() })
}
function handleApkUserSearch(kw) {
  apkUserLoading.value = true
  listUsers({ search: kw, isSupplier: 0 }).then(res => {
    const data = res?.data?.list?.data || res?.data?.list || []
    apkUserOptions.value = data.map(u => ({
      value: u.store_user_id,
      label: u.real_name || u.user_name || ('用户#' + u.store_user_id),
    }))
  }).finally(() => { apkUserLoading.value = false })
}
function loadApkUsers() { handleApkUserSearch('') }

// ---- Tag Management ----
const tagTargetType = ref('customer')
const tagLoading = ref(false)
const tagList = ref([])
const tagGroupOptions = ref([])
const showTagModal = ref(false)
const showTagGroupModal = ref(false)
const tagEditMode = ref('add')
const tagSaving = ref(false)
const tagFormRef = ref()
const tagForm = reactive({ id: 0, name: '', group_id: 0, color: 'blue', sort_order: 100 })
const newGroupName = ref('')

const tagColumns = [
  { title: '标签', key: 'color', width: 150 },
  { title: '分组', key: 'group_name', width: 100 },
  { title: '排序', dataIndex: 'sort_order', width: 60 },
  { title: '操作', key: 'action', width: 120 },
]
const groupTableColumns = [
  { title: '分组名称', dataIndex: 'name' },
  { title: '操作', key: 'action', width: 80 },
]

function onTagTargetChange() {
  loadTagData()
}

function loadTagData() {
  tagLoading.value = true
  // Load groups
  getGroups(tagTargetType.value).then(res => {
    tagGroupOptions.value = res?.data?.list || []
  })
  // Load tags
  getTags({ target_type: tagTargetType.value }).then(res => {
    tagList.value = res?.data?.list || []
    tagLoading.value = false
  }).catch(() => { tagLoading.value = false })
}

function handleTagAdd(groupId) {
  tagEditMode.value = 'add'
  tagFormRef.value?.resetFields()
  tagForm.id = 0; tagForm.name = ''; tagForm.group_id = groupId ?? 0; tagForm.color = 'blue'; tagForm.sort_order = 100
  showTagModal.value = true
}

function handleTagEdit(record) {
  tagEditMode.value = 'edit'
  tagForm.id = record.id; tagForm.name = record.name; tagForm.group_id = record.group_id
  tagForm.color = record.color; tagForm.sort_order = record.sort_order
  showTagModal.value = true
}

function handleTagDelete(record) {
  deleteTag(record.id).then(() => { message.success('已删除'); loadTagData() })
}

function handleTagSubmit() {
  tagFormRef.value.validate().then(() => {
    tagSaving.value = true
    const data = { name: tagForm.name, group_id: tagForm.group_id, color: tagForm.color, sort_order: tagForm.sort_order, target_type: tagTargetType.value }
    const fn = tagEditMode.value === 'edit' ? editTag : addTag
    if (tagEditMode.value === 'edit') data.id = tagForm.id
    fn(tagEditMode.value === 'edit' ? tagForm.id : data, data).then(() => {
      message.success('保存成功')
      showTagModal.value = false
      loadTagData()
    }).finally(() => { tagSaving.value = false })
  }).catch(() => {})
}

function handleGroupAdd() {
  if (!newGroupName.value.trim()) { message.warning('请输入分组名称'); return }
  addGroup({ name: newGroupName.value.trim(), target_type: tagTargetType.value, sort_order: 100 }).then(() => {
    message.success('分组已添加')
    newGroupName.value = ''
    loadTagData()
  })
}

function handleGroupDelete(record) {
  deleteGroup(record.id).then(() => { message.success('已删除'); loadTagData() }).catch(() => {})
}

// ---- Operation Log ----
const oplogLoading = ref(false)
const oplogList = ref([])
const oplogPagination = reactive({ current: 1, pageSize: 15, total: 0, showSizeChanger: true, showTotal: t => `共 ${t} 条` })
const oplogFilter = reactive({ action: '', keyword: '' })
const oplogDetailVisible = ref(false)
const oplogDetailText = ref('')
const oplogColumns = [
  { title: '时间', key: 'create_time', width: 150 },
  { title: '操作人', dataIndex: 'user_name', width: 80 },
  { title: '类型', key: 'action', width: 80 },
  { title: '摘要', dataIndex: 'summary', ellipsis: true },
  { title: 'IP', dataIndex: 'ip', width: 120 },
  { title: '详情', key: 'detail', width: 60 },
]

function oplogActionColor(a) {
  const m = { login:'green', login_fail:'red', logout:'default', add:'blue', edit:'orange', delete:'red' }
  return m[a] || 'default'
}
function oplogActionLabel(a) {
  const m = {
    login: '登录', login_fail: '登录失败', logout: '登出',
    add: '新增', edit: '编辑', delete: '删除',
    claim: '认领', release: '释放', convert: '转化',
  }
  return m[a] || a
}

function loadOplog(reset = false) {
  if (reset) oplogPagination.current = 1
  oplogLoading.value = true
  getOplog({ page: oplogPagination.current, limit: oplogPagination.pageSize, ...oplogFilter })
    .then(res => {
      const d = res?.data?.list || {}
      oplogList.value = d.data || []
      oplogPagination.total = d.total || 0
    }).finally(() => { oplogLoading.value = false })
}

function handleOplogPage(p) { oplogPagination.current = p.current; loadOplog() }

function showOplogDetail(record) {
  try {
    const d = typeof record.detail === 'string' ? JSON.parse(record.detail) : record.detail
    oplogDetailText.value = JSON.stringify(d, null, 2)
  } catch { oplogDetailText.value = record.detail }
  oplogDetailVisible.value = true
}

onMounted(() => { loadSettings(); loadDictList(); loadCfList(); loadDrList(); loadApkList(); loadApkUsers(); loadTagData(); loadOplog() })
</script>

<style scoped>
.ai-hint { color: #999; font-size: 12px; line-height: 1.6; margin-top: 4px; }
.ai-hint a { color: #1677ff; }
.ai-hint-inline { color: #999; font-size: 12px; margin-left: 8px; }
.ai-key-row,
.ai-model-row { display: flex; align-items: center; }
.ai-key-row > .ant-input-password,
.ai-model-row > .ant-select,
.ai-model-row > .ant-input { flex: 1; margin-right: 8px; }
.ai-key-link { white-space: nowrap; font-size: 13px; }
</style>
