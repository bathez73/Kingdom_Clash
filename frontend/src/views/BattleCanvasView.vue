<template>
  <div class="battle-root">

    <!-- ===================== ÉCRAN DE DÉMARRAGE ===================== -->
    <div v-if="phase === 'start'" class="overlay-screen">
      <h1 class="title-text">⚔️ Kingdom Clash 3v3 ⚔️</h1>
      <p class="subtitle-text">Votre équipe contre la couronne ennemie !</p>
      <div class="team-preview">
        <div class="team-box ally">
          <p class="team-label">Votre équipe</p>
          <div v-for="(p, i) in playerTeam" :key="'p'+i" class="unit-badge">
            <span>⚔️</span>
            <span class="badge-hp">{{ p.hp }}/{{ p.maxHp }}</span>
          </div>
        </div>
        <div class="vs-label">VS</div>
        <div class="team-box enemy">
          <p class="team-label">Équipe ennemie</p>
          <div v-for="(e, i) in enemyTeam" :key="'e'+i" class="unit-badge">
            <span>{{ e.isBoss ? '👑' : '🧙' }}</span>
            <span class="badge-hp">{{ e.hp }}/{{ e.maxHp }}</span>
          </div>
        </div>
      </div>
      <button @click="startGame" class="btn-start">🎯 Commencer la Bataille</button>
    </div>

    <!-- ===================== ARÈNE CANVAS ===================== -->
    <div v-if="phase === 'fighting'" class="arena-wrapper">
      <!-- HUD supérieur -->
      <div class="hud-top">
        <div class="hud-team">
          <span class="hud-label">Votre équipe</span>
          <div class="hud-icons">
            <span v-for="(p, i) in playerTeam" :key="'hi'+i"
                  class="hud-icon" :class="p.hp > 0 ? 'alive' : 'dead'">
              {{ p.hp > 0 ? '⚔️' : '💀' }}
            </span>
          </div>
        </div>
        <div class="hud-score">{{ killsPlayer }} — {{ killsEnemy }}</div>
        <div class="hud-team right">
          <span class="hud-label">Équipe ennemie</span>
          <div class="hud-icons">
            <span v-for="(e, i) in enemyTeam" :key="'hi2'+i"
                  class="hud-icon" :class="e.hp > 0 ? 'alive' : 'dead'">
              {{ e.hp > 0 ? (e.isBoss ? '👑' : '🧙') : '⚰️' }}
            </span>
          </div>
        </div>
      </div>

      <!-- Canvas principal -->
      <canvas
        ref="canvasRef"
        width="800"
        height="500"
        class="game-canvas"
        @mousemove="onMouseMove"
        @click="onCanvasClick"
      ></canvas>

      <!-- Aide en bas -->
      <div class="hud-bottom">
        <span class="hint">🖱️ Déplacez la souris pour bouger</span>
        <span class="hint">🖱️ Clic gauche → Attaque à l'épée</span>
        <span class="hint">🔥 Esquivez les sorts ennemis !</span>
      </div>
    </div>

    <!-- ===================== ÉCRAN DE FIN ===================== -->
    <div v-if="phase === 'end'" class="overlay-screen">
      <h1 v-if="victory" class="title-text victory">🎉 Victoire ! 🎉</h1>
      <h1 v-else class="title-text defeat">💀 Défaite 💀</h1>
      <p class="subtitle-text">
        {{ victory ? 'Tu as écrasé le roi et son armée !' : 'Ton équipe a été anéantie...' }}
      </p>
      <div class="score-card">Score final : {{ killsPlayer }} — {{ killsEnemy }}</div>
      <div class="end-buttons">
        <button v-if="victory" @click="conquer" :disabled="conquering" class="btn-conquer">
          {{ conquering ? '⏳ Conquête...' : '🏰 Conquérir le Royaume' }}
        </button>
        <button @click="goHome" class="btn-home">🏠 Retour au Royaume</button>
      </div>
    </div>

  </div>
</template>

