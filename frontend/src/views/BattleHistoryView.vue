<template>
  <div class="max-w-4xl mx-auto space-y-6">
    <div class="text-center">
      <h1 class="text-white font-black text-4xl mb-3">📜 Historique des Batailles</h1>
      <p class="text-gray-300 text-xl">Consultez vos exploits !</p>
    </div>

    <div class="space-y-4">
      <div 
        v-for="battle in battles" 
        :key="battle.id"
        class="bg-gradient-to-b from-gray-700 to-gray-800 rounded-2xl p-6 border-4 border-gray-700"
      >
        <div class="flex items-center justify-between mb-4">
          <div class="flex items-center gap-4">
            <div class="text-5xl">{{ battle.attacker_won ? '🎉' : '😢' }}</div>
            <div>
              <div class="text-white font-black text-2xl">
                {{ battle.attacker_won ? 'Victoire !' : 'Défaite' }}
              </div>
              <div class="text-gray-400 text-lg">
                VS {{ battle.defender_user?.name || 'Adversaire' }}
              </div>
            </div>
          </div>
          <div class="text-right">
            <div class="text-white font-bold text-xl">
              {{ new Date(battle.created_at).toLocaleDateString('fr-FR') }}
            </div>
            <div class="text-gray-400 text-lg">
              {{ new Date(battle.created_at).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' }) }}
            </div>
          </div>
        </div>
        
        <div class="grid grid-cols-2 gap-4">
          <div v-if="battle.gold_stolen" class="bg-yellow-900/50 rounded-xl p-4 text-center">
            <div class="text-yellow-300 font-black text-xl">+{{ battle.gold_stolen }} 💰</div>
            <div class="text-yellow-200 text-sm">Or volé</div>
          </div>
          <div class="bg-purple-900/50 rounded-xl p-4 text-center">
            <div class="font-black text-xl" :class="battle.trophy_change > 0 ? 'text-green-300' : 'text-red-300'">
              {{ battle.trophy_change > 0 ? '+' : '' }}{{ battle.trophy_change }} 🏆
            </div>
            <div class="text-purple-200 text-sm">Trophées</div>
          </div>
        </div>
      </div>

      <div v-if="!battles.length" class="text-center py-16">
        <div class="text-8xl mb-4">⚔️</div>
        <h3 class="text-white font-black text-2xl mb-2">Aucune bataille</h3>
        <p class="text-gray-400 text-lg">Allez attaquer quelqu'un !</p>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { getBattleHistory } from '../services/api'

export default {
  setup() {
    const battles = ref([])

    const loadHistory = async () => {
      try {
        const res = await getBattleHistory()
        battles.value = res.data.battles || []
      } catch (e) {
        console.error('Erreur chargement historique:', e)
      }
    }

    onMounted(() => {
      loadHistory()
    })

    return { battles }
  }
}
</script>
