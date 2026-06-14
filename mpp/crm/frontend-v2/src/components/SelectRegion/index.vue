<template>
  <a-cascader
    :value="modelValue"
    :options="options"
    :placeholder="placeholder"
    style="width: 100%"
    change-on-select
    @change="onChange"
  />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { tree as getRegionTree } from '@/api/region'

const REGION_CACHE_KEY = 'region_tree_v2'
const CACHE_TTL = 24 * 60 * 60 * 1000 // 24 hours

const props = defineProps({
  modelValue: { type: Array, default: () => [] },
  placeholder: { type: String, default: '请选择省市区' },
})

const emit = defineEmits(['update:modelValue', 'change'])

const options = ref([])

function onChange(value) {
  emit('update:modelValue', value)
  emit('change', value)
}

// Convert any list-like (array or PHP keyed object) to array
function toArray(list) {
  if (!list) return []
  return Array.isArray(list) ? list : Object.values(list)
}

// Convert PHP region tree (city/region keys) to cascader options (children key)
function formatOptions(regions) {
  if (!regions) return []
  const list = toArray(regions)
  return list.map(item => {
    const opt = { value: item.id, label: item.name }
    const kids = item.city || item.region
    if (kids) {
      opt.children = formatOptions(kids)
    }
    return opt
  })
}

// Find a region item by ID in the formatted options tree
function findRegion(list, id) {
  if (!list || !id) return null
  for (const item of toArray(list)) {
    if (item.value === id || item.id === id) return item
    if (item.children) {
      const found = findRegion(item.children, id)
      if (found) return found
    }
  }
  return null
}

// Convert cascader ID array to name strings { province, city, area }
function getNames(idArray) {
  const result = { province: '', city: '', area: '' }
  if (!idArray || !idArray.length) return result
  const [provId, cityId, areaId] = idArray
  const prov = findRegion(options.value, provId)
  if (prov) {
    result.province = prov.label || prov.name || ''
    if (cityId && prov.children) {
      const city = findRegion(prov.children, cityId)
      if (city) {
        result.city = city.label || city.name || ''
        if (areaId && city.children) {
          const area = findRegion(city.children, areaId)
          if (area) result.area = area.label || area.name || ''
        }
      }
    }
  }
  return result
}

// Convert name strings { province, city, area } to cascader ID array
function findRegionId(tree, name) {
  if (!name) return null
  for (const item of toArray(tree)) {
    const itemName = item.name || item.label || item.title
    if (itemName === name) return item.value ?? item.id
    const kids = item.children || item.city || item.region
    if (kids) {
      const found = findRegionId(toArray(kids), name)
      if (found) return found
    }
  }
  return null
}

function getIds(names) {
  if (!names) return []
  const { province, city, area } = names
  const ids = []
  if (province) {
    const provId = findRegionId(options.value, province)
    if (provId) {
      ids.push(provId)
      const prov = findRegion(options.value, provId)
      if (prov?.children && city) {
        const cityId = findRegionId(prov.children, city)
        if (cityId) {
          ids.push(cityId)
          const cityNode = findRegion(prov.children, cityId)
          if (cityNode?.children && area) {
            const areaId = findRegionId(cityNode.children, area)
            if (areaId) ids.push(areaId)
          }
        }
      }
    }
  }
  return ids
}

function loadRegions() {
  // Check localStorage cache first
  try {
    const cached = localStorage.getItem(REGION_CACHE_KEY)
    if (cached) {
      const { data, timestamp } = JSON.parse(cached)
      if (Date.now() - timestamp < CACHE_TTL) {
        options.value = formatOptions(data)
        return
      }
    }
  } catch (e) {
    // ignore cache errors
  }

  // Fetch from API
  getRegionTree().then(res => {
    const list = res?.data?.list || []
    options.value = formatOptions(list)
    try {
      localStorage.setItem(REGION_CACHE_KEY, JSON.stringify({
        data: list,
        timestamp: Date.now(),
      }))
    } catch (e) {
      // ignore storage errors
    }
  }).catch(err => {
    console.error('加载地区数据失败:', err)
  })
}

onMounted(() => {
  loadRegions()
})

defineExpose({
  options,
  getNames,
  getIds,
  findRegion,
  findRegionId,
  toArray,
})
</script>
