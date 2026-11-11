<script>
/**
 * 页签级页面容器 —— 多页签方案的核心。
 *
 * ⚠️ 下面的形状是实测出来的，别凭直觉改。每条注释都对应一个踩过的坑。
 *    改动前后都跑一下 _devtests/（见其中的 README）：
 *      node _devtests/store.test.mjs
 *      node _devtests/keepalive.build.mjs && node _devtests/keepalive.run.mjs
 *    后者直接跑本文件的真实代码，15 条断言。
 *
 * 为什么必须放在 RouteView 这一层，而不能放在 BasicLayout：
 *   RouteView.vue 是所有分组路由【共用的同一个组件】，且只有一行 <router-view/>。
 *   所以 BasicLayout 里那个 <router-view> 渲染出来的其实是 RouteView，
 *   压根不是叶子页面 —— 在那一层做 keep-alive 根本够不到页面。
 *   这才是"点来点去一直重新加载"的根因。
 *
 * 本文件没有 <template>：逻辑全在渲染函数里，用模板表达不了
 * （要按条件决定加不加 key、包不包 wrapper，还得提前 return null）。
 */
import { defineComponent, h, KeepAlive, Transition } from 'vue'
import { RouterView } from 'vue-router'
import { useTabsStore, tabCacheName, tabKeyOf } from '@/stores/tabs'

// 每个页签一个名字唯一的 wrapper 组件。
//
// keep-alive 的缓存 key 取 vnode.key（我们传 fullPath），但 include 过滤只看
// 【组件名】。不包 wrapper 的话，所有客户详情共用一个组件名，关掉其中一个会把
// 全部客户一起清掉。有唯一名才能精确关单个页签。
//
// 名字来自 store 的 tabCacheName(key, ver)，必须与 keepAliveNames 同源。
// 名字里带刷新版本号：双击刷新时版本 +1 → 这里生成一个新组件类型，
// KeepAlive 缓存按组件名区分，于是旧实例被淘汰、这一页重新 mount。
// 这里刻意不做清理：wrapper 对象极小（每次刷新多一个），而删除后若同 key 重建
// 会产生新的组件类型，反而有踩到"类型跳变 → 实例重建"的风险。
// 真正的页面实例不会因此堆积 —— 旧缓存名不在 include 里，会立刻被 prune。
const wrapperCache = new Map()
function wrapperFor(cacheName) {
  if (!wrapperCache.has(cacheName)) {
    wrapperCache.set(cacheName, defineComponent({
      name: cacheName,
      props: { src: { type: Object, required: true } },
      // 通过 prop 接每帧新建的 vnode，不要在闭包里锁死旧的 ——
      // 那个 vnode 上挂着 vue-router 塞的 route props / ref / onVnodeUnmounted。
      setup: (props) => () => props.src,
    }))
  }
  return wrapperCache.get(cacheName)
}

export default defineComponent({
  name: 'TabPageView',
  setup() {
    const tabs = useTabsStore()

    return () => {
      return h(RouterView, {}, {
        default: ({ Component, route }) => {
          // 当前层级没匹配到组件时，vue-router 会故意用 Component: undefined 调用
          // slot（好让你渲染 fallback）。初始路由 '/' 下嵌套的 router-view 就是这个
          // 情况 —— 不是错误，直接渲染空。
          if (!Component) return null

          // ⚠️ slot 给的是 VNode，不是组件定义。组件定义在 .type 上。
          //    （vue-router 源码：const component = h(ViewComponent, {...})）
          const def = Component.type
          // key 必须和 store 的页签 key 同源（见 tabKeyOf 注释）
          const key = tabKeyOf(route)
          // 刷新版本号：读 store 里对应页签的 ver，双击刷新时它会 +1。
          // 这里必须真的读 tabs，渲染函数才和 ver 建立依赖，否则刷新不重绘。
          const ver = tabs.tabs.find((t) => t.key === key)?.ver || 0
          // 缓存名同时用作 wrapper 组件名和 vnode key。
          // ⚠️ vnode key 也必须带版本：KeepAlive 按 vnode key 查缓存，
          //    只改组件名不改 key 会命中旧缓存、把旧实例又贴回来。
          const cacheName = tabCacheName(key, ver)

          // 分组路由（RouteView 这类还有 children 的）不缓存、不注册页签，
          // 交给它内部的 TabPageView 去缓存真正的叶子页面。
          // 身份比较成立，是因为 vue-router 会把懒加载解析后的组件写回 record.components。
          const isGroup = !!route.matched
            .find((r) => r.components && r.components.default === def)
            ?.children?.length

          // ⚠️ 分组路由【绝对不能加 key】：加了之后同组内换页会把 RouteView 重建，
          //    连带毁掉它内部 KeepAlive 的整份缓存。
          const child = isGroup
            ? h(Component)
            : h(wrapperFor(cacheName), { key: cacheName, src: Component })

          // ⚠️ 树形必须恒定：分组和叶子都必须是 <KeepAlive><child/></KeepAlive>。
          //    如果给分组路由开"直接 return child"的捷径，同一位置上的组件类型会在
          //    KeepAlive 和 RouteView 之间跳变，Vue 会把整个 KeepAlive 实例卸载，
          //    缓存全丢（实测就是这么丢的）。
          //
          // ⚠️ Transition 不能加 mode="out-in"：out-in 配真正生效的 KeepAlive 会抛
          //    TypeError: Cannot read properties of null (reading '_')（Vue 内部
          //    updateSlots），SFC 编译出来的写法同样复现。默认模式正常。
          return h(Transition, { name: 'page-fade' }, {
            default: () => h(
              KeepAlive,
              // max 兜底：页签开太多时按 LRU 淘汰，防止详情页常驻把内存吃满
              { include: tabs.keepAliveNames, max: 20 },
              { default: () => child },
            ),
          })
        },
      })
    }
  },
})
</script>
