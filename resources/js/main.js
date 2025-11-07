import { createApp } from 'vue'
import App from './App.vue'
import router from './router.js'
import axios from 'axios'

// Configure axios
axios.defaults.baseURL = '/api'
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

// Add axios interceptor for admin token
axios.interceptors.request.use(
  config => {
    const token = localStorage.getItem('admin_token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  error => {
    return Promise.reject(error)
  }
)

const app = createApp(App)
app.use(router)
app.mount('#app')
