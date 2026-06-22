<template>
  <div ref="rootRef" class="war-root" @contextmenu.prevent>

    <!-- ═══════════════════════════════════════════════════════════════
         LOBBY — Sélection du Royaume à conquérir
    ════════════════════════════════════════════════════════════════════ -->
    <div v-if="phase === 'lobby'" class="lobby-screen">
      <div class="lobby-bg-particles" aria-hidden="true">
        <span v-for="n in 18" :key="n" class="lbg-spark" :style="lobbySparkStyle(n)" />
      </div>

      <div class="lobby-header">
        <div class="lobby-crown">👑</div>
        <h1 class="lobby-title">GUERRE DES ROYAUMES</h1>
        <p class="lobby-sub">Entrez dans un royaume ennemi, décimez sa garnison,<br>tuez son Roi et prenez sa couronne.</p>
      </div>

      <div v-if="lobbyLoading" class="lobby-loading">
        <span class="loader-ring" />
        <span>Chargement des royaumes…</span>
      </div>

      <div v-else class="kingdoms-grid">
        <div
          v-for="k in conquestKingdoms"
          :key="k.id"
          class="kingdom-card"
          :class="`diff-${k.difficulty}`"
          @click="selectKingdom(k)"
        >
          <div class="kc-banner" :style="bannerStyle(k.difficulty)">
            <span class="kc-icon">{{ diffIcon(k.difficulty) }}</span>
            <span class="kc-diff-badge">{{ diffLabel(k.difficulty) }}</span>
          </div>
          <div class="kc-body">
            <div class="kc-name">{{ k.name }}</div>
            <div class="kc-level">Niveau {{ k.level }}</div>
            <div class="kc-stats">
              <span>🛡️ {{ k.defense_power }}</span>
              <span>💰 {{ k.gold_capacity.toLocaleString() }}</span>
            </div>
            <div class="kc-wave-preview">
              <span v-for="w in 3" :key="w" class="wave-pip" />
              <span class="boss-pip">👑</span>
            </div>
            <div class="kc-rewards">
              <span>+{{ k.reward_preview.gold.toLocaleString() }} 💰</span>
              <span>+{{ k.reward_preview.trophies }} 🏆</span>
              <span>{{ chestEmoji(k.reward_preview.chestType) }}</span>
            </div>
            <button class="kc-btn" @click.stop="selectKingdom(k)">⚔️ ATTAQUER !</button>
          </div>
        </div>
      </div>

      <button class="btn-back" @click="$router.push('/')">🏠 Retour à la Base</button>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════
         ARÈNE FULLSCREEN
    ════════════════════════════════════════════════════════════════════ -->
    <div v-if="phase === 'fighting'" class="arena-root">

      <!-- HUD superposé -->
      <div class="hud-overlay">

        <!-- Joueur (haut gauche) -->
        <div class="hud-player">
          <div class="hud-avatar" :class="{ 'hud-avatar--rage': ragePct >= 100 }">🗡️</div>
          <div class="hud-bars">
            <div class="hud-name">{{ playerName }}</div>
            <div class="hp-track">
              <div class="hp-fill" :style="{ width: hpPct(playerHp, playerMaxHp), background: hpColor(playerHp, playerMaxHp) }" />
              <span class="hp-text">{{ Math.ceil(playerHp) }} / {{ playerMaxHp }}</span>
            </div>
            <div class="rage-track">
              <div class="rage-fill" :style="{ width: ragePct + '%' }" />
              <span class="rage-text">{{ ragePct >= 100 ? '🔥 RAGE!' : `⚡ ${Math.floor(rage)}%` }}</span>
            </div>
          </div>
        </div>

        <!-- Vague (haut centre) -->
        <div class="hud-wave">
          <div class="wave-label">{{ waveLabel }}</div>
          <div class="wave-pips">
            <span
              v-for="w in totalWaves"
              :key="w"
              class="wpip"
              :class="{ 'wpip--done': w < currentWave, 'wpip--active': w === currentWave }"
            />
            <span class="wpip wpip-boss" :class="{ 'wpip-boss--active': currentWave > totalWaves }">👑</span>
          </div>
        </div>

        <!-- Score (haut droite) -->
        <div class="hud-score-box">
          <div class="score-num">{{ score.toLocaleString() }}</div>
          <div class="score-label">Score</div>
          <div class="kills-label">💀 {{ killCount }} kills</div>
          <div class="timer-label">⏱ {{ timerStr }}</div>
        </div>

        <!-- Streak banner (milieu) -->
        <transition name="streak-fade">
          <div v-if="showStreak" class="streak-banner">
            <span class="streak-num">{{ streakCount }}x</span>
            <span class="streak-txt">COMBO KILL !</span>
          </div>
        </transition>

        <!-- Boss HP bar (bas centre) — n'apparaît que pendant le boss -->
        <transition name="boss-bar-fade">
          <div v-if="bossVisible" class="boss-hud">
            <div class="boss-hud-name">👑 ROI ENNEMI</div>
            <div class="boss-hp-track">
              <div class="boss-hp-fill" :style="{ width: bossHpPct + '%' }" />
              <span class="boss-hp-text">{{ bossHp }} / {{ bossMaxHp }}</span>
            </div>
          </div>
        </transition>

        <!-- Contrôles (bas) -->
        <div class="hud-controls">
          <span>ZQSD / WASD — Bouger</span>
          <span>🖱️ Viser</span>
          <span>Clic Gauche — Épée</span>
          <span>ESC — Abandonner</span>
        </div>
      </div>

      <!-- Canvas de jeu -->
      <canvas
        ref="canvasRef"
        class="game-canvas"
        @mousemove="onMouseMove"
        @mousedown="onMouseDown"
        @mouseup="onMouseUp"
      />
    </div>

    <!-- ═══════════════════════════════════════════════════════════════
         VICTOIRE
    ════════════════════════════════════════════════════════════════════ -->
    <div v-if="phase === 'victory'" class="end-screen victory-screen">
      <div class="victory-particles" aria-hidden="true">
        <span v-for="n in 24" :key="n" class="vp" :style="victoryParticleStyle(n)" />
      </div>
      <div class="end-title">🎉 VICTOIRE !</div>
      <div class="end-kingdom">{{ selectedKingdom?.name }} est à vous !</div>
      <div class="end-rewards">
        <div class="reward-item gold-reward">💰 {{ finalRewards.gold?.toLocaleString() }}</div>
        <div class="reward-item">🪵 {{ finalRewards.wood }}</div>
        <div class="reward-item">🍖 {{ finalRewards.food }}</div>
        <div class="reward-item trophy-reward">🏆 +{{ finalRewards.trophies }}</div>
        <div class="reward-item xp-reward">⭐ +{{ finalRewards.xp }} XP</div>
        <div v-if="finalRewards.chestType" class="reward-item chest-reward">
          {{ chestEmoji(finalRewards.chestType) }} Coffre {{ chestLabel(finalRewards.chestType) }}
        </div>
      </div>
      <div class="end-stats">
        <span>💀 {{ killCount }} éliminations</span>
        <span>⏱ {{ timerStr }}</span>
        <span>🏅 {{ score.toLocaleString() }} pts</span>
      </div>
      <div v-if="conquestDone" class="conquest-done">✅ Royaume transféré dans votre empire !</div>
      <div v-else-if="conquesting" class="conquesting">
        <span class="loader-ring loader-ring--sm" /> Transfert en cours…
      </div>
      <div class="end-buttons">
        <button v-if="!conquestDone && !conquesting" class="btn-conquer" @click="doConquest">
          ⚔️ CONQUÉRIR LE ROYAUME
        </button>
        <button class="btn-home" @click="$router.push('/')">🏠 Retour à la Base</button>
      </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════
         DÉFAITE
    ════════════════════════════════════════════════════════════════════ -->
    <div v-if="phase === 'defeat'" class="end-screen defeat-screen">
      <div class="end-title defeat-title">💀 DÉFAITE</div>
      <div class="end-sub">Votre héros est tombé au combat.</div>
      <div class="end-rewards">
        <div class="reward-item">🏅 {{ score.toLocaleString() }} points</div>
        <div class="reward-item">💀 {{ killCount }} ennemis éliminés</div>
        <div class="reward-item">⏱ {{ timerStr }}</div>
      </div>
      <div class="end-buttons">
        <button class="btn-retry" @click="retryFight">🔄 Réessayer</button>
        <button class="btn-home" @click="$router.push('/')">🏠 Retour</button>
      </div>
    </div>

  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted, nextTick } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { getConquestKingdoms, conquestConquer } from '../services/api'

// ── Assets sprites (fallback canvas si absents) ───────────────────────────────
let dragonKnightIdle, dragonKnightAttack, dragonKnightDead
let arcaneWizardIdle, arcaneWizardAttack, arcaneWizardDead
let shadowLordIdle, shadowLordAttack, shadowLordDead
try {
  dragonKnightIdle   = new URL('../assets/Dragon Knight/0-stop/0 (1).gif',   import.meta.url).href
  dragonKnightAttack = new URL('../assets/Dragon Knight/3-attack/3 (1).gif', import.meta.url).href
  dragonKnightDead   = new URL('../assets/Dragon Knight/6-dead/6 (1).gif',   import.meta.url).href
  arcaneWizardIdle   = new URL('../assets/Arcane Wizard/0-stop/0 (1).gif',   import.meta.url).href
  arcaneWizardAttack = new URL('../assets/Arcane Wizard/1-attack/1 (1).gif', import.meta.url).href
  arcaneWizardDead   = new URL('../assets/Arcane Wizard/5-dead/5 (1).gif',   import.meta.url).href
  shadowLordIdle     = new URL('../assets/Shadow Lord/0-stop/0 (1).gif',     import.meta.url).href
  shadowLordAttack   = new URL('../assets/Shadow Lord/1-attack/1 (1).gif',   import.meta.url).href
  shadowLordDead     = new URL('../assets/Shadow Lord/3-dead/3 (1).gif',     import.meta.url).href
} catch (_) { /* assets absents : fallback Canvas pur */ }

// ── Constantes de jeu ─────────────────────────────────────────────────────────
const PLAYER_SPEED    = 4.8
const PLAYER_R        = 28
const ENEMY_R         = 30
const BOSS_R          = 48
const SWORD_LEN       = 85
const SWORD_FRAMES    = 12
const SWORD_START_OFF = -Math.PI / 2.5
const SWORD_END_OFF   =  Math.PI / 2.5
const PROJ_BASE_SPD   = 4.4
const SHOOT_BASE_MS   = 2200
const KNOCKBACK_SPD   = 9
const KNOCKBACK_DEC   = 0.78
const HEAL_RATIO      = 0.30
const RAGE_PER_KILL   = 22
const TOTAL_WAVES     = 3
const MELEE_COOLDOWN  = 70   // frames (~1.16s) entre deux dégâts de contact
const MAX_PARTICLES   = 250  // pool de particules réutilisé
const STREAK_WINDOW   = 2500 // ms pour enchaîner un combo

