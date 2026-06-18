<template>
  <div class="space-y-6">
    <!-- Deck Section -->
    <div class="bg-gradient-to-b from-amber-700 to-amber-800 rounded-2xl p-4 border-4 border-amber-900 shadow-lg">
      <h2 class="text-white font-black text-lg mb-3 flex items-center gap-2">
        <span class="text-2xl">🎴</span>
        Battle Deck
      </h2>
      <div class="grid grid-cols-4 gap-2">
        <div 
          v-for="(card, index) in deckCards" 
          :key="card?.id || index"
          @click="removeFromDeck(index)"
          class="cursor-pointer"
        >
          <div v-if="card" :class="getCardClass(card.type)" class="rounded-xl p-2 flex flex-col items-center border-3 shadow-md transform hover:scale-105 transition-transform">
            <span class="text-3xl">{{ card.icon }}</span>
            <span class="text-white font-bold text-xs mt-1">{{ card.name }}</span>
            <div class="bg-blue-600 rounded-full w-6 h-6 flex items-center justify-center text-white text-xs font-black -mt-1 border-2 border-blue-300">
              {{ card.pivot?.level || 1 }}
            </div>
          </div>
          <div v-else class="bg-white/20 rounded-xl h-28 border-2 border-dashed border-white/40 flex items-center justify-center">
            <span class="text-4xl opacity-30">+</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Collection Section -->
    <div class="bg-gradient-to-b from-green-700 to-green-800 rounded-2xl p-4 border-4 border-green-900 shadow-lg">
      <h2 class="text-white font-black text-lg mb-3 flex items-center gap-2">
        <span class="text-2xl">📚</span>
        Card Collection
      </h2>
      <div class="grid grid-cols-4 gap-2">
        <div 
          v-for="card in cards" 
          :key="card.id"
          @click="addToDeck(card)"
          class="cursor-pointer"
        >
          <div :class="getCardClass(card.type)" class="rounded-xl p-2 flex flex-col items-center border-3 shadow-md transform hover:scale-105 transition-transform">
            <span class="text-3xl">{{ card.icon }}</span>
            <span class="text-white font-bold text-xs mt-1">{{ card.name }}</span>
            <div class="bg-blue-600 rounded-full w-6 h-6 flex items-center justify-center text-white text-xs font-black -mt-1 border-2 border-blue-300">
              {{ card.pivot?.level || 1 }}
            </div>
            <!-- Copies Progress -->
            <div class="w-full mt-1">
              <div class="flex justify-between text-white/80 text-xs">
                <span>{{ card.pivot?.copies_count || 0 }}/{{ (card.pivot?.level || 1) * 10 }}</span>
              </div>
              <div class="bg-gray-700 rounded-full h-2 mt-1">
                <div 
                  class="bg-gradient-to-r from-green-400 to-green-600 h-2 rounded-full"
                  :style="{ width: `${((card.pivot?.copies_count || 0) / Math.max(1, (card.pivot?.level || 1) * 10)) * 100}%` }"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { getCards, updateDeck } from '../services/api'

export default {
  setup() {
    const cards = ref([])
    const deck = ref(null)

    const deckCards = ref([null, null, null, null, null, null, null, null])

    const getCardClass = (type) => {
      const classes = {
        common: 'bg-gradient-to-b from-gray-400 to-gray-600 border-gray-700',
        rare: 'bg-gradient-to-b from-blue-400 to-blue-600 border-blue-700',
        epic: 'bg-gradient-to-b from-purple-400 to-purple-600 border-purple-700',
        legendary: 'bg-gradient-to-b from-yellow-400 to-yellow-600 border-yellow-700'
      }
      return classes[type] || classes.common
    }

    const loadCards = async () => {
      try {
        const res = await getCards()
        cards.value = res.data.cards
        deck.value = res.data.deck
        
        // Initialize deckCards with 8 elements
        deckCards.value = new Array(8).fill(null)
        
        if (deck.value?.slots?.length) {
          // Map each slot ID to the corresponding card
          deck.value.slots.forEach((slotId, index) => {
            if (index < 8) {
              deckCards.value[index] = cards.value.find(c => c.id === slotId) || null
            }
          })
        }
      } catch (e) {
        console.error('Error loading cards:', e)
      }
    }

    const addToDeck = (card) => {
      // Check if card is already in deck
      const existingIndex = deckCards.value.findIndex(c => c?.id === card.id)
      if (existingIndex !== -1) {
        return
      }
      
      // Find first empty slot
      const emptyIndex = deckCards.value.findIndex(c => c === null)
      if (emptyIndex !== -1) {
        deckCards.value[emptyIndex] = card
        saveDeck()
      }
    }

    const removeFromDeck = (index) => {
      deckCards.value[index] = null
      saveDeck()
    }

    const saveDeck = async () => {
      // Only include non-null card IDs
      const slots = deckCards.value.filter(c => c !== null).map(c => c.id)
      
      try {
        await updateDeck({ slots: slots })
      } catch (e) {
        console.error('Error saving deck:', e)
      }
    }

    onMounted(loadCards)

    return { cards, deckCards, getCardClass, addToDeck, removeFromDeck }
  }
}
</script>
