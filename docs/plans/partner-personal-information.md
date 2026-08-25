# Plan — Page "Mes informations personnelles" (espace partenaire)

Statut : **livré le 2026-08-25** (commit `aa3be74`) — voir
`docs/archive/partner-personal-information-verification.md` pour la trace d'audit (inclut
l'addendum du même jour sur `/user/{uid}/edit`, styling + redirection post-mot de passe).
Décisions actées : lecture seule étendue à tout le bloc "Votre entreprise" + e-mail (pas
seulement l'adresse comme le PRD initial), `/user/{uid}/edit` restreint pour empêcher le
contournement de ces règles via le formulaire core partagé. Voir
[ADR-026](../../.claude/decisions/026-profil-partenaire-mes-informations.md).

Maquette desktop : Figma node 524:20069 (fichier `ZmmVBSOWSsHVkok6EU2Ays`).

Décisions actées avant implémentation :
- **Route** : `/user/mes-informations-personnelles` (reste dans le namespace `/user/*`, cohérent avec `/user/login`, `/user/password`, `/user/logout` — pas de nouveau namespace `/partenaire/*` inventé).
- **Mot de passe** : le bouton "Modifier mon mot de passe" redirige vers `/user/password` (formulaire core "mot de passe oublié"), pas de champ mot de passe dans ce formulaire.
- **Nouveaux champs User** : créés **et** exposés dans le formulaire back-office de création/édition de compte (`/admin/people/create` etc.), pour que l'admin puisse les remplir à la création manuelle d'un compte partenaire.
- **Lecture seule** : étendue à **tout le bloc "Votre entreprise"** (Siret, Raison sociale, Adresse, Complément, Code postal, Ville) **+ e-mail**. Seuls Civilité/Prénom/Nom/Fonction/Téléphone restent modifiables et enregistrables par le partenaire. Écart assumé vs la maquette (qui montre ces champs en fond blanc, non grisé) — à documenter en ADR.

## 1. Intention

Donner à un partenaire connecté (rôle `partenaire`) une page self-service reprenant les champs du webform `account_request`, pour qu'il puisse modifier ses coordonnées personnelles (civilité, prénom, nom, fonction, téléphone) et changer son mot de passe — sans pouvoir toucher à son e-mail ni aux données d'identité de son entreprise, qui restent pilotées par le back-office.

## 2. Fichiers impactés

**Nouveau module** `web/modules/custom/drivematic_partner/` :
- `drivematic_partner.info.yml`
- `drivematic_partner.routing.yml` — route `drivematic_partner.personal_information`, path `/user/mes-informations-personnelles`, `requirements: _role: 'partenaire'`.
- `drivematic_partner.module` — `hook_form_user_form_alter()` : masque les 10 nouveaux champs sur `/user/{uid}/edit` quand l'utilisateur édite **son propre** compte sans permission élevée (détail §4).
- `src/Form/PersonalInformationForm.php` (`FormBase`) — charge `User::load(\Drupal::currentUser()->id())`, affiche les 11 champs (10 custom + e-mail core), soumission ne persiste que les 5 champs modifiables.

**Nouveaux champs sur l'entité User** (config, aucun n'existe aujourd'hui hormis `user_picture`) :

| Champ | Type | Éditable par le partenaire |
|---|---|---|
| `field_civility` | `list_string` (madame/monsieur, miroir du `civilite` webform) | ✅ |
| `field_first_name` | `string` | ✅ |
| `field_last_name` | `string` | ✅ |
| `field_job_title` | `string` (fonction) | ✅ |
| `field_phone` | `string` | ✅ |
| `field_siret` | `string`, max 14 | lecture seule |
| `field_company_name` | `string` (raison sociale) | lecture seule |
| `field_company_address` | `string` | lecture seule |
| `field_address_complement` | `string` | lecture seule |
| `field_postal_code` | `string` | lecture seule |
| `field_city` | `string` | lecture seule |

E-mail → champ core `mail` existant, lecture seule. Pas de nouveau module contrib (pas de module `telephone` installé — `field_phone` en `string` simple).

Fichiers de config associés : `field.storage.user.<name>.yml` + `field.field.user.user.<name>.yml` ×10, et mise à jour de `core.entity_form_display.user.user.default.yml`.

**Thème** `web/themes/custom/drive_matic/` :
- Nouvelle fondation `src/scss/_personal-information-form.scss` (pattern `_user-login-form.scss`/`_user-pass-form.scss` — `_forms.scss` est scopé aux webforms, ADR-015 §1), déclarée dans `style.scss`.
- Nouveau SDC (carte grise arrondie 24px, sections "Vous êtes"/"Votre entreprise", grille 2 colonnes) enveloppant le rendu du `FormBase`, sur le modèle de `login-panel`.
- Template de page dédié si nécessaire (pattern `page--user-login.html.twig`).

**Navigation** : ajout d'un lien "Mes informations personnelles" dans le menu `account` (config `system.menu.account`), pointant vers la nouvelle route — sans construire le reste du sous-menu F12 (Tableau de bord, Mes devis, Supprimer mon compte), hors scope de cette tâche.

**Documentation** : `docs/E2E_SCENARIOS.md` (nouveau scénario), `docs/content-model.md` (nouveaux champs User), `.claude/decisions/026-...md` (nouvel ADR : modèle de données du profil partenaire + restriction du formulaire core self-edit), `README.md` en fin de session (`/sync`).

## 3. Interfaces publiques

- Route protégée `drivematic_partner.personal_information` (`_role: 'partenaire'`, vérifié côté serveur par le route access checker Drupal, pas seulement par un lien masqué).
- `Drupal\drivematic_partner\Form\PersonalInformationForm`.
- `hook_form_user_form_alter()` dans `drivematic_partner.module`.
- Aucun nouveau global JS (pas de comportement JS nécessaire — pas de champ mot de passe ici, contrairement à `login-panel`). Pas de mise à jour de la config du linter.

## 4. Sécurité

- **IDOR** : le formulaire opère toujours sur `\Drupal::currentUser()->id()`, jamais sur un uid pris dans l'URL/la requête.
- **Accès serveur** : gate au niveau routing (`_role: 'partenaire'`), pas une simple condition de visibilité de bloc/menu.
- **Persistance restreinte** : le submit handler ne lit/n'écrit explicitement que les 5 champs modifiables — même si les champs lecture-seule étaient trafiqués côté client, ils sont ignorés côté serveur.
- **⚠️ Point critique identifié en vérifiant les rôles existants** : `/user/{uid}/edit` (formulaire core `entity.user.edit_form`) est accessible par défaut à tout utilisateur authentifié pour son propre compte, indépendamment des permissions de rôle. Si les 10 nouveaux champs sont simplement ajoutés à l'affichage de formulaire par défaut (nécessaire pour que l'admin les remplisse via `/admin/people/create`), un partenaire pourrait contourner la restriction lecture-seule en éditant directement `/user/{uid}/edit`. D'où le `hook_form_user_form_alter()` : masque les 10 champs custom sur ce formulaire core quand `$form_state->getFormObject()->getEntity()->id() === \Drupal::currentUser()->id()` et que l'utilisateur courant n'a pas de permission élevée — les champs restent visibles/éditables uniquement quand un compte admin édite le compte de quelqu'un d'autre.
- CSRF natif (`FormBase`), échappement Twig natif, aucune requête SQL brute (Entity API), aucune donnée sensible exposée à l'anonyme, `t()` sur tous les libellés.
- Accessibilité des champs lecture seule : `readonly` (pas `disabled`) pour rester focusables/lisibles au clavier et par lecteur d'écran.

