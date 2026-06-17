# Site de Covoiturage

Ce projet Symfony est un site de covoiturage permettant aux utilisateurs de proposer et réserver des trajets.

## Fonctionnalités

- **Inscription/Connexion** : Système d'authentification complet
- **Profils utilisateurs** : Gestion des informations personnelles et de la voiture
- **Proposition de trajets** : Les conducteurs peuvent créer des trajets
- **Réservation** : Les passagers peuvent réserver des places
- **Gestion des places** : Nombre de places disponibles par voiture
- **Interface moderne** : Design responsive avec Bootstrap

## Utilisation

1. **Inscription** : Créez un compte et indiquez si vous avez une voiture
2. **Configuration** : Dans votre profil, ajoutez les détails de votre voiture si applicable
3. **Proposer un trajet** : Si vous avez une voiture, créez des trajets
4. **Réserver** : Cherchez et réservez des trajets disponibles
5. **Gestion** : Consultez vos trajets dans "Mes trajets"

## Mail
- **Configuration de .env pour le mail et le mot de passe** : Modifier la variable MAILER_DNS pour l'email et le mot de passe d'application
- **Modifier l'addresse d'expediteur** : Dans le fichier de service `/home/sanda/Public/Projet ihm/covoiturage/src/Service/EmailService.php`, modifier le mail de l'expediteur dans les methodes sendRideBookingNotification() et sendRideReminder()

## Installation

1. Assurez-vous que XAMPP/MySQL est démarré
2. `composer install`
3. `php bin/console doctrine:schema:update --force`
4. `symfony server:start`
5. Accédez à `http://localhost:8000`

## Structure

- `src/Entity/User.php` : Entité utilisateur
- `src/Controller/SecurityController.php` : Gestion de l'authentification
- `src/Controller/HomeController.php` : Page d'accueil
- `templates/` : Templates Twig avec Bootstrap