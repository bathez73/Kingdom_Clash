<template>
  <div>
    <div class="card">
      <h2>Tableau de bord d'Administration</h2>
    </div>

    <div class="card" style="margin-top: 1.5rem;">
      <h3>Utilisateurs</h3>
      <div v-if="loadingUsers" class="skeleton-card">
        <div class="skeleton skeleton-title"></div>
        <div class="skeleton skeleton-line"></div>
        <div class="skeleton skeleton-line short"></div>
      </div>
      <div v-else>
        <div v-if="errorUsers" class="error">{{ errorUsers }}</div>
        <table class="data-table" v-if="users.length">
          <thead>
            <tr>
              <th>Avatar</th>
              <th>ID</th>
              <th>Nom</th>
              <th>Email</th>
              <th>Rôles</th>
              <th>Créé le</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in users" :key="user.id">
              <td>
                <img :src="getUserAvatar(user.id)" :alt="user.name" class="table-avatar">
              </td>
              <td>{{ user.id }}</td>
              <td>{{ user.name }}</td>
              <td>{{ user.email }}</td>
              <td>{{ user.roles.join(', ') }}</td>
              <td>{{ user.created_at }}</td>
              <td class="table-actions">
                <button class="btn-small btn-danger" @click="openBanModal(user.id)">Bannir</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card" style="margin-top: 1.5rem;">
      <h3>Utilisateurs Bannis (Corbeille)</h3>
      <div v-if="loadingTrashedUsers" class="skeleton-card">
        <div class="skeleton skeleton-title"></div>
        <div class="skeleton skeleton-line"></div>
      </div>
      <div v-else>
        <div v-if="errorTrashedUsers" class="error">{{ errorTrashedUsers }}</div>
        <table class="data-table" v-if="trashedUsers.length">
          <thead>
            <tr>
              <th>Avatar</th>
              <th>ID</th>
              <th>Nom</th>
              <th>Email</th>
              <th>Supprimé le</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in trashedUsers" :key="user.id">
              <td>
                <img :src="getUserAvatar(user.id)" :alt="user.name" class="table-avatar">
              </td>
              <td>{{ user.id }}</td>
              <td>{{ user.name }}</td>
              <td>{{ user.email }}</td>
              <td>{{ user.deleted_at }}</td>
              <td class="table-actions">
                <button class="btn-small btn-success" @click="openRestoreUserModal(user.id)">Restaurer</button>
                <button class="btn-small btn-danger" @click="openForceDeleteUserModal(user.id)">Supprimer définitivement</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card" style="margin-top: 1.5rem;">
      <h3>Royaumes</h3>
      <div v-if="loadingKingdoms" class="skeleton-card">
        <div class="skeleton skeleton-title"></div>
        <div class="skeleton skeleton-line"></div>
      </div>
      <div v-else>
        <div v-if="errorKingdoms" class="error">{{ errorKingdoms }}</div>
        <table class="data-table" v-if="kingdoms.length">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nom</th>
              <th>Niveau</th>
              <th>Or</th>
              <th>Bois</th>
              <th>Nourriture</th>
              <th>Propriétaire</th>
              <th>Créé le</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="kingdom in kingdoms" :key="kingdom.id">
              <td>{{ kingdom.id }}</td>
              <td>{{ kingdom.name }}</td>
              <td>{{ kingdom.level }}</td>
              <td>{{ kingdom.gold }}</td>
              <td>{{ kingdom.wood }}</td>
              <td>{{ kingdom.food }}</td>
              <td>{{ kingdom.user }}</td>
              <td>{{ kingdom.created_at }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card" style="margin-top: 1.5rem;">
      <h3>Royaumes Supprimés (Corbeille)</h3>
      <div v-if="loadingTrashedKingdoms" class="skeleton-card">
        <div class="skeleton skeleton-title"></div>
        <div class="skeleton skeleton-line"></div>
      </div>
      <div v-else>
        <div v-if="errorTrashedKingdoms" class="error">{{ errorTrashedKingdoms }}</div>
        <table class="data-table" v-if="trashedKingdoms.length">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nom</th>
              <th>Propriétaire</th>
              <th>Supprimé le</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="kingdom in trashedKingdoms" :key="kingdom.id">
              <td>{{ kingdom.id }}</td>
              <td>{{ kingdom.name }}</td>
              <td>{{ kingdom.user }}</td>
              <td>{{ kingdom.deleted_at }}</td>
              <td class="table-actions">
                <button class="btn-small btn-success" @click="openRestoreKingdomModal(kingdom.id)">Restaurer</button>
                <button class="btn-small btn-danger" @click="openForceDeleteKingdomModal(kingdom.id)">Supprimer définitivement</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <ConfirmModal
      :is-open="showConfirmModal"
      :title="modalTitle"
      :message="modalMessage"
      @confirm="handleConfirmAction"
      @close="closeConfirmModal"
    />
    <SuccessModal
      :is-open="showSuccessModal"
      :message="successMessage"
      @close="closeSuccessModal"
    />
  </div>
</template>

<script>
import { onMounted, ref } from 'vue'
import {
  fetchAllUsers,
  fetchTrashedUsers,
  restoreUser,
  forceDeleteUser,
  banUser,
  unbanUser,
  fetchAllKingdoms,
  fetchTrashedKingdoms,
  restoreKingdom,
  forceDeleteKingdom
} from '../services/api'
import ConfirmModal from '../components/ConfirmModal.vue'
import SuccessModal from '../components/SuccessModal.vue'
import { getAvatarById } from '../useAvatars'

export default {
  components: { ConfirmModal, SuccessModal },
  setup() {
    const loadingUsers = ref(true)
    const loadingTrashedUsers = ref(true)
    const loadingKingdoms = ref(true)
    const loadingTrashedKingdoms = ref(true)
    const errorUsers = ref(null)
    const errorTrashedUsers = ref(null)
    const errorKingdoms = ref(null)
    const errorTrashedKingdoms = ref(null)
    const users = ref([])
    const trashedUsers = ref([])
    const kingdoms = ref([])
    const trashedKingdoms = ref([])
    const showConfirmModal = ref(false)
    const showSuccessModal = ref(false)
    const modalTitle = ref('')
    const modalMessage = ref('')
    const successMessage = ref('')
    const currentAction = ref(null)
    const currentId = ref(null)

    const getUserAvatar = (userId) => {
      return getAvatarById(userId)
    }

    const loadUsers = async () => {
      loadingUsers.value = true
      errorUsers.value = null
      try {
        const res = await fetchAllUsers()
        users.value = res.data.users
      } catch (err) {
        errorUsers.value = err?.response?.data?.message || 'Erreur chargement utilisateurs'
      } finally {
        loadingUsers.value = false
      }
    }

    const loadTrashedUsers = async () => {
      loadingTrashedUsers.value = true
      errorTrashedUsers.value = null
      try {
        const res = await fetchTrashedUsers()
        trashedUsers.value = res.data.trashed_users
      } catch (err) {
        errorTrashedUsers.value = err?.response?.data?.message || 'Erreur chargement utilisateurs bannis'
      } finally {
        loadingTrashedUsers.value = false
      }
    }

    const loadKingdoms = async () => {
      loadingKingdoms.value = true
      errorKingdoms.value = null
      try {
        const res = await fetchAllKingdoms()
        kingdoms.value = res.data.kingdoms
      } catch (err) {
        errorKingdoms.value = err?.response?.data?.message || 'Erreur chargement royaumes'
      } finally {
        loadingKingdoms.value = false
      }
    }

    const loadTrashedKingdoms = async () => {
      loadingTrashedKingdoms.value = true
      errorTrashedKingdoms.value = null
      try {
        const res = await fetchTrashedKingdoms()
        trashedKingdoms.value = res.data.trashed_kingdoms
      } catch (err) {
        errorTrashedKingdoms.value = err?.response?.data?.message || 'Erreur chargement royaumes supprimés'
      } finally {
        loadingTrashedKingdoms.value = false
      }
    }

    const loadAll = async () => {
      await Promise.all([loadUsers(), loadTrashedUsers(), loadKingdoms(), loadTrashedKingdoms()])
    }

    const openBanModal = (userId) => {
      currentAction.value = 'ban'
      currentId.value = userId
      modalTitle.value = 'Bannir Utilisateur'
      modalMessage.value = `Êtes-vous sûr de vouloir bannir l'utilisateur #${userId} ?`
      showConfirmModal.value = true
    }

    const openRestoreUserModal = (userId) => {
      currentAction.value = 'restoreUser'
      currentId.value = userId
      modalTitle.value = 'Restaurer Utilisateur'
      modalMessage.value = `Êtes-vous sûr de vouloir restaurer l'utilisateur #${userId} ?`
      showConfirmModal.value = true
    }

    const openForceDeleteUserModal = (userId) => {
      currentAction.value = 'forceDeleteUser'
      currentId.value = userId
      modalTitle.value = 'Supprimer Définitivement Utilisateur'
      modalMessage.value = `Êtes-vous sûr de vouloir SUPPRIMER DÉFINITIVEMENT l'utilisateur #${userId} ? Cette action est IRRÉVERSIBLE !`
      showConfirmModal.value = true
    }

    const openRestoreKingdomModal = (kingdomId) => {
      currentAction.value = 'restoreKingdom'
      currentId.value = kingdomId
      modalTitle.value = 'Restaurer Royaume'
      modalMessage.value = `Êtes-vous sûr de vouloir restaurer le royaume #${kingdomId} ?`
      showConfirmModal.value = true
    }

    const openForceDeleteKingdomModal = (kingdomId) => {
      currentAction.value = 'forceDeleteKingdom'
      currentId.value = kingdomId
      modalTitle.value = 'Supprimer Définitivement Royaume'
      modalMessage.value = `Êtes-vous sûr de vouloir SUPPRIMER DÉFINITIVEMENT le royaume #${kingdomId} ? Cette action est IRRÉVERSIBLE !`
      showConfirmModal.value = true
    }

    const handleConfirmAction = async () => {
      try {
        let res
        if (currentAction.value === 'ban') {
          res = await banUser(currentId.value)
        } else if (currentAction.value === 'restoreUser') {
          res = await restoreUser(currentId.value)
        } else if (currentAction.value === 'forceDeleteUser') {
          res = await forceDeleteUser(currentId.value)
        } else if (currentAction.value === 'restoreKingdom') {
          res = await restoreKingdom(currentId.value)
        } else if (currentAction.value === 'forceDeleteKingdom') {
          res = await forceDeleteKingdom(currentId.value)
        }
        successMessage.value = res.data.message || 'Action réussie !'
        showSuccessModal.value = true
        await loadAll()
      } catch (err) {
        successMessage.value = err?.response?.data?.message || 'Erreur lors de l\'action'
        showSuccessModal.value = true
      } finally {
        showConfirmModal.value = false
      }
    }

    const closeConfirmModal = () => {
      showConfirmModal.value = false
    }
    
    const closeSuccessModal = () => {
      showSuccessModal.value = false
    }

    onMounted(loadAll)

    return {
      loadingUsers,
      loadingTrashedUsers,
      loadingKingdoms,
      loadingTrashedKingdoms,
      errorUsers,
      errorTrashedUsers,
      errorKingdoms,
      errorTrashedKingdoms,
      users,
      trashedUsers,
      kingdoms,
      trashedKingdoms,
      showConfirmModal,
      showSuccessModal,
      modalTitle,
      modalMessage,
      successMessage,
      openBanModal,
      openRestoreUserModal,
      openForceDeleteUserModal,
      openRestoreKingdomModal,
      openForceDeleteKingdomModal,
      handleConfirmAction,
      closeConfirmModal,
      closeSuccessModal,
      getUserAvatar
    }
  },
}
</script>

<style scoped>
.data-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1rem;
  font-size: 0.9rem;
  border-radius: 12px;
  overflow: hidden;
  border: 3px solid #6d4c41;
  background: #fff;
}

