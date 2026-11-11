import { defineStore } from 'pinia'
import { ref, computed, watch } from 'vue'

/**
 * 页签缓存名约定。
 *
 * TabPageView 会给每个页签包一层名字唯一的 wrapper 组件，用这个名字进
 * keep-alive 的 include —— 这样关掉单个页签时，Vue 只会淘汰那一个实例。
 * （keep-alive 的缓存 key 是 vnode.key，但 include 过滤只看组件名，
 *   所以不包 wrapper 就只能按真实组件名过滤，一关关一窝。）
 *
 * 注册页签和构建 wrapper 是两处代码，名字必须一致 —— 抽到这里做唯一来源，
 * 免得哪天改了一边忘了另一边，症状是"页签不缓存了"且极难查。
 */
export function tabCacheName(key, ver = 0) {
  // ver 是「刷新版本号」：双击刷新时 +1，缓存名跟着变。
  // KeepAlive 的 include 是按组件名匹配的，名字一变旧实例自然被淘汰、
  // 新名字没进过缓存 —— 等价于这一页重新 mount 一次。
  return 'tab:' + key + (ver > 0 ? '@' + ver : '')
}

function titleOf(route) {
  return route.meta?.title || String(route.name || route.path)
}

/**
 * 页签 key。
 *
 * 默认就是 fullPath —— 同一路由不同 query 要各开一个页签（不同客户详情）。
 *
 * 唯一的归一化：客户详情的 from=index 与不带 from 合并。
 *   后端 Customer::detail() 只有 `from === 'collab'` 一个分支，index/pool/空走同一条路；
 *   前端 canEdit / canRelease 等判定也把 'index' 与空视为等价。
 *   但两个入口写出的 URL 不同（今日拜访 → ?id=5，客户列表 → ?id=5&from=index），
 *   会开出两个标题一模一样的页签 —— 纯粹的重名，合并掉。
 *
 * from=pool（隐藏编辑/释放、显示认领）和 from=collab（后端要查协作权限）
 * 是真正不同的视图，保持区分。
 *
 * ⚠️ openTab 和 TabPageView 必须都走这个函数。两边的 key 一旦不一致，
 *    页签栏显示的是一组，keep-alive 缓存的又是另一组，会出现"关了页签缓存还在"之类
 *    极难查的问题。
 */
export function tabKeyOf(route) {
  if (route?.name === 'customer-detail' && route.query?.from === 'index') {
    const q = { ...route.query }
    delete q.from
    const qs = new URLSearchParams(q).toString()
    return route.path + (qs ? '?' + qs : '')
  }
  // 客户列表：首页漏斗用 ?funnel_stage= 跳进来，归到同一个「客户列表」页签。
  // 不归并的话，点几个阶段就多出几个同名页签，关起来很烦。
  // 筛选由列表页 onActivated 监听 query 应用，不依赖页签 key。
  if (route?.name === 'customer-list' && route.query?.funnel_stage) {
    const q = { ...route.query }
    delete q.funnel_stage
    const qs = new URLSearchParams(q).toString()
    return route.path + (qs ? '?' + qs : '')
  }
  return route.fullPath
}

