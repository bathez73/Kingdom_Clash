# Kingdom Clash - Guide de test rapide

## 🚀 Démarrage du projet

### 1. Installation et migration
```bash
# Installer les dépendances
composer install

# Configurer l'environnement
cp .env.example .env
php artisan key:generate

# Exécuter les migrations avec seeder
php artisan migrate:fresh --seed
```

### 2. Lancer les services
```bash
# Terminal 1: Serveur Laravel
php artisan serve

# Terminal 2: Queue worker
php artisan queue:work

# Terminal 3: Reverb WebSocket
php artisan reverb:start

# Terminal 4: Scheduler (tous les minutes)
# Option 1: Développement
php artisan schedule:work

# Option 2: Production (ajouter à crontab)
* * * * * cd /path && php artisan schedule:run
```

---

## 🧪 Tests avec cURL/Postman

### Utilisateurs de test
```
Admin:
- Email: admin@clash.com
- Mot de passe: password123
- Token: À récupérer via login

Joueur:
- Email: player@clash.com
- Mot de passe: password123
- Royaume: Camelot
```

### 1. Inscription
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "New Player",
    "email": "newplayer@example.com",
    "password": "password123",
    "password_confirmation": "password123"
  }'
```

### 2. Connexion
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "player@clash.com",
    "password": "password123"
  }'

# Récupérer le token : "access_token": "..."
```

### 3. Obtenir mon royaume
```bash
curl -X GET http://localhost:8000/api/kingdom \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 4. Voir le classement
```bash
curl -X GET http://localhost:8000/api/kingdom/ranking \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 5. Obtenir mes bâtiments
```bash
curl -X GET http://localhost:8000/api/buildings \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 6. Améliorer un bâtiment
```bash
# Améliorer le bâtiment ID 1
curl -X POST http://localhost:8000/api/buildings/1/upgrade \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{}'
```

**Note:** La réponse contient `upgrade_ends_at`. Le job se déclenche automatiquement.

### 7. Voir mes troupes
```bash
curl -X GET http://localhost:8000/api/soldiers \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 8. Entraîner des soldats
```bash
curl -X POST http://localhost:8000/api/soldiers/train \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "swordsman",
    "quantity": 5
  }'
```

### 9. Voir mes notifications
```bash
curl -X GET http://localhost:8000/api/notifications \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 10. Lancer une attaque
```bash
# Attaquer le royaume ID 1 (Camelot)
curl -X POST http://localhost:8000/api/battles/attack \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "defender_id": 1,
    "troops": {
      "swordsman": 10,
      "archer": 5
    }
  }'
```

### 11. Voir l'historique des batailles
```bash
curl -X GET http://localhost:8000/api/battles/history \
  -H "Authorization: Bearer YOUR_TOKEN"
```

### 12. Marché noir - Échanges
```bash
# Échanger 100 or contre du bois (ratio 2:1 = 50 bois)
curl -X POST http://localhost:8000/api/kingdom/exchange-resources \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "from": "gold",
    "to": "wood",
    "quantity": 100
  }'
```

### 13. Coffre quotidien
```bash
curl -X POST http://localhost:8000/api/kingdom/daily-chest \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{}'
```

### 14. Chat global
```bash
curl -X POST http://localhost:8000/api/chat/send-message \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "message": "Bonjour à tous!"
  }'
```

### 15. Administration - Lister les utilisateurs (Admin uniquement)
```bash
curl -X GET http://localhost:8000/api/admin/users \
  -H "Authorization: Bearer ADMIN_TOKEN"
```

### 16. Bannir un utilisateur (Admin)
```bash
curl -X POST http://localhost:8000/api/admin/users/2/ban \
  -H "Authorization: Bearer ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{}'
```

---

## 🧠 Tester les WebSocket (Reverb)

### Avec Postman
1. Créer une nouvelle requête WebSocket
2. URL: `ws://localhost:8000/app/YOUR_TOKEN?protocol=7&client=js`
3. Dans la console JavaScript:

```javascript
// Écouter le chat global
Echo.channel('global-chat')
    .listen('ChatMessageSent', (event) => {
        console.log('Message reçu:', event);
    });

// Écouter les mises à jour du royaume (privé)
Echo.private(`kingdom.1`)
    .listen('BuildingUpgraded', (event) => {
        console.log('Bâtiment amélioré:', event);
    })
    .listen('SoldierTrained', (event) => {
        console.log('Soldat entraîné:', event);
    })
    .listen('BattleOccurred', (event) => {
        console.log('Bataille:', event);
    });

// Écouter le classement global
Echo.channel('global-rankings')
    .listen('RankingUpdated', (event) => {
        console.log('Classement mis à jour');
    });
```

