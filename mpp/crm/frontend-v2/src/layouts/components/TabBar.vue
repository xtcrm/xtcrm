<template>
  <div class="tab-bar">
    <a-dropdown :trigger="['contextmenu']" placement="bottomLeft">
      <div ref="scrollRef" class="tb-scroll" role="tablist" aria-label="已打开的页面">
        <div
          v-for="t in tabsStore.tabs"
          :key="t.key"
          class="tb-item"
          :class="{ active: t.key === tabsStore.activeKey, refreshing: t.key === refreshingKey }"
          :data-key="t.key"
          role="tab"
          :aria-selected="t.key === tabsStore.activeKey"
          :tabindex="t.key === tabsStore.activeKey ? 0 : -1"
          @click="go(t)"
          @dblclick="refresh(t)"
          @keydown="onTabKeydown($event, t)"
          @contextmenu.prevent="ctxTab = t"
        >
          <span v-if="!t.closable" class="tb-pin" aria-hidden="true" title="固定页签" />
          <span class="tb-title" :title="t.title + '（双击刷新）'">{{ t.title }}</span>
          <!-- 不给 × 单独的 tabindex：Tab 键直接跳过它，用 Delete 关页签。
               否则键盘用户每换一个页签都要多按一次 Tab。 -->
          <close-outlined
            v-if="t.closable"
            class="tb-close"
            role="button"
            tabindex="-1"
            :aria-label="'关闭 ' + t.title"
            @click.stop="close(t)"
          />
        </div>
      </div>
      <template #overlay>
        <a-menu @click="onMenuClick">
          <a-menu-item key="close" :disabled="!ctxTab || !ctxTab.closable">
            <close-outlined /> 关闭
          </a-menu-item>
          <a-menu-item key="refresh" :disabled="!ctxTab">
            <reload-outlined /> 刷新
          </a-menu-item>
          <a-menu-item key="others" :disabled="!ctxTab">关闭其他</a-menu-item>
          <a-menu-item key="all">关闭全部</a-menu-item>
        </a-menu>
      </template>
    </a-dropdown>
  </div>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { CloseOutlined, ReloadOutlined } from '@ant-design/icons-vue'
import { useTabsStore } from '@/stores/tabs'

const route = useRoute()
const router = useRouter()
const tabsStore = useTabsStore()

const scrollRef = ref(null)
// 右键菜单作用在哪个页签上（空白处右键时沿用当前激活页签，避免菜单点了没反应）
const ctxTab = ref(null)

function go(tab) {
  ctxTab.value = tab
  if (tab.key !== route.fullPath) router.push(tab.key)
}

function nav(key) {
  if (key && key !== route.fullPath) router.push(key)
}

function close(tab) {
  nav(tabsStore.closeTab(tab.key))
}

// 双击刷新：只把页签的缓存版本 +1，TabPageView 会重建这一页的实例。
// 双击时浏览器已经先触发了两次 click（go 会先把该页签激活），所以这里不再导航。
const refreshingKey = ref('')
function refresh(tab) {
  if (!tab) return
  tabsStore.refreshTab(tab.key)
  refreshingKey.value = tab.key
  setTimeout(() => {
    if (refreshingKey.value === tab.key) refreshingKey.value = ''
  }, 500)
}

function onMenuClick({ key }) {
  const tab = ctxTab.value
  if (!tab) return
  if (key === 'close') nav(tabsStore.closeTab(tab.key))
  else if (key === 'refresh') refresh(tab)
  else if (key === 'others') nav(tabsStore.closeOthers(tab.key))
  else if (key === 'all') nav(tabsStore.closeAll())
}

function scrollIntoView(key) {
  const el = scrollRef.value?.querySelector(`[data-key="${CSS.escape(key)}"]`)
  el?.scrollIntoView({ block: 'nearest', inline: 'nearest' })
  return el
}

/**
 * 键盘操作。
 * 用的是 ARIA tabs 的漫游 tabindex 模式：整条页签栏只占一个 Tab 停顿，
 * 进入后用左右方向键在页签间移动。Home/End 跳首尾，Delete 关当前页签。
 * （没写 aria-controls / role=tabpanel：那需要给页面套一层 wrapper 容器，
 *   而多套一层 div 会影响页面自身的布局，收益不值这个风险。）
 */
