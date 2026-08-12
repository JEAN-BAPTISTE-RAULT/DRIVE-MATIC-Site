# Plan — Webform Contact (F10)

> Plan d'implementation. Base : specs Passerelle §2.14 + maquettes Figma (nodes 433-7637 devis, 438-9060 SAV, 438-9465 question). Anti-spam = **reCAPTCHA v3** (choix utilisatrice).
>
> ⚠️ **Prerequis bloquant** : necessite le **socle** (Drupal 11 installe + sous-theme + modules) avant implementation. Ce plan est pret-a-implementer une fois le socle en place.

## 1. Intention
Permettre a un visiteur anonyme d'envoyer une **demande de devis**, une **demande de SAV** ou une **question** via un webform unique a champs conditionnels, avec **stockage en back-office + e-mails** (demandeur + `info@drivematiclegrand.com`).

## 2. Champs (validés specs + maquettes)

### Bloc commun — Identité (toujours visible)
| Champ | Element Webform | Requis | Détails |
|-------|-----------------|--------|---------|
| Vous êtes | `select` | ✅ | concession / auto-école / taxi / particulier |
| Nom de l'entreprise | `textfield` | ✅ *sauf « particulier »* | required conditionnel (`#states`) |
| Adresse | `textfield` | ✅ | |
| Complément d'adresse | `textfield` | — | |
| Code postal | `textfield` | ✅ | placeholder « Ex : 13600 », validation 5 chiffres |
| Ville | `textfield` | ✅ | |
| Civilité | `select` | ✅ | **Madame / Monsieur** |
| Prénom | `textfield` | ✅ | |
| Nom | `textfield` | ✅ | placeholder « Votre nom » |
| Téléphone | `tel` | ✅ | validation format |
| E-mail | `email` | ✅ | placeholder « Ex : monemail@gmail.com » |
| **Votre demande concerne** | `select` | ✅ | devis / sav / question — pilote la suite |

### Champs conditionnels (`#states` sur « Votre demande concerne »)
| Champ | Element | Requis | Visible si |
|-------|---------|--------|-----------|
| Marque | `select` (taxo `vehicle_brand`) | ✅ | devis **ou** sav |
| Modèle | `select` (taxo `vehicle_model`, filtré par marque) | ✅ | cascade JS Marque→Modèle |
| Motorisation | `select` (taxo `motorisation`, filtré par modèle) | ✅ | cascade JS Modèle→Motorisation (BVM/BVA/hybride/électrique selon dispo) |
| N° de châssis | `textfield` | ✅ | devis **ou** sav — infobulle « carte grise » (aide accessible) |
| Type de châssis | `textfield` | ✅ | **devis uniquement** — infobulle |
| Document | `managed_file` | — | **sav uniquement** — jpg/pdf, 5 Mo, 1 max, supprimable, **stockage privé** |
| Message | `textarea` | ✅ | dès qu'un cas est choisi (les 3) — placeholder « Description de votre message » |

### Référentiel véhicules (partagé)
Marque / Modèle / Motorisation proviennent d'un **référentiel véhicules réutilisable** (importé de `Drive_Matic_modeles.xlsx` : 31 marques, 124 modèles), **partagé avec le configurateur** (F14/F17). Modélisation :
- `vehicle_brand` — marque (31 termes).
- `vehicle_model` — modèle (124 termes) ; champ parent → `vehicle_brand` + champ multi `motorisation` (motorisations disponibles).
- `motorisation` — 4 termes : manuelle (BVM), automatique (BVA), hybride, électrique.

Cascade front (JS) : map `make→models` et `model→motorisations` passée via `drupalSettings` ; dégradation sans JS (listes complètes). **Nettoyage à l'import** : cellules marque fusionnées, lignes vides, espaces parasites. ⚠️ L'Excel prime sur les specs (4 motorisations vs 3). → Ce référentiel mérite son propre pas de modélisation/import (à cadrer avec le chantier configurateur).

### Bloc final (toujours)
- `checkbox` **consentement** (✅) : « J'autorise ces informations à être utilisées par Drive Matic Legrand »
- **reCAPTCHA v3** (invisible, score-based) — n'apparaît pas sur les maquettes, normal
- Bouton **Envoyer**
- Mention « *Champs obligatoires »

## 3. reCAPTCHA v3
- Modules `captcha` + `recaptcha` (+ `recaptcha_v3` pour le mode score).
- **Clés fournies** : clé **site** (publique → config) ; clé **secrète** → **variable d'environnement / `settings.local.php` gitignoré, jamais commitée**.
- Invisible ; seuil de score configurable ; fallback en cas d'échec.
- **RGPD (F18)** : charge des scripts Google → à intégrer à la CMP/consentement + politique de confidentialité.

## 4. Handlers e-mail (6, conditionnels sur « Votre demande concerne »)
Chaque cas envoie 2 e-mails ; expéditeur **`no-reply@drivematiclegrand.com`**, gabarits HTML conformes aux specs (logo + récap).

| Cas | Objet | Destinataires |
|-----|-------|---------------|
| devis | « Drive Matic Legrand : demande de devis » | `[email]` demandeur + `info@drivematiclegrand.com` |
| sav | « Drive Matic Legrand : SAV » | demandeur + `info@` (joint le document si présent) |
| question | demandeur : « …: votre question » / interne : « …: question » | demandeur + `info@` |

