<template>
  <a-card :bordered="false" class="project-detail" :loading="loading">
    <template #title>
      <div class="topbar">
        <a-button type="text" size="small" class="back" @click="router.back()">← 返回</a-button>
        <span class="topbar-title">{{ project.title || '项目详情' }}</span>
        <a-tag v-if="project.status" :color="statusColor(project.status)">{{ project.status_text }}</a-tag>
        <span class="topbar-actions">
          <a-button type="primary" size="small" @click="openFollowDrawer()">记一笔</a-button>
          <a-dropdown>
            <a-button size="small">新建 <down-outlined /></a-button>
            <template #overlay>
              <a-menu @click="onCreateMenu">
                <a-menu-item key="intent">需求</a-menu-item>
                <a-menu-item key="quote">报价</a-menu-item>
                <a-menu-item key="contract">合同</a-menu-item>
                <a-menu-item key="ticket">交付</a-menu-item>
                <a-menu-item key="participant">参与人</a-menu-item>
              </a-menu>
            </template>
          </a-dropdown>
          <a-dropdown>
            <a-button size="small">更多 <down-outlined /></a-button>
            <template #overlay>
              <a-menu @click="onMoreMenu">
                <a-menu-item key="ai">AI 摘要</a-menu-item>
                <a-menu-divider />
                <a-menu-item v-if="project.status === 1 && !project.archived_at" key="start">开始项目</a-menu-item>
                <a-menu-item v-if="project.status === 2 && !project.archived_at" key="complete">完成项目</a-menu-item>
                <a-menu-item v-if="project.status === 3 && !project.archived_at" key="reopen">重新打开</a-menu-item>
                <a-menu-item v-if="project.status !== 4 && !project.archived_at" key="edit">编辑项目</a-menu-item>
                <a-menu-item v-if="project.status !== 4 && !project.archived_at" key="cancel" danger>取消项目</a-menu-item>
                <a-menu-item v-if="!project.archived_at && (project.status === 3 || project.status === 4)" key="archive">归档</a-menu-item>
              </a-menu>
            </template>
          </a-dropdown>
        </span>
      </div>
    </template>

    <div class="proj-dossier">
      <div class="dossier-head">
        <span class="dossier-title">项目资料</span>
        <span class="dossier-no">{{ project.project_no }}</span>
        <span class="dossier-edit"><a-button type="link" size="small" @click="openEditProject">编辑</a-button></span>
      </div>

      <div class="dossier-grid">
        <div class="dcell">
          <div class="dlabel">客户</div>
          <div class="dcustomer" :class="{ muted: !project.customer_name }" @click="goCustomer">{{ project.customer_name || '未关联客户' }}</div>
          <div v-if="primaryContact" class="dsub">
            {{ primaryContact.contact_name }}<span v-if="primaryContact.mobile"> · {{ primaryContact.mobile }}</span>
          </div>
          <div v-else class="dsub muted">未添加联系人</div>
        </div>

        <div class="dcell">
          <div class="dlabel">负责团队</div>
          <div v-for="p in ownerList" :key="p.role" class="dperson">
            <a-avatar :size="22" class="davatar" :style="{ background: p.color }">{{ (p.name || '?').slice(0, 1) }}</a-avatar>
            <span class="dname">{{ p.name }}</span>
            <span class="drole">{{ p.role }}</span>
          </div>
          <div v-if="!ownerList.length" class="dsub muted">未指定负责人</div>
          <div class="dsub dlink" @click="tab = 'resources'; resTab = 'participants'">参与 {{ participants.length }} 人</div>
        </div>

        <div class="dcell">
          <div class="dlabel">服务期</div>
          <template v-if="project.service_start_time && project.service_end_time">
            <div class="dvalue">{{ servicePeriod }}</div>
            <a-progress :percent="serviceProgress.percent" size="small" :show-info="false" stroke-color="#d6893b" />
            <div class="dsub">{{ serviceProgress.text }}</div>
          </template>
          <div v-else class="dsub muted">未设置 · <a @click="openEditProject">去设置</a></div>
        </div>

        <div class="dcell">
          <div class="dlabel">业务进度</div>
          <div class="dvalue dlink" @click="tab = 'intents'">需求 <b>{{ stats.intent_total || 0 }}</b> 条 <span class="dsub">成交 {{ stats.intent_won || 0 }}</span></div>
          <a-progress :percent="deliveryPercent" size="small" :show-info="false" stroke-color="#52c41a" />
          <div class="dsub dlink" @click="tab = 'deliveries'">交付 {{ stats.delivery_done || 0 }}/{{ stats.delivery_total || 0 }} 已完成</div>
        </div>
      </div>

      <div class="dossier-foot">
        <div class="dfoot-row">
          <span class="dlabel">备注</span>
          <span class="dremark" :class="{ muted: !project.remark }">{{ project.remark || '—' }}</span>
        </div>
        <div class="dfoot-row">
          <span class="dlabel">资料</span>
          <div class="dchips">
            <template v-if="documents.length">
              <a v-for="d in documents.slice(0, 8)" :key="d.id" class="doc-chip" @click="previewDoc(d)">
                <paper-clip-outlined />{{ d.title || d.file_name }}
              </a>
              <a v-if="documents.length > 8" class="doc-more" @click="goFiles">查看全部 {{ documents.length }} 份</a>
            </template>
            <template v-else>
              <span class="muted">暂无资料</span>
              <a style="margin-left:8px" @click="goFiles">去上传</a>
            </template>
          </div>
        </div>
      </div>
    </div>

    <div class="proj-hero">
      <div class="hero-metrics">
        <a class="metric" @click="tab = 'communication'"><b>{{ followups.length }}</b><span>沟通记录</span></a>
        <a class="metric" @click="goSales('orders')"><b>{{ money(stats.order_amount) }}</b><span>订单金额</span></a>
        <a class="metric" @click="goSales('payments')"><b>{{ money(stats.received_amount) }}</b><span>已收款</span></a>
        <a class="metric" @click="goSales('payments')"><b :class="{ warn: stats.unpaid_amount > 0 }">{{ money(stats.unpaid_amount) }}</b><span>待收款</span></a>
      </div>
    </div>

    <a-tabs v-model:activeKey="tab" class="main-tabs">
      <a-tab-pane key="overview">
        <template #tab><span class="tb-label">概览</span></template>
        <div class="overview" :class="{ single: !todoItems.length }">
          <div v-if="!intents.length" class="ov-block start-hint">
            <div class="ov-title">下一步</div>
            <p class="hint-text">这个项目还没有需求。把客户要做的事记成「需求」，后面的报价、订单、合同、交付都挂在需求下面。</p>
            <a-button type="primary" size="small" @click="openCreateIntent">新增需求</a-button>
          </div>
          <div v-if="todoItems.length" class="ov-block">
            <div class="ov-title">待处理 <span class="ov-count">{{ todoItems.length }}</span></div>
            <ul class="ov-list todo-list">
              <li v-for="item in todoItems" :key="item.key">
                <span class="todo-text">{{ item.text }}</span>
                <a-button type="link" size="small" @click="handleTodo(item)">{{ item.action }}</a-button>
              </li>
            </ul>
          </div>
          <div class="ov-block">
            <div class="ov-title">最近动态</div>
            <ul class="ov-list timeline-list">
              <li v-for="item in timeline.slice(0, 5)" :key="item.kind + item.data.id" class="tl-row">
                <div class="tl-head">
                  <span class="tl-title">{{ timelineTitle(item) }}</span>
                  <span class="tl-time">{{ formatTime(item.time) }}</span>
                </div>
                <div v-if="timelineDetail(item)" class="tl-content">{{ timelineDetail(item) }}</div>
              </li>
              <li v-if="!timeline.length">暂无记录</li>
            </ul>
          </div>
        </div>
      </a-tab-pane>

      <a-tab-pane key="communication">
        <template #tab><span class="tb-label">沟通</span><span class="tb-count">{{ followups.length }}</span></template>
        <div class="tab-toolbar">
          <a-button type="primary" @click="openFollowDrawer()">记一笔</a-button>
        </div>
        <a-timeline>
          <a-timeline-item v-for="f in followups" :key="f.id" :color="f.result === '有效' ? 'green' : f.result === '无效' ? 'red' : 'blue'">
            <div class="tl-meta">
              {{ formatTime(f.follow_date) }} · {{ f.follow_type }} · {{ f.result }} · {{ f.real_name || f.user_name || '' }}
            </div>
            <div class="follow-body" v-html="decodeHtml(f.follow_content)"></div>
            <div v-if="f.intent_id" class="tl-meta">
              关联需求：<a @click="goIntentById(f.intent_id)">{{ f.intent_no || ('#' + f.intent_id) }} {{ f.intent_title }}</a>
            </div>
            <div v-if="f.next_follow_date" class="tl-meta" :class="{ overdue: isOverdue(f.next_follow_date) }">
              下次跟进：{{ new Date(f.next_follow_date * 1000).toLocaleDateString() }} {{ f.next_follow_content || '' }}
            </div>
            <div v-if="f.attachment" style="margin-top:2px">
              <a style="cursor:pointer" @click="previewAttachment(f.attachment)"><paper-clip-outlined /> 附件</a>
            </div>
            <a-popconfirm title="确认删除这条沟通记录?" @confirm="deleteFollow(f)">
              <a style="color:#ff4d4f;font-size:12px">删除</a>
            </a-popconfirm>
          </a-timeline-item>
          <a-timeline-item v-if="!followups.length" color="gray">还没有沟通记录，先在上面记一笔</a-timeline-item>
        </a-timeline>
      </a-tab-pane>

      <a-tab-pane key="intents">
        <template #tab><span class="tb-label">需求</span><span class="tb-count">{{ intents.length }}</span></template>
        <a-table :data-source="intents" :pagination="false" row-key="id" size="small" bordered>
          <a-table-column title="需求号" data-index="intent_no" key="intent_no" width="160">
            <template #default="{ record }"><a @click="goIntent(record)">{{ record.intent_no }}</a></template>
          </a-table-column>
          <a-table-column title="标题" data-index="title" key="title" ellipsis />
          <a-table-column title="品类" data-index="catalog_name" key="catalog_name" width="100" />
          <a-table-column title="数量" key="qty" width="80">
            <template #default="{ record }">{{ record.quantity }} {{ record.unit }}</template>
          </a-table-column>
          <a-table-column title="承接人" data-index="assignee_user_name" key="assignee" width="90" />
          <a-table-column title="状态" key="status" width="110">
            <template #default="{ record }">
              <a-tag>{{ record.status_text }}</a-tag>
              <a-tag v-if="hasDraftQuote(record.id)" color="orange">报价草稿</a-tag>
            </template>
          </a-table-column>
          <a-table-column title="操作" key="action" width="290">
            <template #default="{ record }">
              <template v-if="record.status !== 6">
                <a @click="openEditIntent(record)">编辑</a><a-divider type="vertical" />
                <a @click="openAssignIntent(record)">指派</a><a-divider type="vertical" />
                <a @click="openFollowDrawer(record.id)">沟通</a>
                <template v-if="record.status === 2 || record.status === 3">
                  <a-divider type="vertical" /><a @click="openQuote(record)">报价</a>
                </template>
                <template v-if="record.status === 3">
                  <a-divider type="vertical" /><a @click="handleConfirmIntent(record)">成交</a>
                </template>
                <template v-if="record.status === 2 || record.status === 3">
                  <a-divider type="vertical" /><a @click="openReason(record, 'hold')">搁置</a>
                </template>
                <template v-if="record.status === 5">
                  <a-divider type="vertical" /><a @click="handleResumeIntent(record)">重启</a>
                </template>
                <a-divider type="vertical" /><a style="color:#ff4d4f" @click="openReason(record, 'close')">关闭</a>
              </template>
              <span v-else class="muted">已关闭</span>
            </template>
          </a-table-column>
        </a-table>
      </a-tab-pane>

      <a-tab-pane key="sales">
        <template #tab><span class="tb-label">销售</span><span class="tb-count">{{ salesTotal }}</span></template>
        <a-alert v-if="unlinkedTotal" type="info" show-icon style="margin-bottom:12px">
          <template #message>
            客户还有
            <template v-if="unlinked.quotations">{{ unlinked.quotations }} 份报价、</template>
            <template v-if="unlinked.orders">{{ unlinked.orders }} 张订单、</template>
            <template v-if="unlinked.contracts">{{ unlinked.contracts }} 份合同</template>
            未归属项目（不计入本项目统计）<template v-if="unlinked.order_amount">，订单金额 {{ money(unlinked.order_amount) }}</template>。
            <a @click="goCustomer">去客户详情查看</a>
          </template>
        </a-alert>
        <a-tabs v-model:activeKey="salesTab" size="small">
          <a-tab-pane key="quotes" :tab="`报价 ${commitments.quotations.length}`">
        <a-button type="primary" size="small" style="margin-bottom:12px" @click="openQuoteBlank">
          <template #icon><plus-outlined /></template>新建报价
        </a-button>
        <a-table :data-source="commitments.quotations" :pagination="false" row-key="id" size="small" bordered>
          <a-table-column title="报价单号" data-index="quotation_no" key="quotation_no" width="160" />
          <a-table-column title="金额" key="amount" width="110" align="right">
            <template #default="{ record }">{{ money(record.final_amount) }}</template>
          </a-table-column>
          <a-table-column title="状态" key="status" width="90">
            <template #default="{ record }"><a-tag>{{ quoteStatusText(record.status) }}</a-tag></template>
          </a-table-column>
          <a-table-column title="日期" key="date" width="110">
            <template #default="{ record }">{{ record.quotation_date ? new Date(record.quotation_date * 1000).toLocaleDateString() : '-' }}</template>
          </a-table-column>
          <a-table-column title="操作" key="action" width="270">
            <template #default="{ record }">
              <template v-if="record.status === 1">
                <a @click="openEditQuote(record)">编辑</a><a-divider type="vertical" />
                <a @click="changeQuoteStatus(record, 2)">发送</a><a-divider type="vertical" />
                <a-popconfirm title="确认删除?" @confirm="deleteQuote(record)"><a style="color:#ff4d4f">删除</a></a-popconfirm>
              </template>
              <template v-else-if="record.status === 2">
                <a @click="changeQuoteStatus(record, 3)">确认</a><a-divider type="vertical" />
                <a @click="changeQuoteStatus(record, 4)">拒绝</a>
              </template>
              <template v-else-if="record.status === 3">
                <a @click="convertQuote(record)">转订单</a>
              </template>
              <span v-else class="muted">—</span>
              <a-divider type="vertical" />
              <a @click="router.push({ path: '/crm/quotation/detail', query: { id: record.id } })">详情</a>
            </template>
          </a-table-column>
        </a-table>
      </a-tab-pane>

      <a-tab-pane key="orders" :tab="`订单 ${commitments.orders.length}`">
        <a-table :data-source="commitments.orders" :pagination="false" row-key="id" size="small" bordered>
          <a-table-column title="订单号" data-index="order_no" key="order_no" width="160" />
          <a-table-column title="金额" key="amount" width="110" align="right">
            <template #default="{ record }">{{ money(record.final_amount) }}</template>
          </a-table-column>
          <a-table-column title="状态" key="status" width="90">
            <template #default="{ record }"><a-tag>{{ orderStatusText(record.status) }}</a-tag></template>
          </a-table-column>
          <a-table-column title="收款" key="payment" width="90">
            <template #default="{ record }">{{ paymentText(record.payment_status) }}</template>
          </a-table-column>
          <a-table-column title="服务期" key="service" width="170">
            <template #default="{ record }">
              {{ record.service_start_time && record.service_end_time
                ? new Date(record.service_start_time * 1000).toLocaleDateString() + ' ~ ' + new Date(record.service_end_time * 1000).toLocaleDateString()
                : '—' }}
            </template>
          </a-table-column>
          <a-table-column title="操作" key="action" width="260">
            <template #default="{ record }">
              <template v-if="record.status === 1">
                <a @click="orderAction(record, 2, '订单已开始')">确认</a><a-divider type="vertical" />
                <a @click="orderAction(record, 6, '订单已取消')">取消</a>
              </template>
              <template v-else-if="[2, 3, 4].includes(record.status)">
                <a @click="orderAction(record, 5, '订单已完成')">完成</a><a-divider type="vertical" />
                <a style="color:#ff4d4f" @click="orderAction(record, 6, '订单已取消')">取消</a>
              </template>
              <span v-else class="muted">—</span>
              <template v-if="record.status !== 6">
                <template v-if="Number(record.unpaid_amount) > 0">
                  <a-divider type="vertical" />
                  <a @click="openPayment(record)">收款</a>
                </template>
                <a-divider type="vertical" />
                <a @click="convertOrderToContract(record)">转合同</a>
                <template v-if="record.service_start_time && record.service_end_time">
                  <a-divider type="vertical" />
                  <a @click="generateOrderPeriodsAction(record)">生成期次</a>
                </template>
              </template>
              <a-divider type="vertical" />
              <a @click="router.push({ path: '/crm/order/detail', query: { id: record.id } })">详情</a>
            </template>
          </a-table-column>
        </a-table>
      </a-tab-pane>

      <a-tab-pane key="contracts" :tab="`合同 ${commitments.contracts.length}`">
        <a-button type="primary" size="small" style="margin-bottom:12px" @click="openContractBlank">
          <template #icon><plus-outlined /></template>新建合同
        </a-button>
        <a-table :data-source="commitments.contracts" :pagination="false" row-key="id" size="small" bordered>
          <a-table-column title="合同号" data-index="contract_no" key="contract_no" width="160" />
          <a-table-column title="合同名称" data-index="contract_name" key="contract_name" ellipsis />
          <a-table-column title="金额" key="amount" width="110" align="right">
            <template #default="{ record }">{{ money(record.contract_amount) }}</template>
          </a-table-column>
          <a-table-column title="状态" key="status" width="90">
            <template #default="{ record }"><a-tag>{{ contractStatusText(record.status) }}</a-tag></template>
          </a-table-column>
          <a-table-column title="操作" key="action" width="220">
            <template #default="{ record }">
              <template v-if="record.status === 1">
                <a @click="openEditContract(record)">编辑</a><a-divider type="vertical" />
                <a @click="contractAction(record, 2, '已签订')">签订</a><a-divider type="vertical" />
                <a-popconfirm title="确认删除?" @confirm="deleteContractItem(record)"><a style="color:#ff4d4f">删除</a></a-popconfirm>
              </template>
              <template v-else-if="record.status === 2">
                <a @click="contractAction(record, 3, '已开始履行')">履行</a><a-divider type="vertical" />
                <a @click="contractAction(record, 5, '合同已终止')">终止</a>
              </template>
              <template v-else-if="record.status === 3">
                <a @click="contractAction(record, 4, '合同已完成')">完成</a><a-divider type="vertical" />
                <a @click="contractAction(record, 5, '合同已终止')">终止</a>
              </template>
              <span v-else class="muted">—</span>
              <a-divider type="vertical" />
              <a @click="router.push({ path: '/crm/contract/detail', query: { id: record.id } })">详情</a>
            </template>
          </a-table-column>
        </a-table>
          </a-tab-pane>

          <a-tab-pane key="payments" :tab="`收款 ${payments.length}`">
            <div class="pay-summary">
              <div><span class="muted">订单金额</span><strong>{{ money(stats.order_amount) }}</strong></div>
              <div><span class="muted">已收款</span><strong style="color:#52c41a">{{ money(stats.received_amount) }}</strong></div>
              <div><span class="muted">待收款</span><strong style="color:#ff4d4f">{{ money(stats.unpaid_amount) }}</strong></div>
              <a-button type="primary" size="small" @click="openPayment()">登记收款</a-button>
            </div>
            <a-table :data-source="payments" :pagination="false" row-key="id" size="small" bordered>
              <a-table-column title="收款单号" data-index="payment_no" key="payment_no" width="150" />
              <a-table-column title="订单号" data-index="order_no" key="order_no" width="150" />
              <a-table-column title="金额" key="amount" width="110" align="right">
                <template #default="{ record }">{{ money(record.amount) }}</template>
              </a-table-column>
              <a-table-column title="方式" data-index="method" key="method" width="90" />
              <a-table-column title="到账日期" key="paid_at" width="110">
                <template #default="{ record }">{{ record.paid_at ? new Date(record.paid_at * 1000).toLocaleDateString() : '-' }}</template>
              </a-table-column>
              <a-table-column title="登记人" key="actor" width="100">
                <template #default="{ record }">{{ record.real_name || record.user_name || userName(record.actor_user_id) }}</template>
              </a-table-column>
              <a-table-column title="备注" data-index="remark" key="remark" ellipsis />
              <a-table-column title="操作" key="action" width="80">
                <template #default="{ record }">
                  <a-popconfirm title="删除会冲正订单已收金额，确认？" @confirm="deletePaymentItem(record)">
                    <a style="color:#ff4d4f">冲正</a>
                  </a-popconfirm>
                </template>
              </a-table-column>
            </a-table>
          </a-tab-pane>
        </a-tabs>
      </a-tab-pane>

      <a-tab-pane key="deliveries">
        <template #tab><span class="tb-label">交付</span><span class="tb-count">{{ deliveries.length }}</span></template>
        <a-button type="primary" size="small" style="margin-bottom:12px" @click="openTicket()">
          <template #icon><plus-outlined /></template>新建交付
        </a-button>
        <a-table :data-source="deliveries" :pagination="false" row-key="id" size="small" bordered>
          <a-table-column title="工单号" data-index="ticket_no" key="ticket_no" width="160" />
          <a-table-column title="标题" data-index="title" key="title" ellipsis />
          <a-table-column title="类型" key="kind" width="70">
            <template #default="{ record }">{{ record.kind === 2 ? record.period_no + '期' : '一次性' }}</template>
          </a-table-column>
          <a-table-column title="处理人" key="assignee" width="90">
            <template #default="{ record }">{{ userName(record.assignee_user_id) }}</template>
          </a-table-column>
          <a-table-column title="状态" key="status" width="90">
            <template #default="{ record }"><a-tag>{{ ticketStatusText(record.status) }}</a-tag></template>
          </a-table-column>
          <a-table-column title="期望完成" key="expect" width="110">
            <template #default="{ record }">{{ record.expect_date ? new Date(record.expect_date * 1000).toLocaleDateString() : '-' }}</template>
          </a-table-column>
          <a-table-column title="操作" key="action" width="210">
            <template #default="{ record }">
              <template v-if="record.status === 1">
                <a @click="ticketAction(record, 2, '已开始处理')">开始</a><a-divider type="vertical" />
              </template>
              <template v-else-if="record.status === 2">
                <a @click="ticketAction(record, 4, '交付已完成')">完成</a><a-divider type="vertical" />
              </template>
              <template v-else-if="record.status === 3">
                <a @click="ticketAction(record, 2, '继续处理')">继续处理</a><a-divider type="vertical" />
              </template>
              <a v-if="[1, 2, 3].includes(record.status)" style="color:#ff4d4f" @click="ticketAction(record, 5, '交付已取消')">取消</a>
              <span v-else class="muted">—</span>
              <a-divider type="vertical" />
              <a @click="openMaterials(record)">材料</a>
            </template>
          </a-table-column>
        </a-table>
      </a-tab-pane>

      <a-tab-pane key="resources">
        <template #tab><span class="tb-label">资料</span><span class="tb-count">{{ resourcesTotal }}</span></template>
        <a-tabs v-model:activeKey="resTab" size="small">
          <a-tab-pane key="participants" :tab="`参与人 ${participants.length}`">
        <a-button type="primary" size="small" style="margin-bottom:12px" @click="openAddParticipant">
          <template #icon><plus-outlined /></template>添加参与人
        </a-button>
        <a-table :data-source="participants" :pagination="false" row-key="id" size="small" bordered>
          <a-table-column title="姓名" key="name" width="140">
            <template #default="{ record }">{{ record.real_name || record.user_name }}</template>
          </a-table-column>
          <a-table-column title="权限" key="permission" width="110">
            <template #default="{ record }">
              <a-select :value="record.permission" size="small" style="width:90px"
                :options="[{ value: 1, label: '只读' }, { value: 2, label: '可编辑' }]"
                @change="(v) => changeParticipantPermission(record, v)" />
            </template>
          </a-table-column>
          <a-table-column title="来源" key="source" width="100">
            <template #default="{ record }">{{ sourceText(record.source) }}</template>
          </a-table-column>
          <a-table-column title="备注" data-index="remark" key="remark" />
          <a-table-column title="操作" key="action" width="80">
            <template #default="{ record }">
              <a-popconfirm title="确认移出该参与人？" @confirm="removeParticipant(record)"><a style="color:#ff4d4f">移出</a></a-popconfirm>
            </template>
          </a-table-column>
        </a-table>
      </a-tab-pane>

      <a-tab-pane key="contacts" :tab="`联系人 ${contacts.length}`">
        <a-button type="primary" size="small" style="margin-bottom:12px" @click="openAddContact">
          <template #icon><plus-outlined /></template>新增联系人
        </a-button>
        <a-table :data-source="contacts" :pagination="false" row-key="id" size="small" bordered>
          <a-table-column title="姓名" data-index="contact_name" key="contact_name" width="120" />
          <a-table-column title="职位" data-index="position" key="position" width="120" />
          <a-table-column title="手机" data-index="mobile" key="mobile" width="130" />
          <a-table-column title="邮箱" data-index="email" key="email" ellipsis />
          <a-table-column title="首要" key="primary" width="70">
            <template #default="{ record }">{{ record.is_primary == 1 ? '是' : '' }}</template>
          </a-table-column>
          <a-table-column title="操作" key="action" width="80">
            <template #default="{ record }"><a @click="openEditContact(record)">编辑</a></template>
          </a-table-column>
        </a-table>
      </a-tab-pane>

      <a-tab-pane key="files" :tab="`文件 ${documents.length}`">
        <div class="tab-toolbar">
          <a-button type="primary" size="small" @click="openDocModal">
            <template #icon><plus-outlined /></template>上传文件
          </a-button>
          <a-select v-model:value="docCategory" size="small" style="width:120px;margin-left:8px">
            <a-select-option value="">全部分类</a-select-option>
            <a-select-option value="合同">合同</a-select-option>
            <a-select-option value="发票">发票</a-select-option>
            <a-select-option value="方案">方案</a-select-option>
            <a-select-option value="资质">资质</a-select-option>
            <a-select-option value="沟通附件">沟通附件</a-select-option>
            <a-select-option value="其他">其他</a-select-option>
          </a-select>
        </div>
        <a-table :data-source="filteredDocuments" :pagination="false" row-key="id" size="small" bordered>
          <a-table-column title="标题" key="title" ellipsis>
            <template #default="{ record }">
              <a @click.prevent="previewDoc(record)">{{ record.title || record.file_name }}</a>
            </template>
          </a-table-column>
          <a-table-column title="分类" data-index="category" key="category" width="90" />
          <a-table-column title="文件名" data-index="file_name" key="file_name" ellipsis />
          <a-table-column title="上传人" data-index="owner_user_name" key="owner" width="100" />
          <a-table-column title="时间" key="time" width="110">
            <template #default="{ record }">{{ record.create_time ? new Date(record.create_time * 1000).toLocaleDateString() : '' }}</template>
          </a-table-column>
          <a-table-column title="操作" key="action" width="120">
            <template #default="{ record }">
              <a @click="previewDoc(record)">预览</a><a-divider type="vertical" />
              <a-popconfirm title="确认删除?" @confirm="deleteDoc(record)"><a style="color:#ff4d4f">删除</a></a-popconfirm>
            </template>
          </a-table-column>
        </a-table>
          </a-tab-pane>
        </a-tabs>
      </a-tab-pane>

      <a-tab-pane key="timeline">
        <template #tab><span class="tb-label">时间线</span></template>
        <a-timeline>
          <a-timeline-item v-for="item in timeline" :key="item.kind + item.data.id" :color="item.kind === 'followup' ? 'blue' : 'gray'">
            <div class="tl-head">
              <span class="tl-title">{{ timelineTitle(item) }}</span>
              <span class="tl-time">{{ formatTime(item.time) }}</span>
            </div>
            <div v-if="timelineDetail(item)" class="tl-content">{{ timelineDetail(item) }}</div>
            <div v-if="item.kind === 'followup' && item.data.next_follow_date" class="tl-next">
              下次跟进 {{ new Date(item.data.next_follow_date * 1000).toLocaleDateString() }}{{ item.data.next_follow_content ? ' · ' + item.data.next_follow_content : '' }}
            </div>
          </a-timeline-item>
          <a-timeline-item v-if="!timeline.length" color="gray">暂无记录</a-timeline-item>
        </a-timeline>
      </a-tab-pane>
    </a-tabs>

    <!-- 项目编辑 -->
    <a-modal v-model:open="editProjectVisible" title="编辑项目" :confirm-loading="saving" @ok="submitEditProject">
      <a-form :label-col="{ span: 5 }" :wrapper-col="{ span: 18 }">
        <a-form-item label="项目名称" required><a-input v-model:value="projectForm.title" /></a-form-item>
        <a-form-item label="项目负责人">
          <a-select v-model:value="projectForm.owner_user_id" allow-clear show-search :options="userOptions" />
        </a-form-item>
        <a-form-item label="服务期"><a-range-picker v-model:value="projectForm.period" style="width:100%" /></a-form-item>
        <a-form-item label="备注"><a-textarea v-model:value="projectForm.remark" :rows="3" /></a-form-item>
      </a-form>
    </a-modal>

    <!-- 项目取消 -->
    <a-modal v-model:open="cancelProjectVisible" title="取消项目" :confirm-loading="saving" @ok="submitCancelProject">
      <a-alert type="warning" show-icon message="未完成的需求与交付会一并关闭，已完成的历史保留。" style="margin-bottom:12px" />
      <a-form :label-col="{ span: 5 }" :wrapper-col="{ span: 18 }">
        <a-form-item label="取消原因" required><a-textarea v-model:value="cancelProjectReason" :rows="3" /></a-form-item>
      </a-form>
    </a-modal>

    <!-- 需求新增/编辑 -->
    <a-modal v-model:open="intentVisible" :title="editingIntentId ? '编辑需求' : '新增需求'" :confirm-loading="saving" width="680px" @ok="submitIntent">
      <div class="form-hint" style="margin-bottom:12px">客户：{{ project.customer_name }} · 项目：{{ project.title }}</div>
      <a-form :label-col="{ span: 5 }" :wrapper-col="{ span: 18 }">
        <a-form-item label="需求标题" required><a-input v-model:value="intentForm.title" placeholder="例如：注册公司 / 代账 12 个月" /></a-form-item>
        <a-form-item label="服务品类"><ServiceCatalogSelect v-model="intentForm.catalog_id" @change="onCatalogChange" /></a-form-item>
        <a-form-item label="数量">
          <a-input-number v-model:value="intentForm.quantity" :min="0.01" :step="1" style="width:140px" />
          <a-input v-model:value="intentForm.unit" placeholder="单位，如 单 / 月" style="width:120px;margin-left:8px" />
        </a-form-item>
        <a-form-item label="期望时间"><a-date-picker v-model:value="intentForm.expected_date" style="width:180px" /></a-form-item>
        <a-form-item label="承接人">
          <a-select v-model:value="intentForm.assignee_user_id" allow-clear show-search :options="userOptions" />
        </a-form-item>
        <a-form-item label="客户要求"><a-textarea v-model:value="intentForm.description" :rows="3" /></a-form-item>
      </a-form>
    </a-modal>

    <!-- 需求指派 -->
    <a-modal v-model:open="assignVisible" title="指派承接人" :confirm-loading="saving" @ok="submitAssignIntent">
      <a-form :label-col="{ span: 5 }" :wrapper-col="{ span: 18 }">
        <a-form-item label="需求">{{ assignTarget?.intent_no }} · {{ assignTarget?.title }}</a-form-item>
        <a-form-item label="承接人" required>
          <a-select v-model:value="assignUserId" show-search :options="userOptions" placeholder="选择同事" />
        </a-form-item>
      </a-form>
    </a-modal>

    <!-- 需求搁置/关闭 -->
    <a-modal v-model:open="reasonVisible" :title="reasonMode === 'hold' ? '搁置需求' : '关闭需求'" :confirm-loading="saving" @ok="submitReason">
      <a-form :label-col="{ span: 5 }" :wrapper-col="{ span: 18 }">
        <a-form-item :label="reasonMode === 'hold' ? '搁置原因' : '关闭原因'" required>
          <a-textarea v-model:value="reason" :rows="3" />
        </a-form-item>
        <a-form-item v-if="reasonMode === 'hold'" label="唤醒时间">
          <a-date-picker v-model:value="heldUntil" style="width:180px" />
        </a-form-item>
      </a-form>
    </a-modal>

    <!-- 参与人 -->
    <a-modal v-model:open="participantVisible" title="添加参与人" :confirm-loading="saving" @ok="submitParticipant">
      <a-form :label-col="{ span: 5 }" :wrapper-col="{ span: 18 }">
        <a-form-item label="选择人员" required>
          <a-select v-model:value="participantForm.user_ids" mode="multiple" show-search :options="userOptions" placeholder="可多选" />
        </a-form-item>
        <a-form-item label="权限">
          <a-radio-group v-model:value="participantForm.permission">
            <a-radio :value="1">只读</a-radio>
            <a-radio :value="2">可编辑</a-radio>
          </a-radio-group>
        </a-form-item>
        <a-form-item label="备注"><a-input v-model:value="participantForm.remark" placeholder="选填" /></a-form-item>
      </a-form>
    </a-modal>

    <a-modal v-model:open="docModalVisible" title="上传文件" :confirm-loading="docUploading" @ok="submitDoc">
      <a-form :label-col="{ span: 5 }" :wrapper-col="{ span: 18 }">
        <a-form-item label="选择文件" required>
          <a-upload :show-upload-list="false" :before-upload="handleDocFile" accept="*">
            <a-button><paper-clip-outlined /> {{ docForm.fileName || '选择文件' }}</a-button>
          </a-upload>
        </a-form-item>
        <a-form-item label="标题">
          <a-input v-model:value="docForm.title" placeholder="默认使用文件名" />
        </a-form-item>
        <a-form-item label="分类">
          <a-select v-model:value="docForm.category">
            <a-select-option value="合同">合同</a-select-option>
            <a-select-option value="发票">发票</a-select-option>
            <a-select-option value="方案">方案</a-select-option>
            <a-select-option value="资质">资质</a-select-option>
            <a-select-option value="其他">其他</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="备注"><a-textarea v-model:value="docForm.remark" :rows="2" /></a-form-item>
      </a-form>
    </a-modal>

    <ContactForm v-model:open="contactFormVisible" :mdl="contactSelected" :customer-id="project.customer_id" @ok="loadContacts" />

    <a-drawer v-model:open="followDrawerOpen" title="记一笔" width="520" :body-style="{ paddingBottom: '80px' }">
      <a-form :label-col="{ span: 5 }" :wrapper-col="{ span: 18 }">
        <a-form-item label="关联需求">
          <a-select v-model:value="followForm.intent_id" allow-clear show-search
            placeholder="可不选" :options="intentOptions" />
        </a-form-item>
        <a-form-item label="方式 / 结果">
          <a-select v-model:value="followForm.follow_type" style="width:110px;margin-right:8px">
            <a-select-option value="拜访">拜访</a-select-option>
            <a-select-option value="电话">电话</a-select-option>
            <a-select-option value="微信">微信</a-select-option>
            <a-select-option value="邮件">邮件</a-select-option>
            <a-select-option value="其他">其他</a-select-option>
          </a-select>
          <a-select v-model:value="followForm.result" style="width:100px">
            <a-select-option value="有效">有效</a-select-option>
            <a-select-option value="无效">无效</a-select-option>
            <a-select-option value="待定">待定</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="沟通内容">
          <RichEditor v-model="followForm.follow_content" :height="160" />
        </a-form-item>
        <a-form-item label="下次跟进">
          <a-date-picker v-model:value="followForm.next_follow_date" style="width:160px" />
          <a-input v-model:value="followForm.next_follow_content" placeholder="下次跟进计划（可选）" style="margin-top:8px" />
        </a-form-item>
        <a-form-item label="附件">
          <a-upload :show-upload-list="false" :before-upload="handleFollowUpload" accept="*">
            <a-button size="small"><paper-clip-outlined /> 上传附件</a-button>
          </a-upload>
          <a-tag v-if="followForm.attachment" closable style="margin-left:8px" @close.prevent="clearFollowAttachment">
            <a style="cursor:pointer" @click="previewAttachment(followForm.attachment)">{{ followForm.fileName || '已上传附件' }}</a>
          </a-tag>
        </a-form-item>
      </a-form>
      <template #footer>
        <a-button @click="followDrawerOpen = false">取消</a-button>
        <a-button type="primary" :loading="followSaving" @click="submitFollow">保存记录</a-button>
      </template>
    </a-drawer>

    <a-drawer v-model:open="aiDrawerOpen" title="AI 项目摘要" width="480">
      <div v-if="aiSummaryText" class="ai-text">{{ aiSummaryText }}</div>
      <a-empty v-else description="根据沟通、需求、报价、交付生成项目现状与下一步建议" />
      <template #footer>
        <a-button type="primary" :loading="aiSummaryLoading" @click="generateAiSummary">
          {{ aiSummaryText ? '重新生成' : '生成摘要' }}
        </a-button>
      </template>
    </a-drawer>

    <a-drawer v-model:open="materialVisible" :title="`材料 · ${materialTicket.ticket_no || ''}`" width="640">
      <a-spin :spinning="materialLoading">
        <a-descriptions :column="2" size="small" bordered style="margin-bottom:16px">
          <a-descriptions-item label="工单">{{ materialTicket.ticket_no || '-' }}</a-descriptions-item>
          <a-descriptions-item label="状态">{{ materialTicket.status_text || ticketStatusText(materialTicket.status) }}</a-descriptions-item>
          <a-descriptions-item label="标题" :span="2">{{ materialTicket.title || '-' }}</a-descriptions-item>
          <a-descriptions-item label="处理人">{{ userName(materialTicket.assignee_user_id) || '未指派' }}</a-descriptions-item>
          <a-descriptions-item label="期望完成">{{ materialTicket.expect_date ? new Date(materialTicket.expect_date * 1000).toLocaleDateString() : '-' }}</a-descriptions-item>
        </a-descriptions>

        <div v-for="item in materialItems" :key="item.id" class="mat-item">
          <div class="mat-head">
            <strong>{{ item.name }}</strong>
            <a-tag :color="item.status === 2 ? 'green' : item.status === 3 ? 'red' : item.status === 1 ? 'blue' : 'default'">
              {{ item.status_text || itemStatusText(item.status) }}
            </a-tag>
            <a-upload :show-upload-list="false" :before-upload="(f) => handleMaterialUpload(f, item)" accept="*">
              <a-button size="small" :loading="uploadingItemId === item.id">上传</a-button>
            </a-upload>
            <template v-if="item.status === 1">
              <a-button size="small" type="primary" :loading="reviewing" @click="passMaterial(item)">通过</a-button>
              <a-button size="small" danger :loading="reviewing" @click="openRejectMaterial(item)">驳回</a-button>
            </template>
          </div>
          <div v-if="item.remark" class="mat-remark">审核意见：{{ item.remark }}</div>
          <div class="mat-files">
            <template v-if="item.files && item.files.length">
              <a v-for="f in item.files" :key="f.id" :href="f.file_url" target="_blank" class="mat-file">
                <img v-if="f.is_image" :src="f.file_url" class="mat-thumb" />
                <span v-else>{{ f.file_name || '附件' }}</span>
              </a>
            </template>
            <span v-else class="muted">未上传</span>
          </div>
        </div>
        <a-empty v-if="!materialItems.length" description="这张工单没有材料项" />
      </a-spin>
    </a-drawer>

    <a-modal v-model:open="rejectVisible" title="驳回材料" :confirm-loading="reviewing" @ok="submitRejectMaterial">
      <a-form :label-col="{ span: 5 }" :wrapper-col="{ span: 18 }">
        <a-form-item label="材料">{{ rejectItem?.name }}</a-form-item>
        <a-form-item label="驳回原因" required>
          <a-textarea v-model:value="rejectRemark" :rows="3" placeholder="写清缺什么/哪里不对，客户会看到" />
        </a-form-item>
      </a-form>
    </a-modal>

    <a-modal v-model:open="previewVisible" :title="previewTitle" :footer="null" width="80%">
      <img v-if="previewType === 'image'" :src="previewUrl" style="max-width:100%;max-height:70vh;display:block;margin:0 auto" />
      <iframe v-else-if="previewType === 'pdf'" :src="previewUrl" style="width:100%;height:70vh;border:none" />
      <div v-else style="text-align:center;padding:24px">
        <a :href="previewUrl" target="_blank">该文件不支持预览，点击下载</a>
      </div>
    </a-modal>

    <a-modal v-model:open="paymentVisible" title="登记收款" :confirm-loading="paymentSaving" width="520px" @ok="submitPayment">
      <a-form :label-col="{ span: 5 }" :wrapper-col="{ span: 18 }">
        <a-form-item label="订单" required>
          <a-select v-model:value="paymentForm.order_id" :options="unpaidOrders.map(o => ({ value: o.id, label: `${o.order_no} · 待收 ${money(o.unpaid_amount)}` }))"
            placeholder="选择待收款订单" @change="onPaymentOrderChange" />
        </a-form-item>
        <a-form-item label="收款金额" required>
          <a-input-number v-model:value="paymentForm.amount" :min="0.01" :precision="2" style="width:180px" />
        </a-form-item>
        <a-form-item label="收款方式">
          <a-select v-model:value="paymentForm.method" style="width:180px">
            <a-select-option value="银行转账">银行转账</a-select-option>
            <a-select-option value="微信">微信</a-select-option>
            <a-select-option value="支付宝">支付宝</a-select-option>
            <a-select-option value="现金">现金</a-select-option>
            <a-select-option value="其他">其他</a-select-option>
          </a-select>
        </a-form-item>
        <a-form-item label="到账日期">
          <a-date-picker v-model:value="paymentForm.paid_at" style="width:180px" />
        </a-form-item>
        <a-form-item label="备注">
          <a-textarea v-model:value="paymentForm.remark" :rows="2" placeholder="流水号、备注等" />
        </a-form-item>
      </a-form>
    </a-modal>

    <QuotationForm v-model:open="quoteVisible" :mdl="quoteDetail" :preset="quotePreset" @ok="onQuoteSaved" />
    <OrderForm v-model:open="orderVisible" :quotation="orderQuotation" @ok="onOrderCreated" />
    <TicketForm v-model:open="ticketVisible" :preset="ticketPreset" :typeOptions="ticketTypeOptions" @ok="onTicketSaved" />
    <ContractForm v-model:open="contractVisible" :mdl="contractDetail" :preset="contractPreset" :order="contractOrder" @ok="onContractSaved" />
  </a-card>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onActivated } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { message, Modal } from 'ant-design-vue'
