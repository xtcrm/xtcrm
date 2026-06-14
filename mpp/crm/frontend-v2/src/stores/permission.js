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

  function buildMenus(roles) {
    if (roles.isSuper) {
      menus.value = asyncRoutes.map((r) => ({
        ...r,
        children: r.children ? [...r.children] : undefined,
      }))
    } else {
      const permissions = roles.permissions.map((p) => p.permissionId)
      menus.value = filterAsyncRoutes(asyncRoutes, permissions)
    }
  }

  return { menus, buildMenus }
})
