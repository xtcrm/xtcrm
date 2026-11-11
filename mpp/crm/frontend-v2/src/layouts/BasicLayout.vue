<template>
  <a-layout style="height: 100vh">
    <!-- 侧边栏 -->
    <a-layout-sider
      :width="sidebarWidth"
      theme="light"
      class="custom-sider"
      :trigger="null"
      :collapsible="true"
      :collapsed="collapsed"
    >
      <div class="sidebar-inner">
        <!-- 图标栏 -->
        <div class="icon-bar">
          <div class="icon-logo" @click="selectMenu(null)" title="工作台">
            <span>CRM</span>
          </div>
          <div class="icon-list">
            <div
              v-for="menu in menus"
              :key="menu.path"
              class="icon-item"
              :class="{ active: selectedMenuPath === menu.path }"
              :title="menu.meta?.title"
              @click="selectMenu(menu)"
            >
              <component :is="iconMap[menu.meta?.icon]" v-if="menu.meta?.icon" />
              <appstore-outlined v-else />
              <span class="icon-text">{{ menu.meta?.title }}</span>
            </div>
          </div>
          <div class="icon-collapse" @click="collapsed = !collapsed" :title="collapsed ? '展开菜单' : '收起菜单'">
            <menu-fold-outlined v-if="!collapsed" />
            <menu-unfold-outlined v-else />
          </div>
        </div>
        <!-- 子菜单面板 -->
          <div v-if="showSubPanel && !collapsed" class="sub-panel">
            <div class="sub-title">{{ selectedMenu.meta?.title }}</div>
            <div class="sub-list">
              <template v-for="child in selectedMenu.children" :key="child.path + ':' + openGroups.length">
                <template v-if="!child.meta?.hidden">
                  <div v-if="child.children && hasVisibleChildren(child)" class="sub-group">
                    <div
                      class="sub-group-hd"
                      :class="{ folded: !openGroups.includes(child.path) }"
                      @click="toggleGroup(child)"
                    >
                      {{ child.meta?.title }}
                      <down-outlined />
                    </div>
                    <div v-if="openGroups.includes(child.path)" class="sub-group-bd">
                      <div
                        v-for="gc in child.children"
                        :key="gc.path"
                        v-show="!gc.meta?.hidden"
                        class="sub-link"
                        :class="{ active: route.path === gc.path }"
                        @click="router.push(gc.path)"
                      >
                        {{ gc.meta?.title }}
                      </div>
                    </div>
                  </div>
                  <div
                    v-else
                    class="sub-link"
                    :class="{ active: isActiveItem(child) }"
                    @click="navigateToItem(child)"
                  >
                    {{ child.meta?.title }}
                  </div>
                </template>
              </template>
            </div>
          </div>
      </div>
    </a-layout-sider>

    <!-- 主内容 -->
    <a-layout class="main-layout">
      <a-layout-header class="main-header">
        <div class="hd-left">
          <a-button type="text" class="collapse-btn" @click="collapsed = !collapsed">
            <menu-fold-outlined v-if="!collapsed" />
            <menu-unfold-outlined v-else />
          </a-button>
          <a-breadcrumb>
            <a-breadcrumb-item v-for="item in breadcrumbs" :key="item.path">
              {{ item.meta?.title }}
            </a-breadcrumb-item>
          </a-breadcrumb>
        </div>
        <div class="hd-right">
          <notification-bell />
          <a-dropdown placement="bottomRight">
            <span class="user-tag">
              <a-avatar size="small" style="background:#d6893b"><template #icon><user-outlined /></template></a-avatar>
              <span class="user-name">{{ nickname }}</span>
              <down-outlined style="font-size:10px;color:#bbb" />
            </span>
            <template #overlay>
              <a-menu>
                <a-menu-item key="pwd" @click="pwdVisible = true"><key-outlined /> 修改密码</a-menu-item>
                <a-menu-divider />
                <a-menu-item key="out" @click="handleLogout"><poweroff-outlined /> 退出</a-menu-item>
              </a-menu>
            </template>
          </a-dropdown>
        </div>
      </a-layout-header>

      <!-- 页签栏：一个客户一个页签，靠切页签代替来回导航 -->
      <tab-bar />

      <!-- 内容区 + AI 面板（flex row，面板打开时内容自动缩窄） -->
      <div class="main-body">
        <a-layout-content class="main-content">
          <!-- 页面缓存/过渡都在 TabPageView 里，别在这里再包 keep-alive -->
          <tab-page-view />
        </a-layout-content>

        <!-- AI 侧边面板 — 嵌入布局流，不是 fixed 浮层 -->
        <div v-if="aiStore.panelOpen" class="ai-panel">
          <div class="ai-panel-handle" @click="aiStore.togglePanel()" title="收起AI面板">
            <right-outlined />
          </div>
          <AiPanel />
        </div>
      </div>
    </a-layout>

    <!-- AI 面板收起时的侧边标签 -->
    <div v-if="!aiStore.panelOpen" class="ai-panel-tab" @click="aiStore.togglePanel()" title="打开AI面板">
      <RobotOutlined />
      <span>AI</span>
    </div>

    <!-- 修改密码弹窗 -->
    <a-modal :open="pwdVisible" title="修改密码" :confirm-loading="pwdSaving" @ok="handlePwdSubmit" @cancel="pwdVisible = false" :width="400">
      <a-form ref="pwdFormRef" :model="pwdForm" :label-col="{ span: 6 }" :wrapper-col="{ span: 16 }">
        <a-form-item label="新密码" name="password" :rules="[{ required: true, min: 6, message: '请输入至少6个字符' }]">
          <a-input-password v-model:value="pwdForm.password" />
        </a-form-item>
        <a-form-item label="确认密码" name="password_confirm" :rules="[{ required: true, validator: validatePwdConfirm }]">
          <a-input-password v-model:value="pwdForm.password_confirm" />
        </a-form-item>
      </a-form>
    </a-modal>
  </a-layout>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import {
  PoweroffOutlined, UserOutlined, HomeOutlined, KeyOutlined,
  ShoppingOutlined, OrderedListOutlined, TeamOutlined,
  DollarOutlined,
  AppstoreOutlined, SettingOutlined, DownOutlined,
  BulbOutlined, FileTextOutlined, FileProtectOutlined,
  BookOutlined, MenuFoldOutlined, MenuUnfoldOutlined, RightOutlined,
  RobotOutlined, ProjectOutlined, BarChartOutlined, PieChartOutlined,
} from '@ant-design/icons-vue'
import { useUserStore } from '@/stores/user'
import { usePermissionStore } from '@/stores/permission'
import { useAiStore } from '@/stores/ai'
import { useTabsStore } from '@/stores/tabs'
import AiPanel from '@/components/AiPanel/index.vue'
import NotificationBell from '@/components/NotificationBell/index.vue'
import TabBar from '@/layouts/components/TabBar.vue'
import TabPageView from '@/layouts/TabPageView.vue'
import { renew } from '@/api/user'
import { message } from 'ant-design-vue'

