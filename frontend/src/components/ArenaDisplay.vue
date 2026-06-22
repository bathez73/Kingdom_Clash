<template>
  <div class="arena-display p-6 rounded-3xl text-center" :style="arenaStyle">
    <div class="text-5xl mb-2">{{ arena.icon }}</div>
    <div class="text-xl font-black text-white">{{ arena.name }}</div>
    <div class="text-sm text-white/70 mt-1">{{ trophies }} 🏆</div>

    <!-- Progression vers la prochaine arène -->
    <div v-if="nextArena" class="mt-3">
      <div class="text-xs text-white/60 mb-1">
        Prochain : {{ nextArena.name }} ({{ nextArena.min }} 🏆)
      </div>
      <div class="progress-bar">
        <div
          class="progress-fill"
          :style="{ width: `${progressPercent}%` }"
        ></div>
      </div>
      <div class="text-xs text-white/50 mt-1">
        {{ trophies - arena.min }} / {{ nextArena.min - arena.min }}
      </div>
    </div>
    <div v-else class="mt-2 text-yellow-300 font-bold text-sm">🏆 Arène maximale !</div>
  </div>
</template>

<script>
const ARENAS = [
  { name: "Camp d'Entraînement", icon: '🏕️', min: 0,    max: 299,  color: '#607d8b' },
  { name: 'Goblin Stadium',       icon: '👺', min: 300,  max: 599,  color: '#4caf50' },
  { name: 'Bone Pit',             icon: '💀', min: 600,  max: 999,  color: '#795548' },
  { name: 'Barbarian Bowl',       icon: '⚔️', min: 1000, max: 1399, color: '#f44336' },
  { name: "P.E.K.K.A's Playhouse",icon: '🤖', min: 1400, max: 1799, color: '#9c27b0' },
  { name: 'Spell Valley',         icon: '✨', min: 1800, max: 2299, color: '#2196f3' },
  { name: 'Légendaire',           icon: '🏆', min: 2300, max: Infinity, color: '#ffc107' },
]

export default {
  name: 'ArenaDisplay',
  props: {
    trophies: { type: Number, default: 0 }
  },
  computed: {
    arena() {
      return ARENAS.find(a => this.trophies >= a.min && this.trophies <= a.max) || ARENAS[0]
    },
    nextArena() {
      const idx = ARENAS.indexOf(this.arena)
      return idx < ARENAS.length - 1 ? ARENAS[idx + 1] : null
    },
    progressPercent() {
      if (!this.nextArena) return 100
      const range = this.nextArena.min - this.arena.min
      const progress = this.trophies - this.arena.min
      return Math.min(100, Math.floor((progress / range) * 100))
    },
    arenaStyle() {
      return {
        background: `linear-gradient(135deg, ${this.arena.color}33, ${this.arena.color}55)`,
        border: `3px solid ${this.arena.color}88`,
      }
    }
  }
}
</script>

<style scoped>
.progress-bar {
  background: rgba(0,0,0,0.4);
  border-radius: 999px;
  height: 6px;
  overflow: hidden;
}
.progress-fill {
  height: 100%;
  border-radius: 999px;
  background: linear-gradient(90deg, #ffc107, #ff9800);
  transition: width 0.8s ease;
}
</style>