.data-table th, .data-table td {
  padding: 1rem;
  text-align: left;
  border-bottom: 2px solid #bcaaa4;
}

.data-table th {
  color: #3e2723;
  font-weight: 900;
  background: linear-gradient(180deg, #d7ccc8 0%, #bcaaa4 100%);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-size: 0.85rem;
  text-shadow: 1px 1px 0 rgba(255, 255, 255, 0.5);
}

.data-table tbody tr {
  transition: all 0.2s ease;
}

.data-table tbody tr:hover {
  background: #fff3e0;
}

.table-actions {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

.table-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  object-fit: cover;
  border: 2px solid #ffcc00;
}

.btn-small {
  padding: 0.5rem 1rem;
  border-radius: 20px;
  border: none;
  font-size: 0.85rem;
  font-weight: 900;
  cursor: pointer;
  transition: all 0.2s ease;
  letter-spacing: 0.3px;
  box-shadow: 0 3px 0 rgba(0, 0, 0, 0.3), 0 4px 8px rgba(0, 0, 0, 0.2);
}

.btn-small:hover {
  transform: translateY(-2px);
  box-shadow: 0 5px 0 rgba(0, 0, 0, 0.3), 0 6px 12px rgba(0, 0, 0, 0.25);
}

.btn-small:active {
  transform: translateY(2px);
  box-shadow: 0 1px 0 rgba(0, 0, 0, 0.3), 0 2px 6px rgba(0, 0, 0, 0.2);
}

.btn-success {
  background: linear-gradient(180deg, #8bc34a 0%, #689f38 100%);
  color: #1b5e20;
  box-shadow: 0 3px 0 #33691e, 0 4px 8px rgba(0, 0, 0, 0.2);
}

.btn-success:hover {
  box-shadow: 0 5px 0 #33691e, 0 6px 12px rgba(0, 0, 0, 0.25);
}

.btn-danger {
  background: linear-gradient(180deg, #ef5350 0%, #e53935 100%);
  color: #b71c1c;
  box-shadow: 0 3px 0 #b71c1c, 0 4px 8px rgba(0, 0, 0, 0.2);
}

.btn-danger:hover {
  box-shadow: 0 5px 0 #b71c1c, 0 6px 12px rgba(0, 0, 0, 0.25);
}
</style>