import { PlusOutlined, PaperClipOutlined, DownOutlined } from '@ant-design/icons-vue'
import dayjs from 'dayjs'
import {
  getDetail, edit as editProject, cancel as cancelProject, archive as archiveProject,
  startProject, completeProject, reopenProject, aiSummary as getAiSummary,
  addParticipants, removeParticipant as removeParticipantApi, updateParticipant as updateParticipantApi,
} from '@/api/crm/project'
import {
  add as addIntent, edit as editIntent, assign as assignIntent,
  hold as holdIntent, resume as resumeIntent, close as closeIntent, confirm as confirmIntent,
} from '@/api/crm/intent'
import { getDetail as getQuotationDetail, changeStatus as changeQuotationStatus, deleteById as deleteQuotation } from '@/api/crm/quotation'
import { changeStatus as changeOrderStatus, getDetail as getOrderDetail, generatePeriods as generateOrderPeriods } from '@/api/crm/order'
import { getDetail as getContractDetail, changeStatus as changeContractStatus, deleteById as deleteContract } from '@/api/crm/contract'
import { getList as getCatalog } from '@/api/crm/catalog'
import { changeStatus as changeTicketStatus, getDetail as getTicketDetail, reviewItem as reviewTicketItem, uploadFile as uploadTicketFile } from '@skills/workorder/views-v2/api'
import { list as listUsers } from '@/api/user'
import { add as addFollowUp, deleteById as deleteFollowUp } from '@/api/crm/followUp'
import { getByCustomer as getContacts } from '@/api/crm/contact'
import { list as getDocuments, upload as uploadDocument, deleteById as deleteDocument } from '@/api/crm/document'
import request from '@/utils/request'
import RichEditor from '@/components/RichEditor/index.vue'
import ContactForm from '@/views/crm/customer/components/ContactForm.vue'
import { useTabsStore, tabKeyOf } from '@/stores/tabs'
import ServiceCatalogSelect from '@/components/app/ServiceCatalogSelect.vue'
import QuotationForm from '@/views/crm/quotation/components/QuotationForm.vue'
import OrderForm from '@/views/crm/order/components/OrderForm.vue'
import ContractForm from '@/views/crm/contract/components/ContractForm.vue'
import { add as addPayment, deleteById as deletePayment } from '@/api/crm/payment'
import TicketForm from '@skills/workorder/views-v2/components/TicketForm.vue'

