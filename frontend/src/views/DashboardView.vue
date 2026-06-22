<template>
  <div class="dashboard-container">
    <div class="dashboard-overlay"></div>
    <div class="dashboard-content">
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
    <div class="grid grid-cols-3 gap-8 mb-12">
      <router-link 
        to="/war-arena" 
        class="quick-action text-center p-8 rounded-3xl cursor-pointer transition-all hover:scale-105"
        style="background: linear-gradient(180deg, #e53935 0%, #b71c1c 100%); border: 4px solid #ef5350; box-shadow: 0 6px 0 #7f0000, 0 10px 25px rgba(0,0,0,0.4);"
      >
        <span class="text-4xl">⚔️</span>
        <h2 class="text-2xl font-black text-white mt-3">Guerre des Royaumes</h2>
      </router-link>
      <router-link 
        to="/cards" 
        class="quick-action text-center p-8 rounded-3xl cursor-pointer transition-all hover:scale-105"
        style="background: linear-gradient(180deg, #43a047 0%, #1b5e20 100%); border: 4px solid #81c784; box-shadow: 0 6px 0 #0d3d0f, 0 10px 25px rgba(0,0,0,0.4);"
      >
        <span class="text-4xl">🃏</span>
        <h2 class="text-2xl font-black text-white mt-3">Cartes de Troupes</h2>
      </router-link>
      <router-link 
        to="/royaume" 
        class="quick-action text-center p-8 rounded-3xl cursor-pointer transition-all hover:scale-105"
        style="background: linear-gradient(180deg, #1e88e5 0%, #0d47a1 100%); border: 4px solid #42a5f5; box-shadow: 0 6px 0 #0a3d91, 0 10px 25px rgba(0,0,0,0.4);"
      >
        <span class="text-4xl">🏰</span>
        <h2 class="text-2xl font-black text-white mt-3">Mon Royaume</h2>
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
          v-for="(chest, index) in chests" 
          :key="chest.id || index"
          @click="handleChestClick(chest)"
          class="chest-slot text-center p-8 rounded-2xl cursor-pointer transition-all hover:scale-105"
          :class="{ 
            'chest-locked': getChestDisplay(chest).state === 'locked', 
            'chest-ready': getChestDisplay(chest).state === 'ready', 
            'chest-empty': getChestDisplay(chest).state === 'empty'
          }"
        >
          <span class="text-6xl" :class="{ 'chest-animation': getChestDisplay(chest).state === 'ready' }">
            {{ getChestDisplay(chest).icon }}
          </span>
          <div class="text-xl font-bold mt-4">{{ getChestDisplay(chest).label }}</div>
          <div v-if="getChestDisplay(chest).state === 'unlocking'" class="text-sm font-bold text-yellow-300 mt-1">
            ⏱️ {{ formatTimer(chest) }}
          </div>
        </div>
      </div>
    </div>


    <!-- Arena + XP Bar -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
      <ArenaDisplay :trophies="authUser?.trophies || 0" />
      <div class="p-6 rounded-3xl" style="background: linear-gradient(180deg, #311b92 0%, #1a0a5e 100%); border: 3px solid #7c4dff;">
        <div class="text-white font-black text-xl mb-4">Progression du joueur</div>
        <XpBar :level="authUser?.level || 1" :xp="authUser?.xp || 0" />
        <div class="grid grid-cols-2 gap-3 mt-4 text-center">
          <div class="bg-black/30 rounded-xl p-3">
            <div class="text-yellow-300 font-black text-2xl">{{ authUser?.trophies || 0 }}</div>
            <div class="text-purple-200 text-sm">Trophées</div>
          </div>
          <div class="bg-black/30 rounded-xl p-3">
            <div class="text-blue-300 font-black text-2xl">{{ authUser?.gems || 0 }} 💎</div>
            <div class="text-purple-200 text-sm">Gemmes</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quetes journalieres -->
    <div class="mb-8">
      <QuestsPanel
        :quests="quests"
        :loading="questsLoading"
        @claim="handleClaimQuest"
      />
    </div>
    <!-- Arena and XP -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
      <ArenaDisplay :trophies="authUser?.trophies || 0" />
      <div class="p-6 rounded-3xl" style="background: linear-gradient(180deg, #311b92 0%, #1a0a5e 100%); border: 3px solid #7c4dff;">
        <div class="text-white font-black text-xl mb-4">Progression du joueur</div>
        <XpBar :level="authUser?.level || 1" :xp="authUser?.xp || 0" />
        <div class="grid grid-cols-2 gap-3 mt-4 text-center">
          <div class="bg-black/30 rounded-xl p-3"><div class="text-yellow-300 font-black text-2xl">{{ authUser?.trophies || 0 }}</div><div class="text-purple-200 text-sm">Trophees</div></div>
          <div class="bg-black/30 rounded-xl p-3"><div class="text-blue-300 font-black text-2xl">{{ authUser?.gems || 0 }}</div><div class="text-purple-200 text-sm">Gemmes</div></div>
        </div>
      </div>
    </div>
    <!-- Quetes journalieres -->
    <div class="mb-8"><QuestsPanel :quests="quests" :loading="questsLoading" @claim="handleClaimQuest" /></div>
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
    @close="showConfirmModal = false"
  />
  <SuccessModal 
    :is-open="showSuccessModal" 
    icon="🎉" 
    title="Récompense débloquée !"
    :message="successModalMessage"
    @close="showSuccessModal = false"
  />
    </div>
  </div>
