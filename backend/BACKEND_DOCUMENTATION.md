# Kingdom Clash - Backend API Documentation

## Table des matières
1. [Structure du projet](#structure-du-projet)
2. [Installation et configuration](#installation-et-configuration)
3. [Modules et fonctionnalités](#modules-et-fonctionnalités)
4. [Routes API](#routes-api)
5. [Authentification](#authentification)
6. [Exemples d'utilisation](#exemples-dutilisation)

---

## Structure du projet

### Modèles (app/Models/)
- **User** : Utilisateur avec rôles Spatie et soft delete
- **Kingdom** : Royaume avec ressources et relations avec bâtiments/soldats
- **Building** : Bâtiments (gold_mine, sawmill, farm, barracks) avec upgrade asynchrone
- **Soldier** : Unités militaires (swordsman, archer, cavalry)
- **Battle** : Historique des batailles avec pertes et butins
- **Notification** : Notifications en base de données et WebSocket

### Services (app/Services/)
- **KingdomService** : Logique métier des royaumes (création, amélioration, échanges)
- **BattleService** : Système de combat et calcul des pertes
- **SoldierService** : Entraînement asynchrone des soldats

### Contrôleurs (app/Http/Controllers/Api/)
- **AuthController** : Register, Login, Logout
- **KingdomController** : Gestion du royaume et classement
- **BuildingController** : Amélioration des bâtiments
- **SoldierController** : Entraînement des soldats
- **BattleController** : Attaques et historique
- **NotificationController** : Gestion des notifications
- **ChatController** : Chat global
- **AdminController** : Gestion admin (soft delete, ban/unban)

### Jobs asynchrones (app/Jobs/)
- **BuildBuildingJob** : Finalise l'amélioration d'un bâtiment
- **TrainSoldierJob** : Finalise l'entraînement d'un soldat

### Événements (app/Events/)
- **BuildingUpgraded** : Broadcast quand un bâtiment est amélioré
- **SoldierTrained** : Broadcast quand un soldat est entraîné
- **BattleOccurred** : Broadcast en temps réel d'une bataille
- **RankingUpdated** : Broadcast de la mise à jour du classement
- **ChatMessageSent** : Broadcast des messages du chat global

### Commandes (app/Console/Commands/)
- **ResetDailyChests** : Réinitialise les coffres quotidiens à minuit

---

## Installation et configuration

### Prérequis
- Laravel 13
- SQLite
- Laravel Sanctum (API tokens)
- Laravel Reverb (WebSockets)
- Spatie Laravel Permission (rôles et permissions)

### Étapes d'installation

1. **Cloner le repository et installer les dépendances**
```bash
composer install
npm install
```

2. **Configurer l'environnement**
```bash
cp .env.example .env
php artisan key:generate
```

3. **Exécuter les migrations**
```bash
php artisan migrate
php artisan migrate:fresh --seed
```

4. **Lancer les workers de queue**
```bash
php artisan queue:work
```

5. **Lancer Reverb (WebSockets)**
```bash
php artisan reverb:start
```

6. **Lancer le serveur de développement**
```bash
php artisan serve
php artisan tinker  # Pour tester les commandes
```

### Configuration du Kernel
Le fichier `app/Console/Kernel.php` configure le scheduler pour exécuter la réinitialisation des coffres quotidiens à minuit.

---

## Modules et fonctionnalités

### Module 1 : Authentification et rôles

**Endpoints:**
- `POST /api/auth/register` : Créer un compte
- `POST /api/auth/login` : Se connecter
- `POST /api/auth/logout` : Se déconnecter

**Logique d'inscription:**
- Création d'un utilisateur
- Attribution du rôle 'player'
- Création automatique d'un royaume avec ressources initiales (1000 or, 500 bois, 500 nourriture)
- Initialisation de 4 bâtiments (gold_mine, sawmill, farm, barracks) niveau 1

**Rôles disponibles:**
- `admin` : Accès aux fonctionnalités d'administration
- `moderator` : Rôle pour les modérateurs (extensible)
- `player` : Rôle standard pour les joueurs

### Module 2 : Royaume et évolution asynchrone

**Endpoints:**
- `GET /api/kingdom` : Informations du propre royaume
- `GET /api/kingdom/ranking` : Classement global
- `GET /api/kingdom/{id}` : Informations public d'un royaume
- `POST /api/kingdom/exchange-resources` : Marché noir
- `POST /api/kingdom/daily-chest` : Récupérer le coffre quotidien

**Formules:**
- Coût d'amélioration : `base_cost * (niveau_actuel * 1.5)`
- Temps d'amélioration : Niveau 1 = 60s, Niveau 2 = 120s, Niveau 3+ = 300s
- Marché noir : Échange fixe avec ratios (ex: 2 or pour 1 bois)

**Jobs et Queues:**
- L'amélioration est lancée en queue avec un délai
- À la fin, le Job finalise l'upgrade et envoie une notification

### Module 3 : Armée et combats temps réel

**Endpoints:**
- `GET /api/soldiers` : Liste des soldats du royaume
- `POST /api/soldiers/train` : Entraîner des soldats
- `POST /api/battles/attack` : Lancer une attaque
- `GET /api/battles/history` : Historique des batailles

**Types de troupes:**
- Épéiste (swordsman) : Puissance 10, Coût 20 or + 10 nourriture
- Archer (archer) : Puissance 15, Coût 30 or + 15 nourriture
- Cavalier (cavalry) : Puissance 25, Coût 50 or + 25 nourriture

**Système de combat:**
1. Calcul de la puissance d'attaque (somme des puissances * quantité)
2. Calcul de la puissance de défense (niveau * 10 + puissance des troupes du défenseur)
3. Résolution : Attaquant gagne si puissance > défense
4. Pertes : 30% pour le gagnant, 80% pour le perdant (inversé si perte)
5. Butin : Le gagnant pille 50% de l'or du perdant

**Broadcasting temps réel:**
- Événement `BattleOccurred` envoyé aux deux joueurs
- Événement `RankingUpdated` diffusé globalement
- Les notifications sont envoyées en temps réel via WebSocket

### Module 4 : Notifications

**Endpoints:**
- `GET /api/notifications` : Lister les notifications (50 dernières)
- `POST /api/notifications/{id}/mark-as-read` : Marquer comme lue
- `POST /api/notifications/mark-all-as-read` : Marquer tout comme lu

**Types de notifications:**
- Construction terminée
- Entraînement terminé
- Attaque subie (réussite ou échec)
- Attaque gagnée

**Canaux WebSocket:**
- `kingdom.{id}` : Privé, pour les notifications d'un royaume
- `global-chat` : Public, pour le chat global
- `global-rankings` : Public, pour les mises à jour de classement

### Module 5 : Opérations spéciales

#### Marché noir
```
Taux d'échange fixes :
- 2 or → 1 bois
- 2 bois → 1 or
- 2 bois → 1 nourriture
- 2 nourriture → 1 bois
- 3 or → 1 nourriture
- 3 nourriture → 1 or
```

#### Coffre quotidien
- Accessible une fois par jour (cache jusqu'à minuit)
- Récompense aléatoire : 50-200 or, 30-100 bois, 30-100 nourriture
- Réinitialisé automatiquement par la commande scheduler

#### Chat global
- Endpoint: `POST /api/chat/send-message`
- Message broadcasté sur le canal `global-chat`
- Accessible à tous les utilisateurs authentifiés

### Module 6 : Administration

**Endpoints (role:admin):**
- `GET /admin/users` : Lister tous les utilisateurs
- `GET /admin/users/trashed` : Utilisateurs bannis
- `POST /admin/users/{id}/restore` : Débannir un utilisateur
- `DELETE /admin/users/{id}/force-delete` : Supprimer définitivement
- `POST /admin/users/{id}/ban` : Bannir un utilisateur
- `POST /admin/users/{id}/unban` : Débannir
- `GET /admin/kingdoms` : Lister tous les royaumes
- `GET /admin/kingdoms/trashed` : Royaumes supprimés
- `POST /admin/kingdoms/{id}/restore` : Restaurer un royaume
- `DELETE /admin/kingdoms/{id}/force-delete` : Supprimer définitivement

**Soft Delete:**
- Tous les modèles principaux supportent le soft delete
- Les données ne sont jamais supprimées immédiatement
- Les administrateurs peuvent restaurer ou supprimer définitivement

---

## Routes API

### Routes publiques (sans authentification)
```
POST   /api/auth/register          Créer un compte
POST   /api/auth/login             Se connecter
```

### Routes protégées (auth:sanctum)

#### Authentification
```
POST   /api/auth/logout            Se déconnecter
GET    /api/user                   Obtenir l'utilisateur actuel
```

#### Royaume
```
GET    /api/kingdom                Mon royaume
GET    /api/kingdom/ranking        Classement global
GET    /api/kingdom/{id}           Info public d'un royaume
POST   /api/kingdom/exchange-resources    Marché noir
POST   /api/kingdom/daily-chest    Coffre quotidien
```

#### Bâtiments
```
GET    /api/buildings              Mes bâtiments
POST   /api/buildings/{id}/upgrade Améliorer un bâtiment
```

#### Soldats
```
GET    /api/soldiers               Mes troupes
POST   /api/soldiers/train         Entraîner des troupes
```

#### Batailles
```
POST   /api/battles/attack         Lancer une attaque
GET    /api/battles/history        Historique des batailles
```

#### Notifications
```
GET    /api/notifications          Mes notifications
POST   /api/notifications/{id}/mark-as-read    Marquer comme lue
POST   /api/notifications/mark-all-as-read     Marquer tout comme lu
```

#### Chat
```
POST   /api/chat/send-message      Envoyer un message global
```

#### Administration (role:admin)
```
GET    /api/admin/users                   Lister les utilisateurs
GET    /api/admin/users/trashed           Utilisateurs bannis
POST   /api/admin/users/{id}/restore      Débannir
DELETE /api/admin/users/{id}/force-delete Supprimer
POST   /api/admin/users/{id}/ban          Bannir
POST   /api/admin/users/{id}/unban        Débannir
GET    /api/admin/kingdoms                Lister les royaumes
GET    /api/admin/kingdoms/trashed        Royaumes supprimés
POST   /api/admin/kingdoms/{id}/restore   Restaurer
DELETE /api/admin/kingdoms/{id}/force-delete Supprimer
```

---

## Authentification

### Flux d'inscription
```bash
POST /api/auth/register
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

**Réponse:**
```json
{
    "message": "Inscription réussie",
    "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
    },
    "kingdom": {
        "id": 1,
        "name": "Royaume de John Doe",
        "level": 1,
        "gold": 1000,
        "wood": 500,
        "food": 500
    },
    "access_token": "5|...",
    "token_type": "Bearer"
}
```

### Flux de connexion
```bash
POST /api/auth/login
{
    "email": "john@example.com",
    "password": "password123"
}
```

### Utilisation du token
Tous les endpoints protégés nécessitent le header:
```
Authorization: Bearer 5|...
```

### Utilisateurs de test
**Admin:**
- Email: `admin@clash.com`
- Mot de passe: `password123`

**Joueur:**
- Email: `player@clash.com`
- Mot de passe: `password123`
- Royaume: Camelot

---

## Exemples d'utilisation

### 1. Améliorer un bâtiment
```bash
POST /api/buildings/1/upgrade
Authorization: Bearer YOUR_TOKEN
```

Le bâtiment se déverrouille, le délai est calculé (1-5 min), les ressources sont déduites immédiatement, et un Job est lancé en queue.

### 2. Entraîner des soldats
```bash
POST /api/soldiers/train
Authorization: Bearer YOUR_TOKEN
{
    "type": "swordsman",
    "quantity": 10
}
```

Les 10 épéistes seront entraînés les uns après les autres (5s chacun = 50s total). Les ressources sont déduites immédiatement.

### 3. Lancer une attaque
```bash
POST /api/battles/attack
Authorization: Bearer YOUR_TOKEN
{
    "defender_id": 2,
    "troops": {
        "swordsman": 50,
        "archer": 30,
        "cavalry": 10
    }
}
```

La bataille est immédiatement résolue (transaction atomique), les pertes sont appliquées, le butin est transféré, et les notifications sont broadcastées.

### 4. Accéder au chat global
```javascript
// Frontend - WebSocket
Echo.channel('global-chat')
    .listen('ChatMessageSent', (event) => {
        console.log(event.username + ': ' + event.message);
    });
```

### 5. Écouter les mises à jour du royaume
```javascript
// Frontend - WebSocket pour un royaume
Echo.private(`kingdom.${kingdomId}`)
    .listen('BuildingUpgraded', (event) => {
        console.log('Bâtiment amélioré:', event.building_type);
    })
    .listen('SoldierTrained', (event) => {
        console.log('Soldat entraîné:', event.soldier_type);
    })
    .listen('BattleOccurred', (event) => {
        console.log('Bataille:', event.result);
    });
```

### 6. Récupérer le coffre quotidien
```bash
POST /api/kingdom/daily-chest
Authorization: Bearer YOUR_TOKEN
```

**Première tentative du jour:**
```json
{
    "success": true,
    "message": "Coffre récupéré",
    "reward": {
        "gold": 125,
        "wood": 67,
        "food": 89
    },
    "kingdom": {
        "id": 1,
        "gold": 1125,
        "wood": 567,
        "food": 589
    }
}
```

**Tentatives suivantes le même jour:**
```json
{
    "success": false,
    "message": "Vous avez déjà récupéré votre coffre aujourd'hui"
}
```

### 7. Marché noir
```bash
POST /api/kingdom/exchange-resources
Authorization: Bearer YOUR_TOKEN
{
    "from": "gold",
    "to": "wood",
    "quantity": 100
}
```

Échange 200 or pour 100 bois (ratio 2:1).

### 8. Administration - Lister les utilisateurs
```bash
GET /api/admin/users
Authorization: Bearer ADMIN_TOKEN
```

---

## Architecture et sécurité

### Principes de sécurité appliqués
1. **Authentification par token** : Laravel Sanctum pour les API REST
2. **Autorisation par rôles** : Spatie Laravel Permission pour le contrôle d'accès
3. **Transactions atomiques** : Toutes les opérations critiques sont dans des transactions DB
4. **Validation des entrées** : Tous les paramètres sont validés
5. **Soft Delete** : Les données ne sont jamais perdues involontairement
6. **WebSocket sécurisé** : Reverb avec canaux privés/publics

### Pattern utilisé
- **Service Layer** : Logique métier isolée dans les services
- **Repository Pattern** : Accès aux données via Eloquent
- **Event-Driven** : Les événements déclenchent les broadcasts
- **Queue-Based** : Les opérations longues sont mises en queue

### Rate Limiting
À implémenter côté client pour éviter les abus.

---

## Fichiers créés

### Modèles
- `app/Models/Kingdom.php`
- `app/Models/Building.php`
- `app/Models/Soldier.php`
- `app/Models/Battle.php`
- `app/Models/Notification.php`
- `app/Models/User.php` (modifié)

### Services
- `app/Services/KingdomService.php`
- `app/Services/BattleService.php`
- `app/Services/SoldierService.php`

### Contrôleurs
- `app/Http/Controllers/Api/AuthController.php`
- `app/Http/Controllers/Api/KingdomController.php`
- `app/Http/Controllers/Api/BuildingController.php`
- `app/Http/Controllers/Api/SoldierController.php`
- `app/Http/Controllers/Api/BattleController.php`
- `app/Http/Controllers/Api/NotificationController.php`
- `app/Http/Controllers/Api/ChatController.php`
- `app/Http/Controllers/Api/AdminController.php`

### Jobs
- `app/Jobs/BuildBuildingJob.php`
- `app/Jobs/TrainSoldierJob.php`

### Événements
- `app/Events/BuildingUpgraded.php`
- `app/Events/SoldierTrained.php`
- `app/Events/BattleOccurred.php`
- `app/Events/RankingUpdated.php`
- `app/Events/ChatMessageSent.php`

### Notifications
- `app/Notifications/ConstructionCompleted.php`
- `app/Notifications/TrainingCompleted.php`
- `app/Notifications/AttackReceived.php`
- `app/Notifications/AttackWon.php`

### Commandes
- `app/Console/Commands/ResetDailyChests.php`
- `app/Console/Kernel.php`

### Routes
- `routes/api.php` (complètement refondue)
- `routes/channels.php` (complètement refondue)

### Seeder
- `database/seeders/DatabaseSeeder.php` (modifié)

---

## Notes finales

1. **Queue Driver** : Configuré par défaut sur 'database'. Changez si nécessaire dans `.env`
2. **Cache** : Utilisé pour le coffre quotidien. Configuré sur 'file' par défaut
3. **Broadcasting** : Reverb doit tourner en parallèle avec le serveur Laravel
4. **Scheduler** : La commande scheduler doit être exécutée (configurable via cron)
5. **Transactions** : Toutes les mutations de données critiques sont protégées
6. **Soft Delete** : Les requêtes `all()` excluent les supprimés par défaut. Utiliser `withTrashed()` si nécessaire

---

**Version:** 1.0  
**Dernière mise à jour:** 16 Juin 2026  
**Auteur:** Lead Architecte Laravel
