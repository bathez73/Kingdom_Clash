<template>
  <div v-if="isOpen" class="modal-overlay" @click.self="close">
    <div class="modal-content">
      <h3>{{ title }}</h3>
      <p>{{ message }}</p>
      <div class="modal-actions">
        <button class="btn-cancel" @click="close">Annuler</button>
        <button class="btn-confirm" @click="confirm">Confirmer</button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  props: {
    isOpen: { type: Boolean, default: false },
    title: { type: String, default: 'Confirmation' },
    message: { type: String, default: 'Êtes-vous sûr ?' }
  },
  emits: ['confirm', 'close'],
  setup(props, { emit }) {
    const confirm = () => emit('confirm')
    const close = () => emit('close')
    return { confirm, close }
  }
}
</script>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal-content {
  background: linear-gradient(180deg, #efebe9 0%, #d7ccc8 100%);
  padding: 2rem;
  border-radius: 20px;
  max-width: 400px;
  width: 90%;
  border: 4px solid #6d4c41;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3), inset 0 2px 0 rgba(255, 255, 255, 0.5);
}

.modal-content h3 {
  margin-top: 0;
  color: #3e2723;
  font-weight: 900;
  text-shadow: 1px 1px 0 rgba(255, 255, 255, 0.5);
}

.modal-content p {
  color: #5d4037;
  font-weight: 700;
  text-shadow: 1px 1px 0 rgba(255, 255, 255, 0.5);
}

.modal-actions {
  margin-top: 1.5rem;
  display: flex;
  justify-content: flex-end;
  gap: 0.75rem;
}

.btn-cancel {
  padding: 0.75rem 1.25rem;
  border-radius: 20px;
  border: none;
  background: linear-gradient(180deg, #ef5350 0%, #e53935 100%);
  color: #b71c1c;
  font-weight: 900;
  cursor: pointer;
  box-shadow: 0 4px 0 #b71c1c, 0 6px 12px rgba(0, 0, 0, 0.25);
  transition: all 0.2s ease;
  text-shadow: 1px 1px 0 rgba(255, 255, 255, 0.5);
}

.btn-cancel:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 0 #b71c1c, 0 8px 18px rgba(0, 0, 0, 0.3);
}

.btn-cancel:active {
  transform: translateY(2px);
  box-shadow: 0 2px 0 #b71c1c, 0 4px 10px rgba(0, 0, 0, 0.2);
}

.btn-confirm {
  padding: 0.75rem 1.25rem;
  border-radius: 20px;
  border: none;
  background: linear-gradient(180deg, #8bc34a 0%, #689f38 100%);
  color: #1b5e20;
  font-weight: 900;
  cursor: pointer;
  box-shadow: 0 4px 0 #33691e, 0 6px 12px rgba(0, 0, 0, 0.25);
  transition: all 0.2s ease;
  text-shadow: 1px 1px 0 rgba(255, 255, 255, 0.5);
}

.btn-confirm:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 0 #33691e, 0 8px 18px rgba(0, 0, 0, 0.3);
}

.btn-confirm:active {
  transform: translateY(2px);
  box-shadow: 0 2px 0 #33691e, 0 4px 10px rgba(0, 0, 0, 0.2);
}
</style>
