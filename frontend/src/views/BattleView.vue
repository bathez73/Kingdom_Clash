<template>
  <div class="min-h-screen bg-gradient-to-b from-indigo-900 via-purple-900 to-black flex items-center justify-center p-4">
    <div class="w-full max-w-7xl">
      <!-- Confirmation Modal -->
      <Modal
        :is-open="showConfirmModal"
        title="⚔️ Déclarer la guerre !"
        :message="`Vous allez attaquer le territoire de ${opponent?.name}. Voulez-vous lancer l'assaut ?`"
        icon="⚔️"
        confirm-text="Attaquer !"
        cancel-text="Annuler"
        @confirm="startBattle"
        @close="showConfirmModal = false"
      />

      <!-- Search / Select Troops -->
      <div v-if="!battleStarted && !selectingTroops" class="text-center py-12">
        <h1 class="text-white font-black text-6xl mb-8 drop-shadow-lg">⚔️ Bataille pour le territoire ⚔️</h1>
        <button 
          @click="findOpponent"
          :disabled="isSearching"
          class="bg-gradient-to-b from-red-500 via-red-600 to-red-800 text-white font-black text-3xl px-16 py-8 rounded-3xl border-6 border-red-950 shadow-[0_12px_0_rgb(100,20,20)] hover:translate-y-[-4px] active:translate-y-[8px] transition-all disabled:opacity-50"
        >
          {{ isSearching ? "🔍 Recherche d'adversaire..." : "🎯 Trouver un adversaire !" }}
        </button>
      </div>

      <!-- Select Army -->
      <div v-if="selectingTroops && !battleStarted" class="space-y-8">
        <div class="text-center">
          <h1 class="text-white font-black text-5xl mb-4 drop-shadow-lg">⚔️ Choisissez votre deck ⚔️</h1>
          <p class="text-gray-300 text-2xl">Préparez vos troupes pour attaquer {{ opponent?.name }}</p>
        </div>

        <!-- Opponent Info -->
        <div class="bg-gradient-to-br from-blue-700 to-blue-950 rounded-3xl p-10 border-6 border-blue-950 shadow-2xl">
          <div class="flex items-center gap-10 flex-wrap">
            <div class="text-9xl">🏰</div>
            <div class="flex-1 min-w-[400px]">
              <h3 class="text-white font-black text-4xl">{{ opponent?.name }}</h3>
              <div class="grid grid-cols-3 gap-8 mt-6">
                <div class="bg-black/40 rounded-2xl p-6 text-center">
                  <div class="text-yellow-300 font-bold text-3xl">{{ opponentKingdom?.gold }}💰</div>
                  <div class="text-blue-200 text-xl">Or du territoire</div>
                </div>
                <div class="bg-black/40 rounded-2xl p-6 text-center">
                  <div class="text-green-300 font-bold text-3xl">{{ opponentKingdom?.defense_power }}🛡️</div>
                  <div class="text-blue-200 text-xl">Force défensive</div>
                </div>
                <div class="bg-black/40 rounded-2xl p-6 text-center">
                  <div class="text-purple-300 font-bold text-3xl">Niv {{ opponentKingdom?.level }}</div>
                  <div class="text-blue-200 text-xl">Niveau</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Card Selection -->
        <div class="bg-gradient-to-br from-gray-700 to-gray-900 rounded-3xl p-10 border-6 border-gray-700 shadow-2xl">
          <h3 class="text-white font-black text-4xl mb-8">Votre deck de combat</h3>
          <p class="text-gray-300 text-xl mb-6">Cliquez sur les cartes pour les ajouter ou enlever (4-8 cartes)</p>
          <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-6">
            <div 
              v-for="(card, idx) in allCards" 
              :key="card.id"
              @click="toggleCard(idx)"
              class="relative cursor-pointer transform hover:scale-105 transition-all"
            >
              <div 
                :class="[
                  'rounded-2xl p-6 border-4 shadow-xl transition-all',
                  selectedCards.includes(idx) 
                    ? 'bg-gradient-to-br from-yellow-500 via-orange-500 to-red-600 border-yellow-300 shadow-yellow-500/50' 
                    : 'bg-gradient-to-br from-gray-600 to-gray-800 border-gray-500'
                ]"
              >
                <div class="text-7xl text-center mb-4">{{ card.icon }}</div>
                <h4 class="text-white font-black text-2xl text-center">{{ card.name }}</h4>
                <div class="text-center mt-3 space-y-2">
                  <div class="text-yellow-300 font-bold text-xl">⚡ {{ card.cost }}</div>
                  <div class="flex justify-center gap-4 text-sm">
                    <span class="text-red-300">⚔️ {{ card.attack }}</span>
                    <span class="text-green-300">❤️ {{ card.hp }}</span>
                    <span class="text-blue-300">🏃 {{ card.speed }}</span>
                  </div>
                </div>
              </div>
              <div 
                v-if="selectedCards.includes(idx)"
                class="absolute -top-3 -right-3 text-5xl"
              >
                ✅
              </div>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-8 flex-wrap">
          <button 
            @click="cancelSelection"
            class="flex-1 min-w-[250px] bg-gradient-to-b from-gray-600 to-gray-800 text-white font-black text-2xl px-10 py-6 rounded-3xl border-6 border-gray-700 shadow-[0_10px_0_rgb(50,50,50)] hover:translate-y-[-4px] active:translate-y-[6px] transition-all"
          >
            ↩️ Retour
          </button>
          <button 
            @click="launchAttack"
            :disabled="selectedCards.length < 4"
            class="flex-1 min-w-[250px] bg-gradient-to-b from-red-500 to-red-800 text-white font-black text-2xl px-10 py-6 rounded-3xl border-6 border-red-950 shadow-[0_12px_0_rgb(100,20,20)] hover:translate-y-[-4px] active:translate-y-[6px] transition-all disabled:opacity-30"
          >
            {{ selectedCards.length < 4 ? `Choisissez au moins 4 cartes (${selectedCards.length}/4)` : "⚔️ Lancer l'assaut !" }}
          </button>
        </div>
      </div>

      <!-- CLASH ROYALE BATTLE -->
      <div v-else-if="battleStarted && !battleResult" class="space-y-6">
        <!-- HUD -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
          <div class="bg-gradient-to-br from-purple-600 to-purple-900 rounded-3xl p-6 border-6 border-purple-950 text-center shadow-2xl">
            <div class="text-white font-black text-xl mb-2">⚡ Élixir</div>
            <div class="text-5xl font-black text-purple-200">{{ elixir.toFixed(1) }}</div>
          </div>
          <div class="bg-gradient-to-br from-blue-600 to-blue-900 rounded-3xl p-6 border-6 border-blue-950 text-center shadow-2xl">
            <div class="text-white font-black text-xl mb-2">❤️ Votre roi</div>
            <div class="text-5xl font-black text-blue-200">{{ playerKingTowerHP }}/{{ maxPlayerKingTowerHP }}</div>
          </div>
          <div class="bg-gradient-to-br from-red-600 to-red-900 rounded-3xl p-6 border-6 border-red-950 text-center shadow-2xl">
            <div class="text-white font-black text-xl mb-2">❤️ Roi ennemi</div>
            <div class="text-5xl font-black text-red-200">{{ enemyKingTowerHP }}/{{ maxEnemyKingTowerHP }}</div>
          </div>
          <div class="bg-gradient-to-br from-green-600 to-green-900 rounded-3xl p-6 border-6 border-green-950 text-center shadow-2xl">
            <div class="text-white font-black text-xl mb-2">⏱️ Temps</div>
            <div class="text-5xl font-black text-green-200">{{ battleTime }}s</div>
          </div>
        </div>

        <!-- Instructions -->
        <div class="bg-gradient-to-br from-purple-600 to-purple-900 rounded-3xl p-8 border-6 border-purple-950 text-center shadow-xl mb-6">
          <h2 class="text-white font-black text-3xl mb-3">🎮 Comment jouer ?</h2>
          <p class="text-purple-100 text-xl mb-2">1. Choisissez une carte en bas</p>
          <p class="text-purple-100 text-xl mb-2">2. Cliquez sur une zone bleue (▢▢▢) pour déployer</p>
          <p class="text-purple-100 text-xl">3. Détruisez la tour du roi ennemi !</p>
        </div>

        <!-- BATTLE ARENA -->
        <div class="relative bg-gradient-to-b from-green-400 via-green-500 to-green-700 rounded-3xl border-8 border-green-900 w-full aspect-[16/10] overflow-hidden mx-auto shadow-2xl">
          <!-- Grass texture -->
          <div class="absolute inset-0 opacity-40 bg-[url('https://www.transparenttextures.com/patterns/grass.png')]"></div>
          <!-- River -->
          <div class="absolute top-1/2 -translate-y-1/2 left-0 right-0 h-24 bg-gradient-to-b from-blue-300 to-blue-600 opacity-80 border-t-4 border-b-4 border-blue-800"></div>
          <!-- Bridges -->
          <div class="absolute top-1/2 -translate-y-1/2 left-1/4 -translate-x-1/2 w-32 h-24 bg-gradient-to-b from-yellow-700 to-yellow-900 border-4 border-yellow-950 rounded-lg"></div>
          <div class="absolute top-1/2 -translate-y-1/2 left-3/4 -translate-x-1/2 w-32 h-24 bg-gradient-to-b from-yellow-700 to-yellow-900 border-4 border-yellow-950 rounded-lg"></div>

          <!-- YOUR KING TOWER -->
          <div class="absolute bottom-8 left-1/2 -translate-x-1/2 text-center z-20">
            <div class="text-8xl animate-pulse">👑</div>
            <div class="bg-black/80 text-white font-black text-xl rounded-2xl px-4 py-2 mt-2 border-2 border-white">
              ❤️ {{ playerKingTowerHP }}
            </div>
          </div>

          <!-- YOUR PRINCESS TOWERS -->
          <div class="absolute bottom-24 left-16 text-center z-20">
            <div class="text-6xl animate-pulse">🏰</div>
            <div class="bg-black/80 text-white font-black text-sm rounded-xl px-3 py-1 mt-1 border-2 border-white">
              ❤️ {{ playerPrincessTowerHP }}
            </div>
          </div>
          <div class="absolute bottom-24 right-16 text-center z-20">
            <div class="text-6xl animate-pulse">🏰</div>
            <div class="bg-black/80 text-white font-black text-sm rounded-xl px-3 py-1 mt-1 border-2 border-white">
              ❤️ {{ playerPrincessTowerHP }}
            </div>
          </div>

          <!-- ENEMY KING TOWER -->
          <div class="absolute top-8 left-1/2 -translate-x-1/2 text-center z-20">
            <div class="text-8xl animate-pulse" style="transform: rotate(180deg)">👑</div>
            <div class="bg-black/80 text-white font-black text-xl rounded-2xl px-4 py-2 mt-2 border-2 border-white">
              ❤️ {{ enemyKingTowerHP }}
            </div>
          </div>

          <!-- ENEMY PRINCESS TOWERS -->
          <div class="absolute top-24 left-16 text-center z-20">
            <div class="text-6xl animate-pulse" style="transform: rotate(180deg)">🏰</div>
            <div class="bg-black/80 text-white font-black text-sm rounded-xl px-3 py-1 mt-1 border-2 border-white">
              ❤️ {{ enemyPrincessTowerHP }}
            </div>
          </div>
          <div class="absolute top-24 right-16 text-center z-20">
            <div class="text-6xl animate-pulse" style="transform: rotate(180deg)">🏰</div>
            <div class="bg-black/80 text-white font-black text-sm rounded-xl px-3 py-1 mt-1 border-2 border-white">
              ❤️ {{ enemyPrincessTowerHP }}
            </div>
          </div>

          <!-- YOUR TROOPS -->
          <div
            v-for="unit in playerUnits"
            :key="unit.id"
            class="absolute"
            :style="{
              left: unit.x + '%',
              top: unit.y + '%',
              transform: 'translate(-50%, -50%)'
            }"
          >
            <div class="text-6xl">{{ unit.icon }}</div>
            <div 
              class="absolute -top-6 left-1/2 -translate-x-1/2 bg-black/90 rounded-full px-3 py-1 text-xs text-white font-black"
            >
              ❤️ {{ unit.currentHP }}
            </div>
          </div>

          <!-- ENEMY TROOPS -->
          <div
            v-for="unit in enemyUnits"
            :key="unit.id"
            class="absolute"
            :style="{
              left: unit.x + '%',
              top: unit.y + '%',
              transform: 'translate(-50%, -50%)'
            }"
          >
            <div class="text-6xl" style="filter: hue-rotate(180deg)">{{ unit.icon }}</div>
            <div 
              class="absolute -top-6 left-1/2 -translate-x-1/2 bg-red-900/90 rounded-full px-3 py-1 text-xs text-white font-black"
            >
              ❤️ {{ unit.currentHP }}
            </div>
          </div>

          <!-- DEPLOY ZONES - BIG AND EASY TO CLICK -->
          <div
            v-for="zone in deployZones"
            :key="zone.id"
            @click="deployCard(zone)"
            class="absolute cursor-pointer bg-gradient-to-br from-blue-500/50 to-blue-700/50 border-4 border-dashed border-blue-400 rounded-2xl transition-all hover:bg-gradient-to-br from-blue-400/70 to-blue-600/70 hover:scale-105 z-50"
            :style="{
              left: zone.x + '%',
              top: zone.y + '%',
              width: '140px',
              height: '180px',
              transform: 'translate(-50%, -50%)'
            }"
          >
            <div class="flex flex-col items-center justify-center h-full text-white font-black text-lg">
              <span class="text-4xl mb-2">📍</span>
              <span>Déployer</span>
            </div>
          </div>
        </div>

        <!-- YOUR CARDS AT BOTTOM - BIG AND EASY TO CLICK -->
        <div class="bg-gradient-to-b from-gray-800 to-gray-950 rounded-3xl p-8 border-6 border-gray-700 shadow-2xl">
          <h3 class="text-white font-black text-4xl mb-8 text-center">Votre main</h3>
          <div class="flex justify-center gap-6 flex-wrap">
            <button
              v-for="(cardIdx, idx) in selectedCards"
              :key="idx"
              @click="currentDeployCard = cardIdx"
              :disabled="elixir < allCards[cardIdx].cost"
              :class="[
                'rounded-3xl p-8 border-6 shadow-2xl min-w-[220px] transition-all',
                currentDeployCard === cardIdx
                  ? 'bg-gradient-to-br from-yellow-400 via-orange-500 to-red-600 border-yellow-300 shadow-yellow-500/60 scale-110'
                  : 'bg-gradient-to-br from-gray-600 to-gray-800 border-gray-500',
                elixir < allCards[cardIdx].cost ? 'opacity-30 cursor-not-allowed' : 'hover:scale-105 cursor-pointer'
              ]"
            >
              <div class="text-8xl text-center mb-4">{{ allCards[cardIdx].icon }}</div>
              <h4 class="text-white font-black text-2xl text-center">{{ allCards[cardIdx].name }}</h4>
              <div class="text-center mt-4">
                <div class="text-yellow-300 font-black text-3xl">⚡ {{ allCards[cardIdx].cost }}</div>
              </div>
            </button>
          </div>
          <div class="text-center mt-8 text-gray-300 text-2xl">
            {{ currentDeployCard !== null ? "🎯 Cliquez sur une zone bleue pour déployer " + allCards[currentDeployCard].name + " !" : "👆 Choisissez une carte d'abord !" }}
          </div>
        </div>
      </div>

      <!-- BATTLE RESULT -->
      <div v-if="battleResult" class="text-center space-y-8 py-12">
        <h1 :class="['text-8xl font-black drop-shadow-2xl mb-8', battleResult.won ? 'text-yellow-300' : 'text-red-400']">
          {{ battleResult.won ? '🎉 VICTOIRE ! 🎉' : '😤 DÉFAITE 😤' }}
        </h1>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-4xl mx-auto">
          <div class="bg-gradient-to-br from-purple-600 to-purple-900 rounded-3xl p-10 border-6 border-purple-950 shadow-2xl">
            <div class="text-white font-black text-2xl mb-4">Trophées</div>
            <div class="text-5xl font-black" :class="battleResult.trophyChange > 0 ? 'text-green-300' : 'text-red-300'">
              {{ battleResult.trophyChange > 0 ? '+' : '' }}{{ battleResult.trophyChange }}
            </div>
          </div>
          <div class="bg-gradient-to-br from-yellow-600 to-yellow-900 rounded-3xl p-10 border-6 border-yellow-950 shadow-2xl">
            <div class="text-white font-black text-2xl mb-4">Butin</div>
            <div class="text-5xl font-black text-yellow-200">+{{ battleResult.goldWon }} 💰</div>
          </div>
          <div class="bg-gradient-to-br from-blue-600 to-blue-900 rounded-3xl p-10 border-6 border-blue-950 shadow-2xl">
            <div class="text-white font-black text-2xl mb-4">Territoire conquis</div>
            <div class="text-5xl font-black text-blue-200">{{ battleResult.territoryConquered }}</div>
          </div>
        </div>

        <div v-if="battleResult.chest" class="bg-gradient-to-br from-amber-500 to-orange-800 rounded-3xl p-12 border-6 border-amber-950 inline-block animate-bounce shadow-2xl">
          <div class="text-9xl mb-6">{{ getChestIcon(battleResult.chest.type) }}</div>
          <div class="text-white font-black text-4xl">Vous avez gagné un coffre !</div>
        </div>
        
        <button 
          @click="goBack"
          class="bg-gradient-to-b from-green-500 to-green-800 text-white font-black text-3xl px-16 py-8 rounded-3xl border-6 border-green-950 shadow-[0_12px_0_rgb(20,80,20)] hover:translate-y-[-4px] active:translate-y-[6px] transition-all"
        >
          🏠 Retour au royaume
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onUnmounted, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuth } from '../composables/useAuth'
import { startBattle, attack } from '../services/api'
import Modal from '../components/Modal.vue'