const route = useRoute()
const router = useRouter()
const tabsStore = useTabsStore()

const loading = ref(false)
const saving = ref(false)
const project = reactive({})
const intents = ref([])
const deliveries = ref([])
const participants = ref([])
const timeline = ref([])
const commitments = reactive({ quotations: [], orders: [], contracts: [] })
const stats = reactive({})
const unlinked = reactive({ quotations: 0, orders: 0, contracts: 0, order_amount: 0 })
const aiSummaryText = ref('')
const aiSummaryLoading = ref(false)
const aiDrawerOpen = ref(false)
const primaryContact = computed(() => contacts.value.find(c => c.is_primary == 1) || contacts.value[0] || null)
const unlinkedTotal = computed(() => unlinked.quotations + unlinked.orders + unlinked.contracts)
const salesTotal = computed(() =>
  commitments.quotations.length + commitments.orders.length + commitments.contracts.length)
const unpaidOrders = computed(() => commitments.orders.filter(o => o.status !== 6 && Number(o.unpaid_amount) > 0))
const filteredDocuments = computed(() =>
  docCategory.value ? documents.value.filter(d => (d.category || '') === docCategory.value) : documents.value)
const resourcesTotal = computed(() =>
  participants.value.length + contacts.value.length + documents.value.length)
const tab = ref('overview')
const salesTab = ref('quotes')
const resTab = ref('participants')
const followDrawerOpen = ref(false)
const projectId = ref(0)

