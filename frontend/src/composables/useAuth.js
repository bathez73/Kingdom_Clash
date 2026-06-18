import { ref, watch, watchEffect } from 'vue'
import api from '../services/api'

const safeJsonParse = (str, defaultValue = null) => {
  if (!str || str === 'undefined' || str === 'null') {
    return defaultValue
  }
  try {
    return JSON.parse(str)
  } catch (e) {
    return defaultValue
  }
}

const token = ref(localStorage.getItem('auth_token'))
const user = ref(safeJsonParse(localStorage.getItem('auth_user'), null))
const roles = ref(safeJsonParse(localStorage.getItem('auth_roles'), []))
const isAdmin = ref(localStorage.getItem('is_admin') === 'true')

watch(token, (newToken) => {
  if (newToken) {
    localStorage.setItem('auth_token', newToken)
  } else {
    localStorage.removeItem('auth_token')
  }
})

watch(user, (newUser) => {
  if (newUser) {
    localStorage.setItem('auth_user', JSON.stringify(newUser))
  } else {
    localStorage.removeItem('auth_user')
  }
}, { deep: true })

watch(roles, (newRoles) => {
  localStorage.setItem('auth_roles', JSON.stringify(newRoles))
}, { deep: true })

watch(isAdmin, (newIsAdmin) => {
  if (newIsAdmin) {
    localStorage.setItem('is_admin', 'true')
  } else {
    localStorage.removeItem('is_admin')
  }
})

watchEffect(() => {
  const currentToken = localStorage.getItem('auth_token')
  if (currentToken && currentToken !== 'undefined' && currentToken !== 'null') {
    if (!token.value) {
      token.value = currentToken
    }
  }
  const currentUser = localStorage.getItem('auth_user')
  if (currentUser) {
    const parsed = safeJsonParse(currentUser, null)
    if (parsed) {
      user.value = parsed
    }
  }
})

export function useAuth() {
  const setAuth = (data) => {
    token.value = data.token || data.access_token
    user.value = data.user
    roles.value = data.roles || []
    isAdmin.value = data.is_admin
  }

  const clearAuth = () => {
    token.value = null
    user.value = null
    roles.value = []
    isAdmin.value = false
  }

  const refreshAuthUser = async () => {
    try {
      const res = await api.get('/auth/user')
      if (res.data.user) {
        user.value = res.data.user
      }
    } catch (e) {
      console.error('Erreur rafraîchissement user:', e)
    }
  }

  return {
    token,
    authUser: user,
    roles,
    isAdmin,
    setAuth,
    clearAuth,
    refreshAuthUser
  }
}
