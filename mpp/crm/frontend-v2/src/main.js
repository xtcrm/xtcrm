import { createApp } from 'vue'
import { createPinia } from 'pinia'
import Antd from 'ant-design-vue'
import 'ant-design-vue/dist/reset.css'
import formCreate from '@form-create/ant-design-vue'
import FcDesigner from '@form-create/antd-designer'

import App from './App.vue'
import router from './router'
import './permission'
import './global.less'

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.use(Antd)
app.use(formCreate)
app.use(FcDesigner)
app.use(FcDesigner.formCreate)
app.mount('#app')