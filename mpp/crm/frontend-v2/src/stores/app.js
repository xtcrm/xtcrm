import { defineStore } from 'pinia'
import { ref, watch } from 'vue'

export const useAppStore = defineStore('app', () => {
  const collapsed = ref(false)
  const mobile = ref(window.innerWidth < 768)
  const theme = ref('light')

  function toggleCollapsed() {
    collapsed.value = !collapsed.value
  }

  window.addEventListener('resize', () => {
    mobile.value = window.innerWidth < 768
  })

  return { collapsed, mobile, theme, toggleCollapsed }
})