const iconMap = {
  home: HomeOutlined, shopping: ShoppingOutlined,
  'ordered-list': OrderedListOutlined, user: UserOutlined, team: TeamOutlined,
  appstore: AppstoreOutlined, setting: SettingOutlined,
  bulb: BulbOutlined, 'file-text': FileTextOutlined,
  'file-protect': FileProtectOutlined, book: BookOutlined,
  'dollar-circle': DollarOutlined,
  project: ProjectOutlined,
  robot: RobotOutlined, 'bar-chart': BarChartOutlined, 'pie-chart': PieChartOutlined,
}

const router = useRouter()
const route = useRoute()
const userStore = useUserStore()
const permissionStore = usePermissionStore()
const aiStore = useAiStore()
aiStore.init()

const tabsStore = useTabsStore()

// 路由变化 → 打开/激活页签。放在 BasicLayout 是因为所有进后台的页面都从这里过。
// 页签只是"记录"，真正决定缓存的是 TabPageView 里 keep-alive 的 include 名单。
watch(() => route.fullPath, () => tabsStore.openTab(route), { immediate: true })

// 路由切换 → 自动推导 AI 上下文 + 加载历史消息
watch(() => [route.path, route.query], () => {
  const newCtx = buildAiContext(route)
  // 只有上下文真正变了才清消息（比如从客户A切到客户B），同页面刷新不清
  const oldPage = aiStore.currentContext?.page
  const oldId   = aiStore.currentContext?.customer_id
  if (oldPage !== newCtx.page || oldId !== newCtx.customer_id) {
    aiStore.messages = []
  }
  aiStore.setContext(newCtx)
  if (aiStore.panelOpen) {
    aiStore.loadHistory()
  }
}, { immediate: true })

