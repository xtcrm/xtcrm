// 开源版路由（由 sync/opensource/overrides 维护，不要直接改开源仓库里的这份文件）
//
// 与私有版 mpp/crm/frontend-v2/src/router/asyncRoutes.js 的差异：
//   - 只保留已开源的 3 个 skill：contentengine / invoice / quotecn
//   - 去掉 workorder / studio / leadledger / website / entquery 等闭源 skill 的路由
//   - 去掉 AI 应用构建器（/ai/*）与论坛（/forum/*），这两块不在开源范围内
//   - 去掉 router/index.js 里的 /app/preview 路由（见 overrides 里的 index.js）
const Placeholder = () => import('@/views/Placeholder.vue')
const RouteView = () => import('@/router/RouteView.vue')
import contentEngineRoutes from '@skills/contentengine/routes-v2'
import quoteCnRoutes from '@skills/quotecn/routes-v2'
import invoiceRoutes from '@skills/invoice/routes-v2'

export const defaultRedirect = '/index'

export const asyncRoutes = [
  {
    path: '/',
    component: () => import('@/layouts/BasicLayout.vue'),
    redirect: '/index/dashboard',
    children: [
      // ========== 工作台（含岗位工作台） ==========
      {
        path: '/index',
        name: 'index',
        component: RouteView,
        redirect: '/index/dashboard',
        meta: { title: '工作台', icon: 'home' },
        children: [
          {
            path: '/index/dashboard',
            name: 'dashboard',
            component: () => import('@/views/index/Index.vue'),
            meta: { title: '仪表盘', keepAlive: true, affix: true, permission: ['/index'] },
          },
          {
            path: '/crm/workbench/cs',
            name: 'workbench-cs',
            component: () => import('@/views/workbench/Cs.vue'),
            meta: { title: '我的服务客户', permission: ['/crm/workbench/cs'] },
          },
          {
            path: '/crm/workbench/delivery',
            name: 'workbench-delivery',
            component: () => import('@/views/workbench/Delivery.vue'),
            meta: { title: '我的交付', permission: ['/crm/workbench/delivery'] },
          },
          {
            path: '/crm/workbench/review',
            name: 'workbench-review',
            component: () => import('@/views/workbench/Review.vue'),
            meta: { title: '待审队列', permission: ['/crm/workbench/review'] },
          },
        ],
      },
      // ========== 客户管理（含线索） ==========
      {
        path: '/crm/customer',
        name: 'customer',
        component: RouteView,
        redirect: '/crm/customer/today-visit',
        meta: { title: '客户', icon: 'user', permission: ['/crm/customer'] },
        children: [
          { path: '/crm/customer/today-visit', name: 'today-visit', component: () => import('@/views/crm/customer/TodayVisit.vue'), meta: { title: '今日拜访', affix: true, permission: ['/crm/customer/today-visit'] } },
          { path: '/crm/customer/index', name: 'customer-list', component: () => import('@/views/crm/customer/Index.vue'), meta: { title: '客户列表', permission: ['/crm/customer/index', '/crm/customer/lists'] } },
          { path: '/crm/customer/pool', name: 'customer-pool', component: () => import('@/views/crm/customer/pool.vue'), meta: { title: '公海池', permission: ['/crm/customer/pool', '/crm/customer/poolLists'] } },
          { path: '/crm/customer/collab', name: 'customer-collab', component: () => import('@/views/crm/customer/collab.vue'), meta: { title: '协作客户', permission: ['/crm/customer/collab', '/crm/customer/collabLists'] } },
          { path: '/crm/customer/assign', name: 'customer-assign', component: () => import('@/views/crm/customer/assign.vue'), meta: { title: '待分配客户', permission: ['/crm/customer/assign', '/crm/customer/assignLists'] } },
          { path: '/crm/customer/detail', name: 'customer-detail', component: () => import('@/views/crm/customer/detail.vue'), meta: { title: '客户详情', permission: ['/crm/customer/detail'], hidden: true } },
          { path: '/crm/customer/create', name: 'customer-create', component: () => import('@/views/crm/customer/create.vue'), meta: { title: '新增客户', permission: ['/crm/customer/create'] } },

          { path: '/crm/lead/index', name: 'lead-list', component: () => import('@/views/crm/lead/Index.vue'), meta: { title: '线索管理', permission: ['/crm/lead/index', '/crm/lead/lists'] } },
          { path: '/crm/lead/detail', name: 'lead-detail', component: () => import('@/views/crm/lead/detail.vue'), meta: { title: '线索详情', permission: ['/crm/lead/detail'], hidden: true } },
          { path: '/crm/contact/index', name: 'contact-list', component: () => import('@/views/crm/contact/Index.vue'), meta: { title: '联系人管理', permission: ['/crm/contact/index', '/crm/contact/list'] } },
          { path: '/crm/contact/detail', name: 'contact-detail', component: () => import('@/views/crm/contact/detail.vue'), meta: { title: '联系人详情', permission: ['/crm/contact/detail'], hidden: true } },
        ],
      },
      // ========== 项目管理（项目 + 需求） ==========
      {
        path: '/crm/project',
        name: 'project',
        component: RouteView,
        redirect: '/crm/project/index',
        meta: { title: '项目', icon: 'project', permission: ['/crm/project'] },
        children: [
          { path: '/crm/project/index', name: 'project-list', component: () => import('@/views/crm/project/Index.vue'), meta: { title: '项目列表', permission: ['/crm/project/index', '/crm/project/lists'] } },
          { path: '/crm/project/detail', name: 'project-detail', component: () => import('@/views/crm/project/Detail.vue'), meta: { title: '项目详情', permission: ['/crm/project/detail'], hidden: true } },
          { path: '/crm/project/catalog', name: 'catalog-list', component: () => import('@/views/crm/project/Catalog.vue'), meta: { title: '服务目录', permission: ['/crm/project/catalog', '/crm/catalog/lists'] } },
          { path: '/crm/project/review', name: 'project-review', component: () => import('@/views/crm/project/Review.vue'), meta: { title: '审核队列', permission: ['/crm/project/review', '/crm/project/reviewLog'] } },
          { path: '/crm/intent/index', name: 'intent-list', component: () => import('@/views/crm/intent/Index.vue'), meta: { title: '需求池', permission: ['/crm/intent/index', '/crm/intent/lists'] } },
          { path: '/crm/intent/detail', name: 'intent-detail', component: () => import('@/views/crm/intent/Detail.vue'), meta: { title: '需求详情', permission: ['/crm/intent/detail'], hidden: true } },
        ],
      },
      // ========== 销售管理（报价+订单+合同） ==========
      {
        path: '/crm/quotation',
        name: 'sales',
        component: RouteView,
        redirect: '/crm/quotation/index',
        meta: { title: '销售', icon: 'dollar-circle', permission: ['/crm/quotation'] },
        children: [
          { path: '/crm/quotation/index', name: 'quotation-list', component: () => import('@/views/crm/quotation/Index.vue'), meta: { title: '报价管理', permission: ['/crm/quotation/index', '/crm/quotation/lists'] } },
          { path: '/crm/quotation/detail', name: 'quotation-detail', component: () => import('@/views/crm/quotation/detail.vue'), meta: { title: '报价详情', permission: ['/crm/quotation/detail'], hidden: true } },
          ...quoteCnRoutes,
          { path: '/crm/order/index', name: 'order-list', component: () => import('@/views/crm/order/Index.vue'), meta: { title: '订单管理', permission: ['/crm/order/index', '/crm/order/lists'] } },
          { path: '/crm/order/detail', name: 'order-detail', component: () => import('@/views/crm/order/detail.vue'), meta: { title: '订单详情', permission: ['/crm/order/detail'], hidden: true } },
          { path: '/crm/contract/index', name: 'contract-list', component: () => import('@/views/crm/contract/Index.vue'), meta: { title: '合同管理', permission: ['/crm/contract/index', '/crm/contract/lists'] } },
          { path: '/crm/contract/detail', name: 'contract-detail', component: () => import('@/views/crm/contract/detail.vue'), meta: { title: '合同详情', permission: ['/crm/contract/detail'], hidden: true } },
        ],
      },
      // ========== 业绩管理 ==========
      {
        path: '/crm/commission',
        name: 'commission',
        component: RouteView,
        redirect: '/crm/commission/index',
        meta: { title: '业绩', icon: 'bar-chart', permission: ['/crm/commission'] },
        children: [
          { path: '/crm/commission/index', name: 'commission-list', component: () => import('@/views/crm/commission/Index.vue'), meta: { title: '业绩明细', permission: ['/crm/commission/index', '/crm/commission/lists'] } },
          { path: '/crm/commission/rule', name: 'commission-rule', component: () => import('@/views/crm/commission/Rule.vue'), meta: { title: '分账规则', permission: ['/crm/commission/rule', '/crm/commission/saveRule'] } },
          { path: '/crm/commission/override', name: 'commission-override', component: () => import('@/views/crm/commission/Override.vue'), meta: { title: '覆盖审批', permission: ['/crm/commission/override', '/crm/commission/approveOverride'] } },
        ],
      },
      // ========== 报表 ==========
      {
        path: '/crm/report',
        name: 'report',
        component: RouteView,
        redirect: '/crm/report/overview',
        meta: { title: '报表', icon: 'pie-chart' },
        children: [
          { path: '/crm/report/overview', name: 'report-overview', component: () => import('@/views/crm/report/Index.vue'), meta: { title: '经营总览', tab: 'overview', permission: ['/crm/report/overview', '/crm/report/index'] } },
          { path: '/crm/report/sales', name: 'report-sales', component: () => import('@/views/crm/report/Index.vue'), meta: { title: '销售报表', tab: 'sales', permission: ['/crm/report/sales', '/crm/report/index'] } },
          { path: '/crm/report/cs', name: 'report-cs', component: () => import('@/views/crm/report/Index.vue'), meta: { title: '客服报表', tab: 'cs', permission: ['/crm/report/cs', '/crm/report/index'] } },
          { path: '/crm/report/delivery', name: 'report-delivery', component: () => import('@/views/crm/report/Index.vue'), meta: { title: '交付报表', tab: 'delivery', permission: ['/crm/report/delivery', '/crm/report/index'] } },
          { path: '/crm/report/finance', name: 'report-finance', component: () => import('@/views/crm/report/Index.vue'), meta: { title: '财务报表', tab: 'finance', permission: ['/crm/report/finance', '/crm/report/index'] } },
        ],
      },
      // ========== 知识库 ==========
      {
        path: '/crm/knowledge',
        name: 'knowledge',
        component: RouteView,
        redirect: '/crm/knowledge/index',
        meta: { title: '知识', icon: 'book', permission: ['/crm/knowledge'] },
        children: [
          { path: '/crm/knowledge/index', name: 'knowledge-list', component: () => import('@/views/crm/knowledge/Index.vue'), meta: { title: '知识列表', permission: ['/crm/knowledge/index', '/crm/knowledge/lists'] } },
          {
            path: '/crm/content',
            name: 'content-engine',
            component: RouteView,
            redirect: '/crm/content/profile',
            meta: { title: '内容创作', permission: ['/crm/content'] },
            children: contentEngineRoutes,
          },
        ],
      },
      // ========== 应用（开源版只带票无忧） ==========
      {
        path: '/app',
        name: 'app',
        component: RouteView,
        redirect: '/app/invoice/list',
        meta: { title: '应用', icon: 'appstore', permission: ['/app'] },
        children: [
          ...invoiceRoutes,
        ],
      },
      // ========== 系统设置（产品+管理员+CRM设置） ==========
      {
        path: '/crm/product',
        name: 'system',
        component: RouteView,
        redirect: '/crm/product/index',
        meta: { title: '系统', icon: 'setting', permission: ['/crm/product'] },
        children: [
          { path: '/crm/product/index', name: 'product-list', component: () => import('@/views/crm/product/Index.vue'), meta: { title: '产品管理', permission: ['/crm/product/index', '/crm/product/lists'] } },
          { path: '/manage/user/index', name: 'manage-user', component: () => import('@/views/manage/user/Index.vue'), meta: { title: '管理员列表', permission: ['/manage/user/index'] } },
          { path: '/manage/role/index', name: 'manage-role', component: () => import('@/views/manage/role/Index.vue'), meta: { title: '角色管理', permission: ['/manage/role/index'] } },
          { path: '/manage/department/index', name: 'manage-dept', component: () => import('@/views/manage/department/Index.vue'), meta: { title: '部门管理', permission: ['/manage/department/index'] } },
          { path: '/manage/menu/index', name: 'manage-menu', component: () => import('@/views/manage/menu/Index.vue'), meta: { title: '菜单管理', permission: ['/manage/menu/index'] } },
          { path: '/crm/setting', name: 'crm-setting', component: () => import('@/views/crm/setting/Index.vue'), meta: { title: 'CRM 设置', permission: ['/crm/setting'] } },
        ],
      },

    ],
  },
]
