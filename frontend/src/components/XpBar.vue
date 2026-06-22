<template>
  <div class="xp-bar-container">
    <div class="flex justify-between items-center mb-1">
      <span class="text-sm font-black text-purple-200">
        ⭐ Niveau {{ level }}
      </span>
      <span class="text-xs text-purple-300">
        {{ xp }} / {{ xpNeeded }} XP
      </span>
    </div>
    <div class="xp-track">
      <div
        class="xp-fill"
        :style="{ width: `${Math.min(100, (xp / xpNeeded) * 100)}%` }"
      ></div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'XpBar',
  props: {
    level: { type: Number, default: 1 },
    xp: { type: Number, default: 0 },
  },
  computed: {
    xpNeeded() {
      return this.level * 100
    }
  }
}
</script>

<style scoped>
.xp-bar-container {
  width: 100%;
}
.xp-track {
  background: rgba(0,0,0,0.5);
  border-radius: 999px;
  height: 10px;
  overflow: hidden;
  border: 1px solid rgba(156,39,176,0.4);
}
.xp-fill {
  height: 100%;
  border-radius: 999px;
  background: linear-gradient(90deg, #9c27b0, #e040fb);
  transition: width 1s ease;
  box-shadow: 0 0 8px rgba(156,39,176,0.6);
}
</style>
