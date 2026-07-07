import type { ToastContainerOptions } from 'vue3-toastify'
import { createPinia } from 'pinia'
import piniaPluginPersistedstate from 'pinia-plugin-persistedstate'
import { createApp } from 'vue'
import Vue3Toastify from 'vue3-toastify'
import App from './App.vue'
import router from './router'
import 'vue3-toastify/dist/index.css'
import './assets/main.css'

const app = createApp(App)

// Pinia with persisted state
const pinia = createPinia()
pinia.use(piniaPluginPersistedstate)
app.use(pinia)

// Vue Router
app.use(router)

// Toast notifications
app.use(Vue3Toastify, {
  autoClose: 3000,
  position: 'top-right',
  theme: 'light',
} satisfies ToastContainerOptions)

app.mount('#app')