function buildAiContext(r) {
  const ctx = { page: r.name || '', path: r.path }
  const q = r.query || {}

  // 客户
  if (r.name === 'customer-detail') {
    ctx.customer_id = q.id ? parseInt(q.id) : null
  }
  if (r.name === 'customer-list') {
    ctx.keyword = q.keyword || ''
    ctx.tab = q.tab || ''
  }

  // 线索
  if (r.name === 'lead-detail') {
    ctx.lead_id = q.id ? parseInt(q.id) : null
  }

  // 报价/订单/合同/联系人 — 详情页都有 ?id=
  if (['quotation-detail','order-detail','contract-detail','contact-detail'].includes(r.name)) {
    ctx.entity_id = q.id ? parseInt(q.id) : null
  }

  return ctx
}

const collapsed = ref(false)
const showSubPanel = computed(() => !collapsed.value && selectedMenu.value && selectedMenu.value.children?.some((c) => !c.meta?.hidden))
const sidebarWidth = computed(() => {
  if (collapsed.value) return 68
  return showSubPanel.value ? 226 : 68
})
const nickname = computed(() => userStore.userInfo?.nickName || userStore.userInfo?.user_name || '管理员')

const menus = computed(() => {
  const r = permissionStore.menus.find((r) => r.path === '/')
  return r?.children?.filter((m) => !m.meta?.hidden) || []
})

const selectedMenuPath = computed(() => route.matched[1]?.path || null)
const selectedMenu = ref(null)
const openGroups = ref([])

// 完全照抄原 frontend updateSider() 逻辑
function updateSider() {
  const rootRoute = route.matched[1]
  if (rootRoute) {
    const m = menus.value.find((item) => item.name === rootRoute.name)
    if (m) selectedMenu.value = m
  }
  autoOpenGroups()
}
watch(() => route.path, updateSider, { immediate: true })

// 菜单加载后补调
watch(menus, (v) => { if (v.length) updateSider() })
onMounted(() => { setTimeout(updateSider, 100) })

function autoOpenGroups() {
  const groups = []
  if (selectedMenu.value?.children) {
    for (const c of selectedMenu.value.children) {
      if (c.children?.some((gc) => route.path === gc.path || route.path.startsWith(gc.path + '/'))) {
        groups.push(c.path)
      }
    }
  }
  console.log('[autoOpenGroups]', groups, 'selectedMenu:', selectedMenu.value?.path)
  openGroups.value = groups
}

function hasVisibleChildren(m) { return m.children?.some((c) => !c.meta?.hidden) }

function toggleGroup(c) {
  const i = openGroups.value.indexOf(c.path)
  i >= 0 ? openGroups.value.splice(i, 1) : openGroups.value.push(c.path)
}

function isActiveItem(c) {
  if (route.path === c.path) return true
  if (c.children) return c.children.some((gc) => route.path === gc.path)
  return false
}

function navigateToItem(c) {
  if (c.redirect) router.push(c.redirect)
  else if (c.children?.length) {
    const f = c.children.find((x) => !x.meta?.hidden) || c.children[0]
    router.push(f.redirect || f.path)
  }
  else router.push(c.path)
}