## 5. Risques et contraintes techniques

- **Cache** : formulaire Drupal (jeton CSRF) → non mis en cache par Dynamic Page Cache par défaut, pas de risque de fuite entre utilisateurs.
- **Écart assumé vs maquette 524:20069** : la maquette montre les champs "Votre entreprise" en fond blanc, pas grisés comme l'e-mail. Suite à la décision, ils seront stylés comme l'e-mail (fond `#e8e8e8`, lecture seule) — écart documenté (ADR), pas une réinterprétation silencieuse.
- **Fondation SCSS dédiée**, pas de réutilisation de `_forms.scss` (webform-only, ADR-015).
- **i18n** : tous les libellés via `t()`/`|t`.
- **Champ `field_phone` en `string` simple**, pas de module `telephone` — pas de validation de format stricte, comme le webform d'origine.
- **Champs requis** sur les 5 modifiables (civilité/prénom/nom/fonction/téléphone), miroir des règles `account_request`.
- **Pas de conflit de storage partagé multi-bundle** : User n'a qu'un seul bundle (`user`), contrairement au piège déjà rencontré sur les nodes (ADR-018).

## 6. Cohérence avec les spécifications

- Aligné avec PRD F12 ("consulter/modifier Mes informations personnelles", mot de passe via mécanisme dédié) — la restriction "entreprise + e-mail non modifiables" est plus conservatrice que la formulation F12 initiale ("adresse de facturation non modifiable" seulement), mais reste dans son esprit ; à refléter dans le PRD lors du `/sync`.
- Le PRD marquait les champs du "profil partenaire" `[A PRECISER]` (ligne 403, 567) — ce plan les fixe concrètement ; à lever au `/sync`.
- Nouveau parcours authentifié → entrée `docs/E2E_SCENARIOS.md`.
- Ne couvre pas F13 (tableau de bord) ni la suppression de compte — hors scope explicite de cette tâche.

