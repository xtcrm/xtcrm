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
    redirect: '/index',
    children: [
      // ========== 工作台 ==========
      {
        path: '/index',
        name: 'index',
        component: () => import('@/views/index/Index.vue'),
        meta: { title: '工作台', keepAlive: true, icon: 'home', permission: ['/index'] },
      },
      // ========== 客户管理（含线索） ==========
      {
        path: '/crm/customer',
        name: 'customer',
        component: RouteView,
        redirect: '/crm/customer/index',
        meta: { title: '客户管理', icon: 'user', permission: ['/crm/customer'] },
        children: [
          { path: '/crm/customer/index', name: 'customer-list', component: () => import('@/views/crm/customer/Index.vue'), meta: { title: '客户列表', permission: ['/crm/customer/index', '/crm/customer/lists'] } },
          { path: '/crm/customer/pool', name: 'customer-pool', component: () => import('@/views/crm/customer/pool.vue'), meta: { title: '公海池', permission: ['/crm/customer/pool', '/crm/customer/poolLists'] } },
          { path: '/crm/customer/collab', name: 'customer-collab', component: () => import('@/views/crm/customer/collab.vue'), meta: { title: '协作客户', permission: ['/crm/customer/collab', '/crm/customer/collabLists'] } },
          { path: '/crm/customer/detail', name: 'customer-detail', component: () => import('@/views/crm/customer/detail.vue'), meta: { title: '客户详情', permission: ['/crm/customer/detail'], hidden: true } },
          { path: '/crm/customer/create', name: 'customer-create', component: () => import('@/views/crm/customer/create.vue'), meta: { title: '新增客户', permission: ['/crm/customer/create'] } },
          { path: '/crm/lead/index', name: 'lead-list', component: () => import('@/views/crm/lead/Index.vue'), meta: { title: '线索管理', permission: ['/crm/lead/index', '/crm/lead/lists'] } },
          { path: '/crm/lead/detail', name: 'lead-detail', component: () => import('@/views/crm/lead/detail.vue'), meta: { title: '线索详情', permission: ['/crm/lead/detail'], hidden: true } },
        ],
      },
      // ========== 销售管理（报价+订单+合同） ==========
      {
        path: '/crm/quotation',
        name: 'sales',
        component: RouteView,
        redirect: '/crm/quotation/index',
        meta: { title: '销售管理', icon: 'dollar-circle', permission: ['/crm/quotation'] },
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
      // ========== 知识库 ==========
      {
        path: '/crm/knowledge',
        name: 'knowledge',
        component: RouteView,
        redirect: '/crm/knowledge/index',
        meta: { title: '知识库', icon: 'book', permission: ['/crm/knowledge'] },
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
      // ========== 应用 ==========
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
        meta: { title: '系统设置', icon: 'setting', permission: ['/crm/product'] },
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
