# Integra RH – Plateforme Intelligente de Gestion des Ressources Humaines

<p align="center">
  <img src="https://github.com/rayenMrebai/Esprit-PIDEV-3A28-2526-Integra/blob/5e05d9eb07c4c800d8bd0bb78f71b60c761801a8/symf.png?raw=true" alt="Symfony Logo" width="600">
</p>

[![Symfony Version](https://img.shields.io/badge/Symfony-6.4-000000.svg)](https://symfony.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.1-777BB4.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## Sommaire

- [Description](#description)
- [Fonctionnalités principales](#fonctionnalités-principales)
  - [👥 Gestion des utilisateurs](#-gestion-des-utilisateurs)
  - [📢 Gestion du recrutement](#-gestion-du-recrutement)
  - [🎓 Gestion des formations](#-gestion-des-formations)
  - [💰 Gestion des salaires](#-gestion-des-salaires)
  - [📁 Gestion de projets et affectations](#-gestion-de-projets-et-affectations)
  - [📊 Dashboard & Statistiques](#-dashboard--statistiques)
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

La plateforme centralise et automatise de nombreux processus RH grâce à des fonctionnalités modernes, des tableaux de bord interactifs et des intégrations d’intelligence artificielle.

Cette version web couvre l’ensemble des besoins RH :

- Gestion des utilisateurs
- Gestion du recrutement
- Gestion des formations
- Gestion des salaires
- **Gestion de projets et des affectations d’employés**

Le système offre une interface moderne, sécurisée et responsive, adaptée aux administrateurs, managers et employés.

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

### 📁 Gestion de projets et affectations

- CRUD des projets et des affectations d’employés
- Recherche et filtrage avancés (statut, dates, projet, rôle)
- Validation métier côté serveur (contraintes Symfony)
- Exports PDF et Excel avec graphiques et tableaux de bord
- Assistant IA intégré (résumé, amélioration de description, traduction)
- Recommandation d’employés par similarité sémantique (IA)
- Dictée vocale pour la description des projets
- API REST pour les données projets et affectations
- Taux de change en direct (USD, EUR)

### 📊 Dashboard & Statistiques

- KPIs RH interactifs
- Graphiques dynamiques avec Chart.js
- Suivi des utilisateurs actifs/inactifs
- Suivi des candidatures
- Statistiques salariales
- Widget météo via OpenWeatherMap

---

## Fonctionnalités IA

La plateforme intègre plusieurs services d’intelligence artificielle, répartis sur les différents modules :

- 🤖 Analyse automatique des CV
- 🧠 Extraction intelligente des compétences
- 📈 Calcul ATS Score
- 📝 Génération automatique de quiz
- 💡 Génération de recommandations RH
- ✉️ Génération de lettres de refus
- 📊 Modèle de prédiction salariale basé sur la régression linéaire
- 🧠 Assistant de projet (résumé, amélioration, traduction)
- 🧑‍💼 Recommandation d’employés pour un projet
- 🎤 Dictée vocale pour les descriptions de projet

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
- Bootstrap (4 et 5)
- JavaScript
- Chart.js
- Symfony UX Turbo
- Stimulus
- AdminLTE 3.2 (backoffice projets & affectations)
- Clean Blog (frontoffice public)

### Base de données

- MySQL

### APIs & Services externes

- Hugging Face API (embeddings + chat completions)
- Groq API (génération de quiz)
- Ollama (LLM local)
- Vosk (reconnaissance vocale offline)
- Open Exchange Rates API (taux de change)
- OpenWeatherMap API
- Telegram Bot API
- Brevo Mailer
- Gmail Mailer

### Bibliothèques & Bundles

- Dompdf
- PhpSpreadsheet
- FPDI
- Smalot PDF Parser
- Symfony Mailer
- Symfony Security Bundle
- Symfony Notifier
- Symfony Cache
- Symfony HttpClient
- Symfony Asset Mapper
- Twig Extra Bundle
- Monolog Bundle

### Tests & Qualité

- PHPUnit
- PHPStan
- Symfony Debug Bundle
- Symfony Web Profiler

---

## Architecture

Le projet suit une architecture **MVC** classique :

- **Models** : Entités Doctrine
- **Views** : Templates Twig
- **Controllers** : Contrôleurs Symfony

L’architecture est enrichie de :

- Services dédiés (logique métier, exports, IA, taux de change)
- Repository personnalisés avec QueryBuilder pour les filtres
- API REST pour les données projets/affectations
- Extension Twig pour les taux de change
- Notifications asynchrones (emails, Telegram)
- Dashboards interactifs avec graphiques
- Layouts séparés (AdminLTE pour le backoffice, Clean Blog pour le frontoffice)

---

## Installation

### Prérequis

- PHP 8.1 ou supérieur
- Composer
- MySQL
- Symfony CLI (recommandé)
- Node.js et npm (pour Webpack Encore, le cas échéant)
- Python 3.11+ avec `vosk`, `wave` (pour la dictée vocale)
- FFmpeg (pour la conversion audio)
- Ollama (pour l’assistant IA local)

### Cloner le projet

```bash
git clone https://github.com/rayenMrebai/Esprit-PIDEV-3A28-2526-Integra.git
cd Esprit-PIDEV-3A28-2526-Integra
composer install
