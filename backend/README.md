# 🏰 Kingdom Clash - Backend MMORTS

> Un jeu de stratégie en temps réel multijoueur avec Laravel 13, WebSockets et Vue.js

![Version](https://img.shields.io/badge/version-1.0-blue)
![Laravel](https://img.shields.io/badge/Laravel-13-red)
![Status](https://img.shields.io/badge/status-Production%20Ready-green)

---

## 📋 Table des matières

- [Vue d'ensemble](#vue-densemble)
- [Caractéristiques](#caractéristiques)
- [Architecture](#architecture)
- [Installation](#installation)
- [Documentation](#documentation)

---

## 🎮 Vue d'ensemble

Kingdom Clash est un **MMORTS (Massively Multiplayer Online Real-Time Strategy)** construit avec Laravel 13.

### Points forts
- ✅ **50+ endpoints API REST**
- ✅ **WebSocket temps réel** (Reverb)
- ✅ **Système de combat avancé** avec calculs mathématiques
- ✅ **Asynchrone robuste** (Jobs/Queues)
- ✅ **Authentification sécurisée** (Sanctum)
- ✅ **Gestion des rôles** (Spatie Permission)
- ✅ **Soft Delete** (protection des données)
- ✅ **Classement dynamique** en temps réel

---

## ✨ Caractéristiques principales

### 🏗 Système de Royaume
- Création automatique lors de l'inscription
- 4 types de bâtiments améliorables
- Gestion des ressources (Or, Bois, Nourriture)
- Amélioration asynchrone (1-5 minutes)

### ⚔️ Armée et Combat
- 3 types de troupes (Épéiste, Archer, Cavalier)
- Entraînement asynchrone
- Batailles transactionnelles atomiques
- Calcul mathématique des pertes (30% gagnant, 80% perdant)
- Butin automatique (50% de l'or)

### 🔴 Temps Réel WebSocket
- Broadcasting instantané des événements
- Canaux privés et publics
- Chat global en direct
- Mises à jour du classement

### 💰 Opérations Spéciales
- **Marché noir** : Échange de ressources
- **Coffre quotidien** : Récompenses aléatoires
- **Chat global** : Messages en temps réel
- **Classement** : Compétition dynamique

### 👮 Administration
- Gestion des utilisateurs
- Soft delete / Restauration
- Contrôle d'accès par rôles

---

## 🏗 Architecture

```
Frontend (Vue.js)
    ↓
API REST (50+ endpoints)
    ↓
Controllers → Services → Models → Database
    ↓
WebSocket (Reverb) ← Broadcasting Events
    ↓
Background Jobs (Queue)
```

---

## 📦 Installation rapide

```bash
# 1. Cloner et installer
composer install
npm install

# 2. Configurer
cp .env.example .env
php artisan key:generate

# 3. Migrations
php artisan migrate:fresh --seed

# 4. Lancer (dans 3 terminaux différents)
php artisan serve           # Serveur Laravel
php artisan queue:work      # Queue worker
php artisan reverb:start    # WebSocket
```

---

## 🚀 Démarrage rapide

### Créer un compte
```bash
POST /api/auth/register
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

### Se connecter
```bash
POST /api/auth/login
{
  "email": "john@example.com",
  "password": "password123"
}
```

---

## 📚 Documentation

1. **[BACKEND_DOCUMENTATION.md](BACKEND_DOCUMENTATION.md)** - Documentation complète (300+ lignes)
   - Architecture
   - Tous les modules
   - Toutes les routes API
   - Exemples détaillés

2. **[IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md)** - Résumé du projet
   - Fichiers créés
   - Vérification des fonctionnalités
   - Statistiques

3. **[TESTING_GUIDE.md](TESTING_GUIDE.md)** - Guide de test (200+ lignes)
   - Exemples cURL
   - Tests WebSocket
   - Troubleshooting

---

## 📊 Statistiques

| Métrique | Nombre |
|----------|--------|
| Modèles | 6 |
| Services | 3 |
| Contrôleurs | 8 |
| Routes API | 50+ |
| Jobs asynchrones | 2 |
| Événements Broadcast | 5 |
| Fichiers créés | 40+ |
| Lignes de code | 3000+ |

---

## 🔐 Sécurité

- ✅ Authentification JWT (Sanctum)
- ✅ Autorisation par rôles (Spatie)
- ✅ Transactions atomiques ACID
- ✅ Soft Delete (récupération des données)
- ✅ Validation complète des entrées
- ✅ Isolation des données par utilisateur

---

## 🔧 Commandes utiles

```bash
php artisan migrate:fresh --seed    # Réinitialiser la base
php artisan tinker                   # REPL interactif
php artisan cache:clear              # Vider le cache
php artisan queue:flush              # Vider la queue
php artisan app:reset-daily-chests   # Reset coffres
```

---

## 👥 Utilisateurs de test

```
Admin:
- Email: admin@clash.com
- Mot de passe: password123

Joueur:
- Email: player@clash.com
- Mot de passe: password123
- Royaume: Camelot
```

---

## 🚦 État du projet

**✅ Production Ready**

- Tous les modules terminés
- Tous les endpoints fonctionnels
- WebSocket temps réel opérationnel
- Jobs asynchrones configurés
- Documentation complète

---

## 📞 Support

Consulter les fichiers de documentation :
- Questions API → [BACKEND_DOCUMENTATION.md](BACKEND_DOCUMENTATION.md)
- Tests → [TESTING_GUIDE.md](TESTING_GUIDE.md)
- Implémentation → [IMPLEMENTATION_SUMMARY.md](IMPLEMENTATION_SUMMARY.md)

---

**Version:** 1.0  
**Dernière mise à jour:** 16 Juin 2026  
**Status:** ✅ Production Ready

---

*Happy coding! 🎮✨*


## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
