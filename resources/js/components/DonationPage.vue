<template>
  <div class="donation-page">
    <div class="container">
      <header class="hero-section">
        <div class="hero-content">
          <h1 class="hero-title">عيد الخير</h1>
          <p class="hero-subtitle">ساهم في نشر الفرح والخير في عيد الاضحي المبارك</p>
        </div>
      </header>

      <main class="main-content">
        <!-- Search Section -->
        <section class="search-section">
          <div class="search-card">
            <h2 class="section-title">البحث عن المتبرع</h2>
            <div class="search-form">
              <input
                v-model="searchQuery"
                @input="debouncedSearch"
                type="text"
                class="search-input"
                placeholder="ابحث بالاسم أو رقم الهاتف..."
                dir="rtl"
              >
              <button
                @click="search"
                class="search-btn"
                :disabled="isSearching"
              >
                <span v-if="isSearching">جاري البحث...</span>
                <span v-else>بحث</span>
              </button>
              <button
                @click="showAllDonators"
                class="show-all-btn"
                :disabled="isSearching"
                title="عرض جميع المتبرعين"
              >
                <i class="fas fa-users"></i>
                جميع المتبرعين
              </button>
            </div>

            <!-- Search Results -->
            <div v-if="searchResults.length > 0" class="search-results">
              <h3>{{ showAllMode ? 'جميع المتبرعين' : 'نتائج البحث' }} ({{ searchResults.length }})</h3>
              <div class="results-list">
                <div
                  v-for="donator in searchResults"
                  :key="donator.id"
                  class="result-item"
                  @click="selectDonator(donator)"
                  :class="{ selected: selectedDonator?.id === donator.id }"
                >
                  <div class="donator-info">
                    <h4>{{ donator.name }}</h4>
                    <p>{{ donator.phone }}</p>
                    <small>رقم التبرع: {{ donator.donation_number }}</small>
                  </div>
                  <div class="select-indicator">
                    <i class="fas fa-check" v-if="selectedDonator?.id === donator.id"></i>
                  </div>
                </div>
              </div>
            </div>

            <!-- Load More Button -->
            <div v-if="showAllMode && hasMorePages" class="load-more-container">
              <button
                @click="loadMoreDonators"
                class="load-more-btn"
                :disabled="isLoadingMore"
              >
                <span v-if="isLoadingMore">
                  <i class="fas fa-spinner fa-spin"></i>
                  جاري التحميل...
                </span>
                <span v-else>
                  <i class="fas fa-plus"></i>
                  عرض المزيد ({{ searchResults.length }} من أصل المزيد)
                </span>
              </button>
            </div>

            <div v-else-if="searchQuery && !isSearching" class="no-results">
              لا توجد نتائج للبحث "{{ searchQuery }}"
            </div>
          </div>
        </section>

        <!-- Donation Form -->
        <section v-if="selectedDonator" class="donation-section">
          <div class="donation-card">
            <h2 class="section-title">تأكيد التبرع</h2>
            <div class="donator-details">
              <div class="detail-row">
                <span class="label">الاسم:</span>
                <span class="value">{{ selectedDonator.name }}</span>
              </div>
              <div class="detail-row">
                <span class="label">رقم التبرع:</span>
                <span class="value donation-number">{{ globalDonationNumber }}</span>
              </div>
            </div>

            <form @submit.prevent="submitDonation" class="donation-form">
              <div class="form-group">
                <label for="amount">مبلغ التبرع (بالجنيه المصري)</label>
                <input
                  id="amount"
                  v-model="donationForm.amount"
                  type="number"
                  step="0.01"
                  min="0.01"
                  required
                  class="form-input"
                  placeholder="أدخل المبلغ"
                >
              </div>

              <div class="form-group">
                <label for="phone">رقم هاتفك</label>
                <input
                  id="phone"
                  v-model="donationForm.donor_phone_number"
                  type="tel"
                  required
                  class="form-input"
                  placeholder="أدخل رقم هاتفك"
                >
              </div>

              <button
                type="submit"
                class="donate-btn"
                :disabled="isSubmitting"
              >
                <span v-if="isSubmitting">جاري التأكيد...</span>
                <span v-else>تأكيد التبرع</span>
              </button>
            </form>
          </div>
        </section>

        <!-- Success Message -->
        <div v-if="showSuccess" class="success-message" @click="showSuccess = false">
          <div class="success-content">
            <i class="fas fa-check-circle"></i>
            <h3>تم تأكيد التبرع بنجاح!</h3>
            <p>شكراً لك على مشاركتك في نشر الخير</p>
          </div>
        </div>
      </main>
    </div>
  </div>
</template>

<script setup>
import axios from 'axios'
import { onMounted, ref } from 'vue'

