<template>
  <div v-if="isOpen" class="modal-overlay" @click.self="close">
    <div class="modal-content">
      <div class="modal-icon text-7xl mb-4">{{ icon }}</div>
      <h2 class="text-3xl font-black text-center mb-4" style="color: #3e2723;">{{ title }}</h2>
      <p class="text-xl text-center mb-8" style="color: #5d4037;">{{ message }}</p>
      <button 
        @click="close"
        class="w-full py-5 rounded-2xl font-black text-xl cursor-pointer transition-all"
        style="background: linear-gradient(180deg, #ef5350 0%, #c62828 100%); color: white; box-shadow: 0 4px 0 #7f0000, 0 8px 20px rgba(0,0,0,0.3);"
      >
        Fermer
      </button>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    isOpen: { type: Boolean, default: false },
    icon: { type: String, default: '❌' },
    title: { type: String, default: 'Erreur' },
    message: { type: String, default: '' }
  },
  emits: ['close'],
  setup(props, { emit }) {
    const close = () => {
      emit('close')
    }
    return { close }
  }
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.7);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal-content {
  background: linear-gradient(180deg, #efebe9 0%, #d7ccc8 100%);
  padding: 3rem;
  border-radius: 20px;
  max-width: 500px;
  width: 90%;
  border: 4px solid #6d4c41;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5), inset 0 2px 0 rgba(255, 255, 255, 0.5);
}

.modal-content button:hover {
  transform: translateY(-2px);
}

.modal-content button:active {
  transform: translateY(2px);
}
</style>
