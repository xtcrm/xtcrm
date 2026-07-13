<template>
  <div class="login-wrap">
    <div class="login-card">
      <div class="card-header">
        <div class="logo-mark">
          <svg width="28" height="28" viewBox="0 0 28 28"><rect x="2" y="2" width="24" height="24" rx="5" fill="none" stroke="#d6893b" stroke-width="1.5" opacity=".6"/><circle cx="14" cy="14" r="5" fill="none" stroke="#d6893b" stroke-width="2"/><line x1="14" y1="2" x2="14" y2="9" stroke="#d6893b" stroke-width="1" opacity=".5"/><line x1="14" y1="19" x2="14" y2="26" stroke="#d6893b" stroke-width="1" opacity=".5"/><line x1="2" y1="14" x2="9" y2="14" stroke="#d6893b" stroke-width="1" opacity=".5"/><line x1="19" y1="14" x2="26" y2="14" stroke="#d6893b" stroke-width="1" opacity=".5"/></svg>
        </div>
        <h1>管理平台</h1>
        <p>制造业客户管理系统</p>
      </div>

      <a-form :model="formState" :rules="rules" @finish="handleLogin" autocomplete="off">
        <a-form-item name="username">
          <a-input v-model:value="formState.username" size="large" placeholder="用户名">
            <template #prefix><user-outlined /></template>
          </a-input>
        </a-form-item>
        <a-form-item name="password">
          <a-input-password v-model:value="formState.password" size="large" placeholder="密码" @keyup.enter="handleLogin">
            <template #prefix><lock-outlined /></template>
          </a-input-password>
        </a-form-item>
        <a-form-item style="margin-bottom:0">
          <a-button type="primary" html-type="submit" size="large" :loading="loading" block>登 录</a-button>
        </a-form-item>
      </a-form>

      <div class="card-line"></div>
    </div>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { message } from 'ant-design-vue'
import { UserOutlined, LockOutlined } from '@ant-design/icons-vue'
import { useUserStore } from '@/stores/user'

const router = useRouter()
const route = useRoute()
const userStore = useUserStore()
const loading = ref(false)

const formState = reactive({ username: '', password: '' })

const rules = {
  username: [{ required: true, message: '请输入用户名' }],
  password: [{ required: true, message: '请输入密码' }],
}

async function handleLogin() {
  if (loading.value) return
  loading.value = true
  try {
    const ok = await userStore.loginAction(formState.username, formState.password)
    if (ok) { message.success('登录成功'); await router.replace((route.query.redirect || '/').toString()) }
    else { message.error('用户名或密码错误') }
  } catch { message.error('登录失败，请重试') }
  finally { loading.value = false }
}
</script>

<style lang="less" scoped>
.login-wrap { position: relative; z-index: 1; }

.login-card {
  width: 400px; padding: 40px 36px 32px;
  background: rgba(16,20,28,.85); border: 1px solid rgba(214,137,59,.12);
  border-radius: 16px;
  backdrop-filter: blur(20px);
  box-shadow: 0 8px 40px rgba(0,0,0,.4), 0 0 0 1px rgba(214,137,59,.06) inset;
  position: relative; overflow: hidden;
}
.login-card::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0; height: 1px;
  background: linear-gradient(90deg, transparent, rgba(214,137,59,.3), transparent);
}

.card-header { text-align: center; margin-bottom: 28px; }
.logo-mark { margin-bottom: 16px; }
.card-header h1 { font-size: 22px; font-weight: 700; color: #e8d5b0; margin: 0; letter-spacing: 4px; }
.card-header p { font-size: 12px; color: rgba(232,213,176,.35); margin: 8px 0 0; letter-spacing: 2px; }

:deep(.ant-form-item) { margin-bottom: 16px; }
:deep(.ant-input-affix-wrapper) {
  background: rgba(255,255,255,.03); border: 1px solid rgba(255,255,255,.06);
  border-radius: 6px; color: #c8bfa8; transition: all .25s;
  &:hover { border-color: rgba(214,137,59,.25); background: rgba(255,255,255,.05); }
  &.ant-input-affix-wrapper-focused { border-color: rgba(214,137,59,.5); background: rgba(255,255,255,.06); box-shadow: 0 0 0 3px rgba(214,137,59,.08); }
  input { background: transparent; color: #c8bfa8; &::placeholder { color: rgba(200,191,168,.25); } }
}
:deep(.ant-input-prefix) { color: rgba(214,137,59,.4); margin-right: 10px; }
:deep(.ant-input-suffix) { color: rgba(255,255,255,.15); }

:deep(.ant-btn-primary) {
  height: 44px; border-radius: 6px; font-size: 15px; font-weight: 600;
  letter-spacing: 6px; background: linear-gradient(135deg, #d6893b, #b87326);
  border: none; box-shadow: 0 2px 8px rgba(214,137,59,.25);
  transition: all .25s;
  &:hover { transform: translateY(-1px); box-shadow: 0 4px 16px rgba(214,137,59,.35); }
}

.card-line {
  margin-top: 28px; height: 1px;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,.04), transparent);
}
</style>