</template>

<script>
import GameBg from '../assets/backgrounds/game.jpg'
import { ref, onMounted } from 'vue'
import { useAuth } from '../composables/useAuth'
import { getKingdom, getChests, startUnlockChest, openChest, getQuests, claimQuest } from '../services/api'
import Modal from '../components/Modal.vue'
import SuccessModal from '../components/SuccessModal.vue'
import QuestsPanel from '../components/QuestsPanel.vue'
import ArenaDisplay from '../components/ArenaDisplay.vue'
import XpBar from '../components/XpBar.vue'

const chestIcons = {
  silver: '🥈',
  gold: '🥇',
  magical: '✨'
}

const chestLabels = {
  silver: 'Coffre Argent',
  gold: 'Coffre Or',
  magical: 'Coffre Magique'
}

export default {
  components: { Modal, SuccessModal, QuestsPanel, ArenaDisplay, XpBar },
  setup() {
    const { authUser, refreshAuthUser } = useAuth()
    const showDashboard = ref(false)
    const showConfirmModal = ref(false)
    const showSuccessModal = ref(false)
    const confirmModalMessage = ref('')
    const successModalMessage = ref('')
    const selectedChest = ref(null)
    const kingdom = ref(null)
    const chests = ref([])
    const quests = ref([])
    const questsLoading = ref(false)

    // Durées de déverrouillage par type (en secondes)
    const chestUnlockDurations = { silver: 3600, gold: 14400, magical: 43200 }

    const formatTimer = (chest) => {
      if (!chest.unlock_ends_at) return ''
      const ends = new Date(chest.unlock_ends_at)
      const diffMs = ends - new Date()
      if (diffMs <= 0) return 'Prêt !'
      const h = Math.floor(diffMs / 3600000)
      const m = Math.floor((diffMs % 3600000) / 60000)
      const s = Math.floor((diffMs % 60000) / 1000)
      if (h > 0) return `${h}h ${m}m`
      if (m > 0) return `${m}m ${s}s`
      return `${s}s`
    }

    const loadData = async () => {
      await refreshAuthUser()
      try {
        const [kingdomRes, chestsRes] = await Promise.all([
          getKingdom(),
          getChests()
        ])
        kingdom.value = kingdomRes.data.kingdom
        chests.value = chestsRes.data.chests || []
        // Ensure we always have 4 chest slots
        while (chests.value.length < 4) {
          chests.value.push({
            id: null,
            type: 'empty',
            status: 'empty'
          })
        }
      } catch (e) {
        console.error('Erreur chargement données:', e)
      }
    }

    const getChestDisplay = (chest) => {
      if (chest.status === 'empty' || chest.type === 'empty') {
        return { icon: '📦', label: 'Vide', state: 'empty' }
      }
      return {
        icon: chestIcons[chest.type] || '📦',
        label: chest.status === 'unlocking' ? 'Déverrouillage...' : (chest.status === 'ready' ? 'Prêt !' : 'Fermé'),
        state: chest.status
      }
    }

    const handleChestClick = (chest) => {
      const display = getChestDisplay(chest)
      if (display.state === 'empty') return
      
      selectedChest.value = chest
      if (display.state === 'locked') {
        confirmModalMessage.value = `Voulez-vous commencer à déverrouiller le ${chestLabels[chest.type]} ?`
        showConfirmModal.value = true
      } else if (display.state === 'ready') {
        confirmModalMessage.value = `Voulez-vous ouvrir le ${chestLabels[chest.type]} ?`
        showConfirmModal.value = true
      }
    }

    const confirmChestAction = async () => {
      if (!selectedChest.value) return
      
      const display = getChestDisplay(selectedChest.value)
      
      try {
        if (display.state === 'ready') {
          const res = await openChest(selectedChest.value.id)
          const reward = res.data.reward
          let rewardText = ''
          if (reward.gold) rewardText += `${reward.gold} Or `
          if (reward.gems) rewardText += `${reward.gems} Gemmes `
          successModalMessage.value = `Vous avez reçu ${rewardText.trim()} !`
          showSuccessModal.value = true
        } else if (display.state === 'locked') {
          await startUnlockChest(selectedChest.value.id)
          successModalMessage.value = `Deverrouillage commencé ! Revenez plus tard pour l'ouvrir !`
          showSuccessModal.value = true
        }
        
        await loadData()
      } catch (e) {
        console.error('Erreur coffre:', e)
        successModalMessage.value = e?.response?.data?.message || 'Erreur avec le coffre'
        showSuccessModal.value = true
      }
      
      showConfirmModal.value = false
      selectedChest.value = null
    }

    const loadQuests = async () => {
      questsLoading.value = true
      try {
        const res = await getQuests()
        quests.value = res.data.quests
      } catch (e) {
        console.error('Erreur quetes:', e)
      } finally {
        questsLoading.value = false
      }
    }

    const handleClaimQuest = async (questId) => {
      try {
        const res = await claimQuest(questId)
        let msg = 'Recompense reclamee !'
        const r = res.data.reward
        if (r.gold) msg += ` +${r.gold} Or`
        if (r.wood) msg += ` +${r.wood} Bois`
        if (r.food) msg += ` +${r.food} Nourriture`
        if (r.xp)   msg += ` +${r.xp} XP`
        if (res.data.leveled_up) msg += ` Niveau ${res.data.new_level} atteint !`
        successModalMessage.value = msg
        showSuccessModal.value = true
        await Promise.all([loadQuests(), refreshAuthUser(), loadData()])
      } catch (e) {
        console.error('Erreur reclamation quete:', e)
      }
    }

    onMounted(async () => {
      await loadData()
      await loadQuests()
      // Rafraichir les timers coffres chaque seconde
      setInterval(() => { chests.value = [...chests.value] }, 1000)
    })

    return { 
      authUser, 
      kingdom,
      showDashboard,
      chests,
      quests,
      questsLoading,
      showConfirmModal, 
      showSuccessModal, 
      confirmModalMessage, 
      successModalMessage, 
      handleChestClick, 
      confirmChestAction,
      getChestDisplay,
      handleClaimQuest,
      formatTimer,
    }
  }
}
</script>

<style scoped>
.dashboard-container {
  position: relative;
  min-height: 100vh;
  background-image: url('src/assets/backgrounds/game.jpg');
  background-size: cover;
  background-position: center;
  background-attachment: fixed;
}

.dashboard-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(135deg, rgba(15, 23, 42, 0.88) 0%, rgba(30, 41, 59, 0.8) 100%);
  z-index: 0;
}

.dashboard-content {
  position: relative;
  z-index: 1;
  max-width: 1200px;
  margin: 0 auto;
  padding: 1rem;
}

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