function selectMenu(m) {
  if (!m) { router.push('/'); return }
  if (collapsed.value) collapsed.value = false
  selectedMenu.value = m
  if (m.children) {
    for (const c of m.children) {
      if (c.children?.length && !openGroups.value.includes(c.path))
        openGroups.value.push(c.path)
    }
  }
  navigateToItem(m)
}

function syncMenu() {
  const p = route.path
  for (const m of menus.value) {
    if (p === m.path) { selectedMenu.value = m; return }
    if (m.children?.some((c) => {
      if (p === c.path) return true
      return c.children?.some((gc) => p === gc.path)
    })) { selectedMenu.value = m; return }
  }
  for (let i = route.matched.length - 1; i >= 0; i--) {
    const f = menus.value.find((m) => m.path === route.matched[i].path)
    if (f) { selectedMenu.value = f; return }
  }
}

const breadcrumbs = computed(() =>
  route.matched.filter((r) => r.meta?.title && r.path !== '/'),
)

watch(() => [route.path, menus.value], syncMenu, { immediate: true })

// 修改密码
const pwdVisible = ref(false)
const pwdSaving = ref(false)
const pwdFormRef = ref()
const pwdForm = reactive({ password: '', password_confirm: '' })

function validatePwdConfirm(_rule, value) {
  if (value && value !== pwdForm.password) return Promise.reject('两次输入的密码不一致')
  return Promise.resolve()
}

function handlePwdSubmit() {
  pwdFormRef.value.validate().then(() => {
    pwdSaving.value = true
    renew({ form: { password: pwdForm.password, password_confirm: pwdForm.password_confirm } })
      .then(() => { message.success('密码修改成功'); pwdVisible.value = false })
      .finally(() => { pwdSaving.value = false })
  }).catch(() => {})
}

function handleLogout() { userStore.logout(); router.replace('/passport/login') }
</script>

<style lang="less" scoped>
// ===== Sidebar =====
.custom-sider {
  box-shadow: 2px 0 8px rgba(0,0,0,.06);
  transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1) !important;
  :deep(.ant-layout-sider-children) { overflow: hidden; }
}

.sidebar-inner { display: flex; height: 100%; overflow: hidden; }

