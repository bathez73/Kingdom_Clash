<template>
  <div class="cards-page">

    <!-- En-tête de page -->
    <div class="page-header">
      <div class="header-left">
        <button class="btn-home" @click="$router.push('/')">
          🏠 Accueil
        </button>
        <div>
          <h1 class="page-title">🃏 Mes Cartes</h1>
          <p class="page-sub">Gérez votre deck de combat (8 emplacements max)</p>
        </div>
      </div>
      <!-- Feedback sauvegarde discret -->
      <transition name="fade">
        <div v-if="saveStatus" class="save-badge" :class="saveStatus">
          {{ saveStatus === 'ok' ? '✅ Deck sauvegardé' : '❌ Erreur sauvegarde' }}
        </div>
      </transition>
    </div>

    <!-- ── BATTLE DECK ─────────────────────────────────────── -->
    <div class="section deck-section">
      <h2 class="section-title">
        <span>🎴</span> Battle Deck
        <span class="deck-count">{{ filledSlots }} / 8</span>
      </h2>
      <div class="deck-grid">
        <div
          v-for="(card, index) in deckCards"
          :key="card?.id || 'empty-' + index"
          class="deck-slot"
          :class="{ filled: !!card }"
          @click="card ? removeFromDeck(index) : null"
          :title="card ? 'Cliquer pour retirer' : 'Vide'"
        >
          <template v-if="card">
            <div class="card-inner" :class="getCardClass(card.type)">
              <span class="card-icon">{{ card.icon }}</span>
              <span class="card-name">{{ card.name }}</span>
              <div class="card-level">{{ card.pivot?.level || 1 }}</div>
              <div class="remove-hint">✕</div>
            </div>
          </template>
          <template v-else>
            <div class="empty-slot">
              <span class="plus-icon">+</span>
            </div>
          </template>
        </div>
      </div>
    </div>

    <!-- ── COLLECTION ──────────────────────────────────────── -->
    <div class="section collection-section">
      <h2 class="section-title">
        <span>📚</span> Collection de Cartes
        <span class="collection-count">{{ cards.length }} cartes</span>
      </h2>
      <div class="collection-grid">
        <div
          v-for="card in cards"
          :key="card.id"
          class="card-item"
          :class="{ 'in-deck': isInDeck(card), 'deck-full': !isInDeck(card) && filledSlots >= 8 }"
          @click="toggleCard(card)"
          :title="isInDeck(card) ? 'Retirer du deck' : filledSlots >= 8 ? 'Deck plein' : 'Ajouter au deck'"
        >
          <div class="card-inner" :class="getCardClass(card.type)">
            <!-- Badge "dans le deck" -->
            <div v-if="isInDeck(card)" class="in-deck-badge">✓ Deck</div>

            <span class="card-icon">{{ card.icon }}</span>
            <span class="card-name">{{ card.name }}</span>

            <div class="card-level">{{ card.pivot?.level || 1 }}</div>

            <!-- Barre de copies -->
            <div class="copies-bar">
              <div class="copies-labels">
                <span>{{ card.pivot?.copies_count || 0 }}/{{ (card.pivot?.level || 1) * 10 }}</span>
              </div>
              <div class="copies-track">
                <div
                  class="copies-fill"
                  :style="{ width: copiesPct(card) }"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <ErrorModal
      :is-open="showErrorModal"
      icon="❌"
      title="Erreur"
      :message="errorModalMessage"
      @close="showErrorModal = false"
    />
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { getCards, updateDeck } from '../services/api'
import ErrorModal from '../components/ErrorModal.vue'