function onTabKeydown(e, t) {
  const list = tabsStore.tabs
  const i = list.findIndex((x) => x.key === t.key)
  if (i < 0) return

  if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); go(t); return }
  if (e.key === 'Delete') { e.preventDefault(); close(t); return }

  let next
  if (e.key === 'ArrowRight') next = (i + 1) % list.length
  else if (e.key === 'ArrowLeft') next = (i - 1 + list.length) % list.length
  else if (e.key === 'Home') next = 0
  else if (e.key === 'End') next = list.length - 1
  else return

  e.preventDefault()
  const target = list[next]
  if (!target) return
  go(target)                       // 移动即激活（页面是缓存的，切换是瞬时的）
  nextTick(() => scrollIntoView(target.key)?.focus())
}

// 页签变多后激活项可能在视野外，切页签时滚进来
watch(() => tabsStore.activeKey, async (key) => {
  ctxTab.value = tabsStore.tabs.find((t) => t.key === key) || null
  await nextTick()
  scrollIntoView(key)
}, { immediate: true })
</script>

<style lang="less" scoped>
// 页签栏的视觉语言：克制的"文档标签"。
// 底栏比 header 略深一点点，让白色激活片"抬起"来 —— 不用边框，用投影 + 内描边，
// 这样不会撑动盒模型、也不会在高密度页签下糊成一片灰线。
.tab-bar {
  --tb-bar-bg: #fafafa;
  --tb-ink: #595959;
  --tb-ink-strong: #262626;
  --tb-accent: #d6893b;

  height: 40px;
  background: var(--tb-bar-bg);
  border-bottom: 1px solid #f0f0f0;
  flex-shrink: 0;
}

.tb-scroll {
  display: flex;
  align-items: center;
  gap: 6px;
  height: 100%;          // 撑满 40px 的栏，页签才会垂直居中
  // 12px 与下方 .main-content 的 margin:12px 对齐 —— 首个页签的左边和内容卡片
  // 的左边在同一条竖线上。这种对齐说不出来但看得出来。
  padding: 0 12px;
  overflow-x: auto;
  overflow-y: hidden;
  &::-webkit-scrollbar { height: 0; }
}

.tb-item {
  display: flex;
  align-items: center;
  gap: 7px;
  flex: 0 0 auto;
  max-width: 180px;
  height: 30px;
  padding: 0 12px;
  // 8px 与内容卡片、侧边栏图标的圆角同值，全站只有一套圆角语言
  border-radius: 8px;
  font-size: 13px;
  color: var(--tb-ink);
  cursor: pointer;
  transition: background .15s, color .15s, box-shadow .15s;

  &:hover {
    background: rgba(0, 0, 0, .045);
    color: var(--tb-ink-strong);
  }

  // 只在键盘操作时显示焦点环（鼠标点击不显示，否则每点一个页签都留个框）
  &:focus-visible {
    outline: 2px solid rgba(214, 137, 59, .55);
    outline-offset: 1px;
  }

  // 刷新时标题淡入一下，告诉用户"确实重载了"——页面本身可能肉眼无变化。
  // 动透明度而不是背景色：后者会和 .active 的白色底打架，闪完还留一帧跳变。
  &.refreshing .tb-title {
    animation: tb-refresh-fade .5s ease;
  }

  &.active {
    background: #fff;
    color: var(--tb-accent);
    font-weight: 500;
    // 三层：接地阴影压住边缘 + 暖金光晕把它和主题绑在一起 + 内描边当精确的边。
    // 用 box-shadow 而不是 border，是为了不改盒模型（否则激活时文字会位移 1px）。
    box-shadow:
      0 1px 2px rgba(0, 0, 0, .06),
      0 2px 8px rgba(214, 137, 59, .12),
      inset 0 0 0 1px rgba(214, 137, 59, .16);

    &:hover { background: #fff; }
  }
}

.tb-title {
  flex: 1;
  min-width: 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

// 固定页签（关不掉）用小金点标识 —— 比塞个图钉图标安静，也自然解释了为什么没有 ×
.tb-pin {
  flex: 0 0 auto;
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: var(--tb-accent);
  opacity: .45;
}

// 平时隐藏，鼠标进页签或页签激活时才浮现 —— 页签开到十几个也不至于满屏 ×
.tb-close {
  flex: 0 0 auto;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  font-size: 9px;
  color: #8c8c8c;
  opacity: 0;
  pointer-events: none;
  transition: opacity .15s, background .15s, color .15s;

  .tb-item:hover &,
  .tb-item.active & {
    opacity: 1;
    pointer-events: auto;
  }

  &:hover {
    background: rgba(0, 0, 0, .1);
    color: var(--tb-ink-strong);
  }
}

@keyframes tb-refresh-fade {
  0% { opacity: 0; }
  100% { opacity: 1; }
}

@media (prefers-reduced-motion: reduce) {
  .tb-item, .tb-close { transition: none; }
  .tb-item.refreshing .tb-title { animation: none; }
}
</style>
