# Configuration et Utilisation des Emails

## Configuration

### 1. Variables d'environnement

Assurez-vous que votre fichier `.env` contient une configuration MAILER_DSN valide. Exemples :

```env
# Gmail (avec mot de passe application)
MAILER_DSN=gmail+smtp://votreemail@gmail.com:votremotdepasse@default

# Mailtrap (développement)
MAILER_DSN=smtp://username:password@send.mailtrap.io:587?encryption=tls

# SMTP générique
MAILER_DSN=smtp://username:password@host:port?encryption=tls
```

### 2. Email de l'expéditeur

Modifiez l'adresse email d'expédition dans `src/Service/EmailService.php` :

```php
->from(new Address('votre-email@votresociete.fr', 'Nom du Service'))
```

## Fonctionnalités

### 1. Email au propriétaire de la voiture (Réservation instantanée)

**Déclenchement** : Quand un utilisateur réserve une place sur un trajet

**Contenu** :
- Détails du trajet (départ, destination, heure)
- Informations du passager (nom, email, téléphone)
- Nombre de places restantes

**Envoi** : Automatique lors de la réservation

### 2. Email de rappel au passager (1 heure avant le départ)

**Déclenchement** : Via une commande Symfony exécutée par cron

**Contenu** :
- Détails du trajet
- Informations du conducteur
- Rappel d'être prêt 10-15 minutes avant

**Installation de la commande** :

#### Option A : Cron (recommandé)

Ajoutez cette ligne à votre crontab (exécution toutes les 5 minutes) :

```bash
*/5 * * * * cd /home/sanda/Projet\ ihm/projet_ihm && php bin/console app:send-ride-reminders >> /var/log/ride-reminders.log 2>&1
```

**Pour configurer le cron** :

```bash
# Ouvrir l'éditeur cron
crontab -e

# Ajouter la ligne ci-dessus
```

#### Option B : Symfony Scheduler (si Symfony 6.3+)

Vous pouvez aussi utiliser le Scheduler intégré de Symfony (à configurer dans `config/services.yaml`).

## Commande manuelle

Pour tester ou exécuter manuellement :

```bash
php bin/console app:send-ride-reminders
```

## Base de données

Une nouvelle table `ride_reminder` a été créée pour tracker les emails envoyés et éviter les doublons.

### Champs de User modifiés

Un nouveau champ optionnel a été ajouté à l'entité User :
- `phone` : Numéro de téléphone du passager/conducteur

## Vérification des logs

Consultez les logs pour vérifier que les emails sont envoyés :

```bash
# Développement (symfony local server)
tail -f var/log/dev.log

# Cron (si activé)
tail -f /var/log/ride-reminders.log
```

## Dépannage

### Les emails ne sont pas envoyés

1. **Vérifier la configuration MAILER_DSN** :
   ```bash
   php bin/console debug:container | grep mailer
   ```

2. **Activer le profiling Symfony** pour voir les emails en dev :
   - Ouvrez la Web Debug Toolbar
   - Cliquez sur l'icône Email

3. **Mode spool** : En production, les emails peuvent être mis en file d'attente. Exécutez :
   ```bash
   php bin/console messenger:consume async
   ```

### Les rappels ne sont pas envoyés

1. Vérifiez que le cron est configuré et actif :
   ```bash
   crontab -l
   ```

2. Vérifiez les logs du cron

3. Testez manuellement la commande

### Email adresse invalide

Vérifiez que les utilisateurs ont une adresse email valide dans la base de données.

## Test en développement

Pour tester sans envoyer réellement :

```bash
# Utiliser Mailtrap ou console transport
MAILER_DSN=smtp://localhost

# Ou activer le mode debug
php bin/console --env=dev app:send-ride-reminders -v
```
