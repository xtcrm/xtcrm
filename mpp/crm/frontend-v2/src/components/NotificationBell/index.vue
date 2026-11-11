<template>
  <a-dropdown :trigger="['click']" placement="bottomRight" :open="dropdownOpen" @open-change="onOpenChange">
    <span class="notify-bell" @click="dropdownOpen = !dropdownOpen">
      <a-badge :count="unreadCount" :number-style="{ backgroundColor: '#ff4d4f' }" :overflow-count="99">
        <bell-outlined :style="{ fontSize: 18, color: dropdownOpen ? '#d6893b' : '#666' }" />
      </a-badge>
    </span>
    <template #overlay>
      <div class="notify-dropdown">
        <div class="notify-hd">
          <span>消息通知</span>
          <a v-if="unreadCount > 0" @click="handleMarkAll">全部已读</a>
        </div>
        <div class="notify-list" v-if="notifications.length">
          <div
            v-for="item in notifications"
            :key="item.id"
            class="notify-item"
            :class="{ unread: !item.is_read }"
            @click="handleClick(item)"
          >
            <div class="ni-dot" v-if="!item.is_read"></div>
            <div class="ni-body">
              <div class="ni-title">{{ item.title }}</div>
              <div class="ni-content" v-if="item.content">{{ item.content }}</div>
              <div class="ni-time">{{ timeAgo(item.create_time) }}</div>
            </div>
          </div>
        </div>
        <div class="notify-empty" v-else>
          <a-empty :image="simpleImage" description="暂无消息" />
        </div>
      </div>
    </template>
  </a-dropdown>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { BellOutlined } from '@ant-design/icons-vue'
import { Empty } from 'ant-design-vue'
import { getUnread, markRead, markAllRead } from '@/api/notification'

const router = useRouter()
const dropdownOpen = ref(false)
const unreadCount = ref(0)
const notifications = ref([])
const simpleImage = Empty.PRESENTED_IMAGE_SIMPLE

let pollTimer = null

function onOpenChange(open) {
  dropdownOpen.value = open
  if (open) fetchNotifications()
}

async function fetchNotifications() {
  try {
    const res = await getUnread()
    if (res?.status === 200) {
      const data = res.data || {}
      notifications.value = data.list || []
      unreadCount.value = data.count || 0
    }
  } catch {}
}

async function handleClick(item) {
  dropdownOpen.value = false
  try { await markRead(item.id) } catch {}
  unreadCount.value = Math.max(0, unreadCount.value - 1)
  if (item.action_url) {
    router.push(item.action_url)
  }
}

async function handleMarkAll() {
  try { await markAllRead() } catch {}
  unreadCount.value = 0
  notifications.value.forEach(n => n.is_read = 1)
}

function timeAgo(ts) {
  if (!ts) return ''
  const diff = Math.floor(Date.now() / 1000 - ts)
  if (diff < 60) return '刚刚'
  if (diff < 3600) return Math.floor(diff / 60) + '分钟前'
  if (diff < 86400) return Math.floor(diff / 3600) + '小时前'
  if (diff < 604800) return Math.floor(diff / 86400) + '天前'
  return new Date(ts * 1000).toLocaleDateString()
}

onMounted(() => {
  fetchNotifications()
  pollTimer = setInterval(fetchNotifications, 60000)
})

onUnmounted(() => {
  clearInterval(pollTimer)
})
</script>

<style scoped>
.notify-bell {
  cursor: pointer; padding: 4px 8px; display: flex; align-items: center;
  transition: all .2s; border-radius: 4px;
}
.notify-bell:hover { background: rgba(214,137,59,.06); }

.notify-dropdown {
  width: 380px; max-height: 480px;
  background: #fff; border-radius: 8px;
  box-shadow: 0 6px 16px rgba(0,0,0,.12);
  overflow: hidden;
}

.notify-hd {
  display: flex; justify-content: space-between; align-items: center;
  padding: 14px 20px; border-bottom: 1px solid #f0f0f0;
  font-size: 15px; font-weight: 600; color: #1a1a1a;
}
.notify-hd a { font-size: 13px; font-weight: 400; cursor: pointer; }
.notify-hd a:hover { color: #d6893b; }

.notify-list { max-height: 400px; overflow-y: auto; }
.notify-item {
  display: flex; align-items: flex-start; padding: 12px 20px;
  cursor: pointer; transition: background .15s;
  border-bottom: 1px solid #fafafa;
}
.notify-item:hover { background: #fafcff; }
.notify-item.unread { background: #fdf5eb10; }

.ni-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: #ff4d4f; flex-shrink: 0; margin-top: 6px; margin-right: 10px;
}

.ni-body { flex: 1; min-width: 0; }
.ni-title { font-size: 13px; color: #333; line-height: 1.5; }
.notify-item.unread .ni-title { font-weight: 600; }
.ni-content {
  font-size: 12px; color: #999; margin-top: 2px;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
.ni-time { font-size: 11px; color: #bbb; margin-top: 4px; }

.notify-empty { padding: 40px 0; }
</style>
