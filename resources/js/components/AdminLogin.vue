<template>
  <div class="admin-login">
    <div class="login-container">
      <div class="login-card">
        <div class="login-header">
          <h1 class="login-title">دخول الإدارة</h1>
          <p class="login-subtitle">يرجى إدخال كلمة المرور للوصول إلى لوحة التحكم</p>
        </div>

        <form @submit.prevent="login" class="login-form">
          <div class="form-group">
            <label for="password">كلمة المرور</label>
            <input
              id="password"
              v-model="password"
              type="password"
              required
              class="form-input"
              placeholder="أدخل كلمة المرور"
              :disabled="isLoading"
            >
          </div>

          <button
            type="submit"
            class="login-btn"
            :disabled="isLoading"
          >
            <span v-if="isLoading">
              <i class="fas fa-spinner fa-spin"></i>
              جاري تسجيل الدخول...
            </span>
            <span v-else>دخول</span>
          </button>
        </form>

        <div v-if="errorMessage" class="error-message">
          <i class="fas fa-exclamation-triangle"></i>
          {{ errorMessage }}
        </div>

        <div class="back-link">
          <router-link to="/" class="back-btn">
            <i class="fas fa-arrow-right"></i>
            العودة لصفحة التبرع
          </router-link>
        </div>
      </div>

      <!-- Decorative elements -->
      <div class="decorative-elements">
        <div class="floating-shape shape-1"></div>
        <div class="floating-shape shape-2"></div>
        <div class="floating-shape shape-3"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'

const router = useRouter()
const password = ref('')
const isLoading = ref(false)
const errorMessage = ref('')

const login = async () => {
  if (!password.value.trim()) return

  isLoading.value = true
  errorMessage.value = ''

  try {
    const response = await axios.post('/admin/login', {
      password: password.value
    })

    // Store token in localStorage
    localStorage.setItem('admin_token', response.data.token)

    // Redirect to dashboard
    router.push('/admin/dashboard')

  } catch (error) {
    console.error('Login error:', error)
    if (error.response?.status === 401) {
      errorMessage.value = 'كلمة المرور غير صحيحة'
    } else {
      errorMessage.value = 'حدث خطأ في تسجيل الدخول. يرجى المحاولة مرة أخرى.'
    }
  } finally {
    isLoading.value = false
  }
}
</script>

<style scoped>
.admin-login {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  position: relative;
  overflow: hidden;
}

.login-container {
  position: relative;
  z-index: 2;
}

.login-card {
  background: rgba(255, 255, 255, 0.95);
  border-radius: 20px;
  padding: 40px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
  backdrop-filter: blur(10px);
  width: 100%;
  max-width: 400px;
  animation: slideInUp 0.6s ease-out;
}

.login-header {
  text-align: center;
  margin-bottom: 30px;
}

.login-title {
  font-size: 2.5rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0 0 10px 0;
}

.login-subtitle {
  color: #4a5568;
  margin: 0;
  line-height: 1.6;
}

.login-form {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

.form-group {
  display: flex;
  flex-direction: column;
}

.form-group label {
  font-weight: 600;
  color: #4a5568;
  margin-bottom: 8px;
}

.form-input {
  padding: 15px 20px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.form-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.form-input:disabled {
  background: #f7fafc;
  cursor: not-allowed;
}

.login-btn {
  padding: 18px 30px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-top: 10px;
}

.login-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}

.login-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.error-message {
  background: #fed7d7;
  color: #c53030;
  padding: 15px 20px;
  border-radius: 12px;
  margin-top: 20px;
  text-align: center;
  border: 1px solid #feb2b2;
  animation: shake 0.5s ease-in-out;
}

.error-message i {
  margin-left: 8px;
}

.back-link {
  text-align: center;
  margin-top: 30px;
}

.back-btn {
  color: #667eea;
  text-decoration: none;
  font-weight: 500;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.back-btn:hover {
  color: #5a67d8;
  transform: translateX(-3px);
}

/* Decorative elements */
.decorative-elements {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  pointer-events: none;
  z-index: 1;
}

.floating-shape {
  position: absolute;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.1);
  animation: float 6s ease-in-out infinite;
}

.shape-1 {
  width: 100px;
  height: 100px;
  top: 10%;
  left: 10%;
  animation-delay: 0s;
}

.shape-2 {
  width: 60px;
  height: 60px;
  top: 20%;
  right: 15%;
  animation-delay: 2s;
}

.shape-3 {
  width: 80px;
  height: 80px;
  bottom: 15%;
  left: 20%;
  animation-delay: 4s;
}

/* Animations */
@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes float {
  0%, 100% {
    transform: translateY(0px);
  }
  50% {
    transform: translateY(-20px);
  }
}

@keyframes shake {
  0%, 100% {
    transform: translateX(0);
  }
  25% {
    transform: translateX(-5px);
  }
  75% {
    transform: translateX(5px);
  }
}

/* Responsive Design */
@media (max-width: 480px) {
  .login-card {
    padding: 30px 20px;
    margin: 0 10px;
  }

  .login-title {
    font-size: 2rem;
  }

  .login-btn {
    width: 100%;
  }
}
</style>