const storeUsers = ref([])
const userOptions = computed(() => storeUsers.value.map(u => ({
  value: u.store_user_id,
  label: u.real_name || u.user_name || ('#' + u.store_user_id),
})))

const catalogRows = ref([])
const ticketTypeOptions = computed(() =>
  catalogRows.value.filter(t => Number(t.status) === 1).map(t => ({ value: t.id, name: t.name })))
const intentOptions = computed(() => intents.value.map(i => ({
  value: i.id, label: `${i.title}${i.status_text ? ' · ' + i.status_text : ''}`,
})))
const uploadBaseUrl = computed(() => (window.publicConfig?.BASE_API || '').replace(/\/index\.php.*$/, '') + '/uploads/')

const servicePeriod = computed(() => {
  if (!project.service_start_time || !project.service_end_time) return '-'
  return dayjs(project.service_start_time * 1000).format('YYYY-MM-DD') + ' ~ ' + dayjs(project.service_end_time * 1000).format('YYYY-MM-DD')
})

/** 负责团队（负责人 + 销售），空的不占位 */
const ownerList = computed(() => [
  { role: '项目', name: project.owner_user_name, color: '#d6893b' },
  { role: '销售', name: project.sales_user_name, color: '#5b8ff9' },
].filter(p => !!p.name))

