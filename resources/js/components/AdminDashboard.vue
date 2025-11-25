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
            <button @click="openAddDonatorModal" class="action-btn secondary">
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

      <!-- Non Donors Tab -->
      <div v-if="activeTab === 'nonDonors'" class="tab-content">
        <div class="tab-header">
          <div>
            <h2>متبرعون لم يؤكدوا تبرعهم بعد</h2>
            <p class="tab-subtitle">قائمة محدثة بالأشخاص الذين لم يتم تسجيل تبرعات لهم بعد استيراد بياناتهم.</p>
          </div>
          <div class="header-actions">
            <button
              @click="refreshNonDonors"
              class="action-btn secondary"
              :disabled="isNonDonorsLoading"
            >
              <i :class="['fas', isNonDonorsLoading ? 'fa-spinner fa-spin' : 'fa-sync-alt']"></i>
              تحديث
            </button>
          </div>
        </div>

        <div class="donators-table">
          <div class="table-header">
            <div class="non-donors-filters">
              <label class="filter-field">
                <span>نوع الفلتر</span>
                <select v-model="nonDonorsFilter" class="filter-select wide">
                  <option value="never">لم يتبرعوا أبداً</option>
                  <option value="month">لم يتبرعوا خلال شهر محدد</option>
                </select>
              </label>
              <label v-if="isMonthFilter" class="filter-field">
                <span>اختر الشهر</span>
                <input
                  v-model="nonDonorsMonth"
                  type="month"
                  class="month-input"
                  :max="getCurrentMonthValue()"
                >
              </label>
            </div>

            <div class="non-donors-search-row">
              <input
                v-model="nonDonorsSearch"
                type="text"
                placeholder="ابحث بالاسم أو الهاتف أو رقم التبرع..."
                class="table-search"
              >
              <button
                v-if="nonDonorsSearch"
                @click="clearNonDonorsSearch"
                class="clear-search-btn"
              >
                مسح
              </button>
            </div>
            <small class="table-hint">يتم تطبيق البحث بشكل تلقائي لتسهيل التواصل السريع.</small>
          </div>

          <div v-if="isNonDonorsLoading" class="non-donors-state loading">
            <i class="fas fa-spinner fa-spin"></i>
            <span>جاري تحميل القائمة...</span>
          </div>

          <div v-else-if="nonDonorsError" class="non-donors-state error">
            <i class="fas fa-exclamation-triangle"></i>
            <span>{{ nonDonorsError }}</span>
          </div>

          <div v-else-if="!nonDonors.length" class="non-donors-state empty">
            <i class="fas fa-check-circle"></i>
            <span>لا يوجد متبرعون معلقون حاليًا 🎉</span>
          </div>

          <div v-else class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>الاسم</th>
                  <th>رقم الهاتف</th>
                  <th>رقم التبرع</th>
                  <th>تاريخ الإضافة</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="donator in nonDonors" :key="donator.id">
                  <td>{{ donator.name }}</td>
                  <td>
                    <div class="donor-contact">
                      <strong>{{ donator.phone }}</strong>
                      <small>استخدم الرقم للتذكير أو المتابعة</small>
                    </div>
                  </td>
                  <td>
                    <span class="donation-number">{{ donator.donation_number }}</span>
                  </td>
                  <td>{{ donator.created_at ? formatDate(donator.created_at) : '—' }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div v-if="nonDonorsPagination.last_page > 1" class="pagination">
          <button
            @click="changeNonDonorsPage(nonDonorsPagination.current_page - 1)"
            :disabled="nonDonorsPagination.current_page <= 1"
            class="page-btn"
          >
            <i class="fas fa-chevron-right"></i>
            السابق
          </button>

          <span class="page-info">
            الصفحة {{ nonDonorsPagination.current_page }} من {{ nonDonorsPagination.last_page }}
            (إجمالي {{ nonDonorsPagination.total }} شخص)
          </span>

          <button
            @click="changeNonDonorsPage(nonDonorsPagination.current_page + 1)"
            :disabled="nonDonorsPagination.current_page >= nonDonorsPagination.last_page"
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
                  <th>رقم هاتف المتبرع</th>
                  <th>رقم التبرع المرسل إليه</th>
                  <th>الحالة</th>
                  <th>التاريخ</th>
                  <th>الإجراءات</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="donation in filteredDonations" :key="donation.id">
                  <td>
                    <div class="donator-info">
                      <strong>{{ donation.donator.name }}</strong>
                      <small>{{ donation.donator.phone }}</small>
                    </div>
                  </td>
                  <td class="amount">
                    <strong>{{ donation.amount }} جنيه</strong>
                  </td>
                  <td>{{ donation.donor_phone_number }}</td>
                  <td>
                    <span class="donation-number">{{ donation.global_donation_number || 'غير محدد' }}</span>
                  </td>
                  <td>
                    <select
                      :value="donation.status"
                      @change="updateDonationStatus(donation, $event.target.value)"
                      class="status-select"
                      :class="donation.status"
                    >
                      <option value="pending">في الانتظار</option>
                      <option value="confirmed">مؤكدة</option>
                      <option value="completed">مكتملة</option>
                    </select>
                  </td>
                  <td>
                    <div class="date-info">
                      {{ formatDate(donation.created_at) }}
                      <small>{{ formatTime(donation.created_at) }}</small>
                    </div>
                  </td>
                  <td>
                    <div class="actions-group">
                      <button
                        @click="viewDonationDetails(donation)"
                        class="action-btn small info"
                        title="عرض التفاصيل"
                      >
                        <i class="fas fa-eye"></i>
                      </button>
                      <button
                        v-if="donation.status !== 'pending'"
                        @click="rollbackDonationStatus(donation)"
                        class="action-btn small warning"
                        title="تراجع عن الحالة"
                      >
                        <i class="fas fa-undo"></i>
                      </button>
                      <button
                        @click="deleteDonation(donation)"
                        class="action-btn small danger"
                        title="حذف التبرع"
                      >
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
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

    <!-- Donation Details Modal -->
    <div v-if="showDonationDetailsModal" class="modal" @click="closeDonationDetailsModal">
      <div class="modal-content large" @click.stop>
        <div class="modal-header">
          <h3>تفاصيل التبرع</h3>
          <button @click="closeDonationDetailsModal" class="close-btn">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <div class="modal-body" v-if="selectedDonation">
          <div class="donation-details-grid">
            <div class="detail-section">
              <h4>معلومات المتبرع</h4>
              <div class="detail-grid">
                <div class="detail-item">
                  <label>الاسم:</label>
                  <span>{{ selectedDonation.donator.name }}</span>
                </div>
                <div class="detail-item">
                  <label>رقم الهاتف:</label>
                  <span>{{ selectedDonation.donator.phone }}</span>
                </div>
                <div class="detail-item">
                  <label>رقم التبرع الخاص:</label>
                  <span class="donation-number">{{ selectedDonation.donator.donation_number }}</span>
                </div>
              </div>
            </div>

            <div class="detail-section">
              <h4>معلومات التبرع</h4>
              <div class="detail-grid">
                <div class="detail-item">
                  <label>المبلغ:</label>
                  <span class="amount-large">{{ selectedDonation.amount }} جنيه</span>
                </div>
                <div class="detail-item">
                  <label>رقم هاتف المتبرع:</label>
                  <span>{{ selectedDonation.donor_phone_number }}</span>
                </div>
                <div class="detail-item">
                  <label>رقم التبرع المرسل إليه:</label>
                  <span class="donation-number">{{ selectedDonation.global_donation_number || 'غير محدد' }}</span>
                </div>
                <div class="detail-item">
                  <label>الحالة:</label>
                  <span class="status-badge large" :class="selectedDonation.status">
                    {{ getStatusText(selectedDonation.status) }}
                  </span>
                </div>
                <div class="detail-item">
                  <label>تاريخ التبرع:</label>
                  <span>{{ formatDateTime(selectedDonation.created_at) }}</span>
                </div>
                <div class="detail-item" v-if="selectedDonation.updated_at !== selectedDonation.created_at">
                  <label>آخر تحديث:</label>
                  <span>{{ formatDateTime(selectedDonation.updated_at) }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="donation-actions">
            <h4>الإجراءات السريعة</h4>
            <div class="actions-grid">
              <button
                @click="updateDonationStatus(selectedDonation, 'pending')"
                :disabled="selectedDonation.status === 'pending'"
                class="action-btn large"
                :class="{ disabled: selectedDonation.status === 'pending' }"
              >
                <i class="fas fa-clock"></i>
                في الانتظار
              </button>
              <button
                @click="updateDonationStatus(selectedDonation, 'confirmed')"
                :disabled="selectedDonation.status === 'confirmed'"
                class="action-btn large success"
                :class="{ disabled: selectedDonation.status === 'confirmed' }"
              >
                <i class="fas fa-check"></i>
                تأكيد
              </button>
              <button
                @click="updateDonationStatus(selectedDonation, 'completed')"
                :disabled="selectedDonation.status === 'completed'"
                class="action-btn large primary"
                :class="{ disabled: selectedDonation.status === 'completed' }"
              >
                <i class="fas fa-check-circle"></i>
                اكتمال
              </button>
              <button
                v-if="selectedDonation.status !== 'pending'"
                @click="rollbackDonationStatus(selectedDonation)"
                class="action-btn large warning"
              >
                <i class="fas fa-undo"></i>
                تراجع
              </button>
              <button
                @click="deleteDonation(selectedDonation)"
                class="action-btn large danger"
              >
                <i class="fas fa-trash"></i>
                حذف
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

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

          <div class="form-group">
            <label for="donationNumber">رقم التبرع</label>
            <div class="input-with-button">
              <input
                id="donationNumber"
                v-model="donatorForm.donation_number"
                type="text"
                required
                class="form-input"
                placeholder="مثال: DON-000123 أو رقم هاتف"
                @focus="handleDonationNumberFocus"
              >
              <button
                type="button"
                @click="autoFillDonationNumber"
                class="generate-btn"
                title="توليد رقم تلقائي"
              >
                <i class="fas fa-magic"></i>
                تلقائي
              </button>
            </div>
            <small class="form-hint">يجب أن يكون فريد ويمكن أن يكون رقم هاتف أو كود مخصص</small>
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
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

// Navigation
const activeTab = ref('donators')
const tabs = [
  { id: 'donators', label: 'المتبرعين', icon: 'fas fa-users' },
  { id: 'nonDonors', label: 'لم يتبرعوا بعد', icon: 'fas fa-user-clock' },
  { id: 'donations', label: 'التبرعات', icon: 'fas fa-hand-holding-heart' },
  { id: 'settings', label: 'الإعدادات', icon: 'fas fa-cogs' },
  { id: 'stats', label: 'الإحصائيات', icon: 'fas fa-chart-bar' }
]

// Data
const donators = ref([])
const donations = ref([])
const nonDonors = ref([])
const isNonDonorsLoading = ref(false)
const nonDonorsError = ref('')
const hasLoadedNonDonors = ref(false)
const donatorsPagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0
})
const nonDonorsPagination = ref({
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
const nonDonorsSearch = ref('')
const nonDonorsFilter = ref('never')

const getCurrentMonthValue = () => {
  const today = new Date()
  const month = (today.getMonth() + 1).toString().padStart(2, '0')
  return `${today.getFullYear()}-${month}`
}

const nonDonorsMonth = ref(getCurrentMonthValue())


// Modals
const showImportModal = ref(false)
const showAddDonatorModal = ref(false)
const showDonationDetailsModal = ref(false)
const editingDonator = ref(null)
const selectedDonation = ref(null)

// Forms
const selectedFile = ref(null)
const isImporting = ref(false)
const isSaving = ref(false)
const donatorForm = ref({
  name: '',
  phone: '',
  donation_number: ''
})

// Settings
const globalDonationNumber = ref('')
const originalGlobalDonationNumber = ref('')
const isSavingGlobalDonationNumber = ref(false)
const globalDonationNumberError = ref('')

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

const isMonthFilter = computed(() => nonDonorsFilter.value === 'month')

let nonDonorsSearchTimeout = null

// Methods
const generateDonationNumber = () => {
  const timestamp = Date.now()
  const random1 = Math.floor(Math.random() * 100)
  const random2 = Math.floor(Math.random() * 1000)
  const random3 = Math.floor(Math.random() * 100)
  return `DON-${timestamp.toString().slice(-4)}${random1.toString().padStart(2, '0')}${random2.toString().padStart(3, '0')}${random3.toString().padStart(2, '0')}`
}

const autoFillDonationNumber = () => {
  // Always generate a new number when clicked
  donatorForm.value.donation_number = generateDonationNumber()
}

const handleDonationNumberFocus = () => {
  if (!donatorForm.value.donation_number || !donatorForm.value.donation_number.trim()) {
    autoFillDonationNumber()
  }
}

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

const loadNonDonors = async (page = 1) => {
  isNonDonorsLoading.value = true
  nonDonorsError.value = ''

  try {
    const response = await axios.get('/admin/donators/without-donations', {
      params: {
        page,
        search: nonDonorsSearch.value ? nonDonorsSearch.value.trim() : undefined,
        filter: nonDonorsFilter.value,
        month: nonDonorsFilter.value === 'month' ? nonDonorsMonth.value : undefined
      }
    })

    nonDonors.value = response.data.data || []
    nonDonorsPagination.value = {
      current_page: response.data.current_page || 1,
      last_page: response.data.last_page || 1,
      total: response.data.total || nonDonors.value.length
    }
    hasLoadedNonDonors.value = true
  } catch (error) {
    console.error('Load non-donors error:', error)
    if (error.response?.status === 401) {
      router.push('/admin')
    }
    nonDonors.value = []
    nonDonorsPagination.value = {
      current_page: 1,
      last_page: 1,
      total: 0
    }
    nonDonorsError.value = error.response?.data?.message || 'حدث خطأ أثناء تحميل قائمة غير المتبرعين'
  } finally {
    isNonDonorsLoading.value = false
  }
}

const changeNonDonorsPage = async (page) => {
  if (page >= 1 && page <= nonDonorsPagination.value.last_page) {
    await loadNonDonors(page)
  }
}

const refreshNonDonors = async () => {
  const currentPage = nonDonorsPagination.value.current_page || 1
  await loadNonDonors(currentPage)
}

const clearNonDonorsSearch = () => {
  if (!nonDonorsSearch.value) return
  nonDonorsSearch.value = ''
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
    phone: donator.phone,
    donation_number: donator.donation_number
  }
}

const closeDonatorModal = () => {
  showAddDonatorModal.value = false
  editingDonator.value = null
  donatorForm.value = {
    name: '',
    phone: '',
    donation_number: ''
  }
}

const openAddDonatorModal = () => {
  showAddDonatorModal.value = true
  // Auto-generate donation number when opening the modal
  setTimeout(() => {
    autoFillDonationNumber()
  }, 100)
}

const saveDonator = async () => {
  // Check if user is logged in
  const token = localStorage.getItem('admin_token')
  if (!token) {
    alert('يجب تسجيل الدخول أولاً')
    router.push('/admin')
    return
  }

  // Validate required fields
  if (!donatorForm.value.name || !donatorForm.value.name.trim()) {
    alert('يرجى إدخال اسم المتبرع')
    return
  }
  if (!donatorForm.value.phone || !donatorForm.value.phone.trim()) {
    alert('يرجى إدخال رقم هاتف المتبرع')
    return
  }
  if (!donatorForm.value.donation_number || !donatorForm.value.donation_number.trim()) {
    alert('يرجى إدخال رقم التبرع')
    return
  }

  // Check for duplicate donation number in current donators list
  const existingDonator = donators.value.find(d =>
    d.donation_number === donatorForm.value.donation_number.trim() &&
    (!editingDonator.value || d.id !== editingDonator.value.id)
  )
  if (existingDonator) {
    alert('رقم التبرع هذا موجود بالفعل. يرجى اختيار رقم آخر.')
    return
  }

  isSaving.value = true
  try {
    // Prepare data for sending
    const dataToSend = {
      name: donatorForm.value.name.trim(),
      phone: donatorForm.value.phone.trim(),
      donation_number: donatorForm.value.donation_number.trim()
    }

    console.log('Saving donator:', dataToSend)
    let response
    if (editingDonator.value) {
      response = await axios.put(`/admin/donators/${editingDonator.value.id}`, dataToSend)
      console.log('Update response:', response)
    } else {
      response = await axios.post('/admin/donators', dataToSend)
      console.log('Create response:', response)
    }

    alert('تم حفظ المتبرع بنجاح!')
    closeDonatorModal()
    loadData(donatorsPagination.value.current_page)
  } catch (error) {
    console.error('Save donator error:', error)
    if (error.response) {
      console.error('Response data:', error.response.data)
      console.error('Response status:', error.response.status)
      if (error.response.status === 401) {
        alert('انتهت صلاحية الجلسة. يرجى إعادة تسجيل الدخول.')
      } else if (error.response.status === 422) {
        const errors = error.response.data.errors
        let errorMessage = 'خطأ في البيانات:\n'
        for (const field in errors) {
          errorMessage += `${field}: ${errors[field].join(', ')}\n`
        }
        alert(errorMessage)
      } else {
        alert('حدث خطأ في حفظ البيانات: ' + (error.response.data.message || 'خطأ غير معروف'))
      }
    } else {
      alert('حدث خطأ في الاتصال بالخادم')
    }
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

const viewDonationDetails = (donation) => {
  selectedDonation.value = donation
  showDonationDetailsModal.value = true
}

const closeDonationDetailsModal = () => {
  showDonationDetailsModal.value = false
  selectedDonation.value = null
}

const rollbackDonationStatus = async (donation) => {
  let newStatus = 'pending'
  if (donation.status === 'completed') {
    newStatus = 'confirmed'
  } else if (donation.status === 'confirmed') {
    newStatus = 'pending'
  }

  if (!confirm(`هل أنت متأكد من تراجع حالة التبرع إلى "${getStatusText(newStatus)}"؟`)) return

  try {
    await updateDonationStatus(donation, newStatus)
    if (selectedDonation.value && selectedDonation.value.id === donation.id) {
      selectedDonation.value.status = newStatus
    }
  } catch (error) {
    console.error('Rollback donation status error:', error)
  }
}

const deleteDonation = async (donation) => {
  if (!confirm('هل أنت متأكد من حذف هذا التبرع نهائياً؟ لا يمكن التراجع عن هذا الإجراء.')) return

  try {
    await axios.delete(`/admin/donations/${donation.id}`)
    loadData(donatorsPagination.value.current_page)
    closeDonationDetailsModal()
    alert('تم حذف التبرع بنجاح')
  } catch (error) {
    console.error('Delete donation error:', error)
    alert('حدث خطأ في الحذف')
  }
}

const formatDate = (dateString) => {
  return new Date(dateString).toLocaleDateString('ar-EG')
}

const formatTime = (dateString) => {
  return new Date(dateString).toLocaleTimeString('ar-EG', {
    hour: '2-digit',
    minute: '2-digit'
  })
}

const formatDateTime = (dateString) => {
  return new Date(dateString).toLocaleString('ar-EG', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

// Watch active tab to lazy-load heavy sections
watch(activeTab, async (newTab) => {
  if (newTab === 'settings') {
    await loadGlobalDonationNumber()
  }
  if (newTab === 'nonDonors' && !hasLoadedNonDonors.value) {
    await loadNonDonors(1)
  }
})

watch(nonDonorsSearch, () => {
  if (activeTab.value !== 'nonDonors') return
  if (nonDonorsSearchTimeout) {
    clearTimeout(nonDonorsSearchTimeout)
  }
  nonDonorsSearchTimeout = setTimeout(() => {
    loadNonDonors(1)
  }, 500)
})

watch(nonDonorsFilter, () => {
  if (nonDonorsFilter.value === 'month' && !nonDonorsMonth.value) {
    nonDonorsMonth.value = getCurrentMonthValue()
  }
  if (activeTab.value !== 'nonDonors') return
  loadNonDonors(1)
})

watch(nonDonorsMonth, (newValue, oldValue) => {
  if (!isMonthFilter.value) return
  if (newValue === oldValue) return
  if (!newValue) return
  if (activeTab.value !== 'nonDonors') return
  loadNonDonors(1)
})

onBeforeUnmount(() => {
  if (nonDonorsSearchTimeout) {
    clearTimeout(nonDonorsSearchTimeout)
  }
})

// Lifecycle
onMounted(() => {
  loadData(1)
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

.tab-subtitle {
  margin: 8px 0 0;
  color: #718096;
  font-size: 0.95rem;
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

.table-hint {
  display: block;
  margin-top: 10px;
  color: #718096;
  font-size: 0.85rem;
}

.non-donors-search-row {
  display: flex;
  gap: 12px;
  align-items: center;
}

.non-donors-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 16px;
  margin-bottom: 16px;
}

.filter-field {
  display: flex;
  flex-direction: column;
  gap: 6px;
  color: #4a5568;
  font-size: 0.9rem;
}

.filter-select.wide {
  min-width: 220px;
}

.month-input {
  padding: 10px 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
}

.month-input:focus {
  outline: none;
  border-color: #a0611c;
  box-shadow: 0 0 0 3px rgba(160, 97, 28, 0.15);
}

.table-search {
  width: 100%;
  padding: 12px 16px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
}

.clear-search-btn {
  padding: 10px 16px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: white;
  cursor: pointer;
  font-weight: 500;
  color: #a0611c;
  transition: all 0.2s ease;
}

.clear-search-btn:hover {
  border-color: #c1842c;
  color: #c1842c;
  background: #fff8eb;
}

.non-donors-state {
  padding: 40px 20px;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
  color: #4a5568;
}

.non-donors-state i {
  font-size: 2rem;
}

.non-donors-state.loading {
  color: #a0611c;
}

.non-donors-state.error {
  color: #e53e3e;
}

.non-donors-state.empty {
  color: #38a169;
}

.donor-contact strong {
  display: block;
  color: #2d3748;
}

.donor-contact small {
  display: block;
  color: #718096;
  font-size: 0.8rem;
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

.form-hint {
  display: block;
  margin-top: 4px;
  font-size: 12px;
  color: #6c757d;
}

.input-with-button {
  display: flex;
  gap: 8px;
  align-items: stretch;
}

.input-with-button .form-input {
  flex: 1;
}

.generate-btn {
  padding: 12px 16px;
  background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 0.9em;
  font-weight: 500;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 6px;
  white-space: nowrap;
  min-width: 80px;
  justify-content: center;
}

.generate-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 12px rgba(72, 187, 120, 0.3);
}

.generate-btn:active {
  transform: translateY(0);
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

  /* Enhanced Donations Table */
  .donator-info strong {
    display: block;
    color: #2d3748;
  }

  .donator-info small {
    display: block;
    color: #718096;
    font-size: 0.85em;
    margin-top: 2px;
  }

  .amount {
    font-weight: 700;
    color: #38a169;
  }

  .donation-number {
    font-family: 'Courier New', monospace;
    background: #667eea;
    color: white;
    padding: 3px 6px;
    border-radius: 4px;
    font-size: 0.85em;
    font-weight: 600;
  }

  .status-select {
    padding: 4px 8px;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    background: white;
    font-size: 0.85em;
  }

  .status-select.pending {
    border-color: #ffc107;
    color: #856404;
  }

  .status-select.confirmed {
    border-color: #17a2b8;
    color: #138496;
  }

  .status-select.completed {
    border-color: #28a745;
    color: #155724;
  }

  .date-info {
    text-align: center;
  }

  .date-info strong {
    display: block;
    color: #2d3748;
  }

  .date-info small {
    display: block;
    color: #718096;
    font-size: 0.8em;
    margin-top: 2px;
  }

  .actions-group {
    display: flex;
    gap: 4px;
    justify-content: center;
  }

  .action-btn.info {
    background: #17a2b8;
    color: white;
  }

  .action-btn.info:hover:not(:disabled) {
    background: #138496;
  }

  /* Donation Details Modal */
  .modal-content.large {
    max-width: 800px;
    width: 95%;
  }

  .donation-details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    margin-bottom: 30px;
  }

  .detail-section h4 {
    color: #2d3748;
    margin: 0 0 15px 0;
    font-size: 1.2em;
    border-bottom: 2px solid #667eea;
    padding-bottom: 8px;
  }

  .detail-grid {
    display: grid;
    gap: 12px;
  }

  .detail-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f7fafc;
  }

  .detail-item label {
    font-weight: 600;
    color: #4a5568;
    min-width: 140px;
  }

  .detail-item span {
    color: #2d3748;
    text-align: left;
  }

  .amount-large {
    font-size: 1.5em;
    font-weight: 700;
    color: #38a169;
  }

  .status-badge.large {
    font-size: 0.9em;
    padding: 6px 12px;
  }

  .donation-actions h4 {
    color: #2d3748;
    margin: 0 0 15px 0;
    font-size: 1.2em;
  }

  .actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 10px;
  }

  .action-btn.large {
    padding: 12px 16px;
    font-size: 0.9em;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    min-height: 60px;
  }

  .action-btn.large i {
    font-size: 1.2em;
  }

  .action-btn.large.disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }
}
</style>
