<template>
  <div class="admin-dashboard">
    <header class="dashboard-header">
      <div class="header-content">
        <h1 class="dashboard-title">لوحة تحكم الإدارة</h1>
        <button @click="logout" class="logout-btn">
          <i class="fas fa-sign-out-alt"></i>
          خروج
        </button>
      </div>
    </header>

    <nav class="dashboard-nav">
      <button
        v-for="tab in tabs"
        :key="tab.id"
        @click="activeTab = tab.id"
        class="nav-btn"
        :class="{ active: activeTab === tab.id }"
      >
        <i :class="tab.icon"></i>
        {{ tab.label }}
      </button>
    </nav>

    <main class="dashboard-main">
      <!-- Donators Tab -->
      <div v-if="activeTab === 'donators'" class="tab-content">
        <div class="tab-header">
          <h2>إدارة المتبرعين</h2>
          <div class="header-actions">
            <button @click="showImportModal = true" class="action-btn primary">
              <i class="fas fa-upload"></i>
              استيراد من Excel/CSV
            </button>
            <button @click="showAddDonatorModal = true" class="action-btn secondary">
              <i class="fas fa-plus"></i>
              إضافة متبرع جديد
            </button>
          </div>
        </div>

        <div class="donators-table">
          <div class="table-header">
            <input
              v-model="donatorsSearch"
              type="text"
              placeholder="البحث في المتبرعين..."
              class="table-search"
            >
          </div>

          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>الاسم</th>
                  <th>رقم الهاتف</th>
                  <th>الإجراءات</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="donator in filteredDonators" :key="donator.id">
                  <td>{{ donator.name }}</td>
                  <td>{{ donator.phone }}</td>
                  <td>
                    <button @click="editDonator(donator)" class="action-btn small" title="تعديل البيانات">
                      <i class="fas fa-edit"></i>
                    </button>
                    <button @click="deleteDonator(donator)" class="action-btn small danger" title="حذف">
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="donatorsPagination.last_page > 1" class="pagination">
          <button
            @click="changePage(donatorsPagination.current_page - 1)"
            :disabled="donatorsPagination.current_page <= 1"
            class="page-btn"
          >
            <i class="fas fa-chevron-right"></i>
            السابق
          </button>

          <span class="page-info">
            الصفحة {{ donatorsPagination.current_page }} من {{ donatorsPagination.last_page }}
            (إجمالي {{ donatorsPagination.total }} متبرع)
          </span>

          <button
            @click="changePage(donatorsPagination.current_page + 1)"
            :disabled="donatorsPagination.current_page >= donatorsPagination.last_page"
            class="page-btn"
          >
            التالي
            <i class="fas fa-chevron-left"></i>
          </button>
        </div>
      </div>



      <!-- Settings Tab -->
      <div v-if="activeTab === 'settings'" class="tab-content">
        <div class="tab-header">
          <h2>الإعدادات العامة</h2>
        </div>

        <div class="settings-section">
          <div class="setting-card">
            <div class="setting-header">
              <h3>رقم التبرع العام</h3>
              <p>هذا الرقم الذي يظهر لجميع المتبرعين عند تأكيد التبرع</p>
            </div>

            <div class="setting-content">
              <div class="current-setting">
                <label>الرقم الحالي:</label>
                <span class="current-value">{{ originalGlobalDonationNumber || 'لم يتم تعيين رقم بعد' }}</span>
              </div>

              <form @submit.prevent="saveGlobalDonationNumber" class="setting-form">
                <div class="form-group">
                  <label for="globalDonationNumber">رقم التبرع الجديد</label>
                  <input
                    id="globalDonationNumber"
                    v-model="globalDonationNumber"
                    type="text"
                    required
                    placeholder="مثال: 01205100202"
                    class="form-input"
                    :class="{ 'error': globalDonationNumberError }"
                  >
                  <small class="form-hint">أدخل رقم الهاتف أو الحساب البنكي الذي يتلقى التبرعات</small>
                  <div v-if="globalDonationNumberError" class="error-message">
                    {{ globalDonationNumberError }}
                  </div>
                </div>

                <div class="setting-actions">
                  <button
                    type="submit"
                    class="btn primary"
                    :disabled="isSavingGlobalDonationNumber || globalDonationNumber === originalGlobalDonationNumber"
                  >
                    <span v-if="isSavingGlobalDonationNumber">
                      <i class="fas fa-spinner fa-spin"></i>
                      جاري الحفظ...
                    </span>
                    <span v-else>حفظ التغييرات</span>
                  </button>
                  <button
                    type="button"
                    @click="resetGlobalDonationNumber"
                    class="btn secondary"
                    :disabled="globalDonationNumber === originalGlobalDonationNumber"
                  >
                    إعادة تعيين
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- Donations Tab -->
      <div v-if="activeTab === 'donations'" class="tab-content">
        <div class="tab-header">
          <h2>سجل التبرعات</h2>
          <div class="header-actions">
            <select v-model="donationsFilter" class="filter-select">
              <option value="">جميع التبرعات</option>
              <option value="pending">في الانتظار</option>
              <option value="confirmed">مؤكدة</option>
              <option value="completed">مكتملة</option>
            </select>
          </div>
        </div>

        <div class="donations-table">
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>المتبرع</th>
                  <th>المبلغ</th>
                  <th>رقم الهاتف</th>
                  <th>الحالة</th>
                  <th>التاريخ</th>
                  <th>الإجراءات</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="donation in filteredDonations" :key="donation.id">
                  <td>{{ donation.donator.name }}</td>
                  <td class="amount">{{ donation.amount }} جنيه</td>
                  <td>{{ donation.donor_phone_number }}</td>
                  <td>
                    <span class="status-badge" :class="donation.status">
                      {{ getStatusText(donation.status) }}
                    </span>
                  </td>
                  <td>{{ formatDate(donation.created_at) }}</td>
                  <td>
                    <button
                      v-if="donation.status === 'pending'"
                      @click="updateDonationStatus(donation, 'confirmed')"
                      class="action-btn small success"
                    >
                      تأكيد
                    </button>
                    <button
                      v-if="donation.status === 'confirmed'"
                      @click="updateDonationStatus(donation, 'completed')"
                      class="action-btn small primary"
                    >
                      اكتمال
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Statistics Tab -->
      <div v-if="activeTab === 'stats'" class="tab-content">
        <div class="tab-header">
          <h2>الإحصائيات</h2>
        </div>

        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
              <h3>{{ stats.totalDonators }}</h3>
              <p>إجمالي المتبرعين</p>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-hand-holding-heart"></i>
            </div>
            <div class="stat-content">
              <h3>{{ stats.totalDonations }}</h3>
              <p>إجمالي التبرعات</p>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-coins"></i>
            </div>
            <div class="stat-content">
              <h3>{{ stats.totalAmount }} جنيه</h3>
              <p>إجمالي المبالغ</p>
            </div>
          </div>

          <div class="stat-card">
            <div class="stat-icon">
              <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
              <h3>{{ stats.pendingDonations }}</h3>
              <p>تبرعات في الانتظار</p>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Import Modal -->
    <div v-if="showImportModal" class="modal" @click="showImportModal = false">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>استيراد المتبرعين من Excel/CSV</h3>
          <button @click="showImportModal = false" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body">
          <div class="file-upload">
            <input
              ref="fileInput"
              type="file"
              accept=".xlsx,.xls,.csv"
              @change="handleFileSelect"
              class="file-input"
            >
            <div class="file-upload-area" @click="$refs.fileInput.click()">
              <i class="fas fa-cloud-upload-alt"></i>
              <p v-if="!selectedFile">انقر لاختيار ملف Excel أو CSV</p>
              <p v-else>{{ selectedFile.name }}</p>
            </div>
          </div>

          <div class="import-info">
            <h4>تنسيق الملف المطلوب:</h4>
            <p><strong>الملفات المدعومة:</strong> Excel (.xlsx, .xls) أو CSV</p>
            <ul>
              <li>العمود الأول: التاريخ (سيتم تجاهله)</li>
              <li>العمود الثاني: الاسم</li>
              <li>العمود الثالث: رقم الموبايل والواتساب</li>
            </ul>
            <p><strong>كيفية التعرف على الأعمدة:</strong></p>
            <ul>
              <li><strong>الاسم:</strong> أي عمود يحتوي على كلمة "اسم" أو "name"</li>
              <li><strong>الهاتف:</strong> أي عمود يحتوي على "هاتف" أو "موبايل" أو "phone"</li>
            </ul>
          </div>
        </div>

        <div class="modal-footer">
          <button @click="importFile" class="btn primary" :disabled="!selectedFile || isImporting">
            <span v-if="isImporting">
              <i class="fas fa-spinner fa-spin"></i>
              جاري الاستيراد...
            </span>
            <span v-else>استيراد</span>
          </button>
          <button @click="showImportModal = false" class="btn secondary">إلغاء</button>
        </div>
      </div>
    </div>

    <!-- Add/Edit Donator Modal -->
    <div v-if="showAddDonatorModal || editingDonator" class="modal" @click="closeDonatorModal">
      <div class="modal-content" @click.stop>
        <div class="modal-header">
          <h3>{{ editingDonator ? 'تعديل المتبرع' : 'إضافة متبرع جديد' }}</h3>
          <button @click="closeDonatorModal" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <form @submit.prevent="saveDonator" class="modal-body">
          <div class="form-group">
            <label for="donatorName">الاسم</label>
            <input
              id="donatorName"
              v-model="donatorForm.name"
              type="text"
              required
              class="form-input"
            >
          </div>

          <div class="form-group">
            <label for="donatorPhone">رقم الهاتف</label>
            <input
              id="donatorPhone"
              v-model="donatorForm.phone"
              type="tel"
              required
              class="form-input"
            >
          </div>

          <div class="modal-footer">
            <button type="submit" class="btn primary" :disabled="isSaving">
              <span v-if="isSaving">
                <i class="fas fa-spinner fa-spin"></i>
                حفظ...
              </span>
              <span v-else>حفظ</span>
            </button>
            <button type="button" @click="closeDonatorModal" class="btn secondary">إلغاء</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script setup>