export const useTabsStore = defineStore('tabs', () => {
  // sessionStorage 而不是 localStorage：页签是"这一次浏览会话"的东西，
  // 新开一个浏览器标签页应该从干净状态开始。
  const STORAGE_KEY = 'crm_tabs'
  const RESTORE_MAX = 30

  function safeStorage() {
    try {
      // Node 环境（_devtests 直接跑在 node 里）没有 sessionStorage；
      // 浏览器隐私模式下访问它也可能抛异常。整体兜住，不能让持久化拖垮 store。
      return typeof sessionStorage !== 'undefined' ? sessionStorage : null
    } catch { return null }
  }

  /**
   * 刷新后恢复页签列表。
   * 注意只能恢复"页签"，恢复不了 keep-alive 的缓存 —— 页面实例是内存里的东西。
   * 所以刷新后点回某个页签会重新加载一次，这是预期内的。
   */
  function loadPersisted() {
    try {
      const raw = safeStorage()?.getItem(STORAGE_KEY)
      const arr = raw ? JSON.parse(raw) : []
      if (!Array.isArray(arr)) return []
      return arr
        .filter((t) => t && t.key && t.title)
        .slice(-RESTORE_MAX)
    } catch { return [] }
  }

  /** @type {import('vue').Ref<Array<{key:string,path:string,title:string,closable:boolean,cacheable:boolean}>>} */
  const tabs = ref(loadPersisted())
  const activeKey = ref('')

  // 页签增删、以及详情页回填标题，都同步落盘（deep 是为了 setTitle 的改动也存下来）
  watch(tabs, (v) => {
    try { safeStorage()?.setItem(STORAGE_KEY, JSON.stringify(v)) } catch {}
  }, { deep: true })

  /** keep-alive 的 include 名单：只含可缓存的页签 */
  const keepAliveNames = computed(() =>
    tabs.value.filter((t) => t.cacheable).map((t) => tabCacheName(t.key, t.ver || 0))
  )

  /** 固定页签（工作台、今日拜访）——永远在，关不掉 */
  const affixTabs = computed(() => tabs.value.filter((t) => !t.closable))

  /**
   * 打开/激活一个页签。由 BasicLayout 里 watch(route) 调用。
   * 已存在就只激活，并保留既有标题（详情页可能已用 setTitle 回填成客户名）。
   */
  function openTab(route) {
    const key = tabKeyOf(route)
    activeKey.value = key

    const exist = tabs.value.find((t) => t.key === key)
    if (exist) {
      // meta.title 是静态的（详情页永远叫"客户详情"），不能覆盖回填过的标题
      if (!exist.title) exist.title = titleOf(route)
      return exist
    }

    const tab = {
      key,
      path: route.path,
      title: titleOf(route),
      closable: route.meta?.affix !== true,
      cacheable: route.meta?.keepAlive !== false,
      // 刷新版本号，见 tabCacheName()
      ver: 0,
    }
    tabs.value.push(tab)
    return tab
  }

  /**
   * 刷新一个页签（双击页签 / 右键菜单）：版本号 +1。
   *
   * 注意只改版本号、不动 key —— key 决定导航和去重，一动页签就变新页签了。
   * 真正让它重新挂载的是 TabPageView 里缓存名（组件名 + vnode key）跟着变：
   * 名字换了，KeepAlive 把它当另一个组件新建实例，旧实例随 include 变化被 prune。
   * 没有这个机制时，双击只能做到"重新 setHtml 一下数据"这种半吊子刷新。
   */
  function refreshTab(key) {
    const tab = tabs.value.find((t) => t.key === key)
    if (!tab) return false
    tab.ver = (tab.ver || 0) + 1
    return true
  }

  /** 详情页拿到数据后回填实体名，否则 5 个客户会显示 5 个"客户详情" */
  function setTitle(key, title) {
    const tab = tabs.value.find((t) => t.key === key)
    if (tab && title) tab.title = title
  }

  /**
   * 关闭一个页签。
   * @returns {string|null} 需要跳转到的 key；关的不是当前页、或关不掉时返回 null
   */
  function closeTab(key) {
    const i = tabs.value.findIndex((t) => t.key === key)
    if (i === -1) return null
    if (!tabs.value[i].closable) return null

    tabs.value.splice(i, 1)
    if (activeKey.value !== key) return null

    // 关掉的是当前页 —— 优先激活右边的，没有就左边的
    const next = tabs.value[i] || tabs.value[i - 1]
    activeKey.value = next ? next.key : ''
    return next ? next.key : '/index'
  }

  /**
   * 关闭其他（保留 key 和所有固定页签）。
   * @returns {string|null} 需要跳转到的 key
   */
  function closeOthers(key) {
    const keep = tabs.value.find((t) => t.key === key)
    tabs.value = tabs.value.filter((t) => !t.closable || t.key === key)
    if (!keep) return null
    if (activeKey.value === key) return null
    activeKey.value = key
    return key
  }

  /**
   * 关闭全部（只留固定页签）。
   * @returns {string|null} 需要跳转到的 key
   */
  function closeAll() {
    tabs.value = tabs.value.filter((t) => !t.closable)
    if (tabs.value.some((t) => t.key === activeKey.value)) return null
    const next = tabs.value[0]
    activeKey.value = next ? next.key : ''
    return next ? next.key : '/index'
  }

  /** 退出登录时清空 */
  function reset() {
    tabs.value = []
    activeKey.value = ''
  }

  return {
    tabs, activeKey, keepAliveNames, affixTabs,
    openTab, setTitle, refreshTab, closeTab, closeOthers, closeAll, reset,
  }
})