export default {
  name: 'KingdomWarArena',

  setup() {
    const router = useRouter()
    const route  = useRoute()

    // ── Refs DOM ──────────────────────────────────────────────────────────────
    const rootRef   = ref(null)
    const canvasRef = ref(null)

    // ── État global ───────────────────────────────────────────────────────────
    const phase            = ref('lobby')
    const lobbyLoading     = ref(true)
    const conquestKingdoms = ref([])
    const selectedKingdom  = ref(null)

    // ── HUD réactif ───────────────────────────────────────────────────────────
    const playerHp    = ref(200)
    const playerMaxHp = ref(200)
    const playerName  = ref('Héros')
    const score       = ref(0)
    const killCount   = ref(0)
    const rage        = ref(0)
    const ragePct     = computed(() => Math.min(100, rage.value))
    const currentWave = ref(1)
    const totalWaves  = ref(TOTAL_WAVES)
    const waveLabel   = ref('')
    const bossVisible = ref(false)
    const bossHp      = ref(0)
    const bossMaxHp   = ref(0)
    const bossHpPct   = computed(() => bossMaxHp.value > 0 ? Math.max(0, (bossHp.value / bossMaxHp.value) * 100) : 0)
    const showStreak  = ref(false)
    const streakCount = ref(0)
    const timerStr    = ref('00:00')

    // ── Résultats & conquête ──────────────────────────────────────────────────
    const finalRewards = ref({})
    const conquesting  = ref(false)
    const conquestDone = ref(false)

    // ── Helpers UI ────────────────────────────────────────────────────────────
    const hpPct   = (hp, max) => Math.max(0, (hp / max) * 100) + '%'
    const hpColor = (hp, max) => {
      const r = hp / max
      if (r > 0.5) return 'linear-gradient(90deg,#43a047,#69f0ae)'
      if (r > 0.25) return 'linear-gradient(90deg,#fb8c00,#ffd54f)'
      return 'linear-gradient(90deg,#e53935,#ff6f00)'
    }
    const chestEmoji = t => t === 'magical' ? '✨' : t === 'gold' ? '🥇' : '🥈'
    const chestLabel = t => t === 'magical' ? 'Magique !' : t === 'gold' ? 'Or' : 'Argent'
    const diffIcon   = d => ['🏕️', '👺', '🐉', '💀', '👑'][d - 1] || '⚔️'
    const diffLabel  = d => ['Débutant', 'Intermédiaire', 'Difficile', 'Expert', 'Légendaire'][d - 1] || '?'
    const bannerStyle = d => {
      const colors = ['#2e7d32', '#e65100', '#b71c1c', '#6a1b9a', '#0d47a1']
      return { background: `linear-gradient(135deg, ${colors[d-1] || '#37474f'} 0%, rgba(0,0,0,0) 100%)` }
    }
    const lobbySparkStyle = n => ({
      left: `${(n * 47 + 13) % 100}%`,
      top:  `${(n * 67 + 7) % 100}%`,
      animationDelay: `${(n * 0.37) % 3}s`,
      animationDuration: `${2.5 + (n % 4) * 0.5}s`,
    })
    const victoryParticleStyle = n => ({
      left: `${(n * 61 + 5) % 100}%`,
      top:  `${(n * 43 + 10) % 100}%`,
      animationDelay: `${(n * 0.13) % 2}s`,
      background: ['#ffd700','#ff6f00','#e53935','#ce93d8','#69f0ae'][(n % 5)],
    })

    // ── Web Audio Engine (sons synthétiques, zéro fichier externe) ────────────
    let audioCtx = null
    function getAudioCtx() {
      if (!audioCtx) audioCtx = new (window.AudioContext || window.webkitAudioContext)()
      if (audioCtx.state === 'suspended') audioCtx.resume()
      return audioCtx
    }
    function playTone(freq, type, duration, gainVal, decay) {
      try {
        const ctx  = getAudioCtx()
        const osc  = ctx.createOscillator()
        const gain = ctx.createGain()
        osc.connect(gain); gain.connect(ctx.destination)
        osc.type = type; osc.frequency.setValueAtTime(freq, ctx.currentTime)
        gain.gain.setValueAtTime(gainVal, ctx.currentTime)
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + decay)
        osc.start(); osc.stop(ctx.currentTime + duration)
      } catch (_) {}
    }
    const sfx = {
      swing:   () => { playTone(280, 'sawtooth', 0.12, 0.25, 0.10) },
      hit:     () => { playTone(120, 'square',   0.10, 0.30, 0.08) },
      kill:    () => { playTone(440, 'sine',      0.20, 0.20, 0.18); playTone(660, 'sine', 0.25, 0.12, 0.22) },
      damage:  () => { playTone(80,  'sawtooth',  0.08, 0.35, 0.07) },
      victory: () => {
        [523, 659, 784, 1047].forEach((f, i) => setTimeout(() => playTone(f, 'sine', 0.4, 0.2, 0.35), i * 140))
      },
      defeat:  () => { playTone(220, 'sawtooth', 0.5, 0.25, 0.45); setTimeout(() => playTone(110, 'sawtooth', 0.4, 0.2, 0.4), 250) },
      rage:    () => { [200, 300, 450].forEach((f, i) => setTimeout(() => playTone(f, 'square', 0.15, 0.2, 0.12), i * 60)) },
    }

    // ── Variables de runtime non-réactives (perf) ─────────────────────────────
    let canvas = null, ctx = null, cw = 0, ch = 0
    let rafId = null, shootTimers = [], battleStartMs = 0, timerInterval = null
    let _p = null, _enemies = [], _proj = [], _keys = {}, _mx = 0, _my = 0
    let _swinging = false, _swingFrame = 0, _swingAngle = SWORD_START_OFF, _swingHit = new Set()
    let _kbx = 0, _kby = 0
    let _waveIdx = 0, _waveKills = 0, _waveSize = 0
    let _diff = 1, _frame = 0
    let _lastKillTime = 0, _localStreak = 0
    let _rageActive = false, _rageDuration = 0

    // Pool de particules
    const _particles = Array.from({ length: MAX_PARTICLES }, () => ({
      active: false, x: 0, y: 0, vx: 0, vy: 0, life: 0, maxLife: 0, r: 0, color: '#fff',
    }))

    // Sprites
    const _imgs = {}
    function loadSprites() {
      const map = {
        playerIdle:   dragonKnightIdle,   playerAttack: dragonKnightAttack, playerDead: dragonKnightDead,
        enemyIdle:    arcaneWizardIdle,   enemyAttack:  arcaneWizardAttack, enemyDead:  arcaneWizardDead,
        bossIdle:     shadowLordIdle,     bossAttack:   shadowLordAttack,   bossDead:   shadowLordDead,
      }
      for (const [k, src] of Object.entries(map)) {
        if (!src) continue
        _imgs[k] = new Image()
        _imgs[k].src = src
      }
    }

    // ── LOBBY ─────────────────────────────────────────────────────────────────
    async function loadLobby() {
      lobbyLoading.value = true
      try {
        const res = await getConquestKingdoms()
        conquestKingdoms.value = res.data.kingdoms
        if (route.params.kingdomId) {
          const found = conquestKingdoms.value.find(k => k.id == route.params.kingdomId)
          if (found) selectKingdom(found)
        }
      } catch (e) {
        console.error('loadLobby error:', e)
      } finally {
        lobbyLoading.value = false
      }
    }

    function selectKingdom(k) {
      selectedKingdom.value = k
      _diff = k.difficulty
      startFight()
    }

    // ── DÉMARRAGE ─────────────────────────────────────────────────────────────
    function startFight() {
      phase.value = 'fighting'
      try {
        const u = JSON.parse(localStorage.getItem('auth_user'))
        playerName.value = u?.name || 'Héros'
      } catch (_) {}

      nextTick(() => {
        canvas = canvasRef.value
        ctx    = canvas.getContext('2d')
        resizeCanvas()
        window.addEventListener('resize',  resizeCanvas)
        window.addEventListener('keydown', onKeyDown)
        window.addEventListener('keyup',   onKeyUp)
        loadSprites()
        resetGame()
        battleStartMs = Date.now()
        startTimer()
        spawnWave(0)
        rafId = requestAnimationFrame(gameLoop)
      })
    }

    function resizeCanvas() {
      if (!canvas) return
      cw = canvas.width  = window.innerWidth
      ch = canvas.height = window.innerHeight
      if (_p) {
        _p.x = Math.max(PLAYER_R, Math.min(cw - PLAYER_R, _p.x))
        _p.y = Math.max(PLAYER_R, Math.min(ch - PLAYER_R, _p.y))
      }
    }

    function resetGame() {
      _p = {
        x: 180, y: ch / 2 || 400,
        vx: 0, vy: 0,
        hp: 200, maxHp: 200,
        angle: 0,
        iFrames: 0,   // invincibility frames after being hit
      }
      _enemies = []; _proj = []; _keys = {}
      _mx = cw / 2; _my = ch / 2
      _swinging = false; _swingFrame = 0
      _swingAngle = SWORD_START_OFF; _swingHit = new Set()
      _kbx = 0; _kby = 0
      _waveIdx = 0; _waveKills = 0; _waveSize = 0
      _frame = 0; _rageActive = false; _rageDuration = 0
      _lastKillTime = 0; _localStreak = 0

      score.value = 0; killCount.value = 0; rage.value = 0
      currentWave.value = 1; waveLabel.value = ''
      playerHp.value = 200; playerMaxHp.value = 200
      bossVisible.value = false; bossHp.value = 0; bossMaxHp.value = 0
      showStreak.value = false; streakCount.value = 0

      _particles.forEach(p => { p.active = false })
      clearShootTimers()
    }

    function clearShootTimers() {
      shootTimers.forEach(clearInterval)
      shootTimers = []
    }

    function startTimer() {
      if (timerInterval) clearInterval(timerInterval)
      timerInterval = setInterval(() => {
        const elapsed = Math.floor((Date.now() - battleStartMs) / 1000)
        const m = String(Math.floor(elapsed / 60)).padStart(2, '0')
        const s = String(elapsed % 60).padStart(2, '0')
        timerStr.value = `${m}:${s}`
      }, 500)
    }

    // ── PARTICULES ────────────────────────────────────────────────────────────
    function spawnParticles(x, y, count, color, speed, minLife, maxLife) {
      let spawned = 0
      for (let i = 0; i < _particles.length && spawned < count; i++) {
        const p = _particles[i]
        if (p.active) continue
        const angle = Math.random() * Math.PI * 2
        const spd   = speed * (0.5 + Math.random() * 0.5)
        p.active  = true
        p.x       = x; p.y = y
        p.vx      = Math.cos(angle) * spd
        p.vy      = Math.sin(angle) * spd
        p.life    = minLife + Math.random() * (maxLife - minLife)
        p.maxLife = p.life
        p.r       = 2 + Math.random() * 4
        p.color   = color
        spawned++
      }
    }

    function updateParticles() {
      for (const p of _particles) {
        if (!p.active) continue
        p.x    += p.vx
        p.y    += p.vy
        p.vx   *= 0.92
        p.vy   *= 0.92
        p.life--
        if (p.life <= 0) p.active = false
      }
    }

    function drawParticles() {
      for (const p of _particles) {
        if (!p.active) continue
        const alpha = p.life / p.maxLife
        ctx.save()
        ctx.globalAlpha = alpha
        ctx.fillStyle   = p.color
        ctx.shadowColor = p.color
        ctx.shadowBlur  = 6
        ctx.beginPath(); ctx.arc(p.x, p.y, p.r * alpha, 0, Math.PI * 2); ctx.fill()
        ctx.restore()
      }
    }

    // ── VAGUES ────────────────────────────────────────────────────────────────
    function spawnWave(idx) {
      clearShootTimers()
      _waveIdx = idx; _waveKills = 0
      currentWave.value = idx + 1

      if (idx >= TOTAL_WAVES) {
        waveLabel.value   = '👑 LE ROI — Boss Final !'
        bossVisible.value = true
        spawnBoss()
      } else {
        const count = 2 + idx + Math.floor(_diff / 2)
        _waveSize = count
        waveLabel.value = `Vague ${idx + 1} / ${TOTAL_WAVES} — ${count} Soldats d'Élite`
        for (let i = 0; i < count; i++) spawnSoldier(i, count)
        _enemies.forEach(e => armShooter(e))
      }
    }

    function spawnSoldier(i, total) {
      const margin = 120
      const x  = cw - margin - Math.random() * (cw * 0.30)
      const gap = (ch - margin * 2) / Math.max(total - 1, 1)
      const y  = margin + i * gap + (Math.random() - 0.5) * gap * 0.4
      const hp = Math.floor((65 + _diff * 22) * (1 + _waveIdx * 0.25))
      _enemies.push({
        id:       Math.random(),
        x, y,
        hp, maxHp: hp,
        atk:      9 + _diff * 2,
        speed:    1.1 + _diff * 0.15,
        radius:   ENEMY_R,
        isBoss:   false,
        aiCd:     Math.random() * 80,
        meleeCd:  0,
        isDead:   false,
        deadTimer: 0,
        isAttacking: false,
        phase: 1,
      })
    }

    function spawnBoss() {
      const hp = Math.floor(500 * (1 + (_diff - 1) * 0.60))
      const boss = {
        id: 'boss',
        x: cw - 200, y: ch / 2,
        hp, maxHp: hp,
        atk: 20 + _diff * 5,
        speed: 0.75 + _diff * 0.09,
        radius: BOSS_R,
        isBoss: true,
        aiCd: 0,
        meleeCd: 0,
        isDead: false,
        deadTimer: 0,
        isAttacking: false,
        phase: 1,   // 1 = normal, 2 = enraged (<50%), 3 = frenzy (<25%)
      }
      _enemies.push(boss)
      bossHp.value    = hp
      bossMaxHp.value = hp
      armBoss(boss)
    }

    function armShooter(e) {
      const ms = Math.max(700, SHOOT_BASE_MS - (_diff - 1) * 320) + Math.random() * 600
      const t  = setInterval(() => {
        if (phase.value !== 'fighting' || e.isDead) return
        fireAt(e, _p.x, _p.y)
      }, ms)
      shootTimers.push(t)
    }

    function armBoss(b) {
      // Tir direct
      const t1 = setInterval(() => {
        if (phase.value !== 'fighting' || b.isDead) return
        fireAt(b, _p.x, _p.y)
        b.isAttacking = true; setTimeout(() => { b.isAttacking = false }, 200)
      }, 1500)
      // Éventail 3 projectiles
      const t2 = setInterval(() => {
        if (phase.value !== 'fighting' || b.isDead) return
        fireFan(b, 3, Math.PI / 5)
      }, 3000)
      // Éventail 5 projectiles (spirale) — actif seulement en phase 2+
      const t3 = setInterval(() => {
        if (phase.value !== 'fighting' || b.isDead || b.phase < 2) return
        fireFan(b, 5, Math.PI / 3)
      }, 5000)
      // Tir en croix — actif uniquement en phase 3
      const t4 = setInterval(() => {
        if (phase.value !== 'fighting' || b.isDead || b.phase < 3) return
        fireFan(b, 8, Math.PI * 2)  // Cercle complet
      }, 7000)
      shootTimers.push(t1, t2, t3, t4)
    }

    function fireAt(src, tx, ty) {
      const dx  = tx - src.x, dy = ty - src.y
      const d   = Math.sqrt(dx * dx + dy * dy) || 1
      const spd = PROJ_BASE_SPD * (1 + (_diff - 1) * 0.12)
      _proj.push({
        x: src.x, y: src.y,
        vx: (dx / d) * spd,
        vy: (dy / d) * spd,
        r:   src.isBoss ? 14 : 11,
        dmg: src.atk,
        isBoss: src.isBoss,
        life: 220,
      })
    }

    function fireFan(src, count, spread) {
      const baseAngle = Math.atan2(_p.y - src.y, _p.x - src.x)
      const spd       = PROJ_BASE_SPD * (1 + (_diff - 1) * 0.12)
      for (let i = 0; i < count; i++) {
        const a = count > 1
          ? baseAngle - spread / 2 + (spread / (count - 1)) * i
          : baseAngle
        _proj.push({
          x: src.x, y: src.y,
          vx: Math.cos(a) * spd,
          vy: Math.sin(a) * spd,
          r: src.isBoss ? 13 : 10,
          dmg: src.atk * 0.75,
          isBoss: src.isBoss,
          life: 220,
        })
      }
    }

    // ── BOUCLE DE JEU ─────────────────────────────────────────────────────────
    function gameLoop() {
      if (phase.value !== 'fighting') return
      _frame++

      ctx.clearRect(0, 0, cw, ch)

      drawBackground()
      updateParticles()
      drawParticles()

      updatePlayer()
      updateEnemies()
      updateProjectiles()
      updateSword()

      drawProjectiles()
      _enemies.forEach(e => drawEnemy(e))
      drawPlayer()
      drawSword()

      // Nettoyer les ennemis morts après leur animation (60 frames ~1s)
      if (_frame % 60 === 0) {
        _enemies = _enemies.filter(e => !(e.isDead && e.deadTimer > 55))
      }

      syncHud()
      rafId = requestAnimationFrame(gameLoop)
    }

    // ── UPDATE JOUEUR ─────────────────────────────────────────────────────────
    function updatePlayer() {
      if (!_p) return

      if (_p.iFrames > 0) _p.iFrames--

      // Vitesse de base (augmentée en rage)
      const spd = _rageActive ? PLAYER_SPEED * 1.3 : PLAYER_SPEED
      let dx = 0, dy = 0

      if (_keys['z'] || _keys['w'] || _keys['arrowup'])    dy -= spd
      if (_keys['s'] || _keys['arrowdown'])                dy += spd
      if (_keys['q'] || _keys['a'] || _keys['arrowleft'])  dx -= spd
      if (_keys['d'] || _keys['arrowright'])               dx += spd

      // Normalisation diagonale
      if (dx !== 0 && dy !== 0) { dx *= 0.7071; dy *= 0.7071 }

      _p.x += dx
      _p.y += dy

      // Knockback
      _p.x += _kbx; _p.y += _kby
      _kbx *= KNOCKBACK_DEC; _kby *= KNOCKBACK_DEC
      if (Math.abs(_kbx) < 0.05) _kbx = 0
      if (Math.abs(_kby) < 0.05) _kby = 0

      // Clamp dans le canvas
      _p.x = Math.max(PLAYER_R, Math.min(cw - PLAYER_R, _p.x))
      _p.y = Math.max(PLAYER_R, Math.min(ch - PLAYER_R, _p.y))

      // Orientation vers la souris
      _p.angle = Math.atan2(_my - _p.y, _mx - _p.x)

      // Collision physique avec les ennemis vivants (corps solides)
      for (const e of _enemies) {
        if (e.isDead) continue
        const minD = PLAYER_R + e.radius
        const ex   = _p.x - e.x, ey = _p.y - e.y
        const dist = Math.sqrt(ex * ex + ey * ey)
        if (dist < minD && dist > 0) {
          const nx = ex / dist, ny = ey / dist
          _p.x = e.x + nx * minD
          _p.y = e.y + ny * minD
          _p.x = Math.max(PLAYER_R, Math.min(cw - PLAYER_R, _p.x))
          _p.y = Math.max(PLAYER_R, Math.min(ch - PLAYER_R, _p.y))
        }
      }

      // Gestion du Rage Mode
      if (_rageActive) {
        _rageDuration--
        if (_rageDuration <= 0) {
          _rageActive = false
          rage.value  = 0
        }
      }
    }

    // ── UPDATE ENNEMIS ────────────────────────────────────────────────────────
    function updateEnemies() {
      for (const e of _enemies) {
        if (e.isDead) { e.deadTimer++; continue }

        // Update phase du boss
        if (e.isBoss) {
          const hpRatio = e.hp / e.maxHp
          if (hpRatio < 0.25 && e.phase < 3)      { e.phase = 3; e.speed *= 1.3 }
          else if (hpRatio < 0.50 && e.phase < 2) { e.phase = 2; e.speed *= 1.15 }
        }

        // Cooldown mêlée
        if (e.meleeCd > 0) e.meleeCd--

        // Déplacement vers le joueur
        const dx   = _p.x - e.x, dy = _p.y - e.y
        const dist = Math.sqrt(dx * dx + dy * dy)
        const stop = e.radius + PLAYER_R + 4

        if (dist > stop) {
          e.x += (dx / dist) * e.speed
          e.y += (dy / dist) * e.speed
        } else if (dist > 0 && e.meleeCd <= 0) {
          // Dégâts de contact chronométrés
          if (_p.iFrames <= 0) {
            _p.hp -= e.atk
            sfx.damage()
            spawnParticles(_p.x, _p.y, 6, '#ef5350', 4, 15, 30)
            _p.iFrames = 35  // 35 frames d'invincibilité après un hit
            // Knockback depuis l'ennemi
            const nx = (_p.x - e.x) / dist, ny = (_p.y - e.y) / dist
            _kbx += nx * KNOCKBACK_SPD * 0.6
            _kby += ny * KNOCKBACK_SPD * 0.6
          }
          e.meleeCd = MELEE_COOLDOWN
          e.isAttacking = true
          setTimeout(() => { e.isAttacking = false }, 220)
          if (_p.hp <= 0) { triggerDefeat(); return }
        }

        // Séparation entre ennemis (éviter superposition)
        for (const o of _enemies) {
          if (o === e || o.isDead) continue
          const ex   = e.x - o.x, ey = e.y - o.y
          const ed   = Math.sqrt(ex * ex + ey * ey)
          const minS = e.radius + o.radius
          if (ed < minS && ed > 0) {
            const nx   = ex / ed, ny = ey / ed
            const push = (minS - ed) * 0.5
            e.x += nx * push; e.y += ny * push
            o.x -= nx * push; o.y -= ny * push
          }
        }

        // Clamp
        e.x = Math.max(e.radius, Math.min(cw - e.radius, e.x))
        e.y = Math.max(e.radius, Math.min(ch - e.radius, e.y))
      }
    }

    // ── UPDATE PROJECTILES ────────────────────────────────────────────────────
    function updateProjectiles() {
      for (let i = _proj.length - 1; i >= 0; i--) {
        const p = _proj[i]
        p.x += p.vx; p.y += p.vy
        p.life--
        if (p.life <= 0 || p.x < -100 || p.x > cw + 100 || p.y < -100 || p.y > ch + 100) {
          _proj.splice(i, 1); continue
        }
        // Collision joueur — on ignore si invincible
        if (_p.iFrames <= 0) {
          const dx = p.x - _p.x, dy = p.y - _p.y
          if (Math.sqrt(dx * dx + dy * dy) < PLAYER_R + p.r) {
            _p.hp -= p.dmg
            _p.iFrames = 25
            sfx.damage()
            spawnParticles(_p.x, _p.y, 8, p.isBoss ? '#ce93d8' : '#ff9800', 5, 18, 35)
            // Knockback directionnel depuis le projectile
            const d = Math.sqrt(dx * dx + dy * dy) || 1
            _kbx -= (dx / d) * KNOCKBACK_SPD
            _kby -= (dy / d) * KNOCKBACK_SPD
            _proj.splice(i, 1)
            if (_p.hp <= 0) { triggerDefeat(); return }
          }
        }
      }
    }

    // ── UPDATE ÉPÉE ───────────────────────────────────────────────────────────
    function updateSword() {
      if (!_swinging) return
      _swingFrame++
      const t = _swingFrame / SWORD_FRAMES
      // Arc attaché à l'angle courant du joueur (souris)
      _swingAngle = _p.angle + SWORD_START_OFF + (SWORD_END_OFF - SWORD_START_OFF) * t

      // On vérifie la hitbox de l'épée sur chaque frame du swing
      const tipX = _p.x + Math.cos(_swingAngle) * SWORD_LEN
      const tipY = _p.y + Math.sin(_swingAngle) * SWORD_LEN

      for (const e of _enemies) {
        if (e.isDead || _swingHit.has(e.id)) continue
        const dx = tipX - e.x, dy = tipY - e.y
        if (Math.sqrt(dx * dx + dy * dy) < e.radius + 14) {
          const rageMult  = _rageActive ? 2.0 : (rage.value >= 80 ? 1.3 : 1.0)
          const dmg       = Math.floor((14 + _diff * 3.5) * rageMult)
          e.hp           -= dmg
          _swingHit.add(e.id)   // une seule touche par ennemi par swing
          sfx.hit()
          spawnParticles(e.x, e.y, 10, '#ffd54f', 5, 12, 25)
          e.isAttacking = true
          setTimeout(() => { e.isAttacking = false }, 200)
          score.value += Math.floor(dmg * 10)
          if (e.hp <= 0) killEnemy(e)
        }
      }

      if (_swingFrame >= SWORD_FRAMES) {
        _swinging   = false
        _swingFrame = 0
        _swingAngle = SWORD_START_OFF
        _swingHit   = new Set()
      }
    }

    // ── MORT ENNEMI ───────────────────────────────────────────────────────────
    function killEnemy(e) {
      e.hp = 0; e.isDead = true; e.deadTimer = 0
      killCount.value++

      score.value += e.isBoss ? 5000 : (500 + _diff * 120)

      // Vol de vie instantané (30% HP max ennemi)
      const heal = Math.floor(e.maxHp * HEAL_RATIO)
      _p.hp = Math.min(_p.maxHp, _p.hp + heal)
      spawnParticles(e.x, e.y, 12, '#69f0ae', 5, 20, 40)
      sfx.kill()

      // Gestion du streak
      const now = Date.now()
      if (now - _lastKillTime < STREAK_WINDOW) {
        _localStreak++
      } else {
        _localStreak = 1
      }
      _lastKillTime = now

      if (_localStreak >= 2) {
        streakCount.value = _localStreak
        showStreak.value  = true
        setTimeout(() => { showStreak.value = false }, 1400)
        score.value += _localStreak * 150
      }

      // Rage
      const prevRage = rage.value
      rage.value = Math.min(100, rage.value + RAGE_PER_KILL)

      // Déclenchement du Rage Mode à 100
      if (rage.value >= 100 && prevRage < 100 && !_rageActive) {
        _rageActive   = true
        _rageDuration = 300   // 300 frames (~5s)
        sfx.rage()
        spawnParticles(_p.x, _p.y, 20, '#ff6f00', 8, 25, 50)
      }

      // Mise à jour HUD boss
      if (e.isBoss) {
        bossHp.value = 0
      } else {
        // Vérifier si le boss existe et mettre à jour son HP
        const boss = _enemies.find(x => x.isBoss && !x.isDead)
        if (boss) bossHp.value = boss.hp
      }

      const aliveCount = _enemies.filter(x => !x.isDead).length
      if (aliveCount === 0) {
        if (e.isBoss) {
          spawnParticles(cw / 2, ch / 2, 40, '#ffd700', 10, 30, 60)
          setTimeout(() => triggerVictory(), 800)
        } else {
          setTimeout(() => {
            _enemies = []
            _proj    = []
            spawnWave(_waveIdx + 1)
          }, 1400)
        }
      }
    }

    // ── VICTOIRE / DÉFAITE ────────────────────────────────────────────────────
    function triggerVictory() {
      stopFight()
      sfx.victory()
      const elapsed = Math.floor((Date.now() - battleStartMs) / 1000)
      const scoreBonus   = Math.min(0.50, score.value / 2000)
      const speedBonus   = elapsed < 120 ? 0.20 : 0.0
      const goldMult     = 1.0 + scoreBonus + speedBonus
      const goldPct      = 0.40 + (_diff * 0.04)
      const baseGold     = selectedKingdom.value
        ? Math.floor(selectedKingdom.value.gold_capacity * goldPct)
        : 1000
      finalRewards.value = {
        gold:      Math.floor(baseGold * goldMult),
        wood:      selectedKingdom.value ? Math.floor(selectedKingdom.value.level * 80 * (1 + scoreBonus)) : 200,
        food:      selectedKingdom.value ? Math.floor(selectedKingdom.value.level * 60 * (1 + scoreBonus)) : 150,
        trophies:  _diff * 30 + Math.floor(score.value / 100),
        xp:        _diff * 150 + killCount.value * 10,
        chestType: _diff >= 5 ? 'magical' : _diff >= 3 ? 'gold' : 'silver',
      }
      phase.value = 'victory'
    }

    function triggerDefeat() {
      stopFight()
      sfx.defeat()
      phase.value = 'defeat'
    }

    function stopFight() {
      clearShootTimers()
      if (rafId)         { cancelAnimationFrame(rafId); rafId = null }
      if (timerInterval) { clearInterval(timerInterval); timerInterval = null }
      window.removeEventListener('resize',  resizeCanvas)
      window.removeEventListener('keydown', onKeyDown)
      window.removeEventListener('keyup',   onKeyUp)
    }

    function retryFight() {
      conquestDone.value = false
      conquesting.value  = false
      startFight()
    }

    // ── CONQUÊTE API ──────────────────────────────────────────────────────────
    async function doConquest() {
      if (!selectedKingdom.value || conquesting.value || conquestDone.value) return
      conquesting.value = true
      try {
        const elapsed = Math.floor((Date.now() - battleStartMs) / 1000)
        const res = await conquestConquer({
          kingdom_id: selectedKingdom.value.id,
          score:      score.value,
          kills:      killCount.value,
          time_sec:   Math.max(1, elapsed),
        })
        if (res.data.success) {
          conquestDone.value = true
          // Supprimer le royaume conquis de la liste du lobby
          conquestKingdoms.value = conquestKingdoms.value.filter(
            k => k.id !== selectedKingdom.value.id
          )
        }
      } catch (e) {
        console.error('Erreur conquête:', e)
      } finally {
        conquesting.value = false
      }
    }

    // ── HUD SYNC ──────────────────────────────────────────────────────────────
    function syncHud() {
      if (!_p) return
      playerHp.value = Math.max(0, Math.ceil(_p.hp))
      // Sync HP du boss
      const boss = _enemies.find(x => x.isBoss && !x.isDead)
      if (boss) bossHp.value = Math.max(0, boss.hp)
    }

    // ── INPUTS ────────────────────────────────────────────────────────────────
    function onKeyDown(e) {
      _keys[e.key.toLowerCase()] = true
      if (e.key === 'Escape' && phase.value === 'fighting') triggerDefeat()
      // Empêcher le scroll de page avec les touches fléchées / espace
      if (['arrowup','arrowdown','arrowleft','arrowright',' '].includes(e.key.toLowerCase())) {
        e.preventDefault()
      }
    }
    function onKeyUp(e) { _keys[e.key.toLowerCase()] = false }

    function onMouseMove(e) {
      if (!canvas) return
      const rect = canvas.getBoundingClientRect()
      _mx = e.clientX - rect.left
      _my = e.clientY - rect.top
    }
    function onMouseDown(e) {
      if (e.button === 0 && !_swinging) {
        _swinging   = true
        _swingFrame = 0
        _swingHit   = new Set()
        sfx.swing()
      }
    }
    function onMouseUp() {}

    // ══════════════════════════════════════════════════════════════════════════
    // RENDU CANVAS — Production
    // ══════════════════════════════════════════════════════════════════════════

    // Étoiles fixes (position déterministe, calculée une seule fois)
    const _stars = Array.from({ length: 60 }, (_, i) => ({
      x:  (i * 173.1 + 11) % 1,   // fraction 0-1, multipliée par cw au rendu
      y:  (i * 97.7  + 7)  % 0.7, // fraction 0-0.7, multipliée par ch
      r:  (i % 3 === 0) ? 1.5 : (i % 2 === 0) ? 1.0 : 0.7,
      a:  0.4 + (i % 4) * 0.15,
    }))

    let _bgLayers = null

    function getBgLayers() {
      if (!_bgLayers) {
        _bgLayers = {
          // Décors générés une seule fois pour les arbres/pierres
          trees: Array.from({ length: 8 }, (_, i) => ({
            x: 0.3 + (i * 0.08),  // fraction de cw
            y: 0.65 + (i % 3) * 0.05,
            h: 40 + (i % 3) * 20,
          })),
          torches: [
            { x: 0.3, side: 'center' },
            { x: 0.7, side: 'center' },
          ],
        }
      }
      return _bgLayers
    }

    function drawBackground() {
      // ── Ciel dégradé nocturne ──────────────────────────────────────────────
      const sky = ctx.createLinearGradient(0, 0, 0, ch)
      sky.addColorStop(0,    '#04060d')
      sky.addColorStop(0.45, '#0d1b2a')
      sky.addColorStop(0.75, '#16213e')
      sky.addColorStop(1,    '#1a2f1a')
      ctx.fillStyle = sky
      ctx.fillRect(0, 0, cw, ch)

      // ── Étoiles ───────────────────────────────────────────────────────────
      for (const s of _stars) {
        const twinkle = 0.6 + Math.sin(_frame * 0.04 + s.x * 20) * 0.4
        ctx.fillStyle = `rgba(255,255,255,${s.a * twinkle})`
        ctx.beginPath()
        ctx.arc(s.x * cw, s.y * ch, s.r, 0, Math.PI * 2)
        ctx.fill()
      }

      // ── Lune ──────────────────────────────────────────────────────────────
      const moonX = cw * 0.85, moonY = ch * 0.12
      ctx.save()
      ctx.beginPath(); ctx.arc(moonX, moonY, 34, 0, Math.PI * 2)
      ctx.fillStyle = '#fff8dc'; ctx.shadowColor = '#fffde7'
      ctx.shadowBlur = 40; ctx.fill(); ctx.restore()

      // ── Nuages de fumée de bataille (animation lente) ─────────────────────
      for (let i = 0; i < 5; i++) {
        const cx2 = ((cw * 0.15 + i * cw * 0.17) + _frame * (0.2 + i * 0.08)) % (cw + 200) - 100
        const cy2 = ch * (0.55 + Math.sin(i * 1.3 + _frame * 0.006) * 0.04)
        const r2  = 60 + i * 15
        const g   = ctx.createRadialGradient(cx2, cy2, 0, cx2, cy2, r2)
        g.addColorStop(0,   'rgba(80,100,120,0.13)')
        g.addColorStop(0.7, 'rgba(50,70,90,0.06)')
        g.addColorStop(1,   'rgba(0,0,0,0)')
        ctx.fillStyle = g
        ctx.beginPath(); ctx.ellipse(cx2, cy2, r2, r2 * 0.5, 0, 0, Math.PI * 2); ctx.fill()
      }

      // ── Sol (herbe brûlée) ─────────────────────────────────────────────────
      const ground = ctx.createLinearGradient(0, ch * 0.78, 0, ch)
      ground.addColorStop(0, '#1a3a10')
      ground.addColorStop(1, '#0d1f08')
      ctx.fillStyle = ground
      ctx.fillRect(0, ch * 0.78, cw, ch * 0.22)

      // ── Ligne de sol (bordure lumineuse) ──────────────────────────────────
      ctx.fillStyle = 'rgba(100,180,60,0.18)'
      ctx.fillRect(0, ch * 0.78, cw, 3)

      // ── Arbres en silhouette ───────────────────────────────────────────────
      const layers = getBgLayers()
      for (const t of layers.trees) {
        const tx = t.x * cw, ty = t.y * ch
        ctx.fillStyle = '#0a1a06'
        ctx.beginPath()
        ctx.moveTo(tx, ty - t.h)
        ctx.lineTo(tx - 16, ty)
        ctx.lineTo(tx + 16, ty)
        ctx.closePath(); ctx.fill()
        ctx.fillStyle = '#0d2209'
        ctx.fillRect(tx - 5, ty, 10, 18)
      }

      // ── Ligne de démarcation centrale (champ de bataille) ─────────────────
      ctx.save()
      ctx.setLineDash([18, 12])
      ctx.strokeStyle = 'rgba(255,255,255,0.07)'
      ctx.lineWidth   = 1.5
      ctx.beginPath(); ctx.moveTo(cw / 2, 0); ctx.lineTo(cw / 2, ch); ctx.stroke()
      ctx.restore()

      // ── Château joueur (gauche) ────────────────────────────────────────────
      drawCastle(70, ch * 0.78, '#1565c0', '🏰', false)
      // ── Forteresse ennemie (droite) ────────────────────────────────────────
      drawCastle(cw - 70, ch * 0.78, '#b71c1c', '🏯', true)

      // ── Torches animées sur les côtés ─────────────────────────────────────
      drawTorch(120, ch * 0.78 - 20)
      drawTorch(cw - 120, ch * 0.78 - 20)

      // ── Vignette sombre sur les bords ─────────────────────────────────────
      const vig = ctx.createRadialGradient(cw / 2, ch / 2, ch * 0.25, cw / 2, ch / 2, ch * 0.8)
      vig.addColorStop(0,   'rgba(0,0,0,0)')
      vig.addColorStop(0.7, 'rgba(0,0,0,0)')
      vig.addColorStop(1,   'rgba(0,0,0,0.45)')
      ctx.fillStyle = vig
      ctx.fillRect(0, 0, cw, ch)
    }

    function drawCastle(x, groundY, color, icon, mirror) {
      const w = 60, h = 110, battleY = groundY - h
      ctx.save()
      if (mirror) { ctx.translate(x, 0); ctx.scale(-1, 1); x = 0 }
      // Corps du château
      ctx.fillStyle = color + '55'
      ctx.fillRect(x - w / 2, battleY, w, h)
      // Tour gauche
      ctx.fillStyle = color + '77'
      ctx.fillRect(x - w / 2 - 14, battleY - 22, 20, h + 22)
      // Crénelage
      ctx.fillStyle = color + '99'
      for (let i = 0; i < 4; i++) {
        ctx.fillRect(x - w / 2 - 14 + i * 7, battleY - 38, 5, 16)
      }
      // Porte
      ctx.fillStyle = 'rgba(0,0,0,0.6)'
      ctx.beginPath()
      ctx.arc(x, groundY - 22, 16, Math.PI, 0)
      ctx.lineTo(x + 16, groundY); ctx.lineTo(x - 16, groundY)
      ctx.closePath(); ctx.fill()
      // Drapeau
      ctx.font = '28px serif'
      ctx.textAlign = 'center'; ctx.textBaseline = 'alphabetic'
      ctx.fillText(icon, x, battleY - 28)
      ctx.restore()
    }

    function drawTorch(x, y) {
      const flicker = 0.8 + Math.sin(_frame * 0.18 + x) * 0.2
      ctx.save()
      ctx.fillStyle = '#5d4037'
      ctx.fillRect(x - 3, y, 6, 18)
      const g = ctx.createRadialGradient(x, y, 0, x, y, 28 * flicker)
      g.addColorStop(0,   'rgba(255,230,100,0.9)')
      g.addColorStop(0.3, 'rgba(255,140,0,0.5)')
      g.addColorStop(1,   'rgba(255,70,0,0)')
      ctx.fillStyle = g
      ctx.beginPath(); ctx.arc(x, y, 28 * flicker, 0, Math.PI * 2); ctx.fill()
      ctx.restore()
    }

    function drawBar(x, y, w, h, pct, color, label) {
      ctx.fillStyle = 'rgba(0,0,0,0.80)'
      ctx.beginPath()
      ctx.roundRect(x - 1, y - 1, w + 2, h + 2, 4)
      ctx.fill()

      ctx.fillStyle = '#1a0000'
      ctx.beginPath()
      ctx.roundRect(x, y, w, h, 3)
      ctx.fill()

      const p = Math.max(0, pct)
      ctx.fillStyle = color
      ctx.beginPath()
      ctx.roundRect(x, y, w * p, h, 3)
      ctx.fill()

      if (label) {
        ctx.fillStyle = '#fff'
        ctx.font = `bold ${Math.max(8, h - 2)}px "Segoe UI", Arial`
        ctx.textAlign = 'center'; ctx.textBaseline = 'middle'
        ctx.fillText(label, x + w / 2, y + h / 2)
      }
    }

    function drawPlayer() {
      if (!_p) return
      const SIZE = PLAYER_R * 2

      // ── Ombre au sol ───────────────────────────────────────────────────────
      ctx.fillStyle = 'rgba(0,0,0,0.25)'
      ctx.beginPath()
      ctx.ellipse(_p.x, _p.y + PLAYER_R - 4, PLAYER_R * 0.7, 8, 0, 0, Math.PI * 2)
      ctx.fill()

      // ── Aura (invincibilité / rage) ────────────────────────────────────────
      if (_rageActive) {
        const pulse = 1 + Math.sin(_frame * 0.25) * 0.15
        ctx.save()
        ctx.strokeStyle = '#ff6f00'
        ctx.lineWidth   = 4 * pulse
        ctx.shadowColor = '#ff4500'
        ctx.shadowBlur  = 22 * pulse
        ctx.beginPath(); ctx.arc(_p.x, _p.y, PLAYER_R + 7, 0, Math.PI * 2); ctx.stroke()
        ctx.restore()
      } else if (_p.iFrames > 0 && Math.floor(_frame / 4) % 2 === 0) {
        ctx.save()
        ctx.strokeStyle = '#42a5f5'
        ctx.lineWidth   = 2; ctx.shadowColor = '#42a5f5'; ctx.shadowBlur = 12
        ctx.beginPath(); ctx.arc(_p.x, _p.y, PLAYER_R + 4, 0, Math.PI * 2); ctx.stroke()
        ctx.restore()
      }

      // ── Sprite ou fallback ─────────────────────────────────────────────────
      ctx.save()
      ctx.translate(_p.x, _p.y)
      // Flip horizontal si le joueur regarde à gauche
      if (_p.angle > Math.PI / 2 || _p.angle < -Math.PI / 2) ctx.scale(-1, 1)
      // Clignotement pendant invincibilité
      if (_p.iFrames > 0) ctx.globalAlpha = Math.floor(_frame / 3) % 2 === 0 ? 0.5 : 1.0

      const key = _swinging ? 'playerAttack' : 'playerIdle'
      const img = _imgs[key]
      if (img && img.complete && img.naturalWidth > 0) {
        ctx.drawImage(img, -PLAYER_R, -PLAYER_R, SIZE, SIZE)
      } else {
        // Fallback : guerrier stylisé en Canvas
        const bodyGrad = ctx.createRadialGradient(-5, -5, 0, 0, 0, PLAYER_R)
        bodyGrad.addColorStop(0, '#1e88e5')
        bodyGrad.addColorStop(1, '#0d47a1')
        ctx.fillStyle = bodyGrad
        ctx.beginPath(); ctx.arc(0, 0, PLAYER_R, 0, Math.PI * 2); ctx.fill()
        // Casque
        ctx.fillStyle = '#90caf9'
        ctx.beginPath(); ctx.arc(0, -PLAYER_R * 0.3, PLAYER_R * 0.55, Math.PI, 0); ctx.fill()
        // Visage
        ctx.fillStyle = '#ffcc80'
        ctx.beginPath(); ctx.arc(0, -2, PLAYER_R * 0.38, 0, Math.PI * 2); ctx.fill()
        // Contour
        ctx.strokeStyle = '#1565c0'; ctx.lineWidth = 2
        ctx.beginPath(); ctx.arc(0, 0, PLAYER_R, 0, Math.PI * 2); ctx.stroke()
      }
      ctx.restore()

      // ── Barre de vie au-dessus ─────────────────────────────────────────────
      const bw    = 72
      const hpR   = _p.hp / _p.maxHp
      const hpCol = hpR > 0.5 ? '#4caf50' : hpR > 0.25 ? '#ff9800' : '#f44336'
      drawBar(_p.x - bw / 2, _p.y - PLAYER_R - 20, bw, 11, hpR, hpCol)
    }

    function drawSword() {
      if (!_p) return

      // Trail de l'épée (arc multi-points)
      if (_swinging) {
        const trailCount = 6
        for (let i = 0; i < trailCount; i++) {
          const t    = (i / trailCount)
          const ang  = _p.angle + SWORD_START_OFF + (SWORD_END_OFF - SWORD_START_OFF) * (_swingFrame / SWORD_FRAMES - t * 0.15)
          const tipX = _p.x + Math.cos(ang) * SWORD_LEN
          const tipY = _p.y + Math.sin(ang) * SWORD_LEN
          const basX = _p.x + Math.cos(ang) * PLAYER_R
          const basY = _p.y + Math.sin(ang) * PLAYER_R
          const alpha = (1 - t) * 0.55
          ctx.save()
          ctx.strokeStyle = `rgba(255,213,79,${alpha})`
          ctx.lineWidth   = 5 * (1 - t * 0.5)
          ctx.lineCap     = 'round'
          ctx.shadowColor = '#ff6f00'; ctx.shadowBlur = 8
          ctx.beginPath(); ctx.moveTo(basX, basY); ctx.lineTo(tipX, tipY); ctx.stroke()
          ctx.restore()
        }
      }

      // Lame principale
      ctx.save()
      ctx.translate(_p.x, _p.y)
      const angle = _swinging ? _swingAngle : _p.angle + SWORD_START_OFF * 0.3
      ctx.rotate(angle)

      const swordGrad = ctx.createLinearGradient(PLAYER_R - 2, 0, PLAYER_R - 2 + SWORD_LEN, 0)
      if (_swinging) {
        swordGrad.addColorStop(0, '#ffd54f')
        swordGrad.addColorStop(0.6, '#ff9800')
        swordGrad.addColorStop(1, 'rgba(255,152,0,0.2)')
      } else {
        swordGrad.addColorStop(0, '#b0bec5')
        swordGrad.addColorStop(1, '#546e7a')
      }

      ctx.strokeStyle = swordGrad
      ctx.lineWidth   = _swinging ? 8 : 6
      ctx.lineCap     = 'round'
      ctx.shadowColor = _swinging ? '#ff6f00' : 'transparent'
      ctx.shadowBlur  = _swinging ? 18 : 0
      ctx.beginPath()
      ctx.moveTo(PLAYER_R - 2, 0)
      ctx.lineTo(PLAYER_R - 2 + SWORD_LEN, 0)
      ctx.stroke()

      // Garde transversale
      ctx.strokeStyle = _swinging ? '#ffa726' : '#78909c'
      ctx.lineWidth   = 6; ctx.shadowBlur = 0
      ctx.beginPath()
      ctx.moveTo(PLAYER_R + 8, -14)
      ctx.lineTo(PLAYER_R + 8, 14)
      ctx.stroke()

      // Pommeau
      ctx.fillStyle = _swinging ? '#ffe082' : '#90a4ae'
      ctx.beginPath(); ctx.arc(PLAYER_R - 2, 0, 5, 0, Math.PI * 2); ctx.fill()

      ctx.restore()
    }

    function drawEnemy(e) {
      if (e.isDead && e.deadTimer > 55) return

      const key = e.isDead
        ? (e.isBoss ? 'bossDead'   : 'enemyDead')
        : (e.isAttacking
            ? (e.isBoss ? 'bossAttack' : 'enemyAttack')
            : (e.isBoss ? 'bossIdle'   : 'enemyIdle'))
      const r    = e.radius
      const SIZE = r * 2

      // Ombre
      ctx.fillStyle = 'rgba(0,0,0,0.30)'
      ctx.beginPath()
      ctx.ellipse(e.x, e.y + r - 5, r * 0.65, 7, 0, 0, Math.PI * 2)
      ctx.fill()

      // Aura boss (phase 2/3)
      if (e.isBoss && !e.isDead && e.phase >= 2) {
        const pulse = 1 + Math.sin(_frame * 0.18) * 0.2
        ctx.save()
        ctx.strokeStyle = e.phase >= 3 ? '#ff1744' : '#ff6f00'
        ctx.lineWidth   = 4 * pulse
        ctx.shadowColor = ctx.strokeStyle; ctx.shadowBlur = 20
        ctx.beginPath(); ctx.arc(e.x, e.y, r + 10, 0, Math.PI * 2); ctx.stroke()
        ctx.restore()
      }

      ctx.save()
      ctx.translate(e.x, e.y)
      ctx.scale(-1, 1)  // miroir → fait face au joueur venant de gauche

      if (e.isDead) ctx.globalAlpha = Math.max(0, 1 - e.deadTimer / 55)

      const img = _imgs[key]
      if (img && img.complete && img.naturalWidth > 0) {
        ctx.drawImage(img, -r, -r, SIZE, SIZE)
      } else {
        // Fallback : sorcier / boss stylisé
        if (e.isBoss) {
          const g = ctx.createRadialGradient(-6, -6, 0, 0, 0, r)
          g.addColorStop(0, '#ef5350')
          g.addColorStop(1, '#7b0000')
          ctx.fillStyle = g
          ctx.beginPath(); ctx.arc(0, 0, r, 0, Math.PI * 2); ctx.fill()
          // Couronne
          ctx.fillStyle = '#ffd700'
          ctx.font = `${r * 0.75}px serif`
          ctx.textAlign = 'center'; ctx.textBaseline = 'middle'
          ctx.fillText('👑', 0, 0)
        } else {
          const g = ctx.createRadialGradient(-4, -4, 0, 0, 0, r)
          const colors = ['#7b1fa2','#6a1b9a','#9c27b0']
          g.addColorStop(0, colors[0]); g.addColorStop(1, colors[2])
          ctx.fillStyle = g
          ctx.beginPath(); ctx.arc(0, 0, r, 0, Math.PI * 2); ctx.fill()
          ctx.fillStyle = '#e1bee7'
          ctx.font = `${r * 0.75}px serif`
          ctx.textAlign = 'center'; ctx.textBaseline = 'middle'
          ctx.fillText(e.isAttacking ? '🪄' : '🧙', 0, 0)
        }
        // Contour
        ctx.strokeStyle = e.isBoss ? '#ff1744' : '#ce93d8'
        ctx.lineWidth = 2.5
        ctx.beginPath(); ctx.arc(0, 0, r, 0, Math.PI * 2); ctx.stroke()
      }
      ctx.restore()

      // Barres de vie
      if (!e.isDead) {
        const bw    = e.isBoss ? 120 : 80
        const bhCol = e.hp / e.maxHp > 0.5 ? '#4caf50' : e.hp / e.maxHp > 0.25 ? '#ff9800' : '#f44336'
        drawBar(e.x - bw / 2, e.y - r - 20, bw, e.isBoss ? 14 : 10, e.hp / e.maxHp, bhCol)
        if (e.isBoss) {
          ctx.fillStyle = '#ff8a65'
          ctx.font = 'bold 11px "Segoe UI", Arial'
          ctx.textAlign = 'center'; ctx.textBaseline = 'alphabetic'
          ctx.fillText('👑 ROI', e.x, e.y - r - 24)
        }
      }
    }

    function drawProjectiles() {
      for (const p of _proj) {
        ctx.save()
        ctx.translate(p.x, p.y)
        const g = ctx.createRadialGradient(0, 0, 0, 0, 0, p.r)
        if (p.isBoss) {
          g.addColorStop(0, '#ffffff')
          g.addColorStop(0.35, '#ce93d8')
          g.addColorStop(1,   'rgba(156,39,176,0)')
          ctx.shadowColor = '#ce93d8'; ctx.shadowBlur = 14
        } else {
          g.addColorStop(0, '#fff176')
          g.addColorStop(0.35, '#ff9800')
          g.addColorStop(1,   'rgba(244,67,54,0)')
          ctx.shadowColor = '#ff6f00'; ctx.shadowBlur = 10
        }
        ctx.fillStyle = g
        ctx.beginPath(); ctx.arc(0, 0, p.r, 0, Math.PI * 2); ctx.fill()
        ctx.restore()
      }
    }

    // ── LIFECYCLE ─────────────────────────────────────────────────────────────
    onMounted(() => { loadLobby() })

    onUnmounted(() => {
      stopFight()
      window.removeEventListener('resize',  resizeCanvas)
      window.removeEventListener('keydown', onKeyDown)
      window.removeEventListener('keyup',   onKeyUp)
    })

    return {
      rootRef, canvasRef, phase,
      lobbyLoading, conquestKingdoms, selectedKingdom,
      playerHp, playerMaxHp, playerName,
      score, killCount, rage, ragePct,
      currentWave, totalWaves, waveLabel,
      bossVisible, bossHp, bossMaxHp, bossHpPct,
      showStreak, streakCount,
      timerStr,
      finalRewards, conquesting, conquestDone,
      diffIcon, diffLabel, hpPct, hpColor,
      chestEmoji, chestLabel, bannerStyle,
      lobbySparkStyle, victoryParticleStyle,
      selectKingdom, doConquest, retryFight,
      onMouseMove, onMouseDown, onMouseUp,
    }
  },
}
</script>