import axios from 'axios'
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// Navigation
const activeTab = ref('donators')
const tabs = [
  { id: 'donators', label: 'المتبرعين', icon: 'fas fa-users' },
  { id: 'donations', label: 'التبرعات', icon: 'fas fa-hand-holding-heart' },
  { id: 'settings', label: 'الإعدادات', icon: 'fas fa-cogs' },
  { id: 'stats', label: 'الإحصائيات', icon: 'fas fa-chart-bar' }
]

// Data
const donators = ref([])
const donations = ref([])
const donatorsPagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0
})
const stats = ref({
  totalDonators: 0,
  totalDonations: 0,
  totalAmount: 0,
  pendingDonations: 0
})

// Filters and search
const donatorsSearch = ref('')
const donationsFilter = ref('')


// Modals
const showImportModal = ref(false)
const showAddDonatorModal = ref(false)
const editingDonator = ref(null)

// Forms
const selectedFile = ref(null)
const isImporting = ref(false)
const isSaving = ref(false)
const donatorForm = ref({
  name: '',
  phone: ''
})

// Computed
const filteredDonators = computed(() => {
  if (!donatorsSearch.value) return donators.value
  return donators.value.filter(donator =>
    donator.name.toLowerCase().includes(donatorsSearch.value.toLowerCase()) ||
    donator.phone.includes(donatorsSearch.value)
  )
})