---

## 📊 Tester les Jobs asynchrones

### Via Laravel Tinker
```bash
php artisan tinker

# Créer une attaque (lance des jobs)
$kingdom = App\Models\Kingdom::find(1);
$battle = App\Models\Battle::create([
    'attacker_id' => 2,
    'defender_id' => 1,
    'result' => 'attacker_won',
    'gold_stolen' => 500,
    'attacker_losses' => ['swordsman' => 5],
    'defender_losses' => ['swordsman' => 20]
]);

# Voir les jobs en attente
\App\Models\Job::all();

# Exécuter les jobs
php artisan queue:work --stop-when-empty
```

---

## 🔍 Vérifier les données

### Via Tinker
```bash
php artisan tinker

# Voir tous les royaumes
App\Models\Kingdom::all();

# Voir tous les bâtiments du premier royaume
App\Models\Kingdom::find(1)->buildings;

# Voir les notifications d'un royaume
App\Models\Kingdom::find(1)->notifications;

# Voir toutes les batailles
App\Models\Battle::all();

# Voir les users bannis
App\Models\User::onlyTrashed()->all();
```

---

## 📝 Logs et débogage

### Voir les logs
```bash
tail -f storage/logs/laravel.log
```

### Activer le mode debug
```bash
# Dans .env
APP_DEBUG=true
```

### Voir les requêtes SQL
```bash
php artisan tinker

# Enable queries logging
DB::enableQueryLog();
// ... effectuer des requêtes ...
dd(DB::getQueryLog());
```

---

## ⚡ Commandes Artisan utiles

```bash
# Vérifier les rôles et permissions
php artisan permission:list

# Réinitialiser la base de données
php artisan migrate:fresh --seed

# Lancer la commande de réinitialisation des coffres
php artisan app:reset-daily-chests

# Vider le cache
php artisan cache:clear

# Vider les queues
php artisan queue:flush

# Générer une clé d'application
php artisan key:generate
```

---

## 🎯 Scénario complet de test

### 1. Créer deux comptes
```bash
# Joueur 1
POST /api/auth/register → token1

# Joueur 2
POST /api/auth/register → token2
```

### 2. Améliorer un bâtiment (Joueur 1)
```bash
POST /api/buildings/1/upgrade → "upgrade_ends_at": "2024-01-01T00:01:30Z"
```

### 3. Attendre la fin (60 secondes max)
```bash
# Vérifier dans les logs que BuildBuildingJob s'est exécuté
tail -f storage/logs/laravel.log
```

### 4. Vérifier la notification
```bash
GET /api/notifications → "type": "construction_completed"
```

### 5. Entraîner des troupes (Joueur 1)
```bash
POST /api/soldiers/train 
{
  "type": "swordsman",
  "quantity": 10
}
# Attendre 50 secondes (5s × 10)
```

### 6. Lancer une attaque (Joueur 1 → Joueur 2)
```bash
POST /api/battles/attack
{
  "defender_id": 2,
  "troops": {"swordsman": 5}
}
```

### 7. Vérifier les résultats
```bash
# Joueur 1
GET /api/battles/history → voir l'attaque

# Joueur 2
GET /api/notifications → "type": "attack_received"

# Les deux
GET /api/kingdom → ressources et troupes mises à jour
```

---

## 🔐 Notes de sécurité

- Tous les tokens expirent après 15 jours (configurable)
- Les mots de passe sont hashés avec bcrypt
- Soft delete protège les données accidentelles
- Transactions atomiques pour intégrité des données
- Rate limiting (à implémenter côté client)

---

## 📞 Troubleshooting

### Problem: Queue ne s'exécute pas
```bash
# Solution 1: Vérifier le driver
php artisan config:clear

# Solution 2: Relancer le worker
php artisan queue:work --tries=1
```

### Problem: Reverb ne broadcast rien
```bash
# Solution 1: Vérifier que Reverb tourne
php artisan reverb:start

# Solution 2: Vérifier les logs
php artisan config:clear
```

### Problem: Jobs en attente indéfiniment
```bash
# Solution: Nettoyer les jobs échoués
php artisan queue:flush
php artisan queue:work --stop-when-empty
```

---

**Guide de test:** 16 Juin 2026
**Version:** 1.0
