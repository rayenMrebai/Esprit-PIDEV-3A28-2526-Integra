# Integra RH – Plateforme Intelligente de Gestion des Ressources Humaines 

<p align="center">
  <img src="https://github.com/rayenMrebai/Esprit-PIDEV-3A28-2526-Integra/blob/5e05d9eb07c4c800d8bd0bb78f71b60c761801a8/symf.png?raw=true" alt="Symfony Logo" width="400">
</p>

[![Symfony Version](https://img.shields.io/badge/Symfony-6.4-000000.svg)](https://symfony.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.1-777BB4.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## Sommaire

- [Description](#description)
- [Fonctionnalités principales](#fonctionnalités-principales)
- [Fonctionnalités IA](#fonctionnalités-ia)
- [Technologies utilisées](#technologies-utilisées)
- [Architecture](#architecture)
- [Installation](#installation)
- [Améliorations futures](#améliorations-futures)
- [Équipe](#équipe)
- [Encadrants](#encadrants)

---

## Description

**Integra RH** est une plateforme web intelligente de gestion des ressources humaines développée avec **Symfony** dans le cadre d’un projet intégré d’ingénierie à **ESPRIT**.

La plateforme permet de centraliser et automatiser plusieurs processus RH grâce à des fonctionnalités modernes, des tableaux de bord interactifs et des intégrations d’intelligence artificielle.

Cette version web couvre principalement :

- Gestion des utilisateurs
- Gestion du recrutement
- Gestion des formations
- Gestion des salaires

Le système offre une interface moderne, sécurisée et responsive destinée aux administrateurs et managers RH.

---

## Fonctionnalités principales

### 👥 Gestion des utilisateurs

- Inscription et authentification sécurisée
- Gestion des rôles (Admin / Manager / Employé)
- Modification du profil utilisateur
- Activation et désactivation des comptes
- Recherche et filtrage multicritères
- Export PDF des utilisateurs
- Détection automatique des comptes inactifs
- Recommandations RH basées sur l’IA

### 📢 Gestion du recrutement

- CRUD des offres d’emploi
- Gestion des candidats
- Recherche et tri dynamiques
- Dashboard RH interactif
- Analyse intelligente des CV avec IA
- Calcul ATS Score
- Génération automatique de lettres de refus
- Planification des entretiens
- Notifications Telegram automatiques
- Export PDF des données RH

### 🎓 Gestion des formations

- Catalogue des formations
- Inscription aux formations
- Génération automatique de quiz avec IA
- Correction automatique des quiz
- Génération de certificats PDF
- Envoi automatique des certificats par email

### 💰 Gestion des salaires

- Gestion complète des salaires
- Calcul automatique des bonus
- Gestion des états de paiement
- Génération dynamique des fiches de paie PDF
- Export Excel avec statistiques
- Notifications automatiques par email
- Dashboard statistique avec Chart.js
- Prédiction salariale via régression linéaire

### 📊 Dashboard & Statistiques

- KPIs RH interactifs
- Graphiques dynamiques avec Chart.js
- Suivi des utilisateurs actifs/inactifs
- Suivi des candidatures
- Statistiques salariales
- Widget météo via OpenWeatherMap

---

## Fonctionnalités IA

La plateforme intègre plusieurs services d’intelligence artificielle :

- 🤖 Analyse automatique des CV
- 🧠 Extraction intelligente des compétences
- 📈 Calcul ATS Score
- 📝 Génération automatique de quiz
- 💡 Génération de recommandations RH
- ✉️ Génération de lettres de refus
- 📊 Modèle de prédiction salariale basé sur la régression linéaire

---

## Technologies utilisées

### Backend

| Technologie | Version |
|-------------|---------|
| PHP | 8.1 |
| Symfony | 6.4 |
| Doctrine ORM | - |
| Doctrine Migrations | - |

### Frontend

- Twig
- Bootstrap
- JavaScript
- Chart.js
- Symfony UX Turbo
- Stimulus

### Base de données

- MySQL

### APIs & Services externes

- Hugging Face API
- Groq API
- Telegram Bot API
- OpenWeatherMap API
- Brevo Mailer
- Gmail Mailer

### Bibliothèques & Bundles

- Dompdf
- FPDI
- Smalot PDF Parser
- PhpSpreadsheet
- AdminLTE Bundle
- Symfony Mailer
- Symfony Security Bundle
- Symfony Notifier
- Webpack Encore
- Asset Mapper
- GTranslate Bundle
- Monolog Bundle

### Tests & Qualité

- PHPUnit
- PHPStan
- Symfony Debug Bundle
- Symfony Web Profiler

---

## Architecture

Le projet suit une architecture **MVC** :

- **Models** : Entités Doctrine
- **Views** : Templates Twig
- **Controllers** : Contrôleurs Symfony

Le système intègre également :

- APIs REST
- Services IA
- Notifications temps réel
- Dashboards interactifs

---

## Installation

### Prérequis

- PHP 8.1 ou supérieur
- Composer
- MySQL
- Symfony CLI (recommandé)

### Cloner le projet

```bash
git clone https://github.com/rayenMrebai/Esprit-PIDEV-3A28-2526-Integra.git
cd Esprit-PIDEV-3A28-2526-Integra