const filteredDonations = computed(() => {
  if (!donationsFilter.value) return donations.value
  return donations.value.filter(donation => donation.status === donationsFilter.value)
})

// Methods
const logout = async () => {
  try {
    await axios.post('/admin/logout')
  } catch (error) {
    console.error('Logout error:', error)
  } finally {
    localStorage.removeItem('admin_token')
    router.push('/admin')
  }
}

const loadData = async (page = 1) => {
  try {
    const [donatorsRes, donationsRes] = await Promise.all([
      axios.get(`/admin/donators?page=${page}`),
      axios.get('/admin/donations')
    ])

    // Handle paginated data properly
    donators.value = donatorsRes.data.data || []
    donations.value = donationsRes.data.data || []

    // Store pagination info
    donatorsPagination.value = {
      current_page: donatorsRes.data.current_page || 1,
      last_page: donatorsRes.data.last_page || 1,
      total: donatorsRes.data.total || 0
    }

    // Calculate stats using total counts from API response
    stats.value = {
      totalDonators: donatorsRes.data.total || donators.value.length,
      totalDonations: donationsRes.data.total || donations.value.length,
      totalAmount: donations.value.reduce((sum, d) => sum + parseFloat(d.amount || 0), 0),
      pendingDonations: donations.value.filter(d => d.status === 'pending').length
    }
  } catch (error) {
    console.error('Load data error:', error)
    if (error.response?.status === 401) {
      // Redirect to login if not authenticated
      router.push('/admin')
    }
    // Set empty arrays on error
    donators.value = []
    donations.value = []
    donatorsPagination.value = {
      current_page: 1,
      last_page: 1,
      total: 0
    }
    stats.value = {
      totalDonators: 0,
      totalDonations: 0,
      totalAmount: 0,
      pendingDonations: 0
    }
  }
}

