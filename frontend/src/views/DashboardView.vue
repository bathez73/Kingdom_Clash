<template>
  <div v-if="!showDashboard" class="flex items-center justify-center min-h-[70vh]">
    <div class="text-center max-w-2xl">
      <h1 class="text-5xl font-black text-white mb-6 drop-shadow-xl">
        Bon retour, Commandant ! 👋
      </h1>
      <p class="text-2xl text-yellow-100 mb-10">
        Prêt à conquérir ?
      </p>
      <button 
        @click="showDashboard = true"
        class="px-16 py-7 rounded-3xl font-black text-3xl cursor-pointer transition-all hover:scale-105"
        style="background: linear-gradient(180deg, #ffc107 0%, #ff9800 100%); color: #3e2723; box-shadow: 0 8px 0 #e65100, 0 12px 30px rgba(0,0,0,0.4);"
      >
        🚀 Commencer !
      </button>
    </div>
  </div>

  <div v-else class="py-10">
    <!-- Quick Actions -->
    <div class="grid grid-cols-2 gap-8 mb-12">
      <router-link 
        to="/battle" 
        class="quick-action text-center p-8 rounded-3xl cursor-pointer transition-all hover:scale-105"
        style="background: linear-gradient(180deg, #e53935 0%, #b71c1c 100%); border: 4px solid #ef5350; box-shadow: 0 6px 0 #7f0000, 0 10px 25px rgba(0,0,0,0.4);"
      >
        <span class="text-4xl">⚔️</span>
        <h2 class="text-2xl font-black text-white mt-3">Bataille</h2>
      </router-link>
      <router-link 
        to="/royaume" 
        class="quick-action text-center p-8 rounded-3xl cursor-pointer transition-all hover:scale-105"
        style="background: linear-gradient(180deg, #1e88e5 0%, #0d47a1 100%); border: 4px solid #42a5f5; box-shadow: 0 6px 0 #0a3d91, 0 10px 25px rgba(0,0,0,0.4);"
      >
        <span class="text-4xl">🏰</span>
        <h2 class="text-2xl font-black text-white mt-3">Royaume</h2>
      </router-link>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-8 mb-12">
      <div class="stat-card text-center p-8 rounded-3xl" style="background: linear-gradient(180deg, #ffc107 0%, #f57c00 100%); border: 4px solid #ffeb3b; box-shadow: 0 6px 0 #e65100, 0 10px 25px rgba(0,0,0,0.4);">
        <span class="text-5xl">🏆</span>
        <div class="text-3xl font-black text-white mt-3">{{ authUser?.trophies || 0 }}</div>
        <div class="text-xl font-bold text-yellow-100">Trophées</div>
      </div>
      <div class="stat-card text-center p-8 rounded-3xl" style="background: linear-gradient(180deg, #7c4dff 0%, #311b92 100%); border: 4px solid #b388ff; box-shadow: 0 6px 0 #1a237e, 0 10px 25px rgba(0,0,0,0.4);">
        <span class="text-5xl">⭐</span>
        <div class="text-3xl font-black text-white mt-3">Niv {{ authUser?.level || 1 }}</div>
        <div class="text-xl font-bold text-purple-100">Niveau</div>
      </div>
      <div class="stat-card text-center p-8 rounded-3xl" style="background: linear-gradient(180deg, #ff9800 0%, #e65100 100%); border: 4px solid #ffb74d; box-shadow: 0 6px 0 #bf360c, 0 10px 25px rgba(0,0,0,0.4);">
        <span class="text-5xl">💰</span>
        <div class="text-3xl font-black text-white mt-3">{{ authUser?.gold || 0 }}</div>
        <div class="text-xl font-bold text-orange-100">Or</div>
      </div>
    </div>

    <!-- Chests -->
    <div class="chests-section mb-12 p-10 rounded-3xl" style="background: linear-gradient(180deg, #6d4c41 0%, #3e2723 100%); border: 4px solid #8d6e63;">
      <h2 class="text-3xl font-black text-white mb-8 flex items-center gap-4">
        📦 Emplacements de coffres
      </h2>
      <div class="grid grid-cols-4 gap-8">
        <div 
          v-for="chest in chests" 
          :key="chest.id"
          @click="handleChestClick(chest)"
          class="chest-slot text-center p-8 rounded-2xl cursor-pointer transition-all hover:scale-105"
          :class="{ 
            'chest-locked': chest.state === 'locked', 
            'chest-ready': chest.state === 'ready', 
            'chest-empty': chest.state === 'empty'
          }"
        >
          <span class="text-6xl" :class="{ 'chest-animation': chest.state === 'ready' }">
            {{ chest.icon }}
          </span>
          <div class="text-xl font-bold mt-4" :style="{ color: chest.textColor }">{{ chest.label }}</div>
        </div>
      </div>
    </div>

    <!-- Bottom Links -->
    <div class="grid grid-cols-2 gap-8">
      <router-link 
        to="/historique-batailles" 
        class="quick-action text-center p-7 rounded-3xl cursor-pointer transition-all hover:scale-105"
        style="background: linear-gradient(180deg, #616161 0%, #37474f 100%); border: 4px solid #9e9e9e; box-shadow: 0 6px 0 #263238, 0 10px 25px rgba(0,0,0,0.4);"
      >
        <span class="text-3xl">📜</span>
        <h3 class="text-xl font-black text-white mt-2">Historique</h3>
      </router-link>
      <router-link 
        to="/classement" 
        class="quick-action text-center p-7 rounded-3xl cursor-pointer transition-all hover:scale-105"
        style="background: linear-gradient(180deg, #43a047 0%, #1b5e20 100%); border: 4px solid #81c784; box-shadow: 0 6px 0 #0d3d0f, 0 10px 25px rgba(0,0,0,0.4);"
      >
        <span class="text-3xl">🏆</span>
        <h3 class="text-xl font-black text-white mt-2">Classement</h3>
      </router-link>
    </div>
  </div>

  <!-- Modals -->
  <Modal 
    :is-open="showConfirmModal" 
    icon="📦" 
    title="Ouvrir le coffre ?"
    :message="confirmModalMessage"
    @confirm="confirmChestAction"
    @cancel="showConfirmModal = false"
  />
  <SuccessModal 
    :is-open="showSuccessModal" 
    icon="🎉" 
    title="Récompense débloquée !"
    :message="successModalMessage"
    @close="showSuccessModal = false"
  />
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useAuth } from '../composables/useAuth'
import Modal from '../components/Modal.vue'
import SuccessModal from '../components/SuccessModal.vue'

