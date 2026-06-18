<template>
  <div class="kingdom-view py-10">
    <div class="welcome-banner text-center mb-12 p-10 rounded-3xl" style="background: linear-gradient(180deg, #1565c0 0%, #0d47a1 100%); border: 4px solid #2196f3; box-shadow: 0 8px 30px rgba(0,0,0,0.5);">
      <h1 class="text-4xl font-black text-white mb-3 flex items-center justify-center gap-4">
        🏰 Mon Royaume
      </h1>
      <p class="text-xl text-blue-100">Gérez votre empire !</p>
    </div>

    <div class="kingdom-stats grid grid-cols-3 gap-8 mb-12">
      <div class="stat-card text-center p-8 rounded-3xl" style="background: linear-gradient(180deg, #ffc107 0%, #f57c00 100%); border: 4px solid #ffeb3b; box-shadow: 0 6px 0 #e65100, 0 10px 25px rgba(0,0,0,0.4);">
        <span class="text-5xl">💰</span>
        <div class="text-3xl font-black text-white mt-3">{{ kingdom?.gold || 0 }}</div>
        <div class="text-xl font-bold text-yellow-100">Or</div>
      </div>
      <div class="stat-card text-center p-8 rounded-3xl" style="background: linear-gradient(180deg, #4caf50 0%, #2e7d32 100%); border: 4px solid #81c784; box-shadow: 0 6px 0 #1b5e20, 0 10px 25px rgba(0,0,0,0.4);">
        <span class="text-5xl">🪵</span>
        <div class="text-3xl font-black text-white mt-3">{{ kingdom?.wood || 0 }}</div>
        <div class="text-xl font-bold text-green-100">Bois</div>
      </div>
      <div class="stat-card text-center p-8 rounded-3xl" style="background: linear-gradient(180deg, #e91e63 0%, #ad1457 100%); border: 4px solid #f48fb1; box-shadow: 0 6px 0 #880e4f, 0 10px 25px rgba(0,0,0,0.4);">
        <span class="text-5xl">🍖</span>
        <div class="text-3xl font-black text-white mt-3">{{ kingdom?.food || 0 }}</div>
        <div class="text-xl font-bold text-pink-100">Nourriture</div>
      </div>
    </div>

    <div class="buildings-section mb-12 p-10 rounded-3xl" style="background: linear-gradient(180deg, #6d4c41 0%, #3e2723 100%); border: 4px solid #8d6e63;">
      <h2 class="text-3xl font-black text-white mb-8 flex items-center gap-4">
        🏗️ Mes Bâtiments
      </h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div 
          v-for="building in buildings" 
          :key="building.id"
          class="building-card text-center p-8 rounded-3xl"
          :style="{ 
            background: `linear-gradient(180deg, ${getBuildingColor(building.type, 1)} 0%, ${getBuildingColor(building.type, 2)} 100%)`,
            borderColor: getBuildingBorder(building.type),
            borderWidth: '4px',
            borderStyle: 'solid',
            boxShadow: `0 6px 0 ${getBuildingShadow(building.type)}, 0 10px 25px rgba(0,0,0,0.4)`
          }"
        >
          <div class="building-icon text-7xl mb-4">{{ getBuildingIcon(building.type) }}</div>
          <div class="building-name text-2xl font-black text-white mb-2">{{ getBuildingName(building.type) }}</div>
          <div class="building-level text-xl font-bold text-yellow-100 mb-4">
            Niveau {{ building.level }}
          </div>
          <div v-if="building.upgrade_ends_at" class="text-lg font-semibold text-yellow-300 mb-4">
            ⏱️ Amélioration en cours...
          </div>
          <div v-else class="building-cost text-lg font-semibold text-yellow-200 mb-6">
            Coût : 💰 {{ getUpgradeCost(building.type, building.level).gold }} 🪵 {{ getUpgradeCost(building.type, building.level).wood }}
          </div>
          <button 
            v-if="!building.upgrade_ends_at"
            @click="handleUpgradeBuilding(building)"
            class="w-full py-5 rounded-2xl font-black text-xl cursor-pointer transition-all"
            style="background: linear-gradient(180deg, #ffc107 0%, #ff9800 100%); color: #3e2723; box-shadow: 0 4px 0 #e65100, 0 8px 20px rgba(0,0,0,0.3);"
          >
            Améliorer ⬆️
          </button>
        </div>
      </div>
    </div>

    <div class="army-section p-10 rounded-3xl" style="background: linear-gradient(180deg, #4a148c 0%, #311b92 100%); border: 4px solid #7c4dff;">
      <h2 class="text-3xl font-black text-white mb-8 flex items-center gap-4">
        ⚔️ Mon Armée
      </h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div 
          v-for="soldier in soldiers" 
          :key="soldier.type"
          class="unit-card text-center p-8 rounded-3xl"
          :style="{ 
            background: `linear-gradient(180deg, ${getSoldierColor(soldier.type, 1)} 0%, ${getSoldierColor(soldier.type, 2)} 100%)`,
            borderColor: getSoldierBorder(soldier.type),
            borderWidth: '4px',
            borderStyle: 'solid',
            boxShadow: `0 6px 0 ${getSoldierShadow(soldier.type)}, 0 10px 25px rgba(0,0,0,0.4)`
          }"
        >
          <div class="unit-icon text-7xl mb-4">{{ getSoldierIcon(soldier.type) }}</div>
          <div class="unit-name text-2xl font-black text-white mb-2">{{ getSoldierName(soldier.type) }}</div>
          <div class="unit-count text-3xl font-black text-yellow-100 mb-6">
            x{{ soldier.quantity }}
          </div>
          <div class="unit-cost text-lg font-semibold text-yellow-200 mb-6">
            Coût : 🍖 {{ getTrainCost(soldier.type) }}
          </div>
          <button 
            @click="handleTrainSoldier(soldier)"
            class="w-full py-5 rounded-2xl font-black text-xl cursor-pointer transition-all"
            style="background: linear-gradient(180deg, #4caf50 0%, #2e7d32 100%); color: white; box-shadow: 0 4px 0 #1b5e20, 0 8px 20px rgba(0,0,0,0.3);"
          >
            Entraîner +1
          </button>
        </div>
      </div>
    </div>
  </div>

  <Modal 
    :is-open="showConfirmModal" 
    icon="⬆️" 
    title="Améliorer le bâtiment ?"
    :message="confirmModalMessage"
    @confirm="confirmAction"
    @cancel="showConfirmModal = false"
  />
  <SuccessModal 
    :is-open="showSuccessModal" 
    icon="✨" 
    :title="successModalTitle"
    :message="successModalMessage"
    @close="showSuccessModal = false"
  />
  <ErrorModal 
    :is-open="showErrorModal" 
    icon="❌" 
    title="Erreur"
    :message="errorModalMessage"
    @close="showErrorModal = false"
  />