export default {
  components: { ErrorModal },
  setup() {
    const router = useRouter()
    const cards            = ref([])
    const deck             = ref(null)
    const deckCards        = ref(Array(8).fill(null))
    const showErrorModal   = ref(false)
    const errorModalMessage = ref('')
    const saveStatus       = ref(null)   // null | 'ok' | 'error'
    let   saveTimer        = null

    const filledSlots = computed(() => deckCards.value.filter(Boolean).length)

    const getCardClass = (type) => ({
      common:    'card-common',
      rare:      'card-rare',
      epic:      'card-epic',
      legendary: 'card-legendary',
    }[type] || 'card-common')

    const copiesPct = (card) => {
      const count  = card.pivot?.copies_count || 0
      const needed = (card.pivot?.level || 1) * 10
      return Math.min(100, Math.floor((count / needed) * 100)) + '%'
    }

    const isInDeck = (card) => deckCards.value.some(c => c?.id === card.id)

    const loadCards = async () => {
      try {
        const res = await getCards()
        cards.value = res.data.cards || []
        deck.value  = res.data.deck

        deckCards.value = Array(8).fill(null)
        if (deck.value?.slots?.length) {
          deck.value.slots.forEach((slotId, i) => {
            if (i < 8) {
              deckCards.value[i] = cards.value.find(c => c.id === slotId) || null
            }
          })
        }
      } catch (e) {
        console.error('Erreur chargement cartes:', e)
        errorModalMessage.value = 'Impossible de charger les cartes.'
        showErrorModal.value    = true
      }
    }

    const toggleCard = (card) => {
      if (isInDeck(card)) {
        const idx = deckCards.value.findIndex(c => c?.id === card.id)
        if (idx !== -1) removeFromDeck(idx)
      } else {
        if (filledSlots.value >= 8) return
        addToDeck(card)
      }
    }

    const addToDeck = (card) => {
      const emptyIdx = deckCards.value.findIndex(c => c === null)
      if (emptyIdx !== -1) { deckCards.value[emptyIdx] = card; saveDeck() }
    }

    const removeFromDeck = (index) => {
      deckCards.value[index] = null
      saveDeck()
    }

    const saveDeck = async () => {
      clearTimeout(saveTimer)
      const slots = deckCards.value.filter(Boolean).map(c => c.id)
      try {
        await updateDeck({ slots })
        saveStatus.value = 'ok'
      } catch (e) {
        errorModalMessage.value = e?.response?.data?.error || 'Erreur de sauvegarde du deck'
        showErrorModal.value    = true
        saveStatus.value        = 'error'
      } finally {
        saveTimer = setTimeout(() => { saveStatus.value = null }, 2500)
      }
    }

    onMounted(loadCards)

    return {
      cards, deckCards, filledSlots, saveStatus,
      showErrorModal, errorModalMessage,
      getCardClass, copiesPct, isInDeck,
      toggleCard, removeFromDeck,
    }
  }
}
</script>

<style scoped>
/* ── Page ──────────────────────────────────────────────────────────────── */
.cards-page {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 24px;
  padding-bottom: 40px;
}

