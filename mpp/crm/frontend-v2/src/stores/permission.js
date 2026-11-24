import { defineStore } from 'pinia'
import { ref } from 'vue'
import { asyncRoutes } from '@/router/asyncRoutes'

export const usePermissionStore = defineStore('permission', () => {
  const menus = ref([])

  function hasPermission(permissions, route) {
    if (route.meta?.permission) {
      return route.meta.permission.some((p) =>
        permissions.includes(p) || permissions.includes(p.replace(/\//g, '.'))
      )
    }
    return false
  }

  function filterAsyncRoutes(routes, permissions) {
    const res = []
    for (const route of routes) {
      if (route.children) {
        const filteredChildren = filterAsyncRoutes(route.children, permissions)
        if (filteredChildren.length > 0 || hasPermission(permissions, route)) {
          const r = { ...route }
          if (filteredChildren.length > 0) r.children = filteredChildren
          res.push(r)
        }
      } else if (hasPermission(permissions, route)) {
        res.push({ ...route })
      }
    }
    return res
  }

  /** 命中未开通 skill 的菜单前缀 */
  function isDisabledRoute(route, prefixes) {
    const path = String(route?.path || '')
    return prefixes.some((p) => p !== '' && path.startsWith(p))
  }

  /**
   * 按未开通 skill 的前缀过滤静态路由。
   *
   * 超管原来直接拿全部 asyncRoutes，后端虽然会拦接口，但菜单还会露出来
   * （表现：点进去提示「XX 未开通」）。这里统一按后端下发的
   * roles.disabled_prefixes 过滤；分组下的子路由全被过滤时，分组本身也不显示。
   */
  function filterDisabledRoutes(routes, prefixes) {
    if (!prefixes || prefixes.length === 0) {
      return routes.map((r) => ({ ...r, children: r.children ? [...r.children] : undefined }))
    }
    const res = []
    for (const route of routes) {
      if (isDisabledRoute(route, prefixes)) continue
      if (route.children) {
        const children = filterDisabledRoutes(route.children, prefixes)
        if (children.length === 0) continue
        res.push({ ...route, children })
      } else {
        res.push({ ...route })
      }
    }
    return res
  }

  function buildMenus(roles) {
    const prefixes = Array.isArray(roles.disabled_prefixes) ? roles.disabled_prefixes : []
    if (roles.isSuper) {
      menus.value = filterDisabledRoutes(asyncRoutes, prefixes)
    } else {
      const permissions = roles.permissions.map((p) => p.permissionId)
      menus.value = filterDisabledRoutes(filterAsyncRoutes(asyncRoutes, permissions), prefixes)
    }
  }

  return { menus, buildMenus }
})
