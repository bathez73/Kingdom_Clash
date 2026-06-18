<template>
  <div class="max-w-4xl mx-auto space-y-6">
    <div class="text-center">
      <h1 class="text-white font-black text-4xl mb-3">🏆 Classement</h1>
      <p class="text-gray-300 text-xl">Les meilleurs commandants !</p>
    </div>

    <!-- Top 3 -->
    <div class="grid grid-cols-3 gap-6 mb-6">
      <div 
        v-for="(user, index) in [users[1], users[0], users[2]]" 
        :key="user?.id || index"
        v-if="user"
        class="text-center"
      >
        <div class="text-7xl mb-2">
          {{ index === 0 ? '🥈' : index === 1 ? '🥇' : '🥉' }}
        </div>
        <div class="bg-gradient-to-b from-gray-700 to-gray-800 rounded-2xl p-6 border-4 border-gray-700">
          <div class="flex justify-center mb-3">
            <img :src="getAvatarById(user.id)" :alt="user.name" class="w-20 h-20 rounded-full object-cover border-4 border-yellow-400" />
          </div>
          <div class="text-white font-black text-2xl">{{ user.name }}</div>
          <div class="text-yellow-300 font-black text-3xl mt-2">{{ user.trophies }} 🏆</div>
        </div>
      </div>
    </div>

    <!-- Leaderboard List -->
    <div class="space-y-3">
      <div 
        v-for="(user, index) in users" 
        :key="user.id"
        class="bg-gradient-to-b from-gray-700 to-gray-800 rounded-2xl p-5 border-4 border-gray-700 flex items-center justify-between"
      >
        <div class="flex items-center gap-5">
          <div class="text-4xl font-black text-gray-400 w-12 text-center">
            #{{ index + 1 }}
          </div>
          <img :src="getAvatarById(user.id)" :alt="user.name" class="w-12 h-12 rounded-full object-cover border-2 border-yellow-500" />
          <div>
            <div class="text-white font-black text-xl">{{ user.name }}</div>
          </div>
        </div>
        <div class="text-yellow-300 font-black text-2xl">
          {{ user.trophies }} 🏆
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { getLeaderboard } from '../services/api'
import { getAvatarById } from '../useAvatars'

export default {
  setup() {
    const users = ref([])

    const loadLeaderboard = async () => {
      try {
        const res = await getLeaderboard()
        users.value = res.data.users || []
      } catch (e) {
        console.error('Erreur chargement classement:', e)
      }
    }

    onMounted(() => {
      loadLeaderboard()
    })

    return { users, getAvatarById }
  }
}
</script>
