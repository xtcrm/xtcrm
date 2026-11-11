import { createApp } from 'vue'
import { createPinia } from 'pinia'
import Antd from 'ant-design-vue'
import 'ant-design-vue/dist/reset.css'
import formCreate from '@form-create/ant-design-vue'
import FcDesigner from '@form-create/antd-designer'
import dayjs from 'dayjs'
import 'dayjs/locale/zh-cn'

import App from './App.vue'
import router from './router'
import './permission'
import './global.less'

dayjs.locale('zh-cn')

const app = createApp(App)
app.use(createPinia())
app.use(router)
app.use(Antd)
app.use(formCreate)
app.use(FcDesigner)
app.use(FcDesigner.formCreate)
app.mount('#app')