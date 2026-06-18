import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || 'http://127.0.0.1:8000/api',
  headers: {
    'Content-Type': 'application/json',
    Accept: 'application/json',
  },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('auth_token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      localStorage.removeItem('auth_token')
      localStorage.removeItem('auth_user')
      localStorage.removeItem('auth_roles')
      localStorage.removeItem('is_admin')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export function login(credentials) {
  return api.post('/auth/login', credentials)
}

export function register(payload) {
  return api.post('/auth/register', payload)
}

export function logout() {
  return api.post('/auth/logout')
}

// Cards
export function getCards() {
  return api.get('/cards')
}

export function updateDeck(payload) {
  return api.post('/cards/deck', payload)
}

export function upgradeCard(cardId) {
  return api.post(`/cards/${cardId}/upgrade`)
}

// Chests
export function getChests() {
  return api.get('/chests')
}

export function startUnlockChest(chestId) {
  return api.post(`/chests/${chestId}/start-unlock`)
}

export function openChest(chestId) {
  return api.post(`/chests/${chestId}/open`)
}

// Battles
export function startBattle() {
  return api.post('/battles/start')
}

export function finishBattle(won) {
  return api.post('/battles/finish', { won })
}

export function attack(defenderId, troops, score = 0) {
  return api.post(`/battles/attack/${defenderId}`, { troops, score })
}

export function getBattleHistory() {
  return api.get('/battles/history')
}

// Kingdom (Legacy)
export function fetchKingdom() {
  return api.get('/kingdom')
}

export function getKingdom() {
  return api.get('/kingdom')
}

export function getRanking() {
  return api.get('/kingdom/ranking')
}

export function getLeaderboard() {
  return api.get('/kingdom/ranking')
}

export function fetchRanking() {
  return api.get('/kingdom/ranking')
}

export function fetchKingdomById(id) {
  return api.get(`/kingdom/${id}`)
}

export function exchangeResources(from, to, quantity) {
  return api.post('/kingdom/exchange-resources', { from, to, quantity })
}

export function claimDailyChest() {
  return api.post('/kingdom/daily-chest')
}

export function getBuildings() {
  return api.get('/buildings')
}

export function fetchBuildings() {
  return api.get('/buildings')
}

export function upgradeBuilding(buildingType) {
  return api.post(`/buildings/${buildingType}/upgrade`)
}

export function getSoldiers() {
  return api.get('/soldiers')
}

export function fetchSoldiers() {
  return api.get('/soldiers')
}

export function trainSoldier(soldierType) {
  return api.post('/soldiers/train', { type: soldierType, quantity: 1 })
}

export function fetchBattleHistory() {
  return api.get('/battles/history')
}

export function fetchNotifications() {
  return api.get('/notifications')
}

export function markNotificationAsRead(notificationId) {
  return api.post(`/notifications/${notificationId}/mark-as-read`)
}

export function markAllNotificationsAsRead() {
  return api.post('/notifications/mark-all-as-read')
}

export function fetchAllUsers() {
  return api.get('/admin/users')
}

export function fetchTrashedUsers() {
  return api.get('/admin/users/trashed')
}

export function restoreUser(userId) {
  return api.post(`/admin/users/${userId}/restore`)
}

export function forceDeleteUser(userId) {
  return api.delete(`/admin/users/${userId}/force-delete`)
}

export function banUser(userId) {
  return api.post(`/admin/users/${userId}/ban`)
}

export function unbanUser(userId) {
  return api.post(`/admin/users/${userId}/unban`)
}

export function fetchAllKingdoms() {
  return api.get('/admin/kingdoms')
}

export function fetchTrashedKingdoms() {
  return api.get('/admin/kingdoms/trashed')
}

export function restoreKingdom(kingdomId) {
  return api.post(`/admin/kingdoms/${kingdomId}/restore`)
}

export function forceDeleteKingdom(kingdomId) {
  return api.delete(`/admin/kingdoms/${kingdomId}/force-delete`)
}

export function getUser() {
  return api.get('/auth/user')
}

export function updateUser(data) {
  return api.put('/auth/user', data)
}

export default api
