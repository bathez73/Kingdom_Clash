<template>
  <div class="quests-panel p-8 rounded-3xl" style="background: linear-gradient(180deg, #1a237e 0%, #0d1b6e 100%); border: 4px solid #3f51b5;">
    <h2 class="text-2xl font-black text-white mb-6 flex items-center gap-3">
      📋 Quêtes Journalières
      <span class="text-sm font-bold text-blue-200 bg-blue-800/50 px-3 py-1 rounded-full">
        {{ completedCount }}/{{ quests.length }} complétées
      </span>
    </h2>

    <div v-if="loading" class="text-center text-blue-200 py-4">Chargement...</div>

    <div v-else class="space-y-4">
      <div
        v-for="quest in quests"
        :key="quest.id"
        class="quest-item rounded-2xl p-5"
        :class="{
          'quest-claimed': quest.claimed,
          'quest-completed': quest.completed && !quest.claimed,
          'quest-active': !quest.completed
        }"
      >
        <div class="flex items-center justify-between gap-4">
          <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
              <span class="text-xl">{{ getQuestIcon(quest.type) }}</span>
              <span class="font-black text-white text-lg">{{ quest.label }}</span>
              <span v-if="quest.claimed" class="text-green-300 text-sm font-bold">✅ Réclamée</span>
            </div>

            <!-- Barre de progression -->
            <div class="progress-container mb-2">
              <div class="flex justify-between text-xs text-blue-200 mb-1">
                <span>Progression</span>
                <span>{{ quest.progress }} / {{ quest.target }}</span>
              </div>
              <div class="progress-bar">
                <div
                  class="progress-fill"
                  :class="quest.completed ? 'bg-green-400' : 'bg-blue-400'"
                  :style="{ width: `${Math.min(100, (quest.progress / quest.target) * 100)}%` }"
                ></div>
              </div>
            </div>

            <!-- Récompenses -->
            <div class="flex gap-3 text-sm flex-wrap">
              <span v-if="quest.reward.gold" class="reward-badge gold">💰 +{{ quest.reward.gold }}</span>
              <span v-if="quest.reward.wood" class="reward-badge wood">🪵 +{{ quest.reward.wood }}</span>
              <span v-if="quest.reward.food" class="reward-badge food">🍖 +{{ quest.reward.food }}</span>
              <span v-if="quest.reward.xp" class="reward-badge xp">⭐ +{{ quest.reward.xp }} XP</span>
            </div>
          </div>

          <!-- Bouton réclamer -->
          <button
            v-if="quest.completed && !quest.claimed"
            @click="$emit('claim', quest.id)"
            class="claim-btn font-black text-lg px-6 py-3 rounded-2xl transition-all hover:scale-105 active:scale-95"
          >
            🎁 Réclamer
          </button>
        </div>
      </div>

      <div v-if="quests.length === 0" class="text-center text-blue-200 py-4">
        Aucune quête disponible
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'QuestsPanel',
  props: {
    quests: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
  },
  emits: ['claim'],
  computed: {
    completedCount() {
      return this.quests.filter(q => q.completed).length
    }
  },
  methods: {
    getQuestIcon(type) {
      const icons = {
        train_soldiers: '⚔️',
        win_battle: '🏆',
        upgrade_building: '🏗️',
        collect_resources: '💰',
      }
      return icons[type] || '📋'
    }
  }
}
</script>

<style scoped>
.quest-active {
  background: rgba(63, 81, 181, 0.3);
  border: 2px solid rgba(63, 81, 181, 0.5);
}
.quest-completed {
  background: rgba(76, 175, 80, 0.25);
  border: 2px solid #4caf50;
  animation: pulse-green 2s infinite;
}
.quest-claimed {
  background: rgba(255, 255, 255, 0.05);
  border: 2px solid rgba(255, 255, 255, 0.1);
  opacity: 0.6;
}
@keyframes pulse-green {
  0%, 100% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.4); }
  50% { box-shadow: 0 0 0 6px rgba(76, 175, 80, 0); }
}
.progress-bar {
  background: rgba(0,0,0,0.4);
  border-radius: 999px;
  height: 8px;
  overflow: hidden;
}
.progress-fill {
  height: 100%;
  border-radius: 999px;
  transition: width 0.5s ease;
}
.reward-badge {
  padding: 2px 8px;
  border-radius: 999px;
  font-weight: 700;
}
.reward-badge.gold  { background: rgba(255,193,7,0.2);  color: #ffc107; border: 1px solid #ffc107; }
.reward-badge.wood  { background: rgba(76,175,80,0.2);  color: #81c784; border: 1px solid #81c784; }
.reward-badge.food  { background: rgba(233,30,99,0.2);  color: #f48fb1; border: 1px solid #f48fb1; }
.reward-badge.xp    { background: rgba(156,39,176,0.2); color: #ce93d8; border: 1px solid #ce93d8; }
.claim-btn {
  background: linear-gradient(180deg, #ffc107 0%, #ff9800 100%);
  color: #3e2723;
  box-shadow: 0 4px 0 #e65100;
  white-space: nowrap;
}
.claim-btn:hover {
  box-shadow: 0 6px 0 #e65100;
  transform: translateY(-2px);
}
</style>
