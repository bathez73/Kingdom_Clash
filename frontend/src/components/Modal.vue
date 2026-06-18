<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <!-- Overlay -->
    <div 
      class="absolute inset-0 bg-black/60 backdrop-blur-sm" 
      @click="closeModal"
    ></div>
    
    <!-- Modal Content -->
    <div class="relative bg-gradient-to-b from-gray-800 to-gray-900 rounded-3xl p-6 border-4 border-gray-700 shadow-2xl max-w-sm w-full">
      <!-- Icon (optional) -->
      <div v-if="icon" class="text-6xl text-center mb-4">
        {{ icon }}
      </div>
      
      <!-- Title -->
      <h3 class="text-white font-black text-2xl text-center mb-2">
        {{ title }}
      </h3>
      
      <!-- Message -->
      <p class="text-gray-300 text-center mb-6">
        {{ message }}
      </p>
      
      <!-- Actions -->
      <div class="flex gap-3">
        <button
          v-if="showCancel"
          @click="closeModal"
          class="flex-1 bg-gradient-to-b from-gray-600 to-gray-700 text-white font-black px-4 py-3 rounded-full border-2 border-gray-600 hover:translate-y-[-1px] transition-all"
        >
          {{ cancelText }}
        </button>
        <button
          @click="confirmAction"
          class="flex-1 bg-gradient-to-b from-green-500 to-green-700 text-white font-black px-4 py-3 rounded-full border-2 border-green-900 shadow-[0_4px_0_rgb(20,83,45)] hover:translate-y-[-1px] active:translate-y-[2px] transition-all"
        >
          {{ confirmText }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'Modal',
  props: {
    isOpen: {
      type: Boolean,
      default: false
    },
    title: {
      type: String,
      default: 'Confirmation'
    },
    message: {
      type: String,
      default: 'Êtes-vous sûr ?'
    },
    icon: {
      type: String,
      default: null
    },
    showCancel: {
      type: Boolean,
      default: true
    },
    cancelText: {
      type: String,
      default: 'Annuler'
    },
    confirmText: {
      type: String,
      default: 'Confirmer'
    }
  },
  emits: ['confirm', 'close'],
  methods: {
    confirmAction() {
      this.$emit('confirm')
    },
    closeModal() {
      this.$emit('close')
    }
  }
}
</script>