/* ── Header ────────────────────────────────────────────────────────────── */
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
}
.header-left {
  display: flex;
  align-items: center;
  gap: 16px;
}
.btn-home {
  background: linear-gradient(180deg, #455a64, #263238);
  color: #fff;
  border: 2px solid #546e7a;
  border-radius: 14px;
  padding: 10px 20px;
  font-size: 0.95rem;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 4px 0 #1c313a;
  transition: transform 0.1s, box-shadow 0.1s;
  white-space: nowrap;
}
.btn-home:hover  { transform: translateY(-2px); box-shadow: 0 6px 0 #1c313a; }
.btn-home:active { transform: translateY(3px);  box-shadow: 0 1px 0 #1c313a; }

.page-title { font-size: 1.8rem; font-weight: 900; color: #fff; margin: 0; }
.page-sub   { font-size: 0.85rem; color: #90a4ae; margin: 2px 0 0; }

.save-badge {
  padding: 8px 18px;
  border-radius: 999px;
  font-size: 0.9rem;
  font-weight: 700;
}
.save-badge.ok    { background: rgba(76,175,80,0.2);  color: #69f0ae; border: 1px solid #4caf50; }
.save-badge.error { background: rgba(244,67,54,0.2);  color: #ef9a9a; border: 1px solid #f44336; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.4s; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }

/* ── Sections ──────────────────────────────────────────────────────────── */
.section {
  border-radius: 20px;
  padding: 20px;
}
.deck-section       { background: linear-gradient(180deg, #b45309, #92400e); border: 3px solid #d97706; }
.collection-section { background: linear-gradient(180deg, #15803d, #14532d); border: 3px solid #22c55e; }

.section-title {
  display: flex;
  align-items: center;
  gap: 10px;
  color: #fff;
  font-size: 1.1rem;
  font-weight: 900;
  margin-bottom: 16px;
}
.deck-count, .collection-count {
  margin-left: auto;
  font-size: 0.8rem;
  background: rgba(0,0,0,0.3);
  padding: 3px 10px;
  border-radius: 999px;
  color: #fde68a;
}

/* ── Deck grid ─────────────────────────────────────────────────────────── */
.deck-grid {
  display: grid;
  grid-template-columns: repeat(8, 1fr);
  gap: 8px;
}
@media (max-width: 900px) { .deck-grid { grid-template-columns: repeat(4, 1fr); } }

.deck-slot {
  aspect-ratio: 3/4;
  cursor: pointer;
  position: relative;
}
.deck-slot.filled:hover .remove-hint { opacity: 1; }

.empty-slot {
  width: 100%; height: 100%;
  background: rgba(255,255,255,0.08);
  border: 2px dashed rgba(255,255,255,0.3);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
}
.plus-icon { font-size: 1.8rem; color: rgba(255,255,255,0.3); }

/* ── Collection grid ───────────────────────────────────────────────────── */
.collection-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
  gap: 10px;
}
.card-item { cursor: pointer; position: relative; transition: transform 0.15s; }
.card-item:hover { transform: translateY(-4px); }
.card-item.in-deck .card-inner { outline: 3px solid #fbbf24; }
.card-item.deck-full { opacity: 0.5; cursor: not-allowed; }
.card-item.deck-full:hover { transform: none; }

/* ── Card inner (partagé deck + collection) ────────────────────────────── */
.card-inner {
  border-radius: 12px;
  padding: 10px 8px 8px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  height: 100%;
  position: relative;
  border: 2px solid transparent;
  transition: filter 0.15s;
}
.card-common    { background: linear-gradient(180deg, #78909c, #455a64); border-color: #607d8b; }
.card-rare      { background: linear-gradient(180deg, #1e88e5, #0d47a1); border-color: #42a5f5; }
.card-epic      { background: linear-gradient(180deg, #8e24aa, #4a148c); border-color: #ba68c8; }
.card-legendary { background: linear-gradient(180deg, #f9a825, #e65100); border-color: #ffcc02; }

.card-icon  { font-size: 2rem; line-height: 1; }
.card-name  { font-size: 0.7rem; font-weight: 700; color: #fff; text-align: center; line-height: 1.2; }
.card-level {
  background: #1565c0;
  border: 2px solid #42a5f5;
  border-radius: 999px;
  width: 22px; height: 22px;
  display: flex; align-items: center; justify-content: center;
  font-size: 0.7rem; font-weight: 900; color: #fff;
}

.remove-hint {
  position: absolute;
  inset: 0;
  background: rgba(183,28,28,0.75);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.4rem;
  color: #fff;
  font-weight: 900;
  opacity: 0;
  transition: opacity 0.2s;
}

.in-deck-badge {
  position: absolute;
  top: 4px; right: 4px;
  background: #fbbf24;
  color: #3e2723;
  font-size: 0.6rem;
  font-weight: 900;
  padding: 2px 5px;
  border-radius: 999px;
}

.copies-bar   { width: 100%; margin-top: 4px; }
.copies-labels { display: flex; justify-content: flex-end; }
.copies-labels span { font-size: 0.6rem; color: rgba(255,255,255,0.7); }
.copies-track {
  background: rgba(0,0,0,0.4);
  border-radius: 999px;
  height: 5px;
  margin-top: 2px;
  overflow: hidden;
}
.copies-fill {
  height: 100%;
  border-radius: 999px;
  background: linear-gradient(90deg, #4caf50, #69f0ae);
  transition: width 0.5s ease;
}
</style>
