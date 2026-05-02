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

## Identifiants de test

- **Email** : `admin@example.com`
- **Mot de passe** : `admin123`

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