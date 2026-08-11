# DRIVE-MATIC

Site corporate de detail d'equipements automobiles a destination du grand public, avec un espace partenaire authentifie (~100 partenaires).

> Documentation technique de reprise. Les specifications fonctionnelles sont dans [docs/PRD.md](docs/PRD.md), les regles pour l'agent IA dans [CLAUDE.md](CLAUDE.md).

## Stack

| Couche | Technologie |
|--------|-------------|
| CMS / backend | Drupal 11 (PHP 8.3, MariaDB 10.11) |
| Templating | Twig |
| JavaScript | Vanilla JS (comportements Drupal) |
| Styles | SCSS (architecture SMACSS + nommage BEM) |

## Prerequisites

- **PHP** >= 8.3 et **Composer** 2.x
- **Node.js** >= 20 et **npm** (outillage front : ESLint, Stylelint, Prettier)
- (Recommande) **Drush** pour l'administration Drupal en ligne de commande

## Installation

```bash
npm install        # outillage front (ESLint, Stylelint, Prettier)
composer install   # standards de code PHP (drupal/coder : Drupal + DrupalPractice)
```

## Verification (lint & format)

Ces commandes doivent **toutes** passer avant de considerer un travail termine (cf. `/done`) :

```bash
npm run lint          # JS (ESLint) + SCSS (Stylelint) + PHP/Twig (PHPCS)
npm run format:check  # verification du formatage (Prettier)
npm test              # placeholder — strategie de test a definir (voir docs/PRD.md)
```

Correction automatique :

```bash
npm run lint:fix      # eslint --fix, stylelint --fix, phpcbf
npm run format        # applique le formatage Prettier
```

Scripts granulaires : `lint:js`, `lint:css`, `lint:php`. Config : `eslint.config.mjs`, `.stylelintrc.json`, `.prettierrc.json`, `phpcs.xml.dist`.

## Architecture

<!-- A REMPLIR PAR /sync au fur et a mesure : structure des modules/themes custom,
     docroot (web/), flux de donnees, IDs et points d'attention. -->

## Structure du projet

<!-- A REMPLIR PAR /sync : arborescence commentee du code custom. -->

## Documentation

| Fichier | Contenu |
|---------|---------|
| [CLAUDE.md](CLAUDE.md) | Regles, conventions et garde-fous pour l'agent IA |
| [docs/PRD.md](docs/PRD.md) | Specifications fonctionnelles |
| [docs/E2E_SCENARIOS.md](docs/E2E_SCENARIOS.md) | Scenarios de non-regression |
| [.claude/decisions/](.claude/decisions/) | Decisions architecturales (ADR) |
