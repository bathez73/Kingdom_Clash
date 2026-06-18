import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import DashboardView from '../views/DashboardView.vue'
import CardsView from '../views/CardsView.vue'
import KingdomView from '../views/KingdomView.vue'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import AdminView from '../views/AdminView.vue'
import BattleView from '../views/BattleView.vue'
import BattleHistoryView from '../views/BattleHistoryView.vue'
import LeaderboardView from '../views/LeaderboardView.vue'

const routes = [
  { path: '/', component: DashboardView, meta: { requiresAuth: true }, alias: '/dashboard' },
  { path: '/login', component: LoginView, meta: { guest: true } },
  { path: '/register', component: RegisterView, meta: { guest: true } },
  { path: '/cards', component: CardsView, meta: { requiresAuth: true } },
  { path: '/battle', component: BattleView, meta: { requiresAuth: true } },
  { path: '/historique-batailles', component: BattleHistoryView, meta: { requiresAuth: true } },
  { path: '/royaume', component: KingdomView, meta: { requiresAuth: true } },
  { path: '/classement', component: LeaderboardView, meta: { requiresAuth: true } },
  { path: '/admin', component: AdminView, meta: { requiresAuth: true, requiresAdmin: true } },
  // Placeholder routes
  { path: '/shop', component: DashboardView, meta: { requiresAuth: true } },
  { path: '/clan', component: DashboardView, meta: { requiresAuth: true } },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  const token = localStorage.getItem('auth_token')
  // Vérifier si le token est valide (non vide et non "undefined")
  const hasValidToken = token && token !== 'undefined' && token !== 'null'
  const isAdmin = localStorage.getItem('is_admin') === '1'
  
  if (to.meta.requiresAuth && !hasValidToken) {
    // Nettoyer le localStorage au cas où
    localStorage.removeItem('auth_token')
    localStorage.removeItem('auth_user')
    localStorage.removeItem('auth_roles')
    localStorage.removeItem('is_admin')
    return { path: '/login' }
  }
  if (to.meta.requiresAdmin && !isAdmin) {
    return { path: '/' }
  }
  if (to.meta.guest && hasValidToken) {
    return { path: '/' }
  }
})

export default router