// ===== Icon bar =====
.icon-bar {
  width: 68px; flex-shrink: 0;
  background: linear-gradient(180deg, #001529 0%, #002140 100%);
  display: flex; flex-direction: column; align-items: center;
  overflow-y: auto; overflow-x: hidden;
  &::-webkit-scrollbar { width: 0; }
}
.icon-logo {
  width: 42px; height: 42px; margin: 16px 0 20px;
  background: linear-gradient(135deg, rgba(214,137,59,.3), rgba(214,137,59,.1));
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; color: #d6893b; font-size: 16px; font-weight: 700;
  transition: all .2s;
  &:hover { background: linear-gradient(135deg, rgba(214,137,59,.5), rgba(214,137,59,.2)); }
}
.icon-list { flex: 1; display: flex; flex-direction: column; align-items: center; padding: 0 4px; }
.icon-item {
  width: 50px; height: 50px; margin: 3px 0;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  cursor: pointer; color: rgba(255,255,255,0.6); border-radius: 8px;
  transition: all 0.2s; font-size: 18px;
  &:hover { color: #fff; background: rgba(255,255,255,0.1); }
  &.active { color: #d6893b; background: rgba(214,137,59,0.18); }
  .icon-text { font-size: 11px; margin-top: 2px; white-space: nowrap; line-height: 1.2; }
}
.icon-collapse {
  width: 50px; height: 44px; margin: 4px 0 12px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer; color: rgba(255,255,255,0.45); border-radius: 8px;
  font-size: 15px; transition: all .2s;
  &:hover { color: #fff; background: rgba(255,255,255,0.1); }
}

// ===== Sub panel =====
.sub-panel {
  width: 158px; flex-shrink: 0;
  border-right: 1px solid #f0f0f0;
  background: #fafafa;
  overflow-y: auto; overflow-x: hidden;
  display: flex; flex-direction: column;
}
.sub-panel-fade-enter-active, .sub-panel-fade-leave-active { transition: all 0.2s ease; }
.sub-panel-fade-enter-from, .sub-panel-fade-leave-to { opacity: 0; transform: translateX(-8px); }

.sub-title {
  height: 48px; line-height: 48px; padding: 0 16px;
  font-size: 14px; font-weight: 600; color: #1a1a1a;
  border-bottom: 1px solid #f0f0f0;
  background: #fff;
}
.sub-list { padding: 6px 0; }
.sub-link {
  height: 40px; line-height: 40px; padding: 0 12px 0 18px;
  cursor: pointer; color: #595959; font-size: 14px;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  transition: all 0.15s; border-right: 2px solid transparent;
  &:hover { color: #d6893b; background: rgba(214,137,59,0.04); }
  &.active { color: #d6893b; background: rgba(214,137,59,0.06); border-right-color: #d6893b; font-weight: 500; }
}
.sub-group {
  .sub-group-hd {
    height: 40px; line-height: 40px; padding: 0 16px 0 16px;
    cursor: pointer; color: #434343; font-size: 14px; font-weight: 500;
    display: flex; justify-content: space-between; align-items: center;
    transition: all 0.15s;
    &:hover { color: #d6893b; background: rgba(214,137,59,0.04); }
    .anticon { font-size: 11px; transition: transform 0.2s; }
    &.folded .anticon { transform: rotate(-90deg); }
  }
  .sub-group-bd .sub-link { padding-left: 30px; font-size: 13px; }
}

// ===== Main =====
.main-layout { background: #f0f2f5; }
.main-header {
  flex-shrink: 0; z-index: 10;
  background: #fff; padding: 0 20px; height: 56px; line-height: 56px;
  display: flex; justify-content: space-between; align-items: center;
  box-shadow: 0 1px 4px rgba(0,0,0,.06);
  .hd-left { display: flex; align-items: center; gap: 12px; }
  .hd-right { display: flex; align-items: center; gap: 12px; }
  .collapse-btn { font-size: 16px; color: #666; padding: 0; width: 36px; height: 36px;
    &:hover { color: #d6893b; background: rgba(214,137,59,.06); }
  }
  .user-tag { cursor: pointer; display: flex; align-items: center; gap: 8px;
    .user-name { font-size: 13px; color: #333; }
  }
}
.main-content {
  flex: 1; overflow-y: auto;
  margin: 12px; padding: 20px;
  background: #fff; border-radius: 8px;
  box-shadow: 0 1px 2px rgba(0,0,0,.03);
  // 给 page-fade 离场中的页面当作定位参照（见 global.less）
  position: relative;
}

// ===== AI 面板（嵌入布局流，非 fixed 浮层） =====
.main-body {
  display: flex;
  flex: 1;
  min-height: 0;
}
.ai-panel {
  width: 380px;
  flex-shrink: 0;
  border-left: 1px solid #f0f0f0;
  background: #fff;
  overflow: hidden;
  position: relative;
}
.ai-panel-handle {
  position: absolute;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 18px;
  height: 56px;
  background: #f0f0f0;
  border: 1px solid #e0e0e0;
  border-right: none;
  border-radius: 6px 0 0 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: #999;
  font-size: 11px;
  z-index: 10;
  &:hover { color: #1890ff; background: #e6f7ff; }
}

// 面板收起时的侧边标签
.ai-panel-tab {
  position: fixed;
  right: 0;
  top: 50%;
  transform: translateY(-50%);
  width: 28px;
  padding: 12px 4px;
  background: #1890ff;
  color: #fff;
  border-radius: 6px 0 0 6px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  cursor: pointer;
  font-size: 12px;
  z-index: 100;
  box-shadow: -2px 0 8px rgba(24,144,255,.25);
  transition: all .2s;
  &:hover { width: 32px; background: #40a9ff; }
}

// ===== Page transitions =====
// 已移到 global.less —— Transition 现在挂在 TabPageView 渲染出的页面根节点上，
// scoped 属性对不上，写在这里不生效。
</style>