const handleFileSelect = (event) => {
  selectedFile.value = event.target.files[0]
}

const changePage = async (page) => {
  if (page >= 1 && page <= donatorsPagination.value.last_page) {
    await loadData(page)
  }
}


const loadGlobalDonationNumber = async () => {
  try {
    const response = await axios.get('/admin/settings/global-donation-number')
    globalDonationNumber.value = response.data.global_donation_number
    originalGlobalDonationNumber.value = response.data.global_donation_number
  } catch (error) {
    console.error('Load global donation number error:', error)
  }
}

const saveGlobalDonationNumber = async () => {
  if (!globalDonationNumber.value.trim()) return

  isSavingGlobalDonationNumber.value = true
  globalDonationNumberError.value = ''

  try {
    await axios.put('/admin/settings/global-donation-number', {
      global_donation_number: globalDonationNumber.value.trim()
    })

    originalGlobalDonationNumber.value = globalDonationNumber.value
    alert('تم تحديث رقم التبرع العام بنجاح')
  } catch (error) {
    console.error('Save global donation number error:', error)
    if (error.response?.data?.errors?.global_donation_number) {
      globalDonationNumberError.value = error.response.data.errors.global_donation_number[0]
    } else if (error.response?.data?.message) {
      globalDonationNumberError.value = error.response.data.message
    } else {
      globalDonationNumberError.value = 'حدث خطأ في تحديث رقم التبرع العام'
    }
  } finally {
    isSavingGlobalDonationNumber.value = false
  }
}

const resetGlobalDonationNumber = () => {
  globalDonationNumber.value = originalGlobalDonationNumber.value
  globalDonationNumberError.value = ''
}



const importFile = async () => {
  if (!selectedFile.value) return

  isImporting.value = true
  const formData = new FormData()
  formData.append('file', selectedFile.value)

  try {
    const response = await axios.post('/admin/import/donators', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })

    alert(`تم استيراد ${response.data.imported_count} متبرع بنجاح`)
    showImportModal.value = false
    selectedFile.value = null
    loadData(donatorsPagination.value.current_page)
  } catch (error) {
    console.error('Import error:', error)
    let errorMessage = 'حدث خطأ في الاستيراد'

    if (error.response?.data?.message) {
      errorMessage = error.response.data.message
    }

    if (error.response?.data?.errors) {
      const errors = Object.values(error.response.data.errors).flat()
      errorMessage += '\n\n' + errors.join('\n')
    }

    if (error.response?.data?.file_info) {
      errorMessage += '\n\nمعلومات الملف:'
      errorMessage += '\nالاسم: ' + error.response.data.file_info.name
      errorMessage += '\nالحجم: ' + error.response.data.file_info.size + ' bytes'
      errorMessage += '\nالنوع: ' + error.response.data.file_info.mime
    }

    alert(errorMessage)
  } finally {
    isImporting.value = false
  }
}

const editDonator = (donator) => {
  editingDonator.value = donator
  donatorForm.value = {
    name: donator.name,
    phone: donator.phone
  }
}

const closeDonatorModal = () => {
  showAddDonatorModal.value = false
  editingDonator.value = null
  donatorForm.value = {
    name: '',
    phone: ''
  }
}

const saveDonator = async () => {
  isSaving.value = true
  try {
    if (editingDonator.value) {
      await axios.put(`/admin/donators/${editingDonator.value.id}`, donatorForm.value)
    } else {
      await axios.post('/admin/donators', donatorForm.value)
    }

    closeDonatorModal()
    loadData(donatorsPagination.value.current_page)
  } catch (error) {
    console.error('Save donator error:', error)
    alert('حدث خطأ في حفظ البيانات')
  } finally {
    isSaving.value = false
  }
}

const deleteDonator = async (donator) => {
  if (!confirm('هل أنت متأكد من حذف هذا المتبرع؟')) return

  try {
    await axios.delete(`/admin/donators/${donator.id}`)
    loadData(donatorsPagination.value.current_page)
  } catch (error) {
    console.error('Delete donator error:', error)
    alert('حدث خطأ في الحذف')
  }
}

