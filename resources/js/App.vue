<template>
  <div id="app" :class="{ 'rtl': isRTL }">
    <router-view />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'

// RTL support
const isRTL = ref(true) // Default to RTL for Arabic

onMounted(() => {
  // Check if document direction should be RTL
  document.documentElement.dir = isRTL.value ? 'rtl' : 'ltr'
})
</script>

<style scoped>
#app {
  min-height: 100vh;
  background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

.rtl {
  direction: rtl;
  text-align: right;
}
</style>
