<template>
  <div class="h-screen bg-gradient-to-b from-indigo-900 via-purple-900 to-black flex flex-col p-4 overflow-hidden">
    <div class="flex-1 w-full max-w-7xl mx-auto flex flex-col">
      <!-- Confirmation Modal -->
      <Modal
        :is-open="showConfirmModal"
        title="⚔️ Déclarer la guerre !"
        :message="`Vous allez attaquer le territoire de ${opponent?.name}. Voulez-vous lancer l'assaut ?`"
        icon="⚔️"
        confirm-text="Attaquer !"
        cancel-text="Annuler"
        @confirm="startGameBattle"
        @close="showConfirmModal = false"
      />

      <!-- Battle Result -->
      <div v-if="battleResult" class="result-container flex-1 flex flex-col items-center justify-center text-center space-y-6 py-8">
        <h1 :class="['text-7xl font-black drop-shadow-2xl mb-6', battleResult.won ? 'victory-title' : 'defeat-title']">
          {{ battleResult.won ? '🎉 VICTOIRE ! 🎉' : '😤 DÉFAITE 😤' }}
        </h1>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 max-w-4xl w-full">
          <div class="bg-gradient-to-br from-purple-600 to-purple-900 rounded-3xl p-8 border-6 border-purple-950 shadow-2xl">
            <div class="text-white font-black text-xl mb-3">Trophées</div>
            <div class="text-4xl font-black" :class="battleResult.trophyChange > 0 ? 'text-green-300' : 'text-red-300'">
              {{ battleResult.trophyChange > 0 ? '+' : '' }}{{ battleResult.trophyChange }}
            </div>
          </div>
          <div class="bg-gradient-to-br from-yellow-600 to-yellow-900 rounded-3xl p-8 border-6 border-yellow-950 shadow-2xl">
            <div class="text-white font-black text-xl mb-3">Butin</div>
            <div class="text-4xl font-black text-yellow-200">+{{ battleResult.goldWon }} 💰</div>
          </div>
          <div class="bg-gradient-to-br from-blue-600 to-blue-900 rounded-3xl p-8 border-6 border-blue-950 shadow-2xl">
            <div class="text-white font-black text-xl mb-3">XP gagné</div>
            <div class="text-4xl font-black text-blue-200">+{{ battleResult.xp_gained || (battleResult.won ? 80 : 20) }} ⭐</div>
          </div>
          <!-- Arène atteinte -->
          <div v-if="battleResult.arena" class="md:col-span-3 bg-gradient-to-br from-gray-700 to-gray-900 rounded-3xl p-4 border-2 border-gray-600 text-center">
            <span class="text-2xl">{{ battleResult.arena.icon }}</span>
            <span class="text-white font-bold ml-2">Arène : {{ battleResult.arena.name }}</span>
          </div>
        </div>

        <div v-if="battleResult.chest" class="bg-gradient-to-br from-amber-500 to-orange-800 rounded-3xl p-10 border-6 border-amber-950 inline-block animate-bounce shadow-2xl">
          <div class="text-8xl mb-4">{{ getChestIcon(battleResult.chest.type) }}</div>
          <div class="text-white font-black text-3xl">Vous avez gagné un coffre !</div>
        </div>
        
        <button 
          @click="goBack"
          class="bg-gradient-to-b from-green-500 to-green-800 text-white font-black text-3xl px-16 py-7 rounded-3xl border-6 border-green-950 shadow-[0_8px_0_rgb(20,80,20)] hover:translate-y-[-4px] active:translate-y-[6px] transition-all"
        >
          🏠 Retour au royaume
        </button>
      </div>

      <!-- Search Opponent -->
      <div v-else-if="!battleStarted && !selectingTroops" class="flex-1 flex items-center justify-center text-center py-12">
        <div>
          <h1 class="text-white font-black text-5xl mb-6 drop-shadow-xl">⚔️ Bataille pour le territoire ⚔️</h1>
          <button 
            @click="findOpponent"
            :disabled="isSearching"
            class="bg-gradient-to-b from-red-500 via-red-600 to-red-800 text-white font-black text-3xl px-16 py-7 rounded-3xl border-6 border-red-950 shadow-[0_8px_0_rgb(100,20,20)] hover:translate-y-[-4px] active:translate-y-[6px] transition-all disabled:opacity-50"
          >
            {{ isSearching ? "🔍 Recherche d'adversaire..." : "🎯 Trouver un adversaire !" }}
          </button>
        </div>
      </div>

      <!-- Select Troops -->
      <div v-else-if="selectingTroops && !battleStarted" class="flex-1 flex flex-col gap-6 overflow-auto py-4">
        <div class="text-center">
          <h1 class="text-white font-black text-4xl mb-4 drop-shadow-xl">⚔️ Choisissez votre deck ⚔️</h1>
          <p class="text-gray-300 text-xl">Préparez vos troupes pour attaquer {{ opponent?.name }}</p>
        </div>

        <!-- Opponent Info -->
        <div class="bg-gradient-to-br from-blue-700 to-blue-950 rounded-3xl p-6 border-6 border-blue-950 shadow-2xl">
          <div class="flex items-center gap-6 flex-wrap">
            <div class="text-7xl">🏰</div>
            <div class="flex-1 min-w-[300px]">
              <h3 class="text-white font-black text-3xl">{{ opponent?.name }}</h3>
              <div class="grid grid-cols-3 gap-4 mt-4">
                <div class="bg-black/40 rounded-2xl p-4 text-center">
                  <div class="text-yellow-300 font-bold text-2xl">{{ opponentKingdom?.gold }}💰</div>
                  <div class="text-blue-200 text-sm">Or du territoire</div>
                </div>
                <div class="bg-black/40 rounded-2xl p-4 text-center">
                  <div class="text-green-300 font-bold text-2xl">{{ opponentKingdom?.defensePower }}🛡️</div>
                  <div class="text-blue-200 text-sm">Force défensive</div>
                </div>
                <div class="bg-black/40 rounded-2xl p-4 text-center">
                  <div class="text-purple-300 font-bold text-2xl">Niv {{ opponentKingdom?.level }}</div>
                  <div class="text-blue-200 text-sm">Niveau</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Card Selection -->
        <div class="bg-gradient-to-br from-gray-700 to-gray-900 rounded-3xl p-6 border-6 border-gray-700 shadow-2xl">
          <h3 class="text-white font-black text-3xl mb-6">Votre deck de combat</h3>
          <p class="text-gray-300 text-lg mb-4">Cliquez sur les cartes pour les ajouter ou enlever (4-8 cartes)</p>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div 
              v-for="(card, idx) in allCards" 
              :key="card.id"
              @click="toggleCard(idx)"
              class="relative cursor-pointer transform hover:scale-105 transition-all"
            >
              <div 
                :class="[
                  'rounded-2xl p-4 border-6 shadow-xl transition-all',
                  selectedCards.includes(idx) 
                    ? 'bg-gradient-to-br from-yellow-500 via-orange-500 to-red-600 border-yellow-300 shadow-yellow-500/50' 
                    : 'bg-gradient-to-br from-gray-600 to-gray-800 border-gray-500'
                ]"
              >
                <div class="text-5xl text-center mb-2">{{ card.icon }}</div>
                <h4 class="text-white font-black text-lg text-center">{{ card.name }}</h4>
                <div class="text-center mt-2 space-y-1">
                  <div class="text-yellow-300 font-bold text-sm">⚡ {{ card.cost }}</div>
                  <div class="flex justify-center gap-2 text-xs">
                    <span class="text-red-300">⚔️ {{ card.attack }}</span>
                    <span class="text-green-300">❤️ {{ card.hp }}</span>
                    <span class="text-blue-300">🏃 {{ card.speed }}</span>
                  </div>
                </div>
              </div>
              <div 
                v-if="selectedCards.includes(idx)"
                class="absolute -top-2 -right-2 text-4xl"
              >
                ✅
              </div>
            </div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-6 flex-wrap">
          <button 
            @click="cancelSelection"
            class="flex-1 min-w-[200px] bg-gradient-to-b from-gray-600 to-gray-800 text-white font-black text-xl px-8 py-4 rounded-3xl border-6 border-gray-700 shadow-[0_6px_0_rgb(50,50,50)] hover:translate-y-[-4px] active:translate-y-[6px] transition-all"
          >
            ↩️ Retour
          </button>
          <button 
            @click="launchAttack"
            :disabled="selectedCards.length < 4"
            class="flex-1 min-w-[200px] bg-gradient-to-b from-red-500 to-red-800 text-white font-black text-xl px-8 py-4 rounded-3xl border-6 border-red-950 shadow-[0_8px_0_rgb(100,20,20)] hover:translate-y-[-4px] active:translate-y-[6px] transition-all disabled:opacity-30"
          >
            {{ selectedCards.length < 4 ? `Choisissez au moins 4 cartes (${selectedCards.length}/4)` : "⚔️ Lancer l'assaut !" }}
          </button>
        </div>
      </div>

      <!-- ACTIVE BATTLE WITH CONTROLS -->
      <div v-else class="flex-1 flex flex-col gap-3 min-h-0 relative">
        <!-- HUD -->
        <div class="hud-container relative z-20">
          <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="hud-item">
              <div class="hud-label">⚡ Élixir</div>
              <div class="hud-value text-yellow-300">{{ elixir.toFixed(1) }}</div>
            </div>
            <div class="hud-item">
              <div class="hud-label">❤️ Votre roi</div>
              <div class="hud-value text-blue-300">{{ playerKingTowerHP }}</div>
            </div>
            <div class="hud-item">
              <div class="hud-label">❤️ Roi ennemi</div>
              <div class="hud-value text-red-300">{{ enemyKingTowerHP }}</div>
            </div>
            <div class="hud-item">
              <div class="hud-label">⏱️ Temps</div>
              <div class="hud-value text-green-300">{{ battleTime }}s</div>
            </div>
          </div>
          <div class="mt-3 text-center text-white/70 text-sm">
            <p><strong>Contrôles :</strong> WASD/Fleches pour bouger | Espace pour sauter | Shift pour courir | Clic/J pour attaquer</p>
          </div>
        </div>

        <!-- BATTLE ARENA -->
        <div 
          ref="arenaRef"
          class="battle-arena flex-1 min-h-[400px] cursor-crosshair"
          @mousemove="handleMouseMove"
          @click="handleArenaClick"
        >
          <!-- River -->
          <div class="river"></div>
          
          <!-- Bridges -->
          <div class="bridge" style="left: 15%;"></div>
          <div class="bridge" style="left: 75%;"></div>

          <!-- YOUR KING TOWER -->
          <div class="tower" style="bottom: 5%; left: 50%; transform: translateX(-50%);">
            <div class="king-tower">👑</div>
            <div class="health-bar-container">
              <div 
                class="health-bar-fill" 
                :style="{ width: `${(playerKingTowerHP / maxPlayerKingTowerHP) * 100}%` }"
              ></div>
            </div>
          </div>

          <!-- YOUR PRINCESS TOWERS -->
          <div class="tower" style="bottom: 20%; left: 10%;">
            <div class="princess-tower">🏰</div>
            <div class="health-bar-container">
              <div 
                class="health-bar-fill" 
                :style="{ width: `${(playerPrincessTowerHP / 2500) * 100}%` }"
              ></div>
            </div>
          </div>
          <div class="tower" style="bottom: 20%; right: 10%;">
            <div class="princess-tower">🏰</div>
            <div class="health-bar-container">
              <div 
                class="health-bar-fill" 
                :style="{ width: `${(playerPrincessTowerHP / 2500) * 100}%` }"
              ></div>
            </div>
          </div>

          <!-- ENEMY KING TOWER -->
          <div class="tower" style="top: 5%; left: 50%; transform: translateX(-50%);">
            <div class="king-tower" style="transform: rotate(180deg);">👑</div>
            <div class="health-bar-container">
              <div 
                class="health-bar-fill" 
                :style="{ width: `${(enemyKingTowerHP / maxEnemyKingTowerHP) * 100}%` }"
              ></div>
            </div>
          </div>

          <!-- ENEMY PRINCESS TOWERS -->
          <div class="tower" style="top: 20%; left: 10%;">
            <div class="princess-tower" style="transform: rotate(180deg);">🏰</div>
            <div class="health-bar-container">
              <div 
                class="health-bar-fill" 
                :style="{ width: `${(enemyPrincessTowerHP / 2500) * 100}%` }"
              ></div>
            </div>
          </div>
          <div class="tower" style="top: 20%; right: 10%;">
            <div class="princess-tower" style="transform: rotate(180deg);">🏰</div>
            <div class="health-bar-container">
              <div 
                class="health-bar-fill" 
                :style="{ width: `${(enemyPrincessTowerHP / 2500) * 100}%` }"
              ></div>
            </div>
          </div>

          <!-- YOUR MAIN CONTROLLED UNIT -->
          <div 
            v-if="mainUnit"
            class="unit"
            :class="{ 'enemy': false }"
            :style="{
              left: `${mainUnit.x}%`,
              top: `${mainUnit.y}%`,
              transform: 'translate(-50%, -50%)'
            }"
          >
            <img 
              :src="unitSprites[mainUnit.type]?.idle || unitSprites.knight.idle" 
              class="unit-sprite" 
              alt="Main Unit"
            />
            <div class="absolute -bottom-6 left-1/2 -translate-x-1/2 text-white text-xs font-bold bg-black/70 px-2 py-1 rounded">
              VOUS
            </div>
            <div class="unit-health">
              <div 
                class="unit-health-fill" 
                :style="{ width: `${(mainUnit.currentHP / mainUnit.hp) * 100}%` }"
              ></div>
            </div>
          </div>

          <!-- YOUR OTHER TROOPS -->
          <div
            v-for="unit in playerUnits.filter(u => u.id !== mainUnit?.id)"
            :key="unit.id"
            class="unit"
            :style="{
              left: `${unit.x}%`,
              top: `${unit.y}%`,
              transform: 'translate(-50%, -50%)'
            }"
          >
            <img 
              :src="unitSprites[unit.type]?.idle || unitSprites.knight.idle" 
              class="unit-sprite" 
              :alt="unit.name"
            />
            <div class="unit-health">
              <div 
                class="unit-health-fill" 
                :style="{ width: `${(unit.currentHP / unit.hp) * 100}%` }"
              ></div>
            </div>
          </div>

          <!-- ENEMY TROOPS - SHADOW LORD -->
          <div
            v-for="unit in enemyUnits"
            :key="unit.id"
            class="unit enemy"
            :style="{
              left: `${unit.x}%`,
              top: `${unit.y}%`,
              transform: 'translate(-50%, -50%)'
            }"
          >
            <img 
              :src="unitSprites.enemy?.idle || unitSprites.knight.idle" 
              class="unit-sprite" 
              :alt="unit.name"
            />
            <div class="unit-health">
              <div 
                class="unit-health-fill" 
                :style="{ width: `${(unit.currentHP / unit.hp) * 100}%` }"
              ></div>
            </div>
          </div>
        </div>

        <!-- YOUR CARDS AT BOTTOM -->
        <div class="cards-container relative z-20">
          <div class="flex justify-center gap-3">
            <button
              v-for="(cardIdx, idx) in selectedCards"
              :key="idx"
              @click="spawnControlledUnit(cardIdx)"
              :disabled="elixir < allCards[cardIdx].cost"
              :class="[
                'card-button flex-1 min-w-[100px]',
                currentDeployCard === cardIdx ? 'selected' : ''
              ]"
            >
              <div class="card-icon">{{ allCards[cardIdx].icon }}</div>
              <h4 class="card-name">{{ allCards[cardIdx].name }}</h4>
              <div class="card-cost">⚡{{ allCards[cardIdx].cost }}</div>
            </button>
          </div>
          <div class="text-center mt-3 text-gray-300 text-sm">
            Cliquez sur une carte pour déployer une nouvelle unité que vous contrôlerez !
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onUnmounted, onMounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import { startBattle, attack } from '../services/api'
import Modal from '../components/Modal.vue'
import { unitSprites } from '../assets/sprites.js'
import './BattleView.css'