/** 服务期进度：已服务天数 / 总天数 */
const serviceProgress = computed(() => {
  const start = Number(project.service_start_time || 0)
  const end = Number(project.service_end_time || 0)
  if (!start || !end || end <= start) return { percent: 0, text: '' }
  const now = Date.now()
  const totalDays = Math.max(1, Math.round((end - start) / 86400))
  const passed = Math.min(totalDays, Math.max(0, Math.round((now / 1000 - start) / 86400)))
  const percent = Math.min(100, Math.round(passed / totalDays * 100))
  let text = '已服务 ' + passed + '/' + totalDays + ' 天'
  if (now > end * 1000) text = '已到期 · ' + dayjs(end * 1000).format('YYYY-MM-DD')
  else if (now < start * 1000) text = dayjs(start * 1000).format('YYYY-MM-DD') + ' 开始'
  return { percent, text }
})

/** 交付完成率 */
const deliveryPercent = computed(() => {
  const total = Number(stats.delivery_total || 0)
  if (!total) return 0
  return Math.round(Number(stats.delivery_done || 0) / total * 100)
})


/**
 * 待处理事项：只列"现在需要人动手"的，按紧急度排序。
 * 没有就整块不显示 —— 不占地方说"暂无"。
 */
const quotesByIntent = computed(() => {
  const map = {}
  commitments.quotations.forEach(q => {
    (q.intents || []).forEach(it => {
      if (!map[it.intent_id]) map[it.intent_id] = []
      map[it.intent_id].push(q)
    })
  })
  return map
})
function hasDraftQuote(intentId) {
  return (quotesByIntent.value[intentId] || []).some(q => q.status === 1)
}

const todoItems = computed(() => {
  const items = []
  const startOfToday = new Date().setHours(0, 0, 0, 0)
  const endOfToday = new Date().setHours(23, 59, 59, 999)

  followups.value.forEach(f => {
    if (!f.next_follow_date) return
    const ts = f.next_follow_date * 1000
    if (ts > endOfToday) return
    const days = Math.ceil((ts - Date.now()) / 86400000)
    items.push({
      key: 'f' + f.id,
      priority: ts < startOfToday ? 0 : 1,
      text: `${f.intent_title || '客户'}：${ts < startOfToday ? '跟进已逾期 ' + (-days) + ' 天' : '今天该跟进'}`,
      action: '记跟进',
      type: 'follow',
      record: f,
    })
  })

  commitments.quotations.forEach(q => {
    if (q.status === 2) items.push({ key: 'q' + q.id, priority: 2, text: `报价 ${q.quotation_no} 已发送，等客户确认`, action: '去处理', type: 'quote', record: q })
  })
  commitments.orders.forEach(o => {
    if (o.status === 1) items.push({ key: 'o' + o.id, priority: 3, text: `订单 ${o.order_no} 待确认`, action: '确认', type: 'order', record: o })
  })
  intents.value.forEach(i => {
    if (i.status === 2) {
      const quotes = quotesByIntent.value[i.id] || []
      if (quotes.some(q => [3, 5].includes(q.status))) {
        items.push({ key: 'i' + i.id, priority: 4, text: `${i.title}：报价已确认，待成交`, action: '去跟进', type: 'intent_follow', record: i })
      } else if (quotes.some(q => q.status === 2)) {
        items.push({ key: 'i' + i.id, priority: 3, text: `${i.title}：报价已发送，等客户确认`, action: '去处理', type: 'quote', record: quotes.find(q => q.status === 2) })
      } else if (quotes.some(q => q.status === 1)) {
        items.push({ key: 'i' + i.id, priority: 4, text: `${i.title}：报价草稿待发送`, action: '去发送', type: 'quote', record: quotes.find(q => q.status === 1) })
      } else {
        items.push({ key: 'i' + i.id, priority: 4, text: `${i.title}：待报价`, action: '去报价', type: 'intent_quote', record: i })
      }
    } else if (i.status === 3) {
      items.push({ key: 'i' + i.id, priority: 4, text: `${i.title}：已报价，待成交`, action: '去跟进', type: 'intent_follow', record: i })
    } else if (i.status === 5) {
      items.push({ key: 'i' + i.id, priority: 5, text: `${i.title}：已搁置`, action: '重启', type: 'intent_resume', record: i })
    }
  })
  deliveries.value.forEach(t => {
    if ([1, 2, 3].includes(t.status)) items.push({ key: 't' + t.id, priority: 5, text: `交付 ${t.ticket_no}：${ticketStatusText(t.status)}`, action: '去处理', type: 'delivery', record: t })
  })
  commitments.contracts.forEach(c => {
    if ([1, 2, 3].includes(c.status)) items.push({ key: 'c' + c.id, priority: 6, text: `合同 ${c.contract_no}：${contractStatusText(c.status)}`, action: '去处理', type: 'contract', record: c })
  })

  return items.sort((a, b) => a.priority - b.priority).slice(0, 5)
})

function handleTodo(item) {
  switch (item.type) {
    case 'follow': openFollowDrawer(item.record.intent_id || null); break
    case 'intent_quote': openQuote(item.record); break
    case 'intent_follow': openFollowDrawer(item.record.id); break
    case 'intent_resume': handleResumeIntent(item.record); break
    case 'quote': tab.value = 'sales'; salesTab.value = 'quotes'; break
    case 'order': orderAction(item.record, 2, '订单已开始'); break
    case 'delivery': tab.value = 'deliveries'; break
    case 'contract': tab.value = 'sales'; salesTab.value = 'contracts'; break
  }
}

// 项目
const editProjectVisible = ref(false)
const projectForm = reactive({ title: '', owner_user_id: undefined, period: null, remark: '' })
const cancelProjectVisible = ref(false)
const cancelProjectReason = ref('')

// 需求
const intentVisible = ref(false)
const editingIntentId = ref(0)
const intentForm = reactive({ title: '', catalog_id: undefined, quantity: 1, unit: '', expected_date: null, assignee_user_id: undefined, description: '' })
const assignVisible = ref(false)
const assignTarget = ref(null)
const assignUserId = ref(undefined)
const reasonVisible = ref(false)
const reasonMode = ref('close')
const reason = ref('')
const heldUntil = ref(null)
const reasonTarget = ref(null)

// 报价 / 订单 / 交付
const quoteVisible = ref(false)
const quoteDetail = ref(null)
const quotePreset = ref(null)
const orderVisible = ref(false)
const orderQuotation = ref(null)
const ticketVisible = ref(false)
const ticketPreset = ref(null)
const contractVisible = ref(false)
const contractDetail = ref(null)
const contractPreset = ref(null)
const contractOrder = ref(null)

const payments = ref([])
const paymentVisible = ref(false)
const paymentSaving = ref(false)
const paymentForm = reactive({ order_id: undefined, amount: 0, method: '银行转账', paid_at: null, remark: '' })

const materialVisible = ref(false)
const materialLoading = ref(false)
const materialTicket = ref({})
const materialItems = ref([])
const rejectVisible = ref(false)
const rejectItem = ref(null)
const rejectRemark = ref('')
const reviewing = ref(false)
const uploadingItemId = ref(0)

// 沟通 / 联系人 / 文件
const followups = ref([])
const contacts = ref([])
const documents = ref([])
const followSaving = ref(false)
const followForm = reactive({
  follow_type: '拜访', result: '有效', intent_id: undefined,
  follow_content: '', next_follow_date: null, next_follow_content: '',
  attachment: '', fileName: '',
})
const contactFormVisible = ref(false)
const contactSelected = ref(null)
const docModalVisible = ref(false)
const docUploading = ref(false)
const docForm = reactive({ file: null, fileName: '', title: '', category: '其他', remark: '' })
const docCategory = ref('')
const previewVisible = ref(false)
const previewUrl = ref('')
const previewType = ref('')
const previewTitle = ref('')

// 参与人
const participantVisible = ref(false)
const participantForm = reactive({ user_ids: [], permission: 1, remark: '' })

function statusColor(s) { return s === 1 ? 'default' : s === 2 ? 'blue' : s === 3 ? 'green' : 'red' }
function money(v) { return Number(v || 0).toLocaleString('zh-CN', { minimumFractionDigits: 2 }) }
function userName(id) { return userOptions.value.find(u => u.value === id)?.label || '' }
function ticketStatusText(s) { return { 1: '待处理', 2: '处理中', 3: '等待对方', 4: '已完成', 5: '已取消' }[s] || s }
function quoteStatusText(s) { return { 1: '草稿', 2: '已发送', 3: '已确认', 4: '已拒绝', 5: '已转订单', 6: '已过期', 7: '已取消' }[s] || s }
function orderStatusText(s) { return { 1: '待确认', 2: '进行中', 3: '进行中', 4: '进行中', 5: '已完成', 6: '已取消' }[s] || s }
function contractStatusText(s) { return { 1: '草稿', 2: '已签订', 3: '履行中', 4: '已完成', 5: '已终止' }[s] || s }
function paymentText(s) { return { 1: '未付款', 2: '部分付款', 3: '已付清' }[s] || '-' }
function sourceText(s) { return { 1: '手动', 2: '品类默认', 3: '@提及', 4: '导入' }[s] || '手动' }
function formatTime(t) { return t ? new Date(t * 1000).toLocaleString() : '' }
function decodeHtml(html) { if (!html) return ''; return String(html).replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&amp;/g, '&').replace(/&quot;/g, '"') }
const EVENT_ACTIONS = {
  project_created: '创建了项目',
  project_updated: '修改了项目信息',
  project_status_changed: '调整了项目状态',
  project_participant_added: '添加了参与人',
  project_participant_removed: '移出了参与人',
  project_cancelled: '取消了项目',
  project_archived: '归档了项目',
  intent_created: '创建了需求',
  intent_updated: '修改了需求',
  intent_assigned: '指派了需求',
  intent_quoted: '将需求推进到已报价',
  intent_won: '将需求标记为已成交',
  intent_closed: '关闭了需求',
  intent_held: '搁置了需求',
  intent_resumed: '重新启动了需求',
  intent_requote: '将需求退回待报价',
  quotation_created: '创建了报价',
  quotation_sent: '发送了报价',
  quotation_confirmed: '客户确认了报价',
  quotation_rejected: '报价被拒绝',
  quotation_converted: '将报价转为订单',
  order_created: '创建了订单',
  order_status_changed: '更新了订单状态',
  contract_signed: '更新了合同',
  contract_status_changed: '更新了合同状态',
}

function parseEventData(item) {
  try {
    const raw = item.data?.data
    return typeof raw === 'string' ? JSON.parse(raw || '{}') : (raw || {})
  } catch { return {} }
}
/**
 * 富文本转纯文本：先解转义再删标签。
 * 顺序不能反 —— 存库的富文本是转义过的（&lt;p&gt;），先删标签会残留源码。
 */