<style scoped>
/* ═══════════════════════════════════════════════════════════════════════
   IMPORTS & VARIABLES
════════════════════════════════════════════════════════════════════════ */
@import url('https://fonts.googleapis.com/css2?family=Cinzel:wght@700;900&family=Inter:wght@400;600;700;900&display=swap');

:root {
  --gold: #ffd700;
  --rage: #ff6f00;
  --hp-green: #4caf50;
  --hp-orange: #ff9800;
  --hp-red: #f44336;
}

/* ═══════════════════════════════════════════════════════════════════════
   RACINE
════════════════════════════════════════════════════════════════════════ */
.war-root {
  width: 100vw; height: 100vh; overflow: hidden;
  background: #04060d;
  display: flex; align-items: center; justify-content: center;
  font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
  user-select: none;
  -webkit-user-select: none;
}

/* ═══════════════════════════════════════════════════════════════════════
   LOADER
════════════════════════════════════════════════════════════════════════ */
.loader-ring {
  display: inline-block; width: 24px; height: 24px;
  border: 3px solid rgba(255,255,255,.15);
  border-top-color: #ffd700;
  border-radius: 50%;
  animation: spin 0.75s linear infinite;
}
.loader-ring--sm { width: 16px; height: 16px; border-width: 2px; }
@keyframes spin { to { transform: rotate(360deg); } }