</template>

<script>
import { ref, onMounted } from 'vue'
import { getKingdom, getBuildings, getSoldiers, upgradeBuilding, trainSoldier } from '../services/api'
import Modal from '../components/Modal.vue'
import SuccessModal from '../components/SuccessModal.vue'
import ErrorModal from '../components/ErrorModal.vue'

export default {
  components: { Modal, SuccessModal, ErrorModal },
  setup() {
    const kingdom = ref(null)
    const buildings = ref([])
    const soldiers = ref([])
    const showConfirmModal = ref(false)
    const showSuccessModal = ref(false)
    const showErrorModal = ref(false)
    const confirmModalMessage = ref('')
    const successModalTitle = ref('')
    const successModalMessage = ref('')
    const errorModalMessage = ref('')
    const pendingAction = ref(null)

    const buildingIcons = {
      'gold_mine': '💰',
      'sawmill': '🪵',
      'farm': '🌾',
      'barracks': '⚔️'
    }
    const buildingNames = {
      'gold_mine': 'Mine d\'or',
      'sawmill': 'Scierie',
      'farm': 'Ferme',
      'barracks': 'Caserne'
    }
    const buildingColors = {
      'gold_mine': ['#ffc107', '#f57c00'],
      'sawmill': ['#4caf50', '#2e7d32'],
      'farm': ['#8bc34a', '#558b2f'],
      'barracks': ['#f44336', '#c62828']
    }
    const buildingBorders = {
      'gold_mine': '#ffeb3b',
      'sawmill': '#81c784',
      'farm': '#aed581',
      'barracks': '#ef5350'
    }
    const buildingShadows = {
      'gold_mine': '#e65100',
      'sawmill': '#1b5e20',
      'farm': '#33691e',
      'barracks': '#7f0000'
    }

    const soldierIcons = {
      'swordsman': '🗡️',
      'archer': '🏹',
      'cavalry': '🐴'
    }
    const soldierNames = {
      'swordsman': 'Épéiste',
      'archer': 'Archer',
      'cavalry': 'Cavalier'
    }
    const soldierColors = {
      'swordsman': ['#78909c', '#455a64'],
      'archer': ['#ff7043', '#e64a19'],
      'cavalry': ['#7c4dff', '#311b92']
    }
    const soldierBorders = {
      'swordsman': '#90a4ae',
      'archer': '#ffab91',
      'cavalry': '#b388ff'
    }
    const soldierShadows = {
      'swordsman': '#263238',
      'archer': '#bf360c',
      'cavalry': '#1a237e'
    }

    function getBuildingIcon(type) {
      return buildingIcons[type] || '🏠'
    }
    function getBuildingName(type) {
      return buildingNames[type] || type
    }
    function getBuildingColor(type, idx) {
      return buildingColors[type]?.[idx - 1] || '#9e9e9e'
    }
    function getBuildingBorder(type) {
      return buildingBorders[type] || '#757575'
    }
    function getBuildingShadow(type) {
      return buildingShadows[type] || '#424242'
    }

    function getUpgradeCost(type, level) {
      const baseCost = { gold: 100, wood: 50 }
      return {
        gold: Math.floor(baseCost.gold * Math.pow(1.5, level - 1)),
        wood: Math.floor(baseCost.wood * Math.pow(1.5, level - 1))
      }
    }

    function getSoldierIcon(type) {
      return soldierIcons[type] || '⚔️'
    }
    function getSoldierName(type) {
      return soldierNames[type] || type
    }
    function getSoldierColor(type, idx) {
      return soldierColors[type]?.[idx - 1] || '#9e9e9e'
    }
    function getSoldierBorder(type) {
      return soldierBorders[type] || '#757575'
    }
    function getSoldierShadow(type) {
      return soldierShadows[type] || '#424242'
    }

    function getTrainCost(type) {
      const costs = { swordsman: 20, archer: 30, cavalry: 50 }
      return costs[type] || 20
    }

    async function loadKingdomData() {
      try {
        const [kingdomRes, buildingsRes, soldiersRes] = await Promise.all([
          getKingdom(),
          getBuildings(),
          getSoldiers()
        ])
        kingdom.value = kingdomRes.data.kingdom
        buildings.value = buildingsRes.data.buildings || []
        soldiers.value = soldiersRes.data.soldiers || []
      } catch (e) {
        console.error('Error loading kingdom data:', e)
      }
    }

    function handleUpgradeBuilding(building) {
      pendingAction.value = { type: 'upgrade', building }
      const cost = getUpgradeCost(building.type, building.level)
      confirmModalMessage.value = `Améliorer ${getBuildingName(building.type)} au niveau ${building.level + 1} ? (💰${cost.gold} 🪵${cost.wood})`
      showConfirmModal.value = true
    }

    function handleTrainSoldier(soldier) {
      pendingAction.value = { type: 'train', soldier }
      confirmModalMessage.value = `Entraîner un ${getSoldierName(soldier.type)} pour 🍖${getTrainCost(soldier.type)} ?`
      showConfirmModal.value = true
    }

    async function confirmAction() {
      if (!pendingAction.value) return

      try {
        if (pendingAction.value.type === 'upgrade') {
          await upgradeBuilding(pendingAction.value.building.id)
          successModalTitle.value = 'Succès !'
          successModalMessage.value = `Amélioration de ${getBuildingName(pendingAction.value.building.type)} lancée !`
        } else {
          await trainSoldier(pendingAction.value.soldier.type)
          successModalTitle.value = 'Succès !'
          successModalMessage.value = `Soldat ${getSoldierName(pendingAction.value.soldier.type)} entraîné !`
        }

        showSuccessModal.value = true
        await loadKingdomData()
      } catch (e) {
        errorModalMessage.value = e?.response?.data?.message || 'Erreur lors de l\'action'
        showErrorModal.value = true
      } finally {
        showConfirmModal.value = false
        pendingAction.value = null
      }
    }

    onMounted(() => {
      loadKingdomData()
    })

    return {
      kingdom,
      buildings,
      soldiers,
      showConfirmModal,
      showSuccessModal,
      showErrorModal,
      confirmModalMessage,
      successModalTitle,
      successModalMessage,
      errorModalMessage,
      handleUpgradeBuilding,
      handleTrainSoldier,
      confirmAction,
      getBuildingIcon,
      getBuildingName,
      getBuildingColor,
      getBuildingBorder,
      getBuildingShadow,
      getUpgradeCost,
      getSoldierIcon,
      getSoldierName,
      getSoldierColor,
      getSoldierBorder,
      getSoldierShadow,
      getTrainCost
    }
  }
}
</script>