Contenu : récap identité + champs du cas (cf. specs §2.14). Échapper toutes les valeurs (Twig autoescape) ; `From`/`Reply-To` non construits depuis des entrées libres (anti header-injection).

## 5. Fichiers impactés (socle en place)
- **Config Webform** : `webform.webform.contact.yml` (éléments, `#states`, handlers).
- **Module custom** `web/modules/custom/drivematic_forms/` : `js/vehicle-select.js` + `*.libraries.yml` (cascade marque→modèle, `Drupal.behaviors` + `once`), hook d'injection des options, templates e-mail Twig.
- **Type `contact`** (ADR-002) : référence le webform ; contenu page = coordonnées + carte (image sans crop).
- **Thème** : styles du formulaire (fondations + SDC éventuel), infobulle « carte grise » accessible.
- **Config modules** : webform, captcha, recaptcha(_v3), honeypot, flood.
- **Taxonomies véhicules** : `vehicle_brand`, `vehicle_model`, `motorisation` + script/config d'import depuis `Drive_Matic_modeles.xlsx` (avec nettoyage). Partagées avec le configurateur.

## 6. Sécurité
- Public anonyme → **Honeypot + reCAPTCHA v3 + Flood control**.
- **RGPD** : consentement obligatoire ; **rétention = suppression auto des soumissions après 36 mois** ; accès soumissions réservé aux admins ; droit à l'effacement.
- **Upload SAV** : système de fichiers **privé**, jpg/pdf, 5 Mo, 1 fichier, validation extension **+ mime**.
- **XSS / injection e-mail** : autoescape, pas de `|raw`, en-têtes non issus d'entrées libres.
- CSRF : token Webform natif. Pas de donnée partenaire (formulaire public), soumissions non exposées.

## 7. Risques et contraintes
- **Socle** requis avant implémentation (bloquant).
- **Référentiel Marque/Modèle** partagé avec le configurateur (chantier partenaire/devis) — cf. §Décisions.
- **Cascade marque→modèle** : JS custom via `drupalSettings`, **dégradation sans JS** (select complet). Motorisation statique.
- **Cache** : page anonyme avec formulaire → token géré par placeholder/BigPipe.
- **Délivrabilité** : `no-reply@…` + SPF/DKIM (infra, en attente).
- **Accessibilité AA** : labels, requis, erreurs, infobulle clavier/ARIA.
- **i18n** : FR only, chaînes via `t()` / config translation.

## 8. Cohérence specs / PRD
- Implémente **F10** ; type `contact` (ADR-002) ; « soumissions stockées + e-mail » ; touche **F18** (captcha/consentement).
- Ne contredit aucune décision verrouillée. « Demande de devis » publique ≠ devis partenaire (configurateur).
- **E2E** : couvert par **S8** (devis), **S9** (SAV + PJ), **S10** (question).

## 9. Étapes d'implémentation
0. *(Prérequis)* Socle + modules installés.
1. Webform `contact` : bloc identité + « demande concerne » + consentement + submit. *Vérif : affichage, soumission stockée, e-mail basique.*
2. `#states` conditionnels (champs par cas ; « particulier » → entreprise non requise). *Vérif : show/hide + required.*
3. Marque/modèle/motorisation + cascade JS. *Vérif : modèles filtrés ; dégradation sans JS.*
4. Upload SAV (privé, jpg/pdf, 5 Mo, 1, supprimable) + validations. *Vérif : upload OK ; rejets format/taille/2e fichier.*
5. Handlers e-mail (6) conditionnels + gabarits conformes. *Vérif : bons e-mails par cas.*
6. reCAPTCHA v3 + Honeypot + Flood + consentement obligatoire. *Vérif : blocage bot / consentement manquant.*
7. Intégration page `contact` + thème + accessibilité + infobulles châssis. *Vérif : RGAA AA, conformité maquette.*
8. Export config (`drush cex`) + doc. *Vérif : réimport propre.*

## 10. Tests / boucle de feedback
- **Boucle rapide** : navigateur + Mailhog/maillog (e-mails), `drush cr`, DevTools (cascade JS), `npm run lint`.
- **Vérif manuelle par cas** : devis / sav / question → stockage + e-mails + états conditionnels.
- **Tests auto** (à mettre en place) : Functional (BrowserTestBase) happy path + erreurs ; Nightwatch pour la cascade JS.
- **Cas d'erreur** : requis manquants ; particulier sans entreprise ; fichier mauvais format/taille ou 2ᵉ ; consentement décoché ; reCAPTCHA/honeypot déclenché ; échec e-mail.

## 11. Décisions (tranchées)
1. ✅ **Marque / Modèle / Motorisation** = **référentiel véhicules réutilisable** (taxonomies importées de l'Excel), partagé avec le configurateur.
2. ✅ **Expéditeur** : `no-reply@drivematiclegrand.com`.
3. ✅ **Rétention** : suppression auto des soumissions après **36 mois**.
4. ✅ **reCAPTCHA v3** : clés fournies (site en config ; secrète en env, hors dépôt).
5. ✅ **Logo e-mail** : géré globalement au niveau du thème.

## Statut
- [x] Champs validés (specs + maquettes)
- [x] reCAPTCHA v3, handlers e-mail, upload définis
- [x] Décisions tranchées (§11)
- [ ] Référentiel véhicules à modéliser/importer (partagé configurateur)
- [ ] Implémentation (après socle)