// Reactive data
const searchQuery = ref('')
const searchResults = ref([])
const selectedDonator = ref(null)
const globalDonationNumber = ref('01205100202')
const isSearching = ref(false)
const isSubmitting = ref(false)
const showSuccess = ref(false)
const showAllMode = ref(false)
const currentPage = ref(1)
const hasMorePages = ref(false)
const isLoadingMore = ref(false)
const donationForm = ref({
  amount: '',
  donor_phone_number: ''
})

// Load global donation number
const loadGlobalDonationNumber = async () => {
  try {
    const response = await axios.get('/settings/global-donation-number')
    globalDonationNumber.value = response.data.global_donation_number
  } catch (error) {
    console.error('Error loading global donation number:', error)
    // Keep default value if API fails
  }
}

// Debounced search
let searchTimeout = null
const debouncedSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    search()
  }, 300)
}

// Show all donators
const showAllDonators = async () => {
  showAllMode.value = true
  searchQuery.value = '' // Clear search query
  currentPage.value = 1 // Reset to first page
  isSearching.value = true
  try {
    // Fetch first page of donators
    const response = await axios.get('/donators', {
      params: { page: currentPage.value }
    })
    searchResults.value = response.data.data
    hasMorePages.value = response.data.current_page < response.data.last_page
    currentPage.value = response.data.current_page
  } catch (error) {
    console.error('Load all donators error:', error)
    searchResults.value = []
    hasMorePages.value = false
  } finally {
    isSearching.value = false
  }
}

// Load more donators
const loadMoreDonators = async () => {
  if (!hasMorePages.value || isLoadingMore.value) return

  isLoadingMore.value = true
  try {
    const nextPage = currentPage.value + 1
    const response = await axios.get('/donators', {
      params: { page: nextPage }
    })

    // Append new results to existing ones
    searchResults.value = [...searchResults.value, ...response.data.data]
    hasMorePages.value = response.data.current_page < response.data.last_page
    currentPage.value = response.data.current_page
  } catch (error) {
    console.error('Load more donators error:', error)
  } finally {
    isLoadingMore.value = false
  }
}

// Search function
const search = async () => {
  showAllMode.value = false // Exit show all mode when searching
  currentPage.value = 1 // Reset pagination
  hasMorePages.value = false // Reset pagination
  if (!searchQuery.value.trim()) {
    searchResults.value = []
    return
  }

  isSearching.value = true
  try {
    const response = await axios.get('/donators', {
      params: { search: searchQuery.value }
    })
    searchResults.value = response.data.data
  } catch (error) {
    console.error('Search error:', error)
    searchResults.value = []
  } finally {
    isSearching.value = false
  }
}

// Select donator
const selectDonator = (donator) => {
  selectedDonator.value = donator
  showAllMode.value = false // Clear show all mode when selecting
  // Clear previous form data
  donationForm.value = {
    amount: '',
    donor_phone_number: ''
  }
}

// Submit donation
const submitDonation = async () => {
  if (!selectedDonator.value) return

  isSubmitting.value = true
  try {
    const donationData = {
      donator_id: selectedDonator.value.id,
      amount: parseFloat(donationForm.value.amount),
      donor_phone_number: donationForm.value.donor_phone_number,
      global_donation_number: globalDonationNumber.value
    }

    await axios.post('/donations', donationData)

    // Show success message
    showSuccess.value = true

    // Reset form
    selectedDonator.value = null
    donationForm.value = {
      amount: '',
      donor_phone_number: ''
    }
    searchQuery.value = ''
    searchResults.value = []
    showAllMode.value = false
    currentPage.value = 1
    hasMorePages.value = false

  } catch (error) {
    console.error('Donation submission error:', error)
    alert('حدث خطأ في تأكيد التبرع. يرجى المحاولة مرة أخرى.')
  } finally {
    isSubmitting.value = false
  }
}

// Component mounted
onMounted(async () => {
  await loadGlobalDonationNumber()
})

// Close success message after 5 seconds
setTimeout(() => {
  if (showSuccess.value) {
    showSuccess.value = false
  }
}, 5000)
</script>

<style scoped>
.donation-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 20px 0;
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 20px;
}

/* Hero Section */
.hero-section {
  text-align: center;
  margin-bottom: 40px;
}

.hero-content {
  background: rgba(255, 255, 255, 0.95);
  border-radius: 20px;
  padding: 40px;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
  backdrop-filter: blur(10px);
}