const updateDonationStatus = async (donation, status) => {
  try {
    await axios.put(`/admin/donations/${donation.id}`, { status })
    loadData(donatorsPagination.value.current_page)
  } catch (error) {
    console.error('Update donation status error:', error)
    alert('حدث خطأ في تحديث الحالة')
  }
}

const getStatusText = (status) => {
  const statusMap = {
    pending: 'في الانتظار',
    confirmed: 'مؤكدة',
    completed: 'مكتملة'
  }
  return statusMap[status] || status
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('ar-EG')
}

// Lifecycle
onMounted(async () => {
  loadData(1)
  await loadGlobalDonationNumber()
})
</script>

<style scoped>
.admin-dashboard {
  min-height: 100vh;
  background: #f8fafc;
}

.dashboard-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 20px 0;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.header-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.dashboard-title {
  margin: 0;
  font-size: 2rem;
  font-weight: 700;
}

.logout-btn {
  background: rgba(255, 255, 255, 0.2);
  color: white;
  border: 1px solid rgba(255, 255, 255, 0.3);
  padding: 10px 20px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 8px;
}

.logout-btn:hover {
  background: rgba(255, 255, 255, 0.3);
}

.dashboard-nav {
  background: white;
  border-bottom: 1px solid #e2e8f0;
  padding: 0 20px;
}

.nav-btn {
  background: none;
  border: none;
  padding: 15px 25px;
  cursor: pointer;
  font-weight: 500;
  color: #4a5568;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 8px;
}

.nav-btn:hover {
  background: #f7fafc;
  color: #2d3748;
}

.nav-btn.active {
  color: #667eea;
  border-bottom: 3px solid #667eea;
}

.dashboard-main {
  max-width: 1200px;
  margin: 0 auto;
  padding: 30px 20px;
}

.tab-content {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}

.tab-header {
  padding: 25px 30px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.tab-header h2 {
  margin: 0;
  color: #2d3748;
  font-size: 1.5rem;
}

.header-actions {
  display: flex;
  gap: 15px;
}

.action-btn {
  padding: 10px 20px;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 8px;
}

.action-btn.primary {
  background: #667eea;
  color: white;
}

.action-btn.secondary {
  background: #e2e8f0;
  color: #4a5568;
}

.action-btn.small {
  padding: 8px 12px;
  font-size: 0.9rem;
}

.action-btn.success {
  background: #48bb78;
  color: white;
}

.action-btn.danger {
  background: #e53e3e;
  color: white;
}

/* Tables */
.donators-table, .donations-table {
  padding: 0;
}

.table-header {
  padding: 20px 30px;
  border-bottom: 1px solid #e2e8f0;
}

.table-search {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
}

.table-responsive {
  overflow-x: auto;
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table th, .table td {
  padding: 15px 20px;
  text-align: right;
  border-bottom: 1px solid #e2e8f0;
}

.table th {
  background: #f8fafc;
  font-weight: 600;
  color: #4a5568;
}


.amount {
  font-weight: 600;
  color: #48bb78;
}

.status-badge {
  padding: 4px 12px;
  border-radius: 20px;
  font-size: 0.85rem;
  font-weight: 500;
}

.status-badge.pending {
  background: #fef5e7;
  color: #d69e2e;
}

.status-badge.confirmed {
  background: #c6f6d5;
  color: #38a169;
}

.status-badge.completed {
  background: #bee3f8;
  color: #3182ce;
}

/* Statistics */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 20px;
  padding: 30px;
}

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 25px;
  display: flex;
  align-items: center;
  gap: 20px;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
  transition: transform 0.3s ease;
}

