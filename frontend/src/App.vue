<template>
  <div class="min-h-screen relative z-10">
    <!-- User Profile Modal -->
    <UserProfileModal :isOpen="showProfileModal" @close="showProfileModal = false" />

    <!-- Full layout with header when logged in -->
    <template v-if="token">
      <!-- Single Combined Navbar -->
      <header class="bg-gradient-to-r from-yellow-700 to-yellow-600 px-6 py-4 shadow-lg">
        <div class="flex items-center justify-between max-w-7xl mx-auto">
          <!-- Left: Logo & Level -->
          <div class="flex items-center gap-4">
            <span class="text-3xl">⚔️</span>
            <span class="text-white font-black text-xl">Niv {{ authUser?.level || 1 }}</span>
          </div>

          <!-- Center: Main Navigation -->
          <nav class="flex items-center gap-8">
            <router-link 
              to="/royaume" 
              class="flex flex-col items-center gap-1 text-gray-200 hover:text-white transition-colors"
              active-class="text-yellow-300"
            >
              <span class="text-2xl">🏰</span>
              <span class="text-xs font-bold">Royaume</span>
            </router-link>
            
            <router-link 
              to="/historique-batailles" 
              class="flex flex-col items-center gap-1 text-gray-200 hover:text-white transition-colors"
              active-class="text-yellow-300"
            >
              <span class="text-2xl">📜</span>
              <span class="text-xs font-bold">Historique</span>
            </router-link>
            
            <router-link 
              to="/battle" 
              class="flex flex-col items-center gap-1 text-gray-200 hover:text-white transition-colors"
              active-class="text-yellow-300"
            >
              <span class="text-3xl">⚔️</span>
              <span class="text-xs font-bold">Bataille</span>
            </router-link>
            
            <router-link 
              to="/classement" 
              class="flex flex-col items-center gap-1 text-gray-200 hover:text-white transition-colors"
              active-class="text-yellow-300"
            >
              <span class="text-2xl">🏆</span>
              <span class="text-xs font-bold">Classement</span>
            </router-link>
            
            <router-link 
              to="/cards" 
              class="flex flex-col items-center gap-1 text-gray-200 hover:text-white transition-colors"
              active-class="text-yellow-300"
            >
              <span class="text-2xl">🃏</span>
              <span class="text-xs font-bold">Cartes</span>
            </router-link>
          </nav>

          <!-- Right: Stats -->
          <div class="flex items-center gap-6">
            <!-- User Avatar (Clickable!) -->
            <img 
              :src="userAvatar" 
              :alt="authUser?.name" 
              class="w-12 h-12 rounded-full border-2 border-yellow-300 object-cover cursor-pointer hover:scale-110 transition-all"
              @click="showProfileModal = true"
              title="Cliquez pour modifier votre profil"
            >
            <!-- Trophies -->
            <div class="flex items-center gap-2 bg-black/30 rounded-full px-4 py-2">
              <span class="text-2xl">🏆</span>
              <span class="text-white font-bold text-lg">{{ authUser?.trophies || 0 }}</span>
            </div>
            
            <!-- Gold -->
            <div class="flex items-center gap-2 bg-black/30 rounded-full px-4 py-2">
              <span class="text-2xl">💰</span>
              <span class="text-yellow-200 font-bold text-lg">{{ authUser?.gold || 0 }}</span>
            </div>
          </div>
        </div>
      </header>

      <!-- Main Content -->
      <main class="p-6 max-w-7xl mx-auto">
        <router-view />
      </main>
    </template>

    <!-- Simple layout for login/register pages -->
    <template v-else>
      <main class="min-h-screen p-6">
        <router-view />
      </main>
    </template>
  </div>
</template>

<script>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from './composables/useAuth'
import { getAvatarById } from './useAvatars'
import UserProfileModal from './components/UserProfileModal.vue'

export default {
  components: { UserProfileModal },
  setup() {
    const router = useRouter()
    const { token, authUser, refreshAuthUser } = useAuth()
    const showProfileModal = ref(false)

    const userAvatar = computed(() => {
      if (authUser.value?.id) {
        return getAvatarById(authUser.value.id)
      }
      return getAvatarById(1)
    })

    return { token, authUser, userAvatar, showProfileModal }
  },
}
</script>

<style scoped>
/* Custom scrollbar */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: rgba(0, 0, 0, 0.2);
}

::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.3);
  border-radius: 4px;
}
</style>