## 7. Plan d'implémentation

```
1. [Content model] 10 nouveaux champs sur User (field.storage + field.field), ajout à
   core.entity_form_display.user.user.default.yml.
   → vérifier : drush cim, drush cst propre, champs visibles sur /admin/people/create.

2. [Sécurité back-office] hook_form_user_form_alter() dans drivematic_partner.module :
   masque les 10 champs sur l'auto-édition (/user/{uid}/edit pour son propre compte,
   sans permission élevée).
   → vérifier : en session partenaire, /user/{uid}/edit n'affiche plus les champs custom ;
   en session administrator, /admin/people/{uid}/edit les affiche toujours.

3. [Module + route + FormBase] drivematic_partner.routing.yml (path
   /user/mes-informations-personnelles) + PersonalInformationForm.
   → vérifier : la route → 403 en anonyme et pour un compte sans rôle partenaire, 200 pour
   un compte partenaire ; champs préremplis depuis le compte.

4. [Soumission] submit handler : persiste uniquement les 5 champs modifiables.
   → vérifier : modifier civilité/prénom/nom/fonction/téléphone, recharger → valeurs
   persistées ; tenter de trafiquer un champ lecture seule côté client → ignoré côté serveur.

5. [Bouton mot de passe] lien stylé vers /user/password (pas de submit).
   → vérifier au navigateur.

6. [Menu] lien "Mes informations personnelles" dans le menu account.
   → vérifier : visible en session partenaire, redirige correctement.

7. [SDC + SCSS] carte grise, 2 sections, grille responsive, styles readonly/disabled.
   → vérifier : mesures Figma (524:20069) comparées au rendu (getBoundingClientRect/
   getComputedStyle), npm run css + contrôle du .css généré.

8. [Qualité] npm run lint, npm run format:check, self-review (3 questions) dans
   docs/active/partner-personal-information/verification.md, nouvel ADR, entrée
   E2E_SCENARIOS.md, mise à jour content-model.md au /sync.
```

## 8. Stratégie de test et boucle de feedback

- Vérification manuelle (pas de suite automatisée définie sur ce projet) : navigateur en session anonyme / partenaire / administrator à chaque étape, `drush cr` après tout changement de hook/routing, `drush watchdog:show` après la migration de form display.
- Boucle la plus rapide : `drush cim` isolé sur les nouveaux champs avant le reste ; `drush cr` + rechargement pour le PHP/Twig ; `npm run css` + inspection du `.css` généré pour le SCSS.
- Cas d'erreur à tester en plus du happy path :
  - Anonyme sur `/user/mes-informations-personnelles` → 403.
  - Compte authentifié sans rôle `partenaire` (ex. `content_editor`) sur cette route → 403.
  - Partenaire visitant directement `/user/{uid}/edit` → champs custom absents.
  - Soumission avec un champ obligatoire vide (civilité/prénom/nom/fonction/téléphone) → erreurs de validation lisibles.
  - Deux partenaires différents → vérifier qu'aucune fuite de données de l'un vers l'autre (cache/formulaire).
