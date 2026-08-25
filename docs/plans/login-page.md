# Plan — Page de connexion `/user/login` (Espace partenaire)

Statut : approuvé le 2026-08-25. Décision actée : création du bundle `simple_form`
(migration du node "Devenir partenaire" depuis `partner`, puis suppression de `partner`).
Décision actée : 3 cartes CTA en desktop **et** en mobile (harmonisation, écart volontaire
avec la maquette mobile 602:33089 qui n'en montre que 2).

## 1. Intention

Habiller `/user/login` conformément aux maquettes Figma (472:12636 desktop, 602:33089 mobile)
et câbler les 4 parcours de navigation associés (créer un compte → nouvelle page "Demande de
création de compte", devenir partenaire, demander un devis, mot de passe oublié), pour les
~100 partenaires qui s'y connectent et les prospects orientés vers une demande.

## 2. Fichiers impactés

**Content model (nouveau)**
- `config/sync/node.type.simple_form.yml` (nouveau bundle, remplace l'usage de `partner`)
- `config/sync/field.field.node.simple_form.{body,field_webform,field_meta_tags}.yml`
- `config/sync/core.base_field_override.node.simple_form.title.yml`
- `config/sync/core.entity_form_display.node.simple_form.default.yml` + `core.entity_view_display.node.simple_form.default.yml` (copie conforme de la structure `partner`)
- `config/sync/pathauto.pattern.node_simple_form.yml`
- `config/sync/metatag.metatag_defaults.node__simple_form.yml`
- `config/sync/simple_sitemap.bundle_settings.default.node.simple_form.yml`
- Suppression, en fin de migration : `node.type.partner.yml` et tous ses `field.field.node.partner.*`, displays, pathauto, metatag defaults, sitemap setting.

**Migration de données**
- Un `hook_update_N()` qui : bascule le node "Devenir partenaire" de `partner` vers
  `simple_form`, crée le node "Demande de création de compte" (bundle `simple_form`,
  `field_webform` = webform `partner`), puis supprime le bundle `partner` désormais vide.

**Blocs (visibilité)**
- `config/sync/block.block.drive_matic_page_title.yml` — ajouter une condition `request_path` négative sur `/user/login`.
- `config/sync/block.block.drive_matic_primary_local_tasks.yml` et `..._secondary_local_tasks.yml` — même condition.

**Thème — préprocess**
- `web/themes/custom/drive_matic/drive_matic.theme` — nouvelle fonction
  `drive_matic_preprocess_page__user_login()` + helper `_drive_matic_simple_form_node_url(string $title)`
  sur le modèle exact de `_drive_matic_devis_cta_url()` (réutilisée telle quelle pour "Demander un devis").

**Thème — templates**
- `web/themes/custom/drive_matic/templates/layout/page--user-login.html.twig` (copie de `page.html.twig`, seule la zone `layout-content` change).
- Nouveau SDC `web/themes/custom/drive_matic/components/login-panel/` (`.component.yml`, `.twig`, `.scss`, `.js` pour la bascule mot de passe).
- Nouvelle fondation `web/themes/custom/drive_matic/src/scss/_user-login-form.scss` (habillage du `<form id="user-login-form">` core), déclarée dans `style.scss` avec commentaire de dérogation (addendum ADR-015).

**Icônes**
- `images/icons/eye-off.svg` (maquette) et `eye.svg` (état ouvert, à localiser/exporter séparément).

**Documentation**
- `docs/active/maquette-integration/progress.md:762-763` — retirer `partner` de la liste "alias en dur à faire".
- `docs/E2E_SCENARIOS.md` — nouveau scénario.
- `.claude/decisions/024-mutualisation-formulaire-simple.md` (nouvel ADR) + addendum à `.claude/decisions/015-habillage-des-formulaires.md`.
- `docs/content-model.md` — mise à jour du bundle #12, via `/sync` en fin de session.

## 3. Interfaces publiques

- Nouvelle route publique : alias Pathauto pour "Demande de création de compte" (ex. `/demande-de-creation-de-compte`), anonyme, même niveau d'accès que `/devenir-partenaire`.
- Aucune fonction JS globale exposée (`Drupal.behaviors.driveMaticLoginPanel`, IIFE + `once()`).
- Pas d'impact sur la config du linter (pas de nouveau global JS, pas de nouvel export cross-fichier).

## 4. Sécurité

- Authentification concernée mais pas modifiée : on stylise `user_login_form` (core) et on ajoute des liens autour ; validation/soumission inchangées.
- Décision PRD verrouillée respectée (`docs/PRD.md:36`, `user.settings.yml: register: admin_only`) : "Créer un compte" ne pointe jamais vers `/user/register`, il pointe vers la page de demande.
- Recherche des nœuds cibles par bundle + titre via l'API Entity Query, jamais par nid en dur ni SQL concaténé — même idiome que `_drive_matic_devis_cta_url()`.
- Aucune donnée sensible exposée à l'anonyme.
- ⚠️ À documenter (pas un risque sécu) : les soumissions "Demande de création de compte" arrivent dans le même webform `partner` que "Devenir partenaire" (décision explicite, temporaire) — mêmes destinataires/libellés pour les deux parcours. Noté dans l'ADR-024.

## 5. Risques et contraintes techniques

- Renommage de machine name impossible tel que formulé : approche retenue = créer `simple_form`,
  migrer le node existant via `hook_update_N`, supprimer `partner` une fois vide.
- `docs/active/maquette-integration/progress.md` classait `partner` parmi les types à alias en dur : classement caduc dès que le bundle devient multi-instance (Pathauto doit rester actif) — à corriger dans le même mouvement.
- Effet de bord `.block-local-tasks-block` : les blocs de tâches locales n'ont aucune condition de visibilité et s'appliqueraient sur `/user/login` (onglets core "Log in"/"Reset your password") — à vérifier au navigateur avant l'intégration visuelle, corrigé par condition de bloc.
- Titre de page en double : `drive_matic_page_title` n'est masqué que pour 3 bundles de node ; sur une route système sans contexte node il reste visible par défaut → même correctif de condition de bloc.
- Formulaire core hors du périmètre de `_forms.scss` (ADR-015, scopé à `.webform-submission-form`/`.field--type-webform`) : nouvelle fondation dédiée plutôt qu'élargissement silencieux du sélecteur existant, documentée en addendum, réutilisant les tokens `--dm-form-*`.
- Icône "eye" (mot de passe visible) potentiellement manquante : la maquette n'expose que "Eye-off" sur les deux node-ids fournis — à localiser dans le fichier Figma avant d'implémenter la bascule JS.
- Espacement/gabarit : carte grise = `--dm-content-column` (900px, défaut du token, aucun retunage nécessaire) centrée en `padding-inline`. Les 3 cartes CTA suivent la même colonne, réparties en 3 (desktop et mobile, décision d'harmonisation).
- Accessibilité : bouton de bascule mot de passe avec `aria-label`/`aria-pressed` traduisibles, contraste #666 sur fond blanc/gris clair à vérifier (WCAG AA), CTA et lien "FAIRE UNE DEMANDE" en `<a>` natifs.
- i18n : tous les libellés passent par `t()`/`|t` — y compris la reproduction littérale de l'incohérence de maquette ("Demander un devis" au header vs "Demandez un devis" sur la carte auto-école), à reproduire telle quelle sans corriger.

## 6. Cohérence avec les spécifications

- Respecte la décision verrouillée PRD "comptes partenaires créés uniquement en back-office" (pas d'auto-inscription).
- Nouveau parcours utilisateur public ("Demande de création de compte") → entrée dans `docs/E2E_SCENARIOS.md`.
- Ne touche pas à l'espace partenaire authentifié (F12-F16, hors scope, rôle `partner` non implémenté).

## 7. Plan d'implémentation

```
1. [Content model] Créer le bundle `simple_form` (config complète, copie fidèle de la structure `partner` :
   onglets field_group, displays, base_field_override title, pathauto `/[node:title]`, metatag defaults, sitemap)
   → vérifier : `drush cim` sans erreur, `drush cst` propre.

2. [Migration] hook_update_N : bascule "Devenir partenaire" (partner → simple_form), création de
   "Demande de création de compte" (simple_form, field_webform = webform `partner`), suppression du
   bundle `partner` vidé.
   → vérifier : `drush updb`, `/devenir-partenaire` toujours 200 (même nid), nouvelle page accessible,
   plus aucun node de type `partner` en base.

3. [Doc] Retirer `partner` de la liste "alias en dur à faire" dans progress.md.
   → vérifier : relecture du fichier.

4. [Header] Rien à faire — "Espace partenaire" pointe déjà vers `path('user.login')` en anonyme
   (site-header.twig), confirmé par l'exploration.
   → vérifier : test manuel en session anonyme.

5. [Blocs] Conditions de visibilité (request_path négatif sur /user/login) sur page_title et les 2
   local_tasks.
   → vérifier au navigateur, anonyme, AVANT de styliser : plus de boîte flottante ni de double titre.

6. [Icônes] Localiser/exporter eye.svg + eye-off.svg (masque CSS, convention icônes).
   → vérifier : rendu correct en `mask` + `background-color: currentcolor`.

7. [Template + préprocess] page--user-login.html.twig (copie de page.html.twig, zone contenu remplacée),
   préprocess dédié calculant les 3 URLs cibles via `_drive_matic_simple_form_node_url()` /
   `_drive_matic_devis_cta_url()`.
   → vérifier : page complète sans CSS, les 3 liens résolvent vers les bonnes URLs (inspection DOM).

8. [SDC login-panel + fondation _user-login-form.scss] Habillage complet mobile-first puis
   @media (width >= 992px), bascule JS mot de passe, cartes CTA (3 partout), lien "FAIRE UNE
   DEMANDE" (même cible que "Créer un compte").
   → vérifier : mesures Figma (get_design_context / get_metadata) comparées au rendu
   (getBoundingClientRect/getComputedStyle) sur les deux breakpoints, `npm run css` + contrôle du .css généré.

9. [Qualité] npm run lint, npm run format:check, self-review (3 questions) dans
   docs/active/login-page/verification.md, ADR-024 + addendum ADR-015, entrée E2E_SCENARIOS.md.
```

## 8. Stratégie de test et boucle de feedback

- Vérification par étape essentiellement manuelle (pas de suite de tests automatisés définie pour ce projet) — navigateur en session anonyme à chaque étape, `drush watchdog:show` pour détecter toute erreur PHP silencieuse après la migration de bundle.
- Boucle la plus rapide : `drush cr` + rechargement navigateur pour le template/CSS ; `drush updb` isolé sur la base locale avant toute chose.
- Cas d'erreur à tester en plus du happy path :
  - Recharger `/devenir-partenaire` après la migration (pas de doublon d'alias).
  - Soumettre le formulaire de connexion avec des identifiants invalides (message d'erreur core lisible dans la nouvelle mise en page).
  - Naviguer vers `/user/login` déjà connecté (bascule du SDC `site-header` sur le menu compte).
  - Mobile < 768px et desktop ≥ 992px : les 3 cartes CTA s'affichent bien aux deux gabarits.