.stat-card:hover {
  transform: translateY(-2px);
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.stat-content h3 {
  margin: 0 0 5px 0;
  font-size: 2rem;
  font-weight: 700;
  color: #2d3748;
}

.stat-content p {
  margin: 0;
  color: #4a5568;
}

/* Modals */
.modal {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  animation: fadeIn 0.3s ease-out;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  max-height: 90vh;
  overflow-y: auto;
  animation: slideInUp 0.3s ease-out;
}

.modal-header {
  padding: 25px 30px;
  border-bottom: 1px solid #e2e8f0;
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.modal-header h3 {
  margin: 0;
  color: #2d3748;
}

.close-btn {
  background: none;
  border: none;
  font-size: 1.2rem;
  color: #4a5568;
  cursor: pointer;
  padding: 5px;
  border-radius: 50%;
  transition: all 0.3s ease;
}

.close-btn:hover {
  background: #f7fafc;
  color: #2d3748;
}

.modal-body {
  padding: 30px;
}

.file-upload {
  margin-bottom: 25px;
}

.file-input {
  display: none;
}

.file-upload-area {
  border: 2px dashed #cbd5e0;
  border-radius: 8px;
  padding: 40px 20px;
  text-align: center;
  cursor: pointer;
  transition: all 0.3s ease;
}

.file-upload-area:hover {
  border-color: #667eea;
  background: #f7fafc;
}

.file-upload-area i {
  font-size: 2rem;
  color: #a0aec0;
  margin-bottom: 10px;
}

.import-info {
  background: #f7fafc;
  padding: 20px;
  border-radius: 8px;
  border-left: 4px solid #667eea;
}

.import-info h4 {
  margin: 0 0 15px 0;
  color: #2d3748;
}

.import-info ul {
  margin: 0;
  padding-right: 20px;
}

.import-info li {
  color: #4a5568;
  margin-bottom: 5px;
}

.modal-footer {
  padding: 20px 30px;
  border-top: 1px solid #e2e8f0;
  display: flex;
  justify-content: flex-end;
  gap: 15px;
}

.btn {
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.3s ease;
}

.btn.primary {
  background: #667eea;
  color: white;
}

.btn.secondary {
  background: #e2e8f0;
  color: #4a5568;
}

.btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.form-group {
  margin-bottom: 20px;
}

.form-group label {
  display: block;
  margin-bottom: 8px;
  font-weight: 500;
  color: #4a5568;
}

.form-input {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.form-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.filter-select {
  padding: 8px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  background: white;
}

/* Animations */
@keyframes fadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

/* Responsive */
@media (max-width: 768px) {
  .dashboard-main {
    padding: 15px;
  }

  .tab-header {
    flex-direction: column;
    gap: 15px;
    align-items: stretch;
  }

  .header-actions {
    flex-direction: column;
  }

  .stats-grid {
    grid-template-columns: 1fr;
    padding: 20px;
  }

  .stat-card {
    padding: 20px;
  }

  .modal-content {
    margin: 20px;
    width: calc(100% - 40px);
  }
}

.pagination {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 20px;
  margin-top: 20px;
  padding: 15px;
  background: white;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.page-btn {
  padding: 8px 16px;
  border: 1px solid #ddd;
  background: white;
  color: #333;
  border-radius: 4px;
  cursor: pointer;
  transition: all 0.2s;
  display: flex;
  align-items: center;
  gap: 5px;
}

.page-btn:hover:not(:disabled) {
  background: #f8f9fa;
  border-color: #007bff;
}

.page-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.page-info {
  font-weight: 500;
  color: #666;
  white-space: nowrap;
}



.settings-section {
  max-width: 800px;
  margin: 0 auto;
}

.setting-card {
  background: white;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.setting-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  padding: 24px;
}

.setting-header h3 {
  margin: 0 0 8px 0;
  font-size: 20px;
  font-weight: 600;
}

.setting-header p {
  margin: 0;
  opacity: 0.9;
  font-size: 14px;
}

.setting-content {
  padding: 24px;
}

.current-setting {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 24px;
  padding: 16px;
  background: #f8f9fa;
  border-radius: 8px;
  border-left: 4px solid #667eea;
}

.current-setting label {
  font-weight: 600;
  color: #495057;
  min-width: 100px;
}

.current-value {
  font-family: monospace;
  font-weight: bold;
  background: #e9ecef;
  padding: 4px 8px;
  border-radius: 4px;
  color: #495057;
}

.setting-form {
  margin-top: 0;
}

.setting-actions {
  display: flex;
  gap: 12px;
  margin-top: 20px;
}

@media (max-width: 768px) {
  .pagination {
    flex-direction: column;
    gap: 10px;
  }

  .page-info {
    order: -1;
  }

  .setting-actions {
    flex-direction: column;
  }

  .current-setting {
    flex-direction: column;
    align-items: flex-start;
    gap: 8px;
  }
}
</style>