.hero-title {
  font-size: 3.5rem;
  font-weight: 700;
  color: #2d3748;
  margin: 0 0 10px 0;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.hero-subtitle {
  font-size: 1.2rem;
  color: #4a5568;
  margin: 0;
  line-height: 1.6;
}

/* Main Content */
.main-content {
  display: grid;
  gap: 30px;
}

/* Search Section */
.search-section {
  animation: slideInUp 0.6s ease-out;
}

.search-card {
  background: white;
  border-radius: 16px;
  padding: 30px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.section-title {
  font-size: 1.8rem;
  font-weight: 600;
  color: #2d3748;
  margin: 0 0 25px 0;
  text-align: center;
}

.search-form {
  display: flex;
  gap: 15px;
  margin-bottom: 25px;
  flex-wrap: wrap;
}

.search-input {
  flex: 1;
  padding: 15px 20px;
  border: 2px solid #e2e8f0;
  border-radius: 12px;
  font-size: 1rem;
  transition: all 0.3s ease;
}

.search-input:focus {
  outline: none;
  border-color: #667eea;
  box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-btn {
  padding: 15px 30px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
}

.search-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
}

.search-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.show-all-btn {
  padding: 15px 20px;
  background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 0.95rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  align-items: center;
  gap: 8px;
  white-space: nowrap;
}

.show-all-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(23, 162, 184, 0.3);
}

.show-all-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.show-all-btn i {
  font-size: 1rem;
}

/* Load More Button */
.load-more-container {
  text-align: center;
  margin-top: 20px;
  padding: 20px 0;
}

.load-more-btn {
  padding: 15px 30px;
  background: linear-gradient(135deg, #38a169 0%, #2f855a 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  display: inline-flex;
  align-items: center;
  gap: 10px;
  box-shadow: 0 4px 12px rgba(56, 161, 105, 0.3);
}

.load-more-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 8px 20px rgba(56, 161, 105, 0.4);
}

.load-more-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
}

.load-more-btn i {
  font-size: 1rem;
}

/* Search Results */
.search-results {
  margin-top: 20px;
}

.search-results h3 {
  font-size: 1.2rem;
  color: #4a5568;
  margin-bottom: 15px;
}

.results-list {
  max-height: 300px;
  overflow-y: auto;
}

.result-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 15px 20px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  margin-bottom: 10px;
  cursor: pointer;
  transition: all 0.3s ease;
}

.result-item:hover {
  background: #f7fafc;
  border-color: #667eea;
}

.result-item.selected {
  background: #ebf8ff;
  border-color: #667eea;
}

.donator-info h4 {
  margin: 0 0 5px 0;
  color: #2d3748;
  font-weight: 600;
}

.donator-info p {
  margin: 0 0 5px 0;
  color: #4a5568;
}

.donator-info small {
  color: #718096;
  font-size: 0.9rem;
}

.select-indicator {
  color: #667eea;
  font-size: 1.2rem;
}

.no-results {
  text-align: center;
  color: #718096;
  padding: 40px 20px;
  font-style: italic;
}

/* Donation Section */
.donation-section {
  animation: slideInUp 0.6s ease-out 0.3s both;
}

.donation-card {
  background: white;
  border-radius: 16px;
  padding: 30px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
}

.donator-details {
  background: #f7fafc;
  border-radius: 12px;
  padding: 20px;
  margin-bottom: 25px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
}

.detail-row:last-child {
  margin-bottom: 0;
}

.label {
  font-weight: 600;
  color: #4a5568;
}

.value {
  font-weight: 500;
  color: #2d3748;
}

.donation-number {
  font-family: 'Courier New', monospace;
  background: #667eea;
  color: white;
  padding: 5px 10px;
  border-radius: 6px;
  font-weight: bold;
}

/* Form */
.donation-form {
  display: grid;
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

.donate-btn {
  padding: 18px 30px;
  background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  margin-top: 10px;
}

.donate-btn:hover:not(:disabled) {
  transform: translateY(-2px);
  box-shadow: 0 10px 20px rgba(72, 187, 120, 0.3);
}

.donate-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Success Message */
.success-message {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.8);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
  animation: fadeIn 0.3s ease-out;
}

.success-content {
  background: white;
  border-radius: 20px;
  padding: 40px;
  text-align: center;
  max-width: 400px;
  width: 90%;
  box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
  animation: slideInUp 0.5s ease-out;
}

.success-content i {
  font-size: 4rem;
  color: #48bb78;
  margin-bottom: 20px;
}

.success-content h3 {
  color: #2d3748;
  margin: 0 0 15px 0;
  font-size: 1.5rem;
}

.success-content p {
  color: #4a5568;
  margin: 0;
  line-height: 1.6;
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

@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}

/* Responsive Design */
@media (max-width: 768px) {
  .container {
    padding: 0 15px;
  }

  .hero-title {
    font-size: 2.5rem;
  }

  .hero-subtitle {
    font-size: 1rem;
  }

  .search-form {
    flex-direction: column;
  }

  .search-btn,
  .show-all-btn {
    width: 100%;
  }

  .detail-row {
    flex-direction: column;
    align-items: flex-start;
    gap: 5px;
  }

  .donate-btn {
    width: 100%;
  }

  .load-more-btn {
    width: 100%;
    justify-content: center;
  }
}
</style>