export default {
  components: {
    Modal
  },
  setup() {
    const router = useRouter()
    const { authUser, refreshAuthUser } = useAuth()

    // BASIC UI STATE
    const isSearching = ref(false)
    const selectingTroops = ref(false)
    const battleStarted = ref(false)
    const battleTime = ref(180)
    const opponent = ref(null)
    const opponentKingdom = ref(null)
    const userKingdom = ref(null)
    const selectedCards = ref([])
    const battleResult = ref(null)
    const showConfirmModal = ref(false)
    const currentDeployCard = ref(null)

    // BATTLE GAME STATE
    const elixir = ref(5)
    const maxElixir = 10

    // TOWERS
    const playerKingTowerHP = ref(5000)
    const maxPlayerKingTowerHP = 5000
    const playerPrincessTowerHP = ref(2500)

    const enemyKingTowerHP = ref(5000)
    const maxEnemyKingTowerHP = 5000
    const enemyPrincessTowerHP = ref(2500)

    // UNITS
    const playerUnits = ref([])
    const enemyUnits = ref([])

    // DEPLOY ZONES - BIGGER ZONES
    const deployZones = ref([
      { id: 1, x: 25, y: 72 },
      { id: 2, x: 50, y: 72 },
      { id: 3, x: 75, y: 72 }
    ])

    // ALL AVAILABLE CARDS
    const allCards = [
      { id: 'knight', icon: '⚔️', name: 'Chevalier', cost: 3, attack: 120, hp: 800, speed: 1.2, type: 'ground' },
      { id: 'archer', icon: '🏹', name: 'Archer', cost: 3, attack: 100, hp: 300, speed: 1.0, type: 'ranged' },
      { id: 'giant', icon: '🧔', name: 'Géant', cost: 5, attack: 150, hp: 3000, speed: 0.6, type: 'ground' },
      { id: 'wizard', icon: '🧙', name: 'Mage', cost: 5, attack: 200, hp: 400, speed: 0.8, type: 'ranged' },
      { id: 'dragon', icon: '🐉', name: 'Dragon', cost: 4, attack: 180, hp: 600, speed: 0.9, type: 'air' },
      { id: 'cavalry', icon: '🐴', name: 'Cavalier', cost: 4, attack: 200, hp: 1000, speed: 1.8, type: 'ground' },
      { id: 'healer', icon: '👼', name: 'Soigneur', cost: 3, attack: 0, hp: 500, speed: 0.8, type: 'support', heal: 50 },
      { id: 'hog', icon: '🐗', name: 'Cochon', cost: 4, attack: 260, hp: 1400, speed: 2.0, type: 'ground' }
    ]

    let battleInterval = null
    let elixirInterval = null
    let enemySpawnInterval = null
    let countdownInterval = null
    let unitIdCounter = 0

    // --- GAME LOGIC ---

    const findOpponent = async () => {
      console.log("1. Recherche d'adversaire...")
      isSearching.value = true
      
      try {
        const res = await startBattle()
        opponent.value = res.data.opponent
        opponentKingdom.value = res.data.opponent_kingdom
        userKingdom.value = res.data.user_kingdom
      } catch (e) {
        console.error("Erreur recherche adversaire:", e)
        opponent.value = { name: 'Seigneur Noir' }
        opponentKingdom.value = { gold: 5000, defense_power: 200, level: 8 }
      }
      
      // Reset selected cards
      selectedCards.value = [0, 1, 2, 4]
      isSearching.value = false
      selectingTroops.value = true
      console.log("2. Adversaire trouvé, affichage choix deck")
    }

    const toggleCard = (idx) => {
      console.log("Clic sur carte", idx)
      if (selectedCards.value.includes(idx)) {
        selectedCards.value = selectedCards.value.filter(i => i !== idx)
      } else if (selectedCards.value.length < 8) {
        selectedCards.value.push(idx)
      }
    }

    const cancelSelection = () => {
      selectingTroops.value = false
      selectedCards.value = []
    }

    const launchAttack = () => {
      console.log("3. Clic sur Lancer l'assaut")
      showConfirmModal.value = true
    }

    const startBattle = async () => {
      console.log("4. DÉBUT DE LA BATAILLE !")
      showConfirmModal.value = false
      selectingTroops.value = false
      battleStarted.value = true

      // Reset battle state
      elixir.value = 5
      playerKingTowerHP.value = 5000
      enemyKingTowerHP.value = 5000
      playerPrincessTowerHP.value = 2500
      enemyPrincessTowerHP.value = 2500
      playerUnits.value = []
      enemyUnits.value = []
      battleTime.value = 180
      unitIdCounter = 0
      currentDeployCard.value = null

      // Start game loops - DELAYED for 5 sec to give player time
      setTimeout(() => {
        battleInterval = setInterval(updateBattle, 50)
        console.log("5. Battle loop démarrée")
      }, 1000)
      
      setTimeout(() => {
        enemySpawnInterval = setInterval(spawnEnemyUnit, 4000)
        console.log("6. Enemy spawner démarré")
      }, 3000)
      
      elixirInterval = setInterval(() => {
        if (elixir.value < maxElixir) {
          elixir.value = Math.min(maxElixir, elixir.value + 0.2)
        }
      }, 200)
      
      countdownInterval = setInterval(() => {
        if (battleTime.value > 0) {
          battleTime.value--
        } else {
          endBattle(playerKingTowerHP.value > enemyKingTowerHP.value)
        }
      }, 1000)

      console.log("7. Tous les systèmes activés !")
    }

    const deployCard = (zone) => {
      console.log("Tentative de déploiement sur zone", zone)
      console.log("Carte sélectionnée:", currentDeployCard.value)
      if (currentDeployCard.value === null) {
        alert("Choisissez d'abord une carte !")
        return
      }
      
      const card = allCards[currentDeployCard.value]
      console.log("Coût de la carte:", card.cost)
      console.log("Élixir disponible:", elixir.value)
      if (elixir.value < card.cost) {
        alert("Pas assez d'élixir !")
        return
      }

      console.log("🎯 Déploiement de", card.name)

      // Spend elixir
      elixir.value -= card.cost

      // Create unit
      playerUnits.value.push({
        id: unitIdCounter++,
        ...card,
        currentHP: card.hp,
        x: zone.x,
        y: zone.y,
        targetY: 25,
        lastAttack: 0
      })

      currentDeployCard.value = null
    }

    const spawnEnemyUnit = () => {
      if (!battleStarted.value) return
      
      const enemyTypes = [0, 1, 2, 3, 5] // Random from our cards
      const randomIdx = enemyTypes[Math.floor(Math.random() * enemyTypes.length)]
      const card = allCards[randomIdx]
      
      const xPositions = [25, 50, 75]
      const randomX = xPositions[Math.floor(Math.random() * xPositions.length)]

      enemyUnits.value.push({
        id: unitIdCounter++,
        ...card,
        currentHP: Math.floor(card.hp * 1.2), // Enemies a bit stronger
        x: randomX,
        y: 25,
        targetY: 75,
        lastAttack: 0
      })
      console.log("Nouvel ennemi apparait:", card.name)
    }

    const updateBattle = () => {
      if (!battleStarted.value) return

      // --- MOVE PLAYER UNITS ---
      for (let i = playerUnits.value.length - 1; i >= 0; i--) {
        const unit = playerUnits.value[i]
        
        if (unit.y > unit.targetY + 5) {
          unit.y -= unit.speed
        } else if (unit.y < unit.targetY - 5) {
          unit.y += unit.speed
        }

        // Check if enemy unit nearby to attack
        const enemyNearby = enemyUnits.value.find(enemy =>
          Math.abs(enemy.x - unit.x) < 12 && Math.abs(enemy.y - unit.y) < 18
        )

        if (enemyNearby) {
          if (Date.now() - unit.lastAttack > 1000) {
            enemyNearby.currentHP -= unit.attack
            unit.lastAttack = Date.now()
            if (enemyNearby.currentHP <= 0) {
              enemyUnits.value = enemyUnits.value.filter(u => u.id !== enemyNearby.id)
            }
          }
        } else if (unit.y < 35) {
          // Attack towers if close enough
          if (Date.now() - unit.lastAttack > 1000) {
            if (enemyPrincessTowerHP.value > 0 && Math.abs(unit.x - 25) < 20) {
              enemyPrincessTowerHP.value -= unit.attack
            } else if (enemyPrincessTowerHP.value > 0 && Math.abs(unit.x - 75) < 20) {
              enemyPrincessTowerHP.value -= unit.attack
            } else {
              enemyKingTowerHP.value -= unit.attack
            }
            unit.lastAttack = Date.now()
          }
        }

        if (unit.currentHP <= 0) {
          playerUnits.value.splice(i, 1)
        }
      }

      // --- MOVE ENEMY UNITS ---
      for (let i = enemyUnits.value.length - 1; i >= 0; i--) {
        const unit = enemyUnits.value[i]
        
        if (unit.y < unit.targetY - 5) {
          unit.y += unit.speed
        } else if (unit.y > unit.targetY + 5) {
          unit.y -= unit.speed
        }

        const playerNearby = playerUnits.value.find(u =>
          Math.abs(u.x - unit.x) < 12 && Math.abs(u.y - unit.y) < 18
        )

        if (playerNearby) {
          if (Date.now() - unit.lastAttack > 1000) {
            playerNearby.currentHP -= unit.attack
            unit.lastAttack = Date.now()
            if (playerNearby.currentHP <= 0) {
              playerUnits.value = playerUnits.value.filter(u => u.id !== playerNearby.id)
            }
          }
        } else if (unit.y > 65) {
          if (Date.now() - unit.lastAttack > 1000) {
            if (playerPrincessTowerHP.value > 0 && Math.abs(unit.x - 25) < 20) {
              playerPrincessTowerHP.value -= unit.attack
            } else if (playerPrincessTowerHP.value > 0 && Math.abs(unit.x - 75) < 20) {
              playerPrincessTowerHP.value -= unit.attack
            } else {
              playerKingTowerHP.value -= unit.attack
            }
            unit.lastAttack = Date.now()
          }
        }

        if (unit.currentHP <= 0) {
          enemyUnits.value.splice(i, 1)
        }
      }

      // --- CHECK WIN CONDITION ---
      if (enemyKingTowerHP.value <= 0) {
        endBattle(true)
      } else if (playerKingTowerHP.value <= 0) {
        endBattle(false)
      }
    }

    const endBattle = async (won) => {
      console.log("8. FIN DE BATAILLE -", won ? "VICTOIRE" : "DÉFAITE")

      clearAllIntervals()
      battleStarted.value = false

      const score = maxEnemyKingTowerHP - enemyKingTowerHP.value
      const trophyChange = won ? Math.floor(score / 100) + 20 : -Math.floor(10 + Math.random() * 20)
      const goldWon = won ? Math.floor(500 + score / 2) : Math.floor(score / 4)
      const territoryConquered = won ? "1 territoire" : "0"

      try {
        const troops = selectedCards.value.map(i => ({ type: allCards[i].id, quantity: 1 }))
        const res = await attack(opponent.value.id, troops, score)
        battleResult.value = res.data
      } catch (e) {
        battleResult.value = {
          won,
          trophyChange,
          goldWon,
          territoryConquered,
          chest: won && score > 3000 ? { type: score > 4500 ? "magical" : "gold" } : null
        }
      }

      await refreshAuthUser()
    }

    const clearAllIntervals = () => {
      if (battleInterval) clearInterval(battleInterval)
      if (elixirInterval) clearInterval(elixirInterval)
      if (enemySpawnInterval) clearInterval(enemySpawnInterval)
      if (countdownInterval) clearInterval(countdownInterval)
    }

    const getChestIcon = (type) => {
      const icons = { silver: '🥈', gold: '🥇', magical: '✨' }
      return icons[type] || '📦'
    }

    const goBack = () => {
      router.push('/')
    }

    onUnmounted(() => {
      clearAllIntervals()
    })

    return {
      // UI state
      isSearching, selectingTroops, battleStarted, battleTime,
      opponent, opponentKingdom, userKingdom, selectedCards,
      battleResult, showConfirmModal, currentDeployCard,

      // Game state
      elixir, playerKingTowerHP, maxPlayerKingTowerHP,
      enemyKingTowerHP, maxEnemyKingTowerHP,
      playerPrincessTowerHP, enemyPrincessTowerHP,
      playerUnits, enemyUnits, deployZones, allCards,

      // Functions
      findOpponent, toggleCard, cancelSelection,
      launchAttack, startBattle, deployCard,
      getChestIcon, goBack
    }
  }
}
</script>

<style scoped>
@keyframes damageFloat {
  0% {
    opacity: 1;
    transform: translate(-50%, -50%) translateY(0) scale(1);
  }
  100% {
    opacity: 0;
    transform: translate(-50%, -50%) translateY(-50px) scale(1.5);
  }
}
</style>