/* ═══════════════════════════════════════════════════════════════════════
   LOBBY
════════════════════════════════════════════════════════════════════════ */
.lobby-screen {
  position: relative;
  display: flex; flex-direction: column; align-items: center;
  gap: 28px; padding: 40px 20px 60px;
  overflow-y: auto; max-height: 100vh; width: 100%;
  background: radial-gradient(ellipse at 50% 0%, #0d1a30 0%, #04060d 70%);
}

/* Particules décoratives du lobby */
.lobby-bg-particles { position: fixed; inset: 0; pointer-events: none; overflow: hidden; z-index: 0; }
.lbg-spark {
  position: absolute;
  width: 3px; height: 3px;
  border-radius: 50%;
  background: #ffd700;
  opacity: 0;
  animation: sparkFloat linear infinite;
}
@keyframes sparkFloat {
  0%   { opacity: 0; transform: translateY(0) scale(0.5); }
  20%  { opacity: 0.8; }
  80%  { opacity: 0.4; }
  100% { opacity: 0; transform: translateY(-80px) scale(1.5); }
}

.lobby-header { position: relative; z-index: 1; text-align: center; }
.lobby-crown  {
  font-size: clamp(3rem, 6vw, 5rem);
  display: block; margin-bottom: 8px;
  filter: drop-shadow(0 0 20px #ffd70088);
  animation: crownBob 2.5s ease-in-out infinite;
}
@keyframes crownBob {
  0%, 100% { transform: translateY(0); }
  50%       { transform: translateY(-8px); }
}
.lobby-title {
  font-family: 'Cinzel', serif;
  font-size: clamp(1.8rem, 4vw, 3.4rem);
  font-weight: 900; color: #fff; margin: 0;
  text-shadow: 0 0 40px rgba(255,200,0,.45), 0 4px 12px rgba(0,0,0,.5);
  letter-spacing: 2px;
}
.lobby-sub {
  font-size: clamp(0.85rem, 1.5vw, 1rem);
  color: #78909c; margin: 10px 0 0;
  line-height: 1.6;
}
.lobby-loading {
  display: flex; align-items: center; gap: 12px;
  color: #90a4ae; font-size: 1rem; z-index: 1;
}

/* ── Grille de royaumes ──────────────────────────────────────────────── */
.kingdoms-grid {
  position: relative; z-index: 1;
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
  gap: 20px; width: 100%; max-width: 1400px;
}

.kingdom-card {
  background: rgba(255,255,255,0.04);
  border: 1.5px solid rgba(255,255,255,0.10);
  border-radius: 20px;
  overflow: hidden;
  display: flex; flex-direction: column;
  cursor: pointer;
  transition: transform .22s cubic-bezier(.34,1.56,.64,1), border-color .22s, box-shadow .22s;
  position: relative;
}
.kingdom-card:hover {
  transform: translateY(-8px) scale(1.02);
  box-shadow: 0 20px 50px rgba(0,0,0,.55);
}
.diff-1 { border-color: rgba(76,175,80,.35); }   .diff-1:hover { border-color: #4caf50; box-shadow: 0 0 30px rgba(76,175,80,.25); }
.diff-2 { border-color: rgba(255,152,0,.35); }   .diff-2:hover { border-color: #ff9800; box-shadow: 0 0 30px rgba(255,152,0,.25); }
.diff-3 { border-color: rgba(244,67,54,.35); }   .diff-3:hover { border-color: #f44336; box-shadow: 0 0 30px rgba(244,67,54,.25); }
.diff-4 { border-color: rgba(156,39,176,.35); }  .diff-4:hover { border-color: #9c27b0; box-shadow: 0 0 30px rgba(156,39,176,.25); }
.diff-5 {
  border-color: rgba(255,215,0,.5);
  background: linear-gradient(135deg, rgba(255,215,0,.07) 0%, rgba(255,152,0,.04) 100%);
}
.diff-5:hover { border-color: #ffd700; box-shadow: 0 0 40px rgba(255,215,0,.35); }

.kc-banner {
  padding: 18px 18px 12px;
  display: flex; justify-content: space-between; align-items: center;
}
.kc-icon       { font-size: 2.4rem; filter: drop-shadow(0 2px 6px rgba(0,0,0,.5)); }
.kc-diff-badge {
  font-size: 0.68rem; font-weight: 800; text-transform: uppercase;
  letter-spacing: 1.5px; color: rgba(255,255,255,.7);
  background: rgba(0,0,0,.35); border-radius: 999px; padding: 3px 10px;
}
.kc-body        { padding: 0 18px 18px; display: flex; flex-direction: column; gap: 9px; }
.kc-name        { font-family: 'Cinzel', serif; font-size: 1.1rem; font-weight: 700; color: #fff; }
.kc-level       { font-size: .78rem; color: #78909c; }
.kc-stats       { display: flex; gap: 14px; font-size: .82rem; color: #b0bec5; }
.kc-wave-preview {
  display: flex; align-items: center; gap: 5px;
}
.wave-pip {
  display: inline-block; width: 22px; height: 6px; border-radius: 999px;
  background: rgba(255,255,255,.15); border: 1px solid rgba(255,255,255,.1);
}
.boss-pip { font-size: .9rem; margin-left: 4px; opacity: 0.65; }
.kc-rewards    { display: flex; gap: 8px; font-size: .78rem; color: #ffd54f; flex-wrap: wrap; }
.kc-btn {
  margin-top: 6px; padding: 11px; border-radius: 14px; border: none; cursor: pointer;
  background: linear-gradient(180deg, #e53935 0%, #b71c1c 100%);
  color: #fff; font-weight: 800; font-size: .95rem; letter-spacing: .5px;
  box-shadow: 0 5px 0 #7f0000, inset 0 1px 0 rgba(255,255,255,.15);
  transition: transform .1s, box-shadow .1s;
}
.kc-btn:hover  { background: linear-gradient(180deg, #ef5350, #c62828); }
.kc-btn:active { transform: translateY(4px); box-shadow: 0 1px 0 #7f0000; }

.btn-back {
  position: relative; z-index: 1;
  padding: 13px 34px; border-radius: 16px;
  border: 1.5px solid rgba(255,255,255,.12);
  background: rgba(255,255,255,.05); color: #78909c;
  font-size: .95rem; font-weight: 700; cursor: pointer;
  transition: background .2s, color .2s;
}
.btn-back:hover { background: rgba(255,255,255,.1); color: #fff; }

/* ═══════════════════════════════════════════════════════════════════════
   ARÈNE
════════════════════════════════════════════════════════════════════════ */
.arena-root  { position: relative; width: 100vw; height: 100vh; }
.game-canvas {
  position: absolute; inset: 0;
  width: 100%; height: 100%;
  cursor: crosshair; display: block;
}

/* ── HUD overlay ─────────────────────────────────────────────────────── */
.hud-overlay {
  position: absolute; inset: 0;
  pointer-events: none; z-index: 10;
}

/* Joueur */
.hud-player {
  position: absolute; top: 14px; left: 14px;
  display: flex; gap: 10px; align-items: center;
  background: rgba(4,6,13,0.82);
  border: 1.5px solid rgba(255,255,255,.12);
  border-radius: 16px; padding: 10px 14px; min-width: 250px;
  backdrop-filter: blur(8px);
}
.hud-avatar {
  font-size: 1.9rem;
  filter: drop-shadow(0 0 8px rgba(255,255,255,.3));
  transition: filter .3s;
}
.hud-avatar--rage { filter: drop-shadow(0 0 14px #ff6f00) drop-shadow(0 0 4px #ff4500); }
.hud-bars  { flex: 1; display: flex; flex-direction: column; gap: 6px; }
.hud-name  {
  font-size: .72rem; font-weight: 700; color: #78909c;
  text-transform: uppercase; letter-spacing: 1.2px;
}
.hp-track, .rage-track {
  position: relative; width: 100%; height: 14px;
  background: rgba(0,0,0,.55); border-radius: 999px; overflow: hidden;
  border: 1px solid rgba(255,255,255,.08);
}
.hp-fill {
  height: 100%; border-radius: 999px;
  transition: width .15s linear, background .5s;
  box-shadow: inset 0 1px 0 rgba(255,255,255,.2);
}
.rage-fill {
  height: 100%; border-radius: 999px;
  background: linear-gradient(90deg, #ff6f00, #ff3d00);
  transition: width .15s linear;
  box-shadow: inset 0 1px 0 rgba(255,255,255,.2);
}
.hp-text, .rage-text {
  position: absolute; inset: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 8px; font-weight: 800; color: #fff;
  text-shadow: 0 1px 2px rgba(0,0,0,.8);
  pointer-events: none;
}

/* Vague */
.hud-wave {
  position: absolute; top: 14px; left: 50%; transform: translateX(-50%);
  background: rgba(4,6,13,.82); border: 1.5px solid rgba(255,255,255,.12);
  border-radius: 16px; padding: 10px 22px; text-align: center; min-width: 240px;
  backdrop-filter: blur(8px);
}
.wave-label { font-size: .95rem; font-weight: 800; color: #ffd54f; }
.wave-pips  { display: flex; align-items: center; justify-content: center; gap: 6px; margin-top: 7px; }
.wpip {
  width: 28px; height: 6px; border-radius: 999px;
  background: rgba(255,255,255,.12); transition: background .3s;
}
.wpip--done   { background: #4caf50; }
.wpip--active { background: #ffd700; box-shadow: 0 0 8px #ffd700; animation: waveGlow 1s ease-in-out infinite alternate; }
.wpip-boss    { width: auto; height: auto; font-size: .9rem; opacity: .4; }
.wpip-boss--active { opacity: 1; animation: waveGlow 0.6s ease-in-out infinite alternate; }
@keyframes waveGlow { from { opacity: .7; } to { opacity: 1; } }

/* Score */
.hud-score-box {
  position: absolute; top: 14px; right: 14px;
  background: rgba(4,6,13,.82); border: 1.5px solid rgba(255,255,255,.12);
  border-radius: 16px; padding: 10px 20px; text-align: right;
  backdrop-filter: blur(8px); min-width: 130px;
}
.score-num   { font-size: 1.7rem; font-weight: 900; color: #fff; line-height: 1; }
.score-label { font-size: .65rem; color: #546e7a; text-transform: uppercase; letter-spacing: 1px; }
.kills-label { font-size: .82rem; color: #ef9a9a; margin-top: 4px; }
.timer-label { font-size: .78rem; color: #78909c; margin-top: 2px; }

/* Streak */
.streak-banner {
  position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
  display: flex; flex-direction: column; align-items: center;
  pointer-events: none;
}
.streak-num {
  font-family: 'Cinzel', serif;
  font-size: clamp(3rem, 8vw, 6rem); font-weight: 900;
  color: #ffd700;
  text-shadow: 0 0 40px rgba(255,215,0,.7), 0 4px 0 rgba(0,0,0,.5);
  line-height: 1;
}
.streak-txt {
  font-size: clamp(.9rem, 2vw, 1.4rem); font-weight: 800;
  color: #ff9800; text-transform: uppercase; letter-spacing: 3px;
  text-shadow: 0 0 20px rgba(255,152,0,.6);
}
.streak-fade-enter-active, .streak-fade-leave-active { transition: all .3s; }
.streak-fade-enter-from  { opacity: 0; transform: translate(-50%, -40%) scale(0.7); }
.streak-fade-leave-to    { opacity: 0; transform: translate(-50%, -60%) scale(1.2); }

/* Boss HP bar */
.boss-hud {
  position: absolute; bottom: 60px; left: 50%; transform: translateX(-50%);
  min-width: min(90vw, 600px); text-align: center;
}
.boss-hud-name {
  font-family: 'Cinzel', serif;
  font-size: 1.1rem; font-weight: 900; color: #ff8a65;
  text-shadow: 0 0 20px rgba(255,100,50,.5);
  margin-bottom: 7px; letter-spacing: 2px;
}
.boss-hp-track {
  position: relative; width: 100%; height: 22px;
  background: rgba(0,0,0,.75); border-radius: 999px;
  border: 2px solid rgba(255,100,50,.4); overflow: hidden;
}
.boss-hp-fill {
  height: 100%; border-radius: 999px;
  background: linear-gradient(90deg, #ff6f00, #f44336);
  transition: width .2s ease;
  box-shadow: 0 0 12px rgba(244,67,54,.5), inset 0 1px 0 rgba(255,255,255,.15);
}
.boss-hp-text {
  position: absolute; inset: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; font-weight: 800; color: #fff;
  text-shadow: 0 1px 3px rgba(0,0,0,.9);
}
.boss-bar-fade-enter-active, .boss-bar-fade-leave-active { transition: opacity .4s, transform .4s; }
.boss-bar-fade-enter-from  { opacity: 0; transform: translateX(-50%) translateY(20px); }
.boss-bar-fade-leave-to    { opacity: 0; transform: translateX(-50%) translateY(20px); }

/* Contrôles */
.hud-controls {
  position: absolute; bottom: 14px; left: 50%; transform: translateX(-50%);
  display: flex; gap: 10px; flex-wrap: wrap; justify-content: center;
}
.hud-controls span {
  background: rgba(4,6,13,.7); border: 1px solid rgba(255,255,255,.08);
  border-radius: 999px; padding: 5px 14px;
  font-size: .72rem; color: rgba(255,255,255,.4);
}

/* ═══════════════════════════════════════════════════════════════════════
   ÉCRANS DE FIN
════════════════════════════════════════════════════════════════════════ */
.end-screen {
  position: relative;
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 24px; padding: 40px 20px; text-align: center; width: 100%;
  overflow: hidden;
}
.victory-screen { background: radial-gradient(ellipse at center, #0f2a05 0%, #04060d 100%); }
.defeat-screen  { background: radial-gradient(ellipse at center, #2a0505 0%, #04060d 100%); }

/* Particules de victoire */
.victory-particles { position: absolute; inset: 0; pointer-events: none; z-index: 0; }
.vp {
  position: absolute; width: 10px; height: 10px; border-radius: 50%;
  animation: victoryPop 2.5s ease-out infinite;
}
@keyframes victoryPop {
  0%   { opacity: 0; transform: scale(0) translateY(0); }
  20%  { opacity: 1; }
  100% { opacity: 0; transform: scale(1.5) translateY(-120px); }
}

.end-title {
  position: relative; z-index: 1;
  font-family: 'Cinzel', serif;
  font-size: clamp(2.5rem, 7vw, 5.5rem); font-weight: 900; color: #ffd700;
  text-shadow: 0 0 50px rgba(255,215,0,.55), 0 6px 0 rgba(0,0,0,.4);
  animation: pulse 1.3s ease-in-out infinite;
}
.defeat-title {
  color: #f44336;
  text-shadow: 0 0 40px rgba(244,67,54,.4);
  animation: none;
}
.end-kingdom {
  position: relative; z-index: 1;
  font-family: 'Cinzel', serif;
  font-size: clamp(1.1rem, 2.5vw, 1.6rem); color: #a5d6a7; font-weight: 700;
}
.end-sub       { position: relative; z-index: 1; font-size: 1.2rem; color: #ef9a9a; }
.end-stats     {
  position: relative; z-index: 1;
  display: flex; gap: 20px; flex-wrap: wrap; justify-content: center;
  font-size: .9rem; color: #78909c;
}
.end-rewards   { position: relative; z-index: 1; display: flex; gap: 12px; flex-wrap: wrap; justify-content: center; }
.reward-item {
  background: rgba(255,255,255,.07); border: 1.5px solid rgba(255,255,255,.12);
  border-radius: 16px; padding: 12px 22px;
  font-size: 1.05rem; font-weight: 700; color: #ffd54f;
  backdrop-filter: blur(4px);
}
.gold-reward   { border-color: rgba(255,215,0,.4); background: rgba(255,215,0,.08); }
.trophy-reward { border-color: rgba(255,200,0,.4); background: rgba(255,200,0,.08); }
.xp-reward     { border-color: rgba(100,200,100,.4); background: rgba(100,200,100,.07); color: #a5d6a7; }
.chest-reward  { border-color: rgba(255,215,0,.5); background: rgba(255,215,0,.12); color: #ffd700; }
.conquest-done { position: relative; z-index: 1; color: #69f0ae; font-size: 1.1rem; font-weight: 700; }
.conquesting   { position: relative; z-index: 1; display: flex; align-items: center; gap: 10px; color: #78909c; }

.end-buttons { position: relative; z-index: 1; display: flex; gap: 16px; flex-wrap: wrap; justify-content: center; }

.btn-conquer {
  padding: 18px 48px; border-radius: 22px;
  border: 2.5px solid #145214;
  background: linear-gradient(180deg, #2e7d32 0%, #1b5e20 100%);
  color: #fff; font-family: 'Cinzel', serif;
  font-size: 1.15rem; font-weight: 900; letter-spacing: 1px; cursor: pointer;
  box-shadow: 0 7px 0 #0d3d0f, inset 0 1px 0 rgba(255,255,255,.15);
  transition: transform .12s, box-shadow .12s;
}
.btn-conquer:hover  { background: linear-gradient(180deg, #388e3c, #2e7d32); transform: translateY(-3px); }
.btn-conquer:active { transform: translateY(5px); box-shadow: 0 2px 0 #0d3d0f; }

.btn-retry {
  padding: 16px 38px; border-radius: 20px;
  border: 2.5px solid #bf360c;
  background: linear-gradient(180deg, #ff9800, #e65100);
  color: #fff; font-size: 1.05rem; font-weight: 900; cursor: pointer;
  box-shadow: 0 6px 0 #bf360c, inset 0 1px 0 rgba(255,255,255,.15);
  transition: transform .12s, box-shadow .12s;
}
.btn-retry:hover  { transform: translateY(-3px); }
.btn-retry:active { transform: translateY(4px); box-shadow: 0 2px 0 #bf360c; }

.btn-home {
  padding: 16px 38px; border-radius: 20px;
  border: 2.5px solid #0a3d91;
  background: linear-gradient(180deg, #1565c0, #0d47a1);
  color: #fff; font-size: 1.05rem; font-weight: 900; cursor: pointer;
  box-shadow: 0 6px 0 #0a3d91, inset 0 1px 0 rgba(255,255,255,.15);
  transition: transform .12s, box-shadow .12s;
}
.btn-home:hover  { transform: translateY(-3px); }
.btn-home:active { transform: translateY(4px); box-shadow: 0 2px 0 #0a3d91; }

/* ═══════════════════════════════════════════════════════════════════════
   ANIMATIONS GLOBALES
════════════════════════════════════════════════════════════════════════ */
@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50%       { transform: scale(1.04); }
}
</style>