export default {
  components: {
    Modal
  },
  setup() {
    const router = useRouter()
    const arenaRef = ref(null)

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
    const mainUnit = ref(null) // L'unité que vous contrôlez !

    // KEYBOARD STATE
    const keys = ref({})
    const isJumping = ref(false)

    // CURSOR POSITION
    const cursorPos = ref({ x: 50, y: 80 })

    // ALL AVAILABLE CARDS
    const allCards = [
      { id: 'knight', icon: '⚔️', name: 'Chevalier', cost: 3, attack: 120, hp: 800, speed: 1.2, type: 'ground' },
      { id: 'archer', icon: '🧙', name: 'Archer Magicien', cost: 3, attack: 100, hp: 300, speed: 1.0, type: 'ranged' },
      { id: 'giant', icon: '🧔', name: 'Géant', cost: 5, attack: 150, hp: 3000, speed: 0.6, type: 'ground' },
      { id: 'mage', icon: '✨', name: 'Mage', cost: 5, attack: 200, hp: 400, speed: 0.8, type: 'ranged' },
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

    // --- UTILITY ---
    const getChestIcon = (type) => {
      const icons = { silver: '🥈', gold: '🥇', magical: '✨' }
      return icons[type] || '📦'
    }

    // --- GAME FLOW ---
    const findOpponent = async () => {
      isSearching.value = true
      battleResult.value = null
      
      try {
        const res = await startBattle()
        opponent.value = res.data.opponent
        opponentKingdom.value = res.data.opponentKingdom || { gold: 5000, defensePower: 200, level: 8 }
        userKingdom.value = res.data.userKingdom
      } catch (e) {
        console.error("Erreur recherche adversaire:", e)
        opponent.value = { name: 'Seigneur Noir' }
        opponentKingdom.value = { gold: 5000, defensePower: 200, level: 8 }
      }
      
      selectedCards.value = [0, 1, 2, 4]
      isSearching.value = false
      selectingTroops.value = true
    }

    const toggleCard = (idx) => {
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
      showConfirmModal.value = true
    }

    const startGameBattle = async () => {
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
      mainUnit.value = null
      cursorPos.value = { x: 50, y: 80 }
      currentDeployCard.value = null

      // Déployer une première unité automatiquement (Chevalier)
      await nextTick()
      spawnControlledUnit(0)

      // Start game loops
      setTimeout(() => {
        battleInterval = setInterval(updateBattle, 50)
      }, 500)
      
      setTimeout(() => {
        enemySpawnInterval = setInterval(spawnEnemyUnit, 3000)
      }, 2000)
      
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
    }

    // --- UNIT SPAWNING ---
    const spawnControlledUnit = (cardIdx) => {
      const card = allCards[cardIdx]
      if (elixir.value < card.cost) return

      elixir.value -= card.cost

      const newUnit = {
        id: unitIdCounter++,
        type: card.id,
        ...card,
        currentHP: card.hp,
        x: cursorPos.value.x,
        y: Math.min(cursorPos.value.y, 85), // Ne pas déployer trop bas
        targetY: 15,
        lastAttack: 0
      }

      playerUnits.value.push(newUnit)
      mainUnit.value = newUnit // Ceci est votre nouvelle unité principale !
      currentDeployCard.value = cardIdx
    }

    const spawnEnemyUnit = () => {
      if (!battleStarted.value) return
      
      const enemyTypes = [0, 1, 2, 3, 5]
      const randomIdx = enemyTypes[Math.floor(Math.random() * enemyTypes.length)]
      const card = allCards[randomIdx]
      
      const xPositions = [20, 35, 50, 65, 80]
      const randomX = xPositions[Math.floor(Math.random() * xPositions.length)]

      enemyUnits.value.push({
        id: unitIdCounter++,
        type: card.id,
        ...card,
        currentHP: Math.floor(card.hp * 1.2),
        x: randomX,
        y: 20,
        targetY: 80,
        lastAttack: 0
      })
    }

    // --- INPUT HANDLERS ---
    const handleMouseMove = (e) => {
      if (!arenaRef.value || !battleStarted.value) return
      
      const rect = arenaRef.value.getBoundingClientRect()
      cursorPos.value = {
        x: ((e.clientX - rect.left) / rect.width) * 100,
        y: ((e.clientY - rect.top) / rect.height) * 100
      }
    }

    const handleArenaClick = () => {
      if (!battleStarted.value || !mainUnit.value) return
      mainUnit.lastAttack = Date.now()
      // Les attaques seront gérées dans updateBattle
    }

    const handleKeyDown = (e) => {
      keys.value[e.key.toLowerCase()] = true
      
      // Attaque avec J
      if (e.key.toLowerCase() === 'j' && mainUnit.value) {
        mainUnit.value.lastAttack = Date.now()
      }
    }

    const handleKeyUp = (e) => {
      keys.value[e.key.toLowerCase()] = false
    }

    // --- BATTLE UPDATE LOOP ---
    const updateBattle = () => {
      if (!battleStarted.value) return

      // Mettre à jour l'unité principale avec clavier
      if (mainUnit.value) {
        const speed = keys.value['shift'] ? mainUnit.value.speed * 2 : mainUnit.value.speed
        const moveAmount = speed * 0.5

        // Déplacements
        if (keys.value['w'] || keys.value['arrowup']) {
          mainUnit.value.y = Math.max(10, mainUnit.value.y - moveAmount)
        }
        if (keys.value['s'] || keys.value['arrowdown']) {
          mainUnit.value.y = Math.min(90, mainUnit.value.y + moveAmount)
        }
        if (keys.value['a'] || keys.value['arrowleft']) {
          mainUnit.value.x = Math.max(10, mainUnit.value.x - moveAmount)
        }
        if (keys.value['d'] || keys.value['arrowright']) {
          mainUnit.value.x = Math.min(90, mainUnit.value.x + moveAmount)
        }

        // Saut (petit boost vers le haut)
        if ((keys.value[' '] || keys.value['space']) && !isJumping.value) {
          isJumping.value = true
          mainUnit.value.y = Math.max(10, mainUnit.value.y - 10)
          setTimeout(() => {
            isJumping.value = false
          }, 300)
        }

        // Attaque de l'unité principale
        if (Date.now() - mainUnit.value.lastAttack < 500) {
          // Vérifier les ennemis à proximité
          for (let i = enemyUnits.value.length - 1; i >= 0; i--) {
            const enemy = enemyUnits.value[i]
            const dist = Math.sqrt(
              Math.pow(enemy.x - mainUnit.value.x, 2) + 
              Math.pow(enemy.y - mainUnit.value.y, 2)
            )
            if (dist < 15) {
              enemy.currentHP -= mainUnit.value.attack * 2
              if (enemy.currentHP <= 0) {
                enemyUnits.value.splice(i, 1)
              }
              break
            }
          }
        }
      }

      // Déplacer les autres unités alliées vers le curseur
      for (let unit of playerUnits.value.filter(u => u.id !== mainUnit?.id)) {
        const dx = cursorPos.value.x - unit.x
        const dy = cursorPos.value.y - unit.y
        const dist = Math.sqrt(dx * dx + dy * dy)
        
        if (dist > 5) {
          unit.x += (dx / dist) * unit.speed * 0.3
          unit.y += (dy / dist) * unit.speed * 0.3
        }
      }

      // Déplacer les unités ennemies vers vous
      for (let unit of enemyUnits.value) {
        const targetX = mainUnit.value ? mainUnit.value.x : 50
        const targetY = mainUnit.value ? mainUnit.value.y : 80
        
        const dx = targetX - unit.x
        const dy = targetY - unit.y
        const dist = Math.sqrt(dx * dx + dy * dy)
        
        if (dist > 8) {
          unit.x += (dx / dist) * unit.speed * 0.3
          unit.y += (dy / dist) * unit.speed * 0.3
        } else {
          // Attaquer si à portée
          if (Date.now() - unit.lastAttack > 1000 && mainUnit.value) {
            mainUnit.value.currentHP -= unit.attack
            unit.lastAttack = Date.now()
          }
        }
      }

      // Vérifier si l'unité principale est morte
      if (mainUnit.value && mainUnit.value.currentHP <= 0) {
        mainUnit.value = null
      }

      // Enlever les unités mortes
      playerUnits.value = playerUnits.value.filter(u => u.currentHP > 0)
      enemyUnits.value = enemyUnits.value.filter(u => u.currentHP > 0)

      // Attaquer les tours ennemies si on est proche
      if (mainUnit.value && mainUnit.value.y < 35) {
        if (Date.now() - mainUnit.value.lastAttack > 1000) {
          if (enemyPrincessTowerHP.value > 0 && Math.abs(mainUnit.value.x - 20) < 20) {
            enemyPrincessTowerHP.value -= mainUnit.value.attack
          } else if (enemyPrincessTowerHP.value > 0 && Math.abs(mainUnit.value.x - 80) < 20) {
            enemyPrincessTowerHP.value -= mainUnit.value.attack
          } else {
            enemyKingTowerHP.value -= mainUnit.value.attack
          }
          mainUnit.value.lastAttack = Date.now()
        }
      }

      // Attaquer vos tours par les ennemis
      for (let unit of enemyUnits.value) {
        if (unit.y > 65 && Date.now() - unit.lastAttack > 1000) {
          if (playerPrincessTowerHP.value > 0 && Math.abs(unit.x - 20) < 20) {
            playerPrincessTowerHP.value -= unit.attack
          } else if (playerPrincessTowerHP.value > 0 && Math.abs(unit.x - 80) < 20) {
            playerPrincessTowerHP.value -= unit.attack
          } else {
            playerKingTowerHP.value -= unit.attack
          }
          unit.lastAttack = Date.now()
        }
      }

      // Vérifier conditions de victoire
      if (enemyKingTowerHP.value <= 0) {
        endBattle(true)
      } else if (playerKingTowerHP.value <= 0) {
        endBattle(false)
      }
    }

    // --- END BATTLE ---
    const endBattle = async (won) => {
      clearAllIntervals()
      
      const score = maxEnemyKingTowerHP - enemyKingTowerHP.value
      const trophyChange = won ? Math.floor(score / 100) + 20 : -Math.floor(10 + Math.random() * 20)
      const goldWon = won ? Math.floor(500 + score / 2) : Math.floor(score / 4)
      const territoryConquered = won ? '1 territoire' : '0'

      let result
      try {
        const troops = selectedCards.value.map(i => ({ type: allCards[i].id, quantity: 1 }))
        // Construire les deck_cards avec les stats pour le bonus serveur
        const deckCards = selectedCards.value.map(i => ({
          id: allCards[i].id,
          base_damage: allCards[i].attack,
          level: 1,
        }))
        const res = await attack(opponent.value.id, troops, score, deckCards)
        result = res.data
      } catch (e) {
        result = {
          won,
          trophyChange,
          goldWon,
          territoryConquered,
          chest: won && score > 3000 ? { type: score > 4500 ? 'magical' : 'gold' } : null
        }
      }
      
      battleResult.value = result
      battleStarted.value = false
    }

    const clearAllIntervals = () => {
      if (battleInterval) clearInterval(battleInterval)
      if (elixirInterval) clearInterval(elixirInterval)
      if (enemySpawnInterval) clearInterval(enemySpawnInterval)
      if (countdownInterval) clearInterval(countdownInterval)
    }

    const goBack = () => {
      battleResult.value = null
      router.push('/')
    }

    // --- LIFECYCLE ---
    onMounted(() => {
      window.addEventListener('keydown', handleKeyDown)
      window.addEventListener('keyup', handleKeyUp)
    })

    onUnmounted(() => {
      window.removeEventListener('keydown', handleKeyDown)
      window.removeEventListener('keyup', handleKeyUp)
      clearAllIntervals()
    })

    return {
      // UI state
      isSearching, selectingTroops, battleStarted, battleTime,
      opponent, opponentKingdom, userKingdom, selectedCards,
      battleResult, showConfirmModal, currentDeployCard,
      arenaRef,

      // Game state
      elixir, playerKingTowerHP, maxPlayerKingTowerHP,
      enemyKingTowerHP, maxEnemyKingTowerHP,
      playerPrincessTowerHP, enemyPrincessTowerHP,
      playerUnits, enemyUnits, mainUnit,
      allCards, unitSprites,

      // Functions
      findOpponent, toggleCard, cancelSelection,
      launchAttack, startGameBattle, spawnControlledUnit,
      getChestIcon, goBack,
      handleMouseMove, handleArenaClick
    }
  }
}
</script>