<script>
import { ref, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { conquerKingdom as conquerKingdomApi } from '../services/api'

// ─── Import des sprites ──────────────────────────────────────────────────────
import dragonKnightIdle   from '../assets/Dragon Knight/0-stop/0 (1).gif'
import dragonKnightAttack from '../assets/Dragon Knight/3-attack/3 (1).gif'
import dragonKnightDead   from '../assets/Dragon Knight/6-dead/6 (1).gif'
import arcaneWizardIdle   from '../assets/Arcane Wizard/0-stop/0 (1).gif'
import arcaneWizardAttack from '../assets/Arcane Wizard/1-attack/1 (1).gif'
import arcaneWizardDead   from '../assets/Arcane Wizard/5-dead/5 (1).gif'
import shadowLordIdle     from '../assets/Shadow Lord/0-stop/0 (1).gif'
import shadowLordAttack   from '../assets/Shadow Lord/1-attack/1 (1).gif'
import shadowLordDead     from '../assets/Shadow Lord/3-dead/3 (1).gif'

// ─── Constantes physiques ────────────────────────────────────────────────────
const CW             = 800          // canvas width
const CH             = 500          // canvas height
const PLAYER_RADIUS  = 30           // demi-largeur sprite joueur
const ENEMY_RADIUS   = 35           // demi-largeur sprite ennemi
const BOSS_RADIUS    = 42
const SWORD_LENGTH   = 72           // longueur de la lame
const SWORD_WIDTH    = 8
const SWING_FRAMES   = 12           // durée de l'animation d'épée
const SWING_START    = -Math.PI / 4 // -45°
const SWING_END      =  Math.PI / 4 // +45°
const PROJ_RADIUS    = 12
const PROJ_SPEED     = 4.5
const ENEMY_SHOOT_MS = 2000         // intervalle de tir en ms
const PLAYER_FLOOR   = CH - 60      // ligne de sol joueur
const ENEMY_X        = 660          // position X fixe de l'ennemi
const ENEMY_Y        = CH / 2       // position Y fixe de l'ennemi

export default {
  name: 'BattleCanvasView',
  setup() {
    const router   = useRouter()
    const canvasRef = ref(null)

    // ── Phase du jeu : 'start' | 'fighting' | 'end' ──────────────────────────
    const phase     = ref('start')
    const victory   = ref(false)
    const conquering = ref(false)

    // ── Compteurs de kills ────────────────────────────────────────────────────
    const killsPlayer = ref(0)
    const killsEnemy  = ref(0)

    // ── ─── 1. STRUCTURES DE DONNÉES (State Management) ──────────────────────
    //
    // playerTeam : 3 soldats alliés
    // enemyTeam  : 2 soldats + 1 Boss en dernière position
    // Chaque objet contient la position, les stats et l'état d'animation.

    const playerTeam = ref([
      { hp: 100, maxHp: 100, atk: 15, x: 80,  y: PLAYER_FLOOR, alive: true },
      { hp: 100, maxHp: 100, atk: 15, x: -200, y: PLAYER_FLOOR, alive: true }, // en attente
      { hp: 100, maxHp: 100, atk: 15, x: -200, y: PLAYER_FLOOR, alive: true }, // en attente
    ])

    const enemyTeam = ref([
      { hp: 60,  maxHp: 60,  atk: 10, x: ENEMY_X, y: ENEMY_Y, alive: true, isBoss: false },
      { hp: 60,  maxHp: 60,  atk: 10, x: ENEMY_X, y: ENEMY_Y, alive: true, isBoss: false },
      { hp: 250, maxHp: 250, atk: 20, x: ENEMY_X, y: ENEMY_Y, alive: true, isBoss: true  },
    ])

    // Références vers les combattants actifs
    const activePlayer = ref(null)
    const activeEnemy  = ref(null)

    // Tableau des projectiles : { x, y, vx, vy, radius, damage }
    const projectiles  = ref([])

    // ── ─── État de l'épée ────────────────────────────────────────────────────
    let isSwinging    = false
    let swingFrame    = 0
    let swordAngle    = SWING_START
    let damageDealt   = false   // un seul impact par swing

    // ── ─── Curseur souris ────────────────────────────────────────────────────
    let mouseX = 80
    let mouseY = PLAYER_FLOOR

    // ── ─── Images (préchargées une seule fois) ───────────────────────────────
    const imgPlayerIdle   = new Image()
    const imgPlayerAttack = new Image()
    const imgPlayerDead   = new Image()
    const imgEnemyIdle    = new Image()
    const imgEnemyAttack  = new Image()
    const imgEnemyDead    = new Image()
    const imgBossIdle     = new Image()
    const imgBossAttack   = new Image()
    const imgBossDead     = new Image()

    function loadSprites() {
      imgPlayerIdle.src   = dragonKnightIdle
      imgPlayerAttack.src = dragonKnightAttack
      imgPlayerDead.src   = dragonKnightDead
      imgEnemyIdle.src    = arcaneWizardIdle
      imgEnemyAttack.src  = arcaneWizardAttack
      imgEnemyDead.src    = arcaneWizardDead
      imgBossIdle.src     = shadowLordIdle
      imgBossAttack.src   = shadowLordAttack
      imgBossDead.src     = shadowLordDead
    }

    // ── ─── Handles internes (RAF + interval) ─────────────────────────────────
    let rafId          = null
    let shootInterval  = null
    let ctx            = null
    let canvas         = null

    // ════════════════════════════════════════════════════════════════════════
    // 2. BOUCLE DE JEU
    // ════════════════════════════════════════════════════════════════════════

    function gameLoop() {
      if (phase.value !== 'fighting') return
      ctx.clearRect(0, 0, CW, CH)

      drawBackground()
      updatePlayer()        // ← positionne activePlayer sur le curseur
      updateProjectiles()   // ← déplace + collision projectiles
      updateSword()         // ← anime l'arc de l'épée + dégâts

      drawFloorLines()
      drawEnemy()
      drawProjectiles()
      drawPlayer()
      drawSword()

      rafId = requestAnimationFrame(gameLoop)
    }

    // ════════════════════════════════════════════════════════════════════════
    // 3. MISE À JOUR DU JOUEUR + COLLISION AABB
    // ════════════════════════════════════════════════════════════════════════

    function updatePlayer() {
      if (!activePlayer.value) return
      const p = activePlayer.value
      const e = activeEnemy.value

      // Cible voulue = position souris, contrainte dans la moitié gauche du canvas
      let targetX = Math.max(PLAYER_RADIUS, Math.min(CW / 2 - PLAYER_RADIUS, mouseX))
      let targetY = Math.max(PLAYER_RADIUS, Math.min(CH - PLAYER_RADIUS, mouseY))

      // ── Collision AABB : interdiction de traverser l'ennemi ─────────────
      if (e) {
        const eRadius = e.isBoss ? BOSS_RADIUS : ENEMY_RADIUS
        const minDist = PLAYER_RADIUS + eRadius

        const dx = targetX - e.x
        const dy = targetY - e.y
        const dist = Math.sqrt(dx * dx + dy * dy)

        if (dist < minDist && dist > 0) {
          // Repousse le joueur sur le bord de la zone de collision
          const nx = dx / dist
          const ny = dy / dist
          targetX = e.x + nx * minDist
          targetY = e.y + ny * minDist
          // On reclamp pour ne pas sortir du canvas
          targetX = Math.max(PLAYER_RADIUS, Math.min(CW / 2 - PLAYER_RADIUS, targetX))
          targetY = Math.max(PLAYER_RADIUS, Math.min(CH - PLAYER_RADIUS, targetY))
        }
      }

      // Déplacement instantané vers le curseur (spec demande "INSTANTANÉMENT")
      p.x = targetX
      p.y = targetY
    }

    // ════════════════════════════════════════════════════════════════════════
    // 4. ANIMATION DE L'ÉPÉE & DÉGÂTS RÉELS
    // ════════════════════════════════════════════════════════════════════════

    function updateSword() {
      if (!isSwinging) return

      swingFrame++
      const t = swingFrame / SWING_FRAMES                     // 0 → 1
      swordAngle = SWING_START + (SWING_END - SWING_START) * t

      // ── Test d'impact : extrémité de l'épée vs boîte de l'ennemi ─────────
      if (!damageDealt && activePlayer.value && activeEnemy.value) {
        const p = activePlayer.value
        // Pointe de l'épée (dans l'espace monde)
        const tipX = p.x + Math.cos(swordAngle) * SWORD_LENGTH
        const tipY = p.y + Math.sin(swordAngle) * SWORD_LENGTH

        const e      = activeEnemy.value
        const eR     = e.isBoss ? BOSS_RADIUS : ENEMY_RADIUS
        // AABB de l'ennemi centré sur e.x, e.y
        const eLeft  = e.x - eR
        const eTop   = e.y - eR
        const eRight = e.x + eR
        const eBot   = e.y + eR

        // Point-dans-rectangle
        if (tipX >= eLeft && tipX <= eRight && tipY >= eTop && tipY <= eBot) {
          e.hp -= activePlayer.value.atk
          damageDealt = true
          if (e.hp <= 0) handleEnemyDeath()
        }
      }

      // Fin de l'animation → reset
      if (swingFrame >= SWING_FRAMES) {
        isSwinging  = false
        swingFrame  = 0
        swordAngle  = SWING_START
        damageDealt = false
      }
    }

    // ════════════════════════════════════════════════════════════════════════
    // 5. COMPORTEMENT ENNEMI : TIRS DE SORTS
    // ════════════════════════════════════════════════════════════════════════

    function startShooting() {
      stopShooting()
      shootInterval = setInterval(() => {
        if (phase.value !== 'fighting') return
        const e = activeEnemy.value
        const p = activePlayer.value
        if (!e || !p) return

        // Calcul du vecteur directeur normalisé vers la POSITION ACTUELLE du joueur
        const dx   = p.x - e.x
        const dy   = p.y - e.y
        const dist = Math.sqrt(dx * dx + dy * dy)

        projectiles.value.push({
          x:      e.x,
          y:      e.y,
          vx:     (dx / dist) * PROJ_SPEED,
          vy:     (dy / dist) * PROJ_SPEED,
          radius: PROJ_RADIUS,
          damage: e.atk,
        })
      }, ENEMY_SHOOT_MS)
    }

    function stopShooting() {
      if (shootInterval) { clearInterval(shootInterval); shootInterval = null }
    }

    // ════════════════════════════════════════════════════════════════════════
    // MISE À JOUR DES PROJECTILES + COLLISION AVEC LE JOUEUR
    // ════════════════════════════════════════════════════════════════════════

    function updateProjectiles() {
      const p = activePlayer.value
      const list = projectiles.value

      for (let i = list.length - 1; i >= 0; i--) {
        const proj = list[i]
        proj.x += proj.vx
        proj.y += proj.vy

        // Sortie hors canvas → détruire
        if (proj.x < -50 || proj.x > CW + 50 || proj.y < -50 || proj.y > CH + 50) {
          list.splice(i, 1)
          continue
        }

        // Collision circulaire projectile ↔ joueur
        if (p) {
          const dx   = proj.x - p.x
          const dy   = proj.y - p.y
          const dist = Math.sqrt(dx * dx + dy * dy)

          if (dist < PLAYER_RADIUS + proj.radius) {
            p.hp -= proj.damage
            list.splice(i, 1)
            if (p.hp <= 0) handlePlayerDeath()
          }
        }
      }
    }

    // ════════════════════════════════════════════════════════════════════════
    // 6. GESTION DES MORTS ET TRANSITIONS
    // ════════════════════════════════════════════════════════════════════════

    function handleEnemyDeath() {
      const e = activeEnemy.value
      if (!e) return

      // Bonus de soin pour le joueur : moitié des HP max de l'ennemi
      const bonus = Math.floor(e.maxHp / 2)
      if (activePlayer.value) {
        activePlayer.value.hp = Math.min(activePlayer.value.maxHp, activePlayer.value.hp + bonus)
      }

      e.alive = false
      killsPlayer.value++

      // Trouver le prochain ennemi vivant
      const next = enemyTeam.value.find(en => en.alive)
      if (next) {
        activeEnemy.value = next
        // Réinitialiser la position de l'ennemi sur sa case
        next.x = ENEMY_X
        next.y = ENEMY_Y
        // Vider les projectiles de l'ennemi précédent
        projectiles.value = []
      } else {
        // Plus aucun ennemi vivant → victoire
        endGame(true)
      }
    }

    function handlePlayerDeath() {
      const p = activePlayer.value
      if (!p) return

      p.alive = false
      killsEnemy.value++

      // Trouver le prochain allié vivant
      const next = playerTeam.value.find(pl => pl.alive && pl !== p)
      if (next) {
        // Positionner le remplaçant à la position courante de la souris
        next.x = Math.max(PLAYER_RADIUS, Math.min(CW / 2 - PLAYER_RADIUS, mouseX))
        next.y = Math.max(PLAYER_RADIUS, Math.min(CH - PLAYER_RADIUS, mouseY))
        activePlayer.value = next
      } else {
        // Toute l'équipe morte → défaite
        endGame(false)
      }
    }

    function endGame(won) {
      stopShooting()
      if (rafId) { cancelAnimationFrame(rafId); rafId = null }
      victory.value = won
      phase.value   = 'end'
    }

    // ════════════════════════════════════════════════════════════════════════
    // RENDU CANVAS
    // ════════════════════════════════════════════════════════════════════════

    function drawBackground() {
      // Ciel (dégradé)
      const sky = ctx.createLinearGradient(0, 0, 0, CH)
      sky.addColorStop(0, '#1a1a2e')
      sky.addColorStop(1, '#16213e')
      ctx.fillStyle = sky
      ctx.fillRect(0, 0, CW, CH)

      // Sol en herbe
      ctx.fillStyle = '#1b5e20'
      ctx.fillRect(0, CH - 50, CW, 50)

      // Ligne de séparation (frontière de territoire)
      ctx.save()
      ctx.setLineDash([10, 8])
      ctx.strokeStyle = 'rgba(255,255,255,0.2)'
      ctx.lineWidth = 2
      ctx.beginPath()
      ctx.moveTo(CW / 2, 0)
      ctx.lineTo(CW / 2, CH)
      ctx.stroke()
      ctx.restore()

      // Tours décoratives
      drawTower(80,  CH - 50, '#1565c0', '🏰')
      drawTower(720, CH - 50, '#b71c1c', '🏰')
    }

    function drawTower(x, baseY, color, icon) {
      ctx.fillStyle = color
      ctx.fillRect(x - 20, baseY - 80, 40, 80)
      ctx.fillStyle = 'rgba(0,0,0,0.3)'
      ctx.fillRect(x - 20, baseY - 80, 40, 80)
      ctx.font = '28px serif'
      ctx.textAlign = 'center'
      ctx.textBaseline = 'alphabetic'
      ctx.fillText(icon, x, baseY - 85)
    }

    function drawFloorLines() {
      // Reflet sol
      ctx.fillStyle = 'rgba(76,175,80,0.15)'
      ctx.fillRect(0, CH - 50, CW, 4)
    }

    // ── Barre de vie ─────────────────────────────────────────────────────────
    function drawHealthBar(x, y, hp, maxHp, barWidth = 80) {
      const barH   = 12
      const filled = Math.max(0, (hp / maxHp))
      const bx     = x - barWidth / 2
      const by     = y - 14

      // Fond noir
      ctx.fillStyle = 'rgba(0,0,0,0.75)'
      ctx.fillRect(bx - 1, by - 1, barWidth + 2, barH + 2)

      // Fond rouge
      ctx.fillStyle = '#b71c1c'
      ctx.fillRect(bx, by, barWidth, barH)

      // Remplissage vert/orange/rouge selon HP
      let barColor = '#4caf50'
      if (filled < 0.5) barColor = '#ff9800'
      if (filled < 0.25) barColor = '#f44336'
      ctx.fillStyle = barColor
      ctx.fillRect(bx, by, barWidth * filled, barH)

      // Texte HP
      ctx.fillStyle = '#fff'
      ctx.font = 'bold 10px Arial'
      ctx.textAlign = 'center'
      ctx.textBaseline = 'middle'
      ctx.fillText(`${Math.ceil(hp)}/${maxHp}`, x, by + barH / 2)
    }

    // ── Dessin du joueur actif ────────────────────────────────────────────────
    function drawPlayer() {
      const p = activePlayer.value
      if (!p) return

      const SIZE = PLAYER_RADIUS * 2
      const img  = isSwinging ? imgPlayerAttack : imgPlayerIdle

      ctx.save()
      ctx.translate(p.x, p.y)
      if (img.complete && img.naturalWidth > 0) {
        ctx.drawImage(img, -PLAYER_RADIUS, -PLAYER_RADIUS, SIZE, SIZE)
      } else {
        // Fallback cercle bleu si le sprite n'est pas encore chargé
        ctx.fillStyle = '#1976d2'
        ctx.beginPath()
        ctx.arc(0, 0, PLAYER_RADIUS, 0, Math.PI * 2)
        ctx.fill()
        ctx.fillStyle = '#fff'
        ctx.font = '20px serif'
        ctx.textAlign = 'center'
        ctx.textBaseline = 'middle'
        ctx.fillText('⚔️', 0, 0)
      }
      ctx.restore()

      // Indicateur "VOUS" au-dessus
      ctx.fillStyle = '#42a5f5'
      ctx.font      = 'bold 11px Arial'
      ctx.textAlign = 'center'
      ctx.textBaseline = 'alphabetic'
      ctx.fillText('VOUS', p.x, p.y - PLAYER_RADIUS - 18)

      drawHealthBar(p.x, p.y - PLAYER_RADIUS - 6, p.hp, p.maxHp, 70)
    }

    // ── Dessin de l'épée ──────────────────────────────────────────────────────
    function drawSword() {
      const p = activePlayer.value
      if (!p) return

      ctx.save()
      ctx.translate(p.x, p.y)

      // Lame
      ctx.rotate(swordAngle)
      ctx.strokeStyle = isSwinging ? '#ffd54f' : '#b0bec5'
      ctx.lineWidth   = SWORD_WIDTH
      ctx.lineCap     = 'round'
      ctx.shadowColor = isSwinging ? '#ff6f00' : 'transparent'
      ctx.shadowBlur  = isSwinging ? 12 : 0
      ctx.beginPath()
      ctx.moveTo(PLAYER_RADIUS - 4, 0)         // départ au bord du sprite
      ctx.lineTo(PLAYER_RADIUS - 4 + SWORD_LENGTH, 0)
      ctx.stroke()

      // Garde de l'épée (croix)
      ctx.strokeStyle = '#78909c'
      ctx.lineWidth   = 5
      ctx.shadowBlur  = 0
      ctx.beginPath()
      ctx.moveTo(PLAYER_RADIUS + 6, -12)
      ctx.lineTo(PLAYER_RADIUS + 6,  12)
      ctx.stroke()

      ctx.restore()
    }

    // ── Dessin de l'ennemi actif ──────────────────────────────────────────────
    function drawEnemy() {
      const e = activeEnemy.value
      if (!e) return

      const eR   = e.isBoss ? BOSS_RADIUS : ENEMY_RADIUS
      const SIZE  = eR * 2
      const img   = e.isBoss ? imgBossIdle : imgEnemyIdle

      ctx.save()
      ctx.translate(e.x, e.y)
      // Miroir horizontal : l'ennemi regarde à gauche
      ctx.scale(-1, 1)
      if (img.complete && img.naturalWidth > 0) {
        ctx.drawImage(img, -eR, -eR, SIZE, SIZE)
      } else {
        ctx.fillStyle = e.isBoss ? '#d32f2f' : '#e53935'
        ctx.beginPath()
        ctx.arc(0, 0, eR, 0, Math.PI * 2)
        ctx.fill()
        ctx.fillStyle = '#fff'
        ctx.font = '24px serif'
        ctx.textAlign = 'center'
        ctx.textBaseline = 'middle'
        ctx.fillText(e.isBoss ? '👑' : '🧙', 0, 0)
      }
      ctx.restore()

      // Étiquette boss
      if (e.isBoss) {
        ctx.fillStyle = '#ff8a65'
        ctx.font      = 'bold 11px Arial'
        ctx.textAlign = 'center'
        ctx.textBaseline = 'alphabetic'
        ctx.fillText('👑 ROI', e.x, e.y - eR - 18)
      }

      drawHealthBar(e.x, e.y - eR - 6, e.hp, e.maxHp, e.isBoss ? 100 : 80)
    }

    // ── Dessin des projectiles ────────────────────────────────────────────────
    function drawProjectiles() {
      projectiles.value.forEach(proj => {
        ctx.save()
        ctx.translate(proj.x, proj.y)

        // Halo lumineux
        const glow = ctx.createRadialGradient(0, 0, 0, 0, 0, proj.radius)
        glow.addColorStop(0,   'rgba(255, 235, 59, 1)')
        glow.addColorStop(0.4, 'rgba(255, 152,  0, 0.9)')
        glow.addColorStop(1,   'rgba(244,  67, 54, 0)')
        ctx.fillStyle = glow
        ctx.beginPath()
        ctx.arc(0, 0, proj.radius, 0, Math.PI * 2)
        ctx.fill()

        // Contour brillant
        ctx.strokeStyle = '#fff176'
        ctx.lineWidth   = 2
        ctx.shadowColor = '#ff6f00'
        ctx.shadowBlur  = 8
        ctx.stroke()

        ctx.restore()
      })
    }

    // ════════════════════════════════════════════════════════════════════════
    // GESTIONNAIRES D'ÉVÉNEMENTS
    // ════════════════════════════════════════════════════════════════════════

    function onMouseMove(event) {
      if (phase.value !== 'fighting') return
      const rect = canvas.getBoundingClientRect()
      mouseX = event.clientX - rect.left
      mouseY = event.clientY - rect.top
    }

    function onCanvasClick() {
      if (phase.value !== 'fighting') return
      // Un seul swing à la fois
      if (!isSwinging) {
        isSwinging  = true
        swingFrame  = 0
        swordAngle  = SWING_START
        damageDealt = false
      }
    }

    // ════════════════════════════════════════════════════════════════════════
    // DÉMARRAGE & NETTOYAGE
    // ════════════════════════════════════════════════════════════════════════

    function startGame() {
      // Réinitialiser les équipes
      playerTeam.value = [
        { hp: 100, maxHp: 100, atk: 15, x: 80,  y: PLAYER_FLOOR, alive: true },
        { hp: 100, maxHp: 100, atk: 15, x: -200, y: PLAYER_FLOOR, alive: true },
        { hp: 100, maxHp: 100, atk: 15, x: -200, y: PLAYER_FLOOR, alive: true },
      ]
      enemyTeam.value = [
        { hp: 60,  maxHp: 60,  atk: 10, x: ENEMY_X, y: ENEMY_Y, alive: true, isBoss: false },
        { hp: 60,  maxHp: 60,  atk: 10, x: ENEMY_X, y: ENEMY_Y, alive: true, isBoss: false },
        { hp: 250, maxHp: 250, atk: 20, x: ENEMY_X, y: ENEMY_Y, alive: true, isBoss: true  },
      ]

      activePlayer.value = playerTeam.value[0]
      activeEnemy.value  = enemyTeam.value[0]
      projectiles.value  = []
      killsPlayer.value  = 0
      killsEnemy.value   = 0

      isSwinging  = false
      swingFrame  = 0
      swordAngle  = SWING_START
      damageDealt = false
      mouseX      = 80
      mouseY      = PLAYER_FLOOR

      phase.value = 'fighting'

      // Lancer la boucle et le tir ennemi après un tick (le canvas doit être monté)
      setTimeout(() => {
        canvas = canvasRef.value
        ctx    = canvas.getContext('2d')
        gameLoop()
        startShooting()
      }, 50)
    }

    async function conquer() {
      conquering.value = true
      try {
        await conquerKingdomApi(1)
        router.push('/')
      } catch (err) {
        console.error(err)
      } finally {
        conquering.value = false
      }
    }

    function goHome() {
      router.push('/')
    }

    // ── Lifecycle ─────────────────────────────────────────────────────────────
    onMounted(() => {
      loadSprites()
    })

    onUnmounted(() => {
      stopShooting()
      if (rafId) cancelAnimationFrame(rafId)
    })

    return {
      canvasRef,
      phase, victory, conquering,
      playerTeam, enemyTeam,
      killsPlayer, killsEnemy,
      startGame, conquer, goHome,
      onMouseMove, onCanvasClick,
    }
  }
}
</script>

<style scoped>
/* ── Racine ────────────────────────────────────────────────────────────────── */
.battle-root {
  min-height: 100vh;
  background: radial-gradient(ellipse at center, #0d1b2a 0%, #0a0f1e 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  font-family: 'Segoe UI', system-ui, sans-serif;
}

/* ── Écrans overlay (start / end) ─────────────────────────────────────────── */
.overlay-screen {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 24px;
  padding: 40px;
  text-align: center;
}

.title-text {
  font-size: 3rem;
  font-weight: 900;
  color: #fff;
  text-shadow: 0 0 30px rgba(255,200,0,0.5);
}
.title-text.victory { color: #ffd700; animation: pulse 1s infinite; }
.title-text.defeat  { color: #f44336; }

.subtitle-text {
  font-size: 1.3rem;
  color: #b0bec5;
}

.score-card {
  background: rgba(255,255,255,0.08);
  border: 2px solid rgba(255,255,255,0.15);
  border-radius: 16px;
  padding: 14px 32px;
  font-size: 1.4rem;
  font-weight: 700;
  color: #ffd54f;
}

/* ── Aperçu des équipes avant bataille ─────────────────────────────────────── */
.team-preview {
  display: flex;
  align-items: center;
  gap: 32px;
}
.team-box {
  background: rgba(255,255,255,0.06);
  border-radius: 16px;
  padding: 16px 24px;
  min-width: 140px;
}
.team-box.ally  { border: 2px solid #1976d2; }
.team-box.enemy { border: 2px solid #c62828; }
.team-label {
  font-size: 0.85rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #90a4ae;
  margin-bottom: 10px;
}
.unit-badge {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-bottom: 6px;
  font-size: 1.1rem;
}
.badge-hp { font-size: 0.75rem; color: #4caf50; font-weight: 700; }
.vs-label { font-size: 2.5rem; font-weight: 900; color: #ff7043; }

/* ── Boutons ──────────────────────────────────────────────────────────────── */
.btn-start {
  background: linear-gradient(180deg, #e53935, #b71c1c);
  color: #fff;
  font-size: 1.4rem;
  font-weight: 900;
  padding: 18px 52px;
  border: 3px solid #7f0000;
  border-radius: 24px;
  cursor: pointer;
  box-shadow: 0 8px 0 #4a0000, 0 12px 30px rgba(0,0,0,0.5);
  transition: transform 0.1s, box-shadow 0.1s;
}
.btn-start:hover  { transform: translateY(-3px); box-shadow: 0 11px 0 #4a0000; }
.btn-start:active { transform: translateY(6px);  box-shadow: 0 2px 0 #4a0000; }

.end-buttons { display: flex; gap: 16px; flex-wrap: wrap; justify-content: center; }

.btn-conquer {
  background: linear-gradient(180deg, #2e7d32, #1b5e20);
  color: #fff;
  font-size: 1.2rem;
  font-weight: 900;
  padding: 16px 36px;
  border: 3px solid #0d3d0f;
  border-radius: 20px;
  cursor: pointer;
  box-shadow: 0 6px 0 #0d3d0f;
  transition: transform 0.1s;
}
.btn-conquer:hover:not(:disabled)  { transform: translateY(-3px); }
.btn-conquer:active:not(:disabled) { transform: translateY(4px); }
.btn-conquer:disabled { opacity: 0.5; cursor: default; }

.btn-home {
  background: linear-gradient(180deg, #1565c0, #0d47a1);
  color: #fff;
  font-size: 1.2rem;
  font-weight: 900;
  padding: 16px 36px;
  border: 3px solid #0a3d91;
  border-radius: 20px;
  cursor: pointer;
  box-shadow: 0 6px 0 #0a3d91;
  transition: transform 0.1s;
}
.btn-home:hover  { transform: translateY(-3px); }
.btn-home:active { transform: translateY(4px); }

/* ── Wrapper arène ────────────────────────────────────────────────────────── */
.arena-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

/* ── HUD ──────────────────────────────────────────────────────────────────── */
.hud-top {
  width: 800px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: rgba(0,0,0,0.7);
  border: 2px solid rgba(255,255,255,0.1);
  border-radius: 12px;
  padding: 8px 16px;
}
.hud-team { display: flex; flex-direction: column; gap: 4px; }
.hud-team.right { align-items: flex-end; }
.hud-label { font-size: 0.7rem; text-transform: uppercase; color: #90a4ae; letter-spacing: 1px; }
.hud-icons { display: flex; gap: 6px; }
.hud-icon { font-size: 1.4rem; transition: opacity 0.3s; }
.hud-icon.dead { opacity: 0.25; filter: grayscale(1); }

.hud-score {
  font-size: 2rem;
  font-weight: 900;
  color: #ffd54f;
  text-shadow: 0 0 12px rgba(255,213,79,0.6);
}

.hud-bottom {
  width: 800px;
  display: flex;
  justify-content: center;
  gap: 16px;
  flex-wrap: wrap;
  padding: 6px 0;
}
.hint {
  font-size: 0.8rem;
  color: rgba(255,255,255,0.5);
  background: rgba(255,255,255,0.06);
  padding: 4px 10px;
  border-radius: 999px;
}

/* ── Canvas ────────────────────────────────────────────────────────────────── */
.game-canvas {
  border: 3px solid #5d4037;
  border-radius: 16px;
  cursor: crosshair;
  box-shadow: 0 0 40px rgba(0,0,0,0.8), inset 0 0 60px rgba(0,0,0,0.3);
  display: block;
}

@keyframes pulse {
  0%, 100% { transform: scale(1); }
  50%       { transform: scale(1.03); }
}
</style>