function stripHtml(html) {
  let text = String(html || '')
  if (!text) return ''
  // 最多解两层转义，兼容历史双重转义数据
  for (let i = 0; i < 2 && /&(lt|gt|amp|quot|nbsp|#39);/.test(text); i++) {
    text = text
      .replace(/&nbsp;/g, ' ')
      .replace(/&lt;/g, '<').replace(/&gt;/g, '>')
      .replace(/&quot;/g, '"').replace(/&#39;/g, "'").replace(/&amp;/g, '&')
  }
  return text
    .replace(/<img[^>]*>/gi, '［图片］')
    .replace(/<br\s*\/?>/gi, ' ')
    .replace(/<\/(p|div|li|h[1-6])>/gi, ' ')
    .replace(/<[^>]*>/g, '')
    .replace(/\s+/g, ' ')
    .trim()
}
function projectStatusText(s) { return { 1: '潜在', 2: '进行中', 3: '已完成', 4: '已取消' }[s] || s }
function eventActor(item) {
  const d = item.data || {}
  if (item.kind === 'followup') return d.real_name || d.user_name || '同事'
  if (!d.actor_user_id) return '系统'
  return d.real_name || d.user_name || userName(d.actor_user_id) || '同事'
}
function targetName(item) {
  const id = parseInt(item.data?.target_id || item.data?.id || 0)
  const d = parseEventData(item)
  switch (item.data?.target_type) {
    case 'quotation': return commitments.quotations.find(q => Number(q.id) === id)?.quotation_no || d.quotation_no || ('报价 #' + id)
    case 'order': return commitments.orders.find(o => Number(o.id) === id)?.order_no || d.order_no || ('订单 #' + id)
    case 'contract': return commitments.contracts.find(c => Number(c.id) === id)?.contract_no || d.contract_no || ('合同 #' + id)
    case 'intent': {
      const it = intents.value.find(x => Number(x.id) === id)
      return it ? `${it.intent_no} ${it.title}` : (d.intent_no || ('需求 #' + id))
    }
    case 'project': return project.title || '项目'
    case 'customer': return project.customer_name || '客户'
    default: return ''
  }
}
function timelineTitle(item) {
  if (item.kind === 'followup') {
    const d = item.data || {}
    return `${eventActor(item)} 记录了${d.follow_type || '沟通'}（${d.result || '待定'}）${d.intent_title ? ' · ' + d.intent_title : ''}`
  }
  const e = item.data?.event_type || ''
  const d = parseEventData(item)
  let action = EVENT_ACTIONS[e] || '更新了记录'
  if (e === 'contract_signed' && d.to_status === undefined) action = '创建了合同'
  const name = targetName(item)
  return `${eventActor(item)} ${action}${name ? ' · ' + name : ''}`
}
function timelineDetail(item) {
  if (item.kind === 'followup') {
    const d = item.data || {}
    const content = stripHtml(d.follow_content).slice(0, 160)
    return content
  }
  const e = item.data?.event_type || ''
  const d = parseEventData(item)
  if (e === 'project_status_changed') return `${projectStatusText(d.from)} → ${projectStatusText(d.to)}`
  if (e === 'intent_assigned') {
    // 历史事件里字段叫 to，新事件叫 assignee_user_id，两个都兼容
    const uid = Number(d.assignee_user_id ?? d.to ?? 0)
    if (!uid) return '取消指派'
    return `指派给 ${userName(uid) || ('#' + uid)}`
  }
  if (e === 'project_participant_added') return '参与人：' + (d.user_ids || []).map(id => userName(id) || ('#' + id)).join('、')
  if (e === 'project_participant_removed') return `移出：${userName(d.user_id) || ('#' + d.user_id)}`
  if (e === 'order_status_changed') return `${orderStatusText(d.from_status)} → ${orderStatusText(d.to_status)}`
  if (e === 'quotation_sent' || e === 'quotation_confirmed' || e === 'quotation_rejected' || e === 'quotation_created') {
    return d.amount !== undefined && Number(d.amount) > 0 ? `金额 ${money(d.amount)}` : ''
  }
  if (e === 'order_created') return d.amount ? `金额 ${money(d.amount)}` : ''
  if (e === 'contract_signed') {
    if (d.to_status !== undefined) return `状态：${contractStatusText(d.to_status)}`
    return d.amount ? `金额 ${money(d.amount)}` : ''
  }
  if (e === 'intent_closed' || e === 'intent_held' || e === 'project_cancelled') return d.reason || ''
  if (e === 'project_updated') return (d.fields || []).length ? '修改了：' + d.fields.join('、') : ''
  return ''
}

async function loadDetail() {
  const id = parseInt(route.query.id)
  if (!id) return
  projectId.value = id
  loading.value = true
  try {
    const res = await getDetail(id)
    const d = res?.data || {}
    Object.keys(project).forEach(k => delete project[k])
    Object.assign(project, d.project || {})
    intents.value = d.intents || []
    deliveries.value = d.deliveries || []
    commitments.quotations = d.commitments?.quotations || []
    commitments.orders = d.commitments?.orders || []
    commitments.contracts = d.commitments?.contracts || []
    participants.value = d.participants || []
    followups.value = d.followups || []
    payments.value = d.payments || []
    timeline.value = d.timeline || []
    Object.assign(stats, d.stats || {})
    Object.assign(unlinked, d.unlinked || {})
    if (project.title) tabsStore.setTitle(tabKeyOf(route), project.title)
    loadContacts()
    loadDocuments()
  } catch (e) {
    console.error('[项目详情] 加载失败：', e)
  } finally { loading.value = false }
}

// ---- 项目操作 ----
function openEditProject() {
  projectForm.title = project.title || ''
  projectForm.owner_user_id = project.owner_user_id || undefined
  projectForm.remark = project.remark || ''
  projectForm.period = project.service_start_time && project.service_end_time
    ? [dayjs(project.service_start_time * 1000), dayjs(project.service_end_time * 1000)]
    : null
  editProjectVisible.value = true
}

async function submitEditProject() {
  if (!projectForm.title.trim()) { message.warning('请输入项目名称'); return }
  saving.value = true
  try {
    await editProject({
      id: projectId.value,
      title: projectForm.title.trim(),
      owner_user_id: projectForm.owner_user_id || 0,
      remark: projectForm.remark || '',
      service_start_time: projectForm.period?.[0] ? dayjs(projectForm.period[0]).format('YYYY-MM-DD') : '',
      service_end_time: projectForm.period?.[1] ? dayjs(projectForm.period[1]).format('YYYY-MM-DD') : '',
    })
    message.success('保存成功')
    editProjectVisible.value = false
    loadDetail()
  } catch (e) { console.error('[项目] 保存失败：', e) } finally { saving.value = false }
}

function openCancelProject() { cancelProjectReason.value = ''; cancelProjectVisible.value = true }
async function submitCancelProject() {
  if (!cancelProjectReason.value.trim()) { message.warning('请填写取消原因'); return }
  saving.value = true
  try {
    await cancelProject(projectId.value, cancelProjectReason.value.trim())
    message.success('已取消')
    cancelProjectVisible.value = false
    loadDetail()
  } catch (e) { console.error('[项目] 取消失败：', e) } finally { saving.value = false }
}
const openIntentCount = computed(() => intents.value.filter(i => [1, 2, 3, 5].includes(i.status)).length)
const openDeliveryCount = computed(() => deliveries.value.filter(t => [1, 2, 3].includes(t.status)).length)
const completeConfirmText = computed(() => {
  const parts = []
  if (openIntentCount.value) parts.push(openIntentCount.value + ' 条需求未结束')
  if (openDeliveryCount.value) parts.push(openDeliveryCount.value + ' 个交付未完成')
  return parts.length ? `还有 ${parts.join('、')}，确认完成项目？` : '确认完成该项目？'
})

async function handleStartProject() {
  try { await startProject(projectId.value); message.success('项目已开始'); loadDetail() }
  catch (e) { console.error('[项目] 开始失败：', e) }
}
async function handleCompleteProject() {
  try { await completeProject(projectId.value); message.success('项目已完成'); loadDetail() }
  catch (e) { console.error('[项目] 完成失败：', e) }
}
async function handleReopenProject() {
  try { await reopenProject(projectId.value); message.success('项目已重新打开'); loadDetail() }
  catch (e) { console.error('[项目] 重新打开失败：', e) }
}

async function handleArchiveProject() {
  try { await archiveProject(projectId.value); message.success('已归档'); loadDetail() }
  catch (e) { console.error('[项目] 归档失败：', e) }
}

// ---- 需求 ----
function resetIntentForm() {
  intentForm.title = ''
  intentForm.catalog_id = undefined
  intentForm.quantity = 1
  intentForm.unit = ''
  intentForm.expected_date = null
  intentForm.assignee_user_id = project.owner_user_id || undefined
  intentForm.description = ''
}
function openCreateIntent() {
  editingIntentId.value = 0
  resetIntentForm()
  intentVisible.value = true
}
function openEditIntent(record) {
  editingIntentId.value = record.id
  intentForm.title = record.title || ''
  intentForm.catalog_id = record.catalog_id || undefined
  intentForm.quantity = Number(record.quantity) || 1
  intentForm.unit = record.unit || ''
  intentForm.expected_date = record.expected_date ? dayjs(record.expected_date * 1000) : null
  intentForm.assignee_user_id = record.assignee_user_id || undefined
  intentForm.description = record.description || ''
  intentVisible.value = true
}
function onCatalogChange(row) {
  if (row && row.default_assignee_user_id && !intentForm.assignee_user_id) {
    intentForm.assignee_user_id = row.default_assignee_user_id
  }
}
async function submitIntent() {
  if (!intentForm.title.trim()) { message.warning('请输入需求标题'); return }
  saving.value = true
  try {
    const payload = {
      customer_id: project.customer_id,
      project_id: projectId.value,
      title: intentForm.title.trim(),
      catalog_id: intentForm.catalog_id || 0,
      quantity: intentForm.quantity,
      unit: intentForm.unit || '',
      expected_date: intentForm.expected_date ? dayjs(intentForm.expected_date).format('YYYY-MM-DD') : '',
      assignee_user_id: intentForm.assignee_user_id || 0,
      description: intentForm.description || '',
      source: 2,
    }
    if (editingIntentId.value) {
      await editIntent({ id: editingIntentId.value, ...payload })
      message.success('保存成功')
    } else {
      await addIntent(payload)
      message.success('需求已创建')
    }
    intentVisible.value = false
    tab.value = 'intents'
    loadDetail()
  } catch (e) { console.error('[项目] 需求保存失败：', e) } finally { saving.value = false }
}
function openAssignIntent(record) { assignTarget.value = record; assignUserId.value = record.assignee_user_id || undefined; assignVisible.value = true }
async function submitAssignIntent() {
  if (!assignUserId.value) { message.warning('请选择承接人'); return }
  saving.value = true
  try {
    await assignIntent(assignTarget.value.id, assignUserId.value)
    message.success('已指派')
    assignVisible.value = false
    loadDetail()
  } catch (e) { console.error('[项目] 指派失败：', e) } finally { saving.value = false }
}
function openReason(record, mode) { reasonTarget.value = record; reasonMode.value = mode; reason.value = ''; heldUntil.value = null; reasonVisible.value = true }
async function submitReason() {
  if (!reason.value.trim()) { message.warning('请填写原因'); return }
  saving.value = true
  try {
    if (reasonMode.value === 'hold') {
      await holdIntent(reasonTarget.value.id, reason.value.trim(), heldUntil.value ? dayjs(heldUntil.value).format('YYYY-MM-DD') : null)
      message.success('已搁置')
    } else {
      await closeIntent(reasonTarget.value.id, reason.value.trim())
      message.success('已关闭')
    }
    reasonVisible.value = false
    loadDetail()
  } catch (e) { console.error('[项目] 需求操作失败：', e) } finally { saving.value = false }
}
async function handleResumeIntent(record) {
  try { await resumeIntent(record.id); message.success('已重新启动'); loadDetail() }
  catch (e) { console.error('[项目] 重启失败：', e) }
}
async function handleConfirmIntent(record) {
  try { await confirmIntent(record.id); message.success('已成交'); loadDetail() }
  catch (e) { console.error('[项目] 成交失败：', e) }
}

// ---- 报价 ----
function catalogPrice(catalogId) {
  const row = catalogRows.value.find(c => Number(c.id) === Number(catalogId))
  return Number(row?.price) || 0
}
function openQuote(intent) {
  quoteDetail.value = null
  quotePreset.value = {
    customer_id: project.customer_id,
    project_id: projectId.value,
    items: [{
      intent_id: intent.id,
      catalog_id: intent.catalog_id || 0,
      product_name: intent.service_name || intent.title,
      unit: intent.unit || '',
      quantity: Number(intent.quantity) || 1,
      unit_price: catalogPrice(intent.catalog_id),
    }],
  }
  quoteVisible.value = true
}
function openQuoteBlank() {
  quoteDetail.value = null
  quotePreset.value = { customer_id: project.customer_id, project_id: projectId.value, items: [] }
  quoteVisible.value = true
}
async function openEditQuote(record) {
  try {
    const res = await getQuotationDetail(record.id)
    quotePreset.value = null
    quoteDetail.value = res?.data?.detail || null
    quoteVisible.value = true
  } catch (e) { console.error('[项目] 读取报价失败：', e) }
}
function onQuoteSaved() { quoteVisible.value = false; tab.value = 'sales'; salesTab.value = 'quotes'; loadDetail() }
async function changeQuoteStatus(record, status) {
  try {
    await changeQuotationStatus(record.id, status)
    message.success('已更新')
    loadDetail()
  } catch (e) { console.error('[项目] 报价状态更新失败：', e) }
}
async function deleteQuote(record) {
  try { await deleteQuotation(record.id); message.success('已删除'); loadDetail() }
  catch (e) { console.error('[项目] 删除报价失败：', e) }
}
async function convertQuote(record) {
  try {
    const res = await getQuotationDetail(record.id)
    orderQuotation.value = res?.data?.detail || null
    orderVisible.value = true
  } catch (e) { console.error('[项目] 读取报价失败：', e) }
}
function onOrderCreated() { orderVisible.value = false; tab.value = 'sales'; salesTab.value = 'orders'; message.success('已转为订单'); loadDetail() }

// ---- 订单 ----
async function orderAction(record, status, label) {
  try {
    await changeOrderStatus(record.id, status)
    message.success(label)
    loadDetail()
  } catch (e) { console.error('[项目] 订单操作失败：', e) }
}

// ---- 交付 ----
function openTicket(intent = null) {
  ticketPreset.value = {
    customer_id: project.customer_id,
    project_id: projectId.value,
    intent_id: intent?.id || 0,
    title: intent ? intent.title : (project.title || '交付'),
  }
  ticketVisible.value = true
}
function onTicketSaved() { ticketVisible.value = false; tab.value = 'deliveries'; loadDetail() }
async function ticketAction(record, status, label) {
  try {
    await changeTicketStatus({ id: record.id, status, remark: '' })
    message.success(label)
    loadDetail()
  } catch (e) { console.error('[项目] 交付操作失败：', e) }
}

// ---- 参与人 ----
function openAddParticipant() {
  tab.value = 'resources'
  resTab.value = 'participants'
  participantForm.user_ids = []
  participantForm.permission = 1
  participantForm.remark = ''
  participantVisible.value = true
}
async function submitParticipant() {
  if (!participantForm.user_ids.length) { message.warning('请选择人员'); return }
  saving.value = true
  try {
    await addParticipants({
      project_id: projectId.value,
      user_ids: participantForm.user_ids,
      permission: participantForm.permission,
      remark: participantForm.remark,
    })
    message.success('已添加')
    participantVisible.value = false
    loadDetail()
  } catch (e) { console.error('[项目] 添加参与人失败：', e) } finally { saving.value = false }
}
async function changeParticipantPermission(record, permission) {
  try {
    await updateParticipantApi({ project_id: projectId.value, user_id: record.user_id, permission })
    record.permission = permission
    message.success('权限已更新')
  } catch (e) { console.error('[项目] 修改参与人权限失败：', e) }
}

async function removeParticipant(record) {
  try {
    await removeParticipantApi({ project_id: projectId.value, user_id: record.user_id, reason: '' })
    message.success('已移出')
    loadDetail()
  } catch (e) { console.error('[项目] 移出失败：', e) }
}

// ---- 沟通 ----
function focusComposer() { openFollowDrawer() }
function goSales(sub) { tab.value = 'sales'; salesTab.value = sub }
function goFiles() { tab.value = 'resources'; resTab.value = 'files' }
function goCustomer() { if (project.customer_id) router.push({ path: '/crm/customer/detail', query: { id: project.customer_id } }) }
function onCreateMenu({ key }) {
  if (key === 'intent') openCreateIntent()
  else if (key === 'quote') openQuoteBlank()
  else if (key === 'contract') openContractBlank()
  else if (key === 'ticket') openTicket()
  else if (key === 'participant') openAddParticipant()
}
function onMoreMenu({ key }) {
  if (key === 'ai') {
    aiDrawerOpen.value = true
    if (!aiSummaryText.value) generateAiSummary()
    return
  }
  if (key === 'start') return handleStartProject()
  if (key === 'reopen') return handleReopenProject()
  if (key === 'edit') return openEditProject()
  if (key === 'cancel') return openCancelProject()
  if (key === 'archive') return handleArchiveProject()
  if (key === 'complete') {
    Modal.confirm({
      title: '完成项目',
      content: completeConfirmText.value,
      okText: '确认完成',
      onOk: handleCompleteProject,
    })
  }
}
function openFollowDrawer(intentId = null) {
  if (intentId) followForm.intent_id = intentId
  followDrawerOpen.value = true
}
function isOverdue(ts) {
  if (!ts) return false
  return ts * 1000 < new Date().setHours(0, 0, 0, 0)
}
function goIntentById(id) { router.push({ path: '/crm/intent/detail', query: { id } }) }
async function deleteFollow(record) {
  try { await deleteFollowUp(record.id); message.success('已删除'); loadDetail() }
  catch (e) { console.error('[项目] 删除沟通记录失败：', e) }
}

async function submitFollow() {
  if (!followForm.follow_content) { message.warning('请输入沟通内容'); return }
  followSaving.value = true
  try {
    await addFollowUp({
      customer_id: project.customer_id,
      project_id: projectId.value,
      intent_id: followForm.intent_id || 0,
      follow_type: followForm.follow_type,
      result: followForm.result,
      follow_content: followForm.follow_content,
      next_follow_date: followForm.next_follow_date ? dayjs(followForm.next_follow_date).format('YYYY-MM-DD') : '',
      next_follow_content: followForm.next_follow_content || '',
      attachment: followForm.attachment || '',
    })
    message.success('已记录')
    followDrawerOpen.value = false
    followForm.follow_content = ''
    followForm.next_follow_date = null
    followForm.next_follow_content = ''
    followForm.intent_id = undefined
    clearFollowAttachment()
    loadDetail()
    loadDocuments() // 附件已归档到「文件」，同步刷新列表
  } catch (e) { console.error('[项目] 保存沟通记录失败：', e) }
  finally { followSaving.value = false }
}

function handleFollowUpload(file) {
  const formData = new FormData()
  formData.append('iFile', file)
  request({ url: '/upload/file', method: 'post', data: formData, headers: { 'Content-Type': 'multipart/form-data' } })
    .then(res => {
      // /upload/file 返回扁平结构 { file_id, file_name, file_path, ... }
      // 只有老的 /upload/image 才是 { fileInfo: { file_path } }，这里两种都兼容
      const d = res?.data || {}
      const path = d.file_path || d.fileInfo?.file_path || ''
      if (!path) {
        console.error('[项目] 上传返回缺少 file_path：', res)
        message.error('上传失败：未获取到文件路径')
        return
      }
      followForm.attachment = path
      followForm.fileName = d.file_name || file.name
      message.success('上传成功')
    })
    .catch(err => { console.error('[项目] 附件上传失败：', err); message.error('上传失败') })
  return false
}
function clearFollowAttachment() { followForm.attachment = ''; followForm.fileName = '' }

// ---- 联系人 ----
async function loadContacts() {
  if (!project.customer_id) return
  try {
    const res = await getContacts(project.customer_id)
    contacts.value = res?.data?.list || []
  } catch (e) { console.error('[项目] 读取联系人失败：', e) }
}
function openAddContact() { contactSelected.value = null; contactFormVisible.value = true }
function openEditContact(record) { contactSelected.value = record; contactFormVisible.value = true }

// ---- 文件 ----
async function loadDocuments() {
  if (!projectId.value) return
  try {
    const res = await getDocuments({ project_id: projectId.value, customer_id: project.customer_id, list_rows: 100 })
    const list = res?.data?.list
    documents.value = list?.data || list || []
  } catch (e) { console.error('[项目] 读取文件失败：', e) }
}
function openDocModal() {
  docForm.file = null; docForm.fileName = ''; docForm.title = ''; docForm.category = '其他'; docForm.remark = ''
  docModalVisible.value = true
}
function handleDocFile(file) {
  docForm.file = file
  docForm.fileName = file.name
  if (!docForm.title) docForm.title = file.name
  return false
}
async function submitDoc() {
  if (!docForm.file) { message.warning('请选择文件'); return }
  docUploading.value = true
  try {
    const formData = new FormData()
    formData.append('iFile', docForm.file)
    formData.append('customer_id', project.customer_id)
    formData.append('project_id', projectId.value)
    formData.append('title', docForm.title || docForm.fileName)
    formData.append('category', docForm.category || '其他')
    formData.append('remark', docForm.remark || '')
    await uploadDocument(formData)
    message.success('上传成功')
    docModalVisible.value = false
    loadDocuments()
  } catch (e) { console.error('[项目] 上传文件失败：', e) }
  finally { docUploading.value = false }
}
/** 打开文件预览弹窗：url 为完整地址，title 为弹窗标题 */
function openFilePreview(url, title) {
  const ext = String(url).split(/[?#]/)[0].split('.').pop().toLowerCase()
  previewUrl.value = url
  previewTitle.value = title || '文件预览'
  previewType.value = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico'].includes(ext) ? 'image'
    : ext === 'pdf' ? 'pdf' : 'other'
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

async function deleteDoc(record) {
  try { await deleteDocument(record.id); message.success('已删除'); loadDocuments() }
  catch (e) { console.error('[项目] 删除文件失败：', e) }
}

async function generateAiSummary() {
  aiSummaryLoading.value = true
  try {
    const res = await getAiSummary(projectId.value)
    aiSummaryText.value = res?.data?.summary || ''
    if (!aiSummaryText.value) message.info('AI 没有返回内容')
  } catch (e) { console.error('[项目] AI 摘要失败：', e) }
  finally { aiSummaryLoading.value = false }
}

// ---- 材料审核 ----
function itemStatusText(s) { return { 0: '待提交', 1: '已提交', 2: '已通过', 3: '已驳回', 4: '免交' }[s] || s }

async function openMaterials(record) {
  materialVisible.value = true
  materialLoading.value = true
  try {
    const res = await getTicketDetail(record.id)
    const d = res?.data || {}
    materialTicket.value = d.ticket || {}
    materialItems.value = d.items || []
  } catch (e) { console.error('[项目] 读取工单材料失败：', e) }
  finally { materialLoading.value = false }
}

async function reloadMaterial() {
  if (!materialTicket.value?.id) return
  try {
    const res = await getTicketDetail(materialTicket.value.id)
    const d = res?.data || {}
    materialTicket.value = d.ticket || materialTicket.value
    materialItems.value = d.items || []
  } catch (e) { console.error('[项目] 刷新工单材料失败：', e) }
}

async function passMaterial(item) {
  reviewing.value = true
  try {
    await reviewTicketItem({ item_id: item.id, pass: 1, remark: '' })
    message.success('已通过')
    await reloadMaterial()
    loadDetail()
  } catch (e) { console.error('[项目] 材料审核失败：', e) }
  finally { reviewing.value = false }
}

function openRejectMaterial(item) {
  rejectItem.value = item
  rejectRemark.value = ''
  rejectVisible.value = true
}

async function submitRejectMaterial() {
  if (!rejectRemark.value.trim()) { message.warning('请填写驳回原因'); return }
  reviewing.value = true
  try {
    await reviewTicketItem({ item_id: rejectItem.value.id, pass: 0, remark: rejectRemark.value.trim() })
    message.success('已驳回')
    rejectVisible.value = false
    await reloadMaterial()
    loadDetail()
  } catch (e) { console.error('[项目] 驳回材料失败：', e) }
  finally { reviewing.value = false }
}

function handleMaterialUpload(file, item) {
  if (!materialTicket.value?.id) return false
  uploadingItemId.value = item.id
  const formData = new FormData()
  formData.append('iFile', file)
  formData.append('ticket_id', materialTicket.value.id)
  formData.append('item_id', item.id)
  uploadTicketFile(formData)
    .then(() => { message.success('上传成功'); reloadMaterial() })
    .catch(err => { console.error('[项目] 上传材料失败：', err); message.error('上传失败') })
    .finally(() => { uploadingItemId.value = 0 })
  return false
}

async function generateOrderPeriodsAction(record) {
  try {
    const res = await generateOrderPeriods(record.id)
    const d = res?.data || {}
    message.success(`已生成 ${d.created || 0} 期${d.skipped ? '，跳过 ' + d.skipped + ' 期' : ''}`)
    tab.value = 'deliveries'
    loadDetail()
  } catch (e) { console.error('[项目] 生成期次失败：', e) }
}

// ---- 收款 ----
function openPayment(order = null) {
  paymentForm.order_id = order?.id || (unpaidOrders.value[0]?.id)
  paymentForm.amount = order ? Number(order.unpaid_amount) : Number(unpaidOrders.value[0]?.unpaid_amount || 0)
  paymentForm.method = '银行转账'
  paymentForm.paid_at = null
  paymentForm.remark = ''
  paymentVisible.value = true
}
function onPaymentOrderChange(id) {
  const order = unpaidOrders.value.find(o => Number(o.id) === Number(id))
  if (order) paymentForm.amount = Number(order.unpaid_amount)
}
async function submitPayment() {
  if (!paymentForm.order_id) { message.warning('请选择订单'); return }
  if (!paymentForm.amount || Number(paymentForm.amount) <= 0) { message.warning('请输入收款金额'); return }
  paymentSaving.value = true
  try {
    await addPayment({
      project_id: projectId.value,
      order_id: paymentForm.order_id,
      amount: paymentForm.amount,
      method: paymentForm.method,
      paid_at: paymentForm.paid_at ? dayjs(paymentForm.paid_at).format('YYYY-MM-DD') : '',
      remark: paymentForm.remark || '',
    })
    message.success('收款已登记')
    paymentVisible.value = false
    tab.value = 'sales'
    salesTab.value = 'payments'
    loadDetail()
  } catch (e) { console.error('[项目] 登记收款失败：', e) }
  finally { paymentSaving.value = false }
}
async function deletePaymentItem(record) {
  try { await deletePayment(record.id); message.success('已冲正'); loadDetail() }
  catch (e) { console.error('[项目] 冲正失败：', e) }
}

// ---- 合同 ----
function openContractBlank() {
  contractDetail.value = null
  contractOrder.value = null
  contractPreset.value = {
    customer_id: project.customer_id,
    project_id: projectId.value,
    contract_name: (project.title || '服务') + ' 合同',
  }
  contractVisible.value = true
}
async function openEditContract(record) {
  try {
    const res = await getContractDetail(record.id)
    contractPreset.value = null
    contractOrder.value = null
    contractDetail.value = res?.data?.detail || null
    contractVisible.value = true
  } catch (e) { console.error('[项目] 读取合同失败：', e) }
}
async function convertOrderToContract(record) {
  try {
    const res = await getOrderDetail(record.id)
    contractDetail.value = null
    contractPreset.value = null
    contractOrder.value = res?.data?.detail || null
    contractVisible.value = true
  } catch (e) { console.error('[项目] 读取订单失败：', e) }
}
function onContractSaved() { contractVisible.value = false; tab.value = 'sales'; salesTab.value = 'contracts'; loadDetail() }
async function contractAction(record, status, label) {
  try { await changeContractStatus(record.id, status); message.success(label); loadDetail() }
  catch (e) { console.error('[项目] 合同操作失败：', e) }
}
async function deleteContractItem(record) {
  try { await deleteContract(record.id); message.success('已删除'); loadDetail() }
  catch (e) { console.error('[项目] 删除合同失败：', e) }
}

function goIntent(record) { router.push({ path: '/crm/intent/detail', query: { id: record.id } }) }

async function loadUsers() {
  try {
    const res = await listUsers({})
    storeUsers.value = (res?.data?.list?.data || res?.data?.list || []).filter(u => u.is_supplier !== 1)
  } catch (e) { console.error('[项目详情] 读取员工失败：', e) }
}
async function loadCatalogRows() {
  try {
    const res = await getCatalog()
    catalogRows.value = Array.isArray(res?.data) ? res.data : (res?.data?.list || [])
  } catch (e) { console.error('[项目详情] 读取服务目录失败：', e) }
}

onMounted(() => { loadUsers(); loadCatalogRows(); loadDetail() })
onActivated(() => { if (projectId.value) loadDetail() })
</script>

<style scoped>
.topbar { display: flex; align-items: center; gap: 10px; padding: 10px 0; }
.back { padding-left: 0; color: #666; }
.topbar-title { font-size: 16px; font-weight: 600; }
.topbar-actions { margin-left: auto; display: flex; gap: 8px; align-items: center; }

.project-detail :deep(.ant-card-head) { border-bottom: none; padding: 0 24px; }
.project-detail :deep(.ant-card-body) { padding: 0 24px 24px; }

.proj-hero {
  background: linear-gradient(135deg, #fffaf3 0%, #ffffff 70%);
  border: 1px solid #f2e4d2;
  border-radius: 12px;
  padding: 16px 20px 14px;
  margin-bottom: 14px;
}
.hero-meta { display: flex; flex-wrap: wrap; align-items: center; gap: 20px; color: #666; font-size: 13px; }
.hero-meta .anticon { color: #d6893b; margin-right: 4px; }
.hero-no { margin-left: auto; color: #bbb; font-size: 12px; }
.hero-metrics { display: flex; margin-top: 0; }
.metric { flex: 1; min-width: 0; padding: 0 14px; border-left: 1px solid #f2e4d2; text-decoration: none; color: inherit; }
.metric:first-child { border-left: none; padding-left: 0; }
.metric b { display: block; font-size: 20px; color: #2c2416; font-variant-numeric: tabular-nums; line-height: 1.2; }
.metric b.warn { color: #ff4d4f; }
.metric span { font-size: 12px; color: #999; }
.metric:hover b { color: #d6893b; }
.main-tabs :deep(.ant-tabs-nav) { position: sticky; top: 0; background: #fff; z-index: 5; margin-bottom: 10px; }
.main-tabs :deep(.ant-tabs-nav::before) { border-bottom: 1px solid #f3f3f3; }
.main-tabs :deep(.ant-tabs-tab) { padding: 10px 2px 12px; }
.main-tabs :deep(.ant-tabs-tab + .ant-tabs-tab) { margin-left: 30px; }
.main-tabs :deep(.ant-tabs-tab-btn) { font-size: 14px; color: #6b6b6b; transition: color .18s; }
.main-tabs :deep(.ant-tabs-tab:hover .ant-tabs-tab-btn) { color: #d6893b; }
.main-tabs :deep(.ant-tabs-tab-active .ant-tabs-tab-btn) { color: #2c2416; font-weight: 600; }
.main-tabs :deep(.ant-tabs-ink-bar) { height: 3px; border-radius: 2px; background: linear-gradient(90deg, #e9ad6d, #d6893b); }
.main-tabs :deep(.ant-tabs-tab-active .tb-count) { background: #fdf1e4; color: #d6893b; }
.tb-count { display: inline-block; min-width: 18px; height: 18px; line-height: 18px; padding: 0 5px; margin-left: 6px; border-radius: 9px; background: #f5f5f5; color: #9b9b9b; font-size: 12px; font-weight: 400; text-align: center; transition: all .18s; }
.quick-actions { display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap; }
.tab-toolbar { margin-bottom: 12px; }
.overdue { color: #ff4d4f; }
.follow-body { font-size: 13px; line-height: 1.7; margin: 2px 0; }
.stat-row { display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; margin-bottom: 16px; }
.stat-item { background: #fafafa; border: 1px solid #f0f0f0; border-radius: 8px; padding: 12px 14px; }
.stat-num { font-size: 20px; font-weight: 700; color: #2c2416; }
.stat-label { font-size: 12px; color: #999; margin-top: 2px; }
.overview { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.overview.single { grid-template-columns: 1fr; }
.proj-dossier {
  border: 1px solid #eee;
  border-radius: 12px;
  padding: 14px 20px 12px;
  margin-bottom: 14px;
  background: #fff;
}
.dossier-head { display: flex; align-items: center; gap: 8px; margin-bottom: 14px; }
.dossier-title { font-size: 14px; font-weight: 600; color: #2c2416; }
.dossier-no { color: #bbb; font-size: 12px; }
.dossier-edit { margin-left: auto; }
.dossier-grid { display: grid; grid-template-columns: 1.2fr 1fr 1.15fr 1.05fr; }
.dcell { padding: 0 18px; border-left: 1px solid #f2f2f2; min-width: 0; }
.dcell:first-child { padding-left: 0; border-left: none; }
.dlabel { font-size: 12px; color: #999; margin-bottom: 6px; }
.dcustomer { font-size: 15px; font-weight: 500; color: #2c2416; cursor: pointer; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.dcustomer:hover { color: #d6893b; }
.dperson { display: flex; align-items: center; gap: 6px; margin-bottom: 6px; font-size: 13px; color: #333; }
.davatar { font-size: 12px; flex: none; }
.dname { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.drole { font-size: 12px; color: #bbb; margin-left: auto; flex: none; }
.dvalue { font-size: 13px; color: #2c2416; margin-bottom: 2px; }
.dvalue b { font-size: 16px; }
.dsub { font-size: 12px; color: #8c8c8c; }
.dsub.muted { color: #bbb; }
.dlink { cursor: pointer; margin-top: 4px; }
.dlink:hover { color: #d6893b; }
.dossier-grid :deep(.ant-progress) { margin: 4px 0 1px; }
@media (max-width: 1280px) {
  .dossier-grid { grid-template-columns: 1fr 1fr; row-gap: 14px; }
  .dcell:nth-child(odd) { border-left: none; padding-left: 0; }
}
.dossier-foot { margin-top: 12px; padding-top: 10px; border-top: 1px dashed #f0f0f0; display: flex; flex-direction: column; gap: 6px; }
.dfoot-row { display: flex; align-items: flex-start; gap: 10px; font-size: 13px; color: #333; }
.dfoot-row .dlabel { margin: 2px 0 0; flex: 0 0 28px; }
.dremark { color: #555; line-height: 1.6; }
.dchips { display: flex; flex-wrap: wrap; align-items: center; }
.doc-chip { display: inline-flex; align-items: center; gap: 4px; padding: 1px 8px; margin: 0 6px 6px 0; background: #fafafa; border: 1px solid #f0f0f0; border-radius: 10px; font-size: 12px; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.doc-chip:hover { border-color: #d6893b; color: #d6893b; }
.doc-more { font-size: 12px; margin-bottom: 6px; }
.start-hint .hint-text { color: #666; font-size: 13px; line-height: 1.7; margin: 4px 0 10px; }
.dossier-desc :deep(.ant-descriptions-item-label) { color: #999; width: 76px; }
.doc-link { margin-right: 14px; }
.ai-summary { grid-column: 1 / -1; }
.ai-text { font-size: 13px; line-height: 1.8; color: #444; white-space: pre-wrap; }
.ov-count { color: #d6893b; font-size: 12px; margin-left: 4px; }
.todo-list { list-style: none; padding-left: 0; }
.todo-list li { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 5px 0; border-bottom: 1px dashed #eee; }
.todo-list li:last-child { border-bottom: none; }
.todo-text { flex: 1; min-width: 0; }
.timeline-list { list-style: none; padding-left: 0; }
.tl-row { padding: 6px 0; border-bottom: 1px dashed #eee; }
.tl-row:last-child { border-bottom: none; }
.tl-title { font-size: 13px; color: #333; font-weight: 500; }
.tl-meta { font-size: 11px; color: #999; margin-top: 2px; }
.ov-block { background: #fafafa; border-radius: 8px; padding: 14px 16px; }
.ov-title { font-size: 13px; font-weight: 600; color: #333; margin-bottom: 8px; }
.ov-list { margin: 0; padding-left: 18px; color: #666; font-size: 13px; line-height: 1.9; }
.tl-meta { font-size: 12px; color: #999; }
.tl-content { font-size: 13px; color: #555; margin-top: 3px; line-height: 1.6; word-break: break-word; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.tl-head { display: flex; align-items: baseline; gap: 10px; }
.tl-head .tl-title { flex: 1; min-width: 0; }
.tl-time { font-size: 12px; color: #bbb; flex: none; }
.tl-next { margin-top: 4px; font-size: 12px; color: #d6893b; }
.muted { color: #bbb; }
.pay-summary { display: flex; align-items: center; gap: 24px; margin-bottom: 12px; }
.pay-summary strong { margin-left: 6px; font-size: 16px; }
.mat-item { border: 1px solid #f0f0f0; border-radius: 8px; padding: 12px 14px; margin-bottom: 10px; }
.mat-head { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.mat-head strong { margin-right: 4px; }
.mat-remark { color: #ff4d4f; font-size: 12px; margin-top: 6px; }
.mat-files { margin-top: 8px; display: flex; flex-wrap: wrap; gap: 8px; }
.mat-file { display: inline-flex; align-items: center; font-size: 12px; }
.mat-thumb { width: 64px; height: 64px; object-fit: cover; border-radius: 4px; border: 1px solid #eee; }
.form-hint { color: #999; font-size: 12px; line-height: 1.6; margin-top: 4px; }
</style>
