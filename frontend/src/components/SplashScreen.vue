<template>
  <div class="splash">
    <div class="splash-inner">
      <img :src="CastleSVG" class="splash-logo" alt="castle" />
      <h2 class="splash-title">Kingdom Clash</h2>
      <p class="splash-sub">Chargement du royaume…</p>
    </div>
  </div>
</template>

<script>
import { onMounted } from 'vue'
import CastleSVG from '../assets/castle.svg'

export default {
  emits: ['finished'],
  setup(_, { emit }) {
    onMounted(() => {
      setTimeout(() => emit('finished'), 1500)
    })
    return { CastleSVG }
  },
}
</script>

<style scoped>
.splash {
  position: fixed;
  inset: 0;
  display:flex;
  align-items:center;
  justify-content:center;
  background: linear-gradient(180deg, rgba(2,6,23,0.95), rgba(2,6,23,0.9));
  z-index: 9999;
}
.splash-inner { text-align:center; color:#fff }
.splash-logo { width:84px; height:84px; animation: pop 900ms ease both }
.splash-title { margin: 12px 0 6px; font-size:24px; animation: fadeInUp 900ms both }
.splash-sub { opacity:0.8; animation: fadeInUp 1100ms both }

@keyframes pop { 0% { transform: scale(.6); opacity:0 } 100% { transform: scale(1); opacity:1 } }
@keyframes fadeInUp { 0% { transform: translateY(8px); opacity:0 } 100% { transform: translateY(0); opacity:1 } }
</style>
