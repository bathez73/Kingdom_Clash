<template>
  <div v-if="isOpen" class="modal-overlay" @click.self="close">
    <div class="modal-content">
      <div class="text-center mb-6">
        <img :src="avatar" :alt="authUser?.name" class="w-24 h-24 rounded-full border-4 border-yellow-400 mx-auto mb-4 object-cover">
        <h2 class="text-2xl font-black" style="color: #3e2723;">Mon Profil 👤</h2>
      </div>
      <form @submit.prevent="handleSubmit">
        <label class="block font-bold text-lg mt-4" for="name" style="color: #5d4037;">Nom</label>
        <input 
          id="name" 
          v-model="form.name" 
          type="text" 
          required 
          class="w-full mt-2 p-4 rounded-xl border-3 text-lg font-semibold" 
          style="border-color: #8d6e63; background: rgba(255, 255, 255, 0.95); color: #3e2723;"
          placeholder="Votre nom"
        >

        <label class="block font-bold text-lg mt-4" for="email" style="color: #5d4037;">Email</label>
        <input 
          id="email" 
          v-model="form.email" 
          type="email" 
          required 
          class="w-full mt-2 p-4 rounded-xl border-3 text-lg font-semibold" 
          style="border-color: #8d6e63; background: rgba(255, 255, 255, 0.95); color: #3e2723;"
          placeholder="Votre email"
        >

        <label class="block font-bold text-lg mt-4" for="password" style="color: #5d4037;">Nouveau mot de passe (optionnel)</label>
        <input 
          id="password" 
          v-model="form.password" 
          type="password" 
          class="w-full mt-2 p-4 rounded-xl border-3 text-lg font-semibold" 
          style="border-color: #8d6e63; background: rgba(255, 255, 255, 0.95); color: #3e2723;"
          placeholder="Votre nouveau mot de passe (laisser vide pour ne pas changer)"
        >

        <div v-if="error" class="error mt-4 p-4 rounded-xl text-center font-bold" style="background: rgba(244,67,54,0.12); color: #c62828; border: 2px solid #ef5350;">{{ error }}</div>
        <div v-if="success" class="mt-4 p-4 rounded-xl text-center font-bold" style="background: rgba(76,175,80,0.12); color: #2e7d32; border: 2px solid #4caf50;">{{ success }}</div>

        <div class="flex gap-4 mt-6">
          <button 
            type="button" 
            @click="handleLogout" 
            class="flex-1 py-4 rounded-2xl font-black text-xl cursor-pointer transition-all"
            style="background: linear-gradient(180deg, #ef5350 0%, #c62828 100%); color: white; box-shadow: 0 4px 0 #7f0000, 0 6px 12px rgba(0,0,0,0.25);"
          >
             Déconnexion
          </button>
          <button 
            type="button" 
            @click="close" 
            class="flex-1 py-4 rounded-2xl font-black text-xl cursor-pointer transition-all"
            style="background: linear-gradient(180deg, #9e9e9e 0%, #616161 100%); color: white; box-shadow: 0 4px 0 #424242, 0 6px 12px rgba(0,0,0,0.25);"
          >
            Annuler
          </button>
          <button 
            type="submit" 
            :disabled="loading"
            class="flex-1 py-4 rounded-2xl font-black text-xl cursor-pointer transition-all"
            style="background: linear-gradient(180deg, #4caf50 0%, #2e7d32 100%); color: white; box-shadow: 0 4px 0 #1b5e20, 0 6px 12px rgba(0,0,0,0.25);"
          >
            {{ loading ? 'Mise à jour...' : 'Sauvegarder' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { ref, watch, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'
import { getAvatarById } from '../useAvatars'
import { updateUser, logout as logoutApi } from '../services/api'

export default {
  props: {
    isOpen: { type: Boolean, default: false }
  },
  emits: ['close'],
  setup(props, { emit }) {
    const router = useRouter()
    const { authUser, refreshAuthUser, clearAuth } = useAuth()
    const loading = ref(false)
    const error = ref(null)
    const success = ref(null)
    const form = ref({
      name: '',
      email: '',
      password: ''
    })

    const avatar = computed(() => {
      if (authUser.value?.id) {
        return getAvatarById(authUser.value.id)
      }
      return getAvatarById(1)
    })

    watch(() => props.isOpen, (newVal) => {
      if (newVal && authUser.value) {
        form.value = {
          name: authUser.value.name || '',
          email: authUser.value.email || '',
          password: ''
        }
        error.value = null
        success.value = null
      }
    })

    const close = () => {
      emit('close')
    }

    const handleSubmit = async () => {
      loading.value = true
      error.value = null
      success.value = null

      try {
        const data = {
          name: form.value.name,
          email: form.value.email
        }
        if (form.value.password) {
          data.password = form.value.password
        }
        await updateUser(data)
        await refreshAuthUser()
        success.value = 'Profil mis à jour avec succès !'
        setTimeout(() => {
          close()
        }, 1500)
      } catch (e) {
        error.value = e?.response?.data?.message || 'Erreur lors de la mise à jour'
      } finally {
        loading.value = false
      }
    }

    const handleLogout = async () => {
      try {
        await logoutApi()
      } catch (e) {
        // Ignore errors on logout
      }
      clearAuth()
      close()
      router.push('/login')
    }

    return { authUser, avatar, loading, error, success, form, close, handleSubmit, handleLogout }
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
  padding: 2rem;
  border-radius: 20px;
  max-width: 600px;
  width: 90%;
  border: 4px solid #6d4c41;
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.4), inset 0 2px 0 rgba(255, 255, 255, 0.5);
}

.modal-content button:hover:not(:disabled) {
  transform: translateY(-2px);
}

.modal-content button:active:not(:disabled) {
  transform: translateY(2px);
}

.modal-content button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