export default {
  components: { Modal, SuccessModal },
  setup() {
    const { authUser } = useAuth()
    const showDashboard = ref(false)
    const showConfirmModal = ref(false)
    const showSuccessModal = ref(false)
    const confirmModalMessage = ref('')
    const successModalMessage = ref('')
    const selectedChest = ref(null)

    const chests = ref([
      { id: 1, state: 'locked', icon: '🥈', label: 'Fermé', reward: '50 Or' },
      { id: 2, state: 'ready', icon: '🥇', label: 'Prêt !', reward: '100 Or' },
      { id: 3, state: 'locked', icon: '✨', label: 'Fermé', reward: '150 Or' },
      { id: 4, state: 'empty', icon: '📦', label: 'Vide', reward: null }
    ])

    const handleChestClick = (chest) => {
      if (chest.state === 'empty') return
      
      if (chest.state === 'locked') {
        selectedChest.value = chest
        confirmModalMessage.value = `Voulez-vous déverrouiller un coffre ${chest.label} ?`
        showConfirmModal.value = true
      } else if (chest.state === 'ready') {
        selectedChest.value = chest
        confirmModalMessage.value = `Voulez-vous ouvrir le coffre et recevoir ${chest.reward} ?`
        showConfirmModal.value = true
      }
    }

    const confirmChestAction = () => {
      if (!selectedChest.value) return
      
      if (selectedChest.value.state === 'ready') {
        successModalMessage.value = `Vous avez reçu ${selectedChest.value.reward} !`
        selectedChest.value.state = 'empty'
        selectedChest.value.icon = '📦'
        selectedChest.value.label = 'Vide'
        showSuccessModal.value = true
      } else {
        successModalMessage.value = `Coffre déverrouillé ! Il sera prêt bientôt !`
        selectedChest.value.state = 'ready'
        selectedChest.value.label = 'Prêt !'
        showSuccessModal.value = true
      }
      
      showConfirmModal.value = false
      selectedChest.value = null
    }

    return { 
      authUser, 
      showDashboard,
      chests, 
      showConfirmModal, 
      showSuccessModal, 
      confirmModalMessage, 
      successModalMessage, 
      handleChestClick, 
      confirmChestAction 
    }
  }
}
</script>

<style scoped>
.chest-slot {
  background: rgba(0,0,0,0.2);
  border: 3px solid #8d6e63;
  transition: all 0.3s ease;
}

.chest-locked {
  background: linear-gradient(180deg, #78909c 0%, #455a64 100%);
  border-color: #90a4ae;
}

.chest-ready {
  background: linear-gradient(180deg, #ffc107 0%, #f57c00 100%);
  border-color: #ffeb3b;
}

.chest-empty {
  opacity: 0.4;
  cursor: default !important;
}

.chest-ready:hover {
  transform: scale(1.05);
  box-shadow: 0 0 30px rgba(255,193,7,0.6);
}

.chest-animation {
  animation: bounce 1s infinite;
}

@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-10px); }
}
</style>
