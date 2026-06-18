<template>
  <div>
    <SplashScreen v-if="showSplash" @finished="onSplashFinished" />

    <div v-else class="hero-section">
      <div class="card hero-card">
        <div class="hero-content">
          <div class="hero-icon-container">
            <img :src="CastleSVG" class="hero-icon" alt="logo" />
            <div class="sparkle sparkle-1">✨</div>
            <div class="sparkle sparkle-2">⭐</div>
            <div class="sparkle sparkle-3">🌟</div>
          </div>
          <div>
            <h1 class="hero-title slide-up">⚔️ Kingdom Clash ⚔️</h1>
            <p class="hero-subtitle fade-in">Plonge dans le MMORTS — construis ton empire, forme ton armée, domine le monde ! 🏰</p>
          </div>
        </div>

        <div class="hero-actions">
          <router-link to="/login" class="btn-primary pulse">Se connecter 🔑</router-link>
          <router-link to="/register" class="btn-secondary">Créer un compte 🚀</router-link>
          <router-link to="/dashboard" class="btn-secondary">Aller au tableau de bord 📊</router-link>
        </div>

        <div class="features-grid">
          <div class="feature-card">
            <div class="feature-icon">🏗️</div>
            <h3>Construis</h3>
            <p>Améliore tes bâtiments et développe ton royaume</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">⚔️</div>
            <h3>Combats</h3>
            <p>Forme ton armée et attaque les autres royaumes</p>
          </div>
          <div class="feature-card">
            <div class="feature-icon">👑</div>
            <h3>Domine</h3>
            <p>Grimpe dans le classement et deviens le roi !</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import SplashScreen from '../components/SplashScreen.vue'
import CastleSVG from '../assets/castle.svg'

export default {
  components: { SplashScreen },
  setup() {
    const router = useRouter()
    const showSplash = ref(true)

    const onSplashFinished = () => {
      const token = localStorage.getItem('auth_token')
      if (token) {
        router.push('/dashboard')
        return
      }
      showSplash.value = false
    }

    return { showSplash, onSplashFinished, CastleSVG }
  },
}
</script>

<style scoped>
.hero-section {
  display: flex;
  justify-content: center;
  padding: 1rem;
}

.hero-card {
  max-width: 1000px;
  width: 100%;
  text-align: center;
}

.hero-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1.5rem;
  margin-bottom: 2rem;
}

.hero-icon-container {
  position: relative;
  width: 140px;
  height: 140px;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: bounce 2s ease-in-out infinite;
}

@keyframes bounce {
  0%, 100% { transform: translateY(0); }
  50% { transform: translateY(-15px); }
}

.hero-icon {
  width: 100%;
  height: 100%;
  filter: drop-shadow(0 0 20px rgba(96, 165, 250, 0.5));
}

.sparkle {
  position: absolute;
  font-size: 1.5rem;
  animation: sparkleFloat 1.5s ease-in-out infinite;
}

.sparkle-1 {
  top: 0;
  left: 0;
  animation-delay: 0s;
}

.sparkle-2 {
  top: 20%;
  right: 0;
  animation-delay: 0.5s;
}

.sparkle-3 {
  bottom: 0;
  left: 30%;
  animation-delay: 1s;
}

@keyframes sparkleFloat {
  0%, 100% { transform: scale(1) rotate(0deg); opacity: 1; }
  50% { transform: scale(1.3) rotate(20deg); opacity: 0.7; }
}

.hero-title {
  font-size: 2.8rem;
  margin: 0;
  background: linear-gradient(135deg, #60a5fa, #a78bfa, #f472b6, #60a5fa);
  background-size: 300% 300%;
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
  animation: gradientShift 4s ease infinite;
  text-shadow: 0 0 40px rgba(96, 165, 250, 0.3);
}

@keyframes gradientShift {
  0%, 100% { background-position: 0% 50%; }
  50% { background-position: 100% 50%; }
}

.hero-subtitle {
  font-size: 1.2rem;
  color: #94a3b8;
  max-width: 600px;
  margin: 0 auto;
  line-height: 1.7;
}

.hero-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  flex-wrap: wrap;
  margin-bottom: 3rem;
}

.btn-primary, .btn-secondary {
  padding: 1rem 2rem;
  border-radius: 9999px;
  font-weight: 700;
  font-size: 1.05rem;
  text-decoration: none;
  transition: all 0.3s ease;
  cursor: pointer;
  display: inline-block;
}

.btn-primary {
  background: linear-gradient(135deg, #2563eb, #60a5fa);
  color: white;
  border: none;
  box-shadow: 0 10px 30px rgba(37, 99, 235, 0.4);
}

.btn-primary:hover {
  transform: translateY(-3px);
  box-shadow: 0 15px 40px rgba(37, 99, 235, 0.5);
}

.btn-secondary {
  background: rgba(255,255,255,0.08);
  color: white;
  border: 1px solid rgba(255,255,255,0.2);
}

.btn-secondary:hover {
  transform: translateY(-2px);
  background: rgba(255,255,255,0.12);
  border-color: rgba(96, 165, 250, 0.5);
}

.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 1.5rem;
  margin-top: 1rem;
}

.feature-card {
  background: linear-gradient(145deg, rgba(30, 41, 59, 0.9), rgba(15, 23, 42, 0.95));
  padding: 2rem 1.5rem;
  border-radius: 1.25rem;
  border: 1px solid rgba(148, 163, 184, 0.2);
  transition: all 0.3s ease;
}

.feature-card:hover {
  transform: translateY(-5px);
  border-color: rgba(167, 139, 250, 0.5);
  box-shadow: 0 15px 40px rgba(167, 139, 250, 0.2);
}

.feature-icon {
  font-size: 3rem;
  margin-bottom: 1rem;
  animation: pulseIcon 2s ease-in-out infinite;
}

@keyframes pulseIcon {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.1); }
}

.feature-card h3 {
  margin: 0 0 0.5rem;
  color: white;
  font-size: 1.3rem;
}

.feature-card p {
  margin: 0;
  color: #94a3b8;
  font-size: 0.95rem;
  line-height: 1.6;
}

.pulse {
  animation: pulse 2s infinite;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.5); }
  50% { transform: scale(1.02); box-shadow: 0 0 0 20px rgba(37, 99, 235, 0); }
}
</style>
