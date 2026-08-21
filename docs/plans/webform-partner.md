# Plan — Webform Devenir partenaire (F11)

> Plan d'implementation. Base : specs Passerelle §2.15 + maquette Figma (node 438-9838). Formulaire simple, mono-cas (ni conditionnel, ni referentiel, ni upload). Reutilise les decisions du webform contact.
>
> ⚠️ **Prerequis bloquant** : socle (Drupal 11 + sous-theme + modules) en place avant implementation.

## 1. Intention
Permettre a un prospect de soumettre une **demande de partenariat** (stockee en BO + e-mails). **Aucune creation de compte** (decision #4 : les comptes partenaires sont crees manuellement par Drive Matic).

## 2. Champs (validés specs + maquette)

Section « Vous êtes » = **titre** (pas un champ — ≠ contact).

| Champ | Element Webform | Requis | Détails |
|-------|-----------------|--------|---------|
| Nom de l'entreprise | `textfield` | ✅ *(toujours)* | placeholder « Nom de l'entreprise » |
| Adresse | `textfield` | ✅ | |
| Complément d'adresse | `textfield` | — | |
| Code postal | `textfield` | ✅ | « Ex : 13600 », validation 5 chiffres |
| Ville | `textfield` | ✅ | |
| Civilité | `select` | ✅ | Madame / Monsieur |
| Prénom | `textfield` | ✅ | |
| Nom | `textfield` | ✅ | « Votre nom » |
| Téléphone | `tel` | ✅ | |
| E-mail | `email` | ✅ | « Ex : monemail@gmail.com » |
| Êtes-vous aménageur qualifié véhicule auto-école ? | `radios` (Oui / Non) | ✅ | rendu **obligatoire** (l'astérisque manquant en maquette est corrigé) |
| Votre message | `textarea` | ✅ | « Description de votre message » |
| Consentement | `checkbox` | ✅ | « J'autorise ces informations à être utilisées par Drive Matic Legrand » |
| reCAPTCHA v3 | invisible | — | n'apparaît pas sur la maquette (normal) |
| Envoyer | submit | — | |

**Confirmation à l'écran** (message Webform) : « Votre demande de partenariat a bien été envoyée ! Notre équipe l'étudiera et vous recontactera dans les meilleurs délais. »

## 3. E-mails (2 handlers)
Objet « Drive Matic Legrand : devenir partenaire », expéditeur **`no-reply@drivematiclegrand.com`** :
- au **demandeur** (`[email]`) : récap identité + aménageur Oui/Non + message.
- à **`info@drivematiclegrand.com`** : idem, présentation « Demandeur ».

Échapper les valeurs (autoescape), `From`/`Reply-To` non issus d'entrées libres.

## 4. Réutilisé du webform contact (mêmes décisions)
reCAPTCHA v3 (clé site en config, **secrète en env hors dépôt**) + Honeypot + Flood ; stockage + e-mail ; **rétention 36 mois** ; consentement obligatoire ; accès soumissions réservé aux admins ; accessibilité AA ; i18n ; anti header-injection.

## 5. Fichiers impactés (socle en place)
- **Config Webform** : `webform.webform.partner.yml`.
- **Type `partner`** (ADR-002) : référence le webform.
- **Pas de JS custom** (aucune cascade), pas d'upload, pas de référentiel.
- Config modules déjà en place via le webform contact (webform, captcha/recaptcha, honeypot, flood).

## 6. Sécurité
Public anonyme → Honeypot + reCAPTCHA v3 + Flood. RGPD : consentement obligatoire, rétention 36 mois, soumissions admin-only, droit à l'effacement. Pas de donnée sensible / partenaire exposée. CSRF natif Webform.

## 7. Cohérence specs / PRD
Implémente **F11** ; type `partner` (ADR-002) ; « soumissions stockées + e-mail ». **Aucune création de compte** (décision #4). E2E **S11**. Aucune contradiction avec les décisions verrouillées.

## 8. Étapes d'implémentation
0. *(Prérequis)* Socle + modules (déjà requis par le contact).
1. Webform `partner` : bloc identité + aménageur + message + consentement + submit. *Vérif : affichage, soumission stockée.*
2. Handlers e-mail (2) + gabarits conformes. *Vérif : e-mails demandeur + info@.*
3. Message de confirmation à l'écran. *Vérif : texte exact affiché.*
4. reCAPTCHA v3 + Honeypot + Flood + consentement obligatoire. *Vérif : blocage bot / consentement manquant.*
5. Intégration page `partner` + thème + accessibilité. *Vérif : RGAA AA, conformité maquette.*
6. Export config (`drush cex`). *Vérif : réimport propre.*

## 9. Tests / boucle de feedback
- Navigateur + Mailhog/maillog (e-mails), `drush cr`, `npm run lint`.
- Cas d'erreur : requis manquants ; consentement décoché ; reCAPTCHA/honeypot ; échec e-mail.
- Test auto (à mettre en place) : Functional (BrowserTestBase) happy path + erreurs.

## 10. Décisions (tranchées)
1. ✅ **« Aménageur qualifié auto-école ? »** : **obligatoire**.
2. ✅ **Rétention 36 mois** (alignée sur le contact).

## Statut
- [x] Champs validés (specs + maquette)
- [x] E-mails, confirmation, réutilisation contact définis
- [x] Décisions tranchées (§10)
- [x] Implémenté (webform + type partner + page /devenir-partenaire) — vérifié
- [x] **Gabarits e-mail conformes — fait le 2026-08-21** ([ADR-022](../../.claude/decisions/022-gabarit-email-webform.md)) : mêmes conventions que le formulaire de contact (pas de ligne Statut, ce formulaire n'a pas de champ « Vous êtes » ; ligne Adresse ajoutée bien qu'absente de la maquette 810:10324/810:10435, les 4 champs adresse étant obligatoires).
