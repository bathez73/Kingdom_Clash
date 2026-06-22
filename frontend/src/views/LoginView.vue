<template>
  <div class="min-h-screen flex items-center justify-center p-8">
    <div class="w-full max-w-md">
      <div class="text-center mb-8">
        <span class="text-8xl">⚔️</span>
        <h1 class="text-4xl font-black text-white mt-4 drop-shadow-lg">Kingdom Clash</h1>
        <p class="text-xl text-yellow-100 mt-2">Connectez-vous pour commencer votre aventure !</p>
      </div>
      
      <div class="bg-white/10 backdrop-blur-xl border-3 border-yellow-400 rounded-3xl p-10 shadow-2xl">
        <form @submit.prevent="handleLogin">
          <label 
            for="email" 
            class="block font-bold text-xl mb-4 text-white"
          >
            Email
          </label>
          <input 
            id="email" 
            v-model="form.email" 
            type="email" 
            required 
            class="w-full p-6 rounded-2xl border-4 text-xl font-semibold mb-6 bg-white/90 focus:outline-none focus:ring-4 focus:ring-yellow-400/50"
            style="border-color: #d4a853; color: #3e2723;"
            placeholder="Votre email"
          >

          <label 
            for="password" 
            class="block font-bold text-xl mb-4 text-white"
          >
            Mot de passe
          </label>
          <input 
            id="password" 
            v-model="form.password" 
            type="password" 
            required 
            class="w-full p-6 rounded-2xl border-4 text-xl font-semibold mb-8 bg-white/90 focus:outline-none focus:ring-4 focus:ring-yellow-400/50"
            style="border-color: #d4a853; color: #3e2723;"
            placeholder="Votre mot de passe"
          >

          <div v-if="error" class="error p-6 rounded-2xl text-center font-bold mb-8" style="background: rgba(244,67,54,0.15); color: #ffcdd2; border: 3px solid #ef5350;">
            {{ error }}
          </div>

          <button 
            type="submit" 
            :disabled="loading"
            class="w-full py-6 rounded-2xl font-black text-2xl cursor-pointer transition-all"
            style="background: linear-gradient(180deg, #ffc107 0%, #ff9800 100%); color: #3e2723; box-shadow: 0 6px 0 #e65100, 0 10px 25px rgba(0,0,0,0.3);"
          >
            {{ loading ? 'Connexion en cours...' : 'Se connecter' }}
          </button>
        </form>

        <div class="text-center mt-10">
          <p class="text-yellow-100 text-xl">Pas encore de compte ?</p>
          <router-link 
            to="/register" 
            class="inline-block font-bold text-2xl text-yellow-300 hover:text-yellow-100 mt-3 transition-colors"
          >
            Créer un compte →
          </router-link>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'
import { login } from '../services/api'

export default {
  setup() {
    const router = useRouter()
    const { setAuth } = useAuth()
    const loading = ref(false)
    const error = ref(null)
    const form = ref({
      email: '',
      password: ''
    })

    const handleLogin = async () => {
      loading.value = true
      error.value = null

      try {
        const response = await login(form.value.email, form.value.password)
        setAuth(response.data)
        router.push('/')
      } catch (e) {
        error.value = e?.response?.data?.message || 'Identifiants incorrects'
      } finally {
        loading.value = false
      }
    }

    return { loading, error, form, handleLogin }
  }
}
</script>
