# ADR-026 : Profil partenaire (« Mes informations personnelles ») — modele de donnees et restriction du formulaire core

## Statut

Accepte

## Date

2026-08-25

## Contexte

Le PRD (F12) decrit une page « Mes informations personnelles » consultable/modifiable par un
partenaire connecte, avec une exception deja actee : « l'adresse de facturation est affichee
mais non modifiable en front ». Les champs exacts du profil restaient `[A PRECISER]`
(docs/PRD.md, section « Partenaire »).

Aucun champ metier (societe, telephone, SIRET, adresse...) n'existait sur l'entite User (seul
`user_picture`, champ image standard du profil, etait present) : toutes les infos collectees
par le webform `account_request` restent aujourd'hui uniquement dans la soumission Webform,
jamais reportees sur le compte cree manuellement en back-office. Aucun `FormBase` custom,
aucune route custom, n'existaient nulle part dans le code du projet avant cette tache.

Maquette : Figma 524:20069 (desktop). Elle reprend exactement les champs du webform
`account_request`, avec l'e-mail seul grise (lecture seule) — le reste (dont le bloc
« Votre entreprise ») visuellement identique aux champs modifiables.

## Options considerees

### Perimetre lecture seule

**Option A : lecture seule limitee a l'e-mail (litteral maquette)**
- Avantages : conforme pixel-pres a la maquette.
- Inconvenients : le SIRET et la raison sociale, qui engagent l'identite legale et les
  conditions commerciales du partenaire (cf. PRD F16, taux de remise lie au partenaire),
  deviendraient auto-modifiables sans controle — risque metier au-dela du simple ecart visuel.

**Option B (retenue) : lecture seule etendue a tout le bloc « Votre entreprise »**
- Avantages : coherent avec l'esprit de la contrainte PRD deja posee sur l'adresse de
  facturation (donnee d'entreprise pilotee par le back-office) ; evite qu'un partenaire modifie
  seul des donnees qui engagent la relation commerciale.
- Inconvenients : ecart assume vis-a-vis de la maquette (qui ne grise visuellement que
  l'e-mail) — les 6 champs du bloc entreprise recoivent le meme traitement visuel (fond grise)
  que l'e-mail, sans base dans la maquette elle-meme.

### Stockage des donnees

**Option A : garder les donnees uniquement dans la soumission Webform, sans champ User**
- Avantages : aucun nouveau champ a creer.
- Inconvenients : rend la demande impossible a satisfaire (la page doit **afficher** et
  **enregistrer** ces valeurs sur le compte) ; le lien soumission Webform <-> compte cree
  manuellement n'existe nulle part (creation manuelle, sans mapping automatique).

**Option B (retenue) : 10 nouveaux champs sur l'entite User (+ le champ `mail` core pour
l'e-mail)**
- Avantages : modele de donnees explicite, reutilisable par les futurs chantiers F13/F16
  (tableau de bord, gestion partenaires back-office).
- Inconvenients : ces champs doivent aussi etre exposes sur le formulaire back-office de
  creation/edition de compte (`/admin/people/*`) pour que l'admin les remplisse a la creation
  manuelle — sans quoi tout nouveau compte partenaire arriverait avec un profil vide.

## Decision

**Champs crees** (tous `string`, sauf `field_civility` en `list_string` madame/monsieur,
miroir des options du webform `account_request`) : `field_civility`, `field_first_name`,
`field_last_name`, `field_job_title`, `field_phone`, `field_siret` (max 14),
`field_company_name`, `field_company_address`, `field_address_complement`,
`field_postal_code` (max 5), `field_city`. Aucun `required: true` au niveau du `FieldConfig` :
le caractere obligatoire des 5 champs modifiables est impose par le formulaire
(`PersonalInformationForm`), pas par l'entite — un compte existant ou une action admin qui ne
renseigne pas (encore) ces champs ne doit pas etre bloquee.

**Perimetre modifiable** : Civilite / Prenom / Nom / Fonction / Telephone, enregistrables par
« Mettre a jour mes informations ». E-mail (`mail` core) + le bloc « Votre entreprise » en
entier restent en lecture seule (Option B ci-dessus) — `#attributes: readonly` (pas
`#disabled`, pour rester focalisables/lisibles par un lecteur d'ecran) sur les elements de
formulaire, **et** liste blanche explicite des 5 champs dans le submit handler (aucune
confiance dans les attributs cote client).

**⚠️ Restriction du formulaire core `/user/{uid}/edit`** (decouvert en verifiant les
permissions existantes, pas anticipe au plan initial) : ce formulaire (`user_form`,
`ProfileForm`) est accessible par defaut a tout utilisateur authentifie pour **son propre**
compte, independamment des permissions de role. Comme les 11 champs sont exposes sur
l'affichage de formulaire **par defaut** (necessaire pour l'admin), ce meme affichage est
aussi celui de l'auto-edition : sans intervention, un partenaire aurait pu contourner le
caractere lecture-seule impose par `PersonalInformationForm` en visitant directement cette
URL. `hook_form_user_form_alter()` (`drivematic_partner.module`) masque les 11 champs quand
`$form_state->getFormObject()->getEntity()->id() === currentUser()->id()` **et** que
l'utilisateur courant n'a pas la permission `administer users` — ils restent visibles/editables
quand un compte avec cette permission (aujourd'hui, seul `administrator`, bypass `is_admin` ;
`content_editor`/« Admin » ne l'a pas) edite le compte de quelqu'un d'autre.

**Route** : `/user/mes-informations-personnelles` (namespace `/user/*` existant plutot qu'un
nouveau `/partenaire/*`, choix utilisatrice), `_role: 'partenaire'` — access checker core
(`RoleAccessCheck`, module `user`), pas de permission a creer. Nouveau module
`drivematic_partner` (le premier a porter une route/`FormBase` custom sur ce projet — jusque
la, `drivematic_forms` ne fait que du `hook_form_alter()` sur des formulaires existants).

**Mot de passe** : « Modifier mon mot de passe » redirige vers `/user/password` (formulaire
core de demande de reinitialisation par e-mail, deja stylise pour `/user/login` via
`_user-pass-form.scss`), pas de champ mot de passe direct sur cette page — choix utilisatrice
plutot que `/user/{uid}/edit` (qui aurait aussi expose username/e-mail dans le meme
formulaire).

**⚠️ Navigation** : le menu `account` portait deja, avant cette tache, un squelette de 5 liens
cree a la main en back-office (Tableau de bord, Mes devis, **Mes informations
personnelles** [stub vers `/user`], Me deconnecter, Supprimer mon compte) — non detecte a la
phase de planification (contenu, pas de config exportee, invisible a un grep de
`config/sync`). Le lien existant a ete repointe vers la nouvelle route (URI mise a jour,
titre/poids/position conserves) plutot que d'en creer un doublon ; aucune logique de creation
de lien n'a ete laissee dans le module (ce contenu est gere a la main sur ce projet, comme les
4 autres liens du meme menu, tous encore des stubs `route:<nolink>`).

**Fondation SCSS** : `src/scss/_personal-information-form.scss`, meme derogation qu'ADR-015
§1 (markup FormBase, pas un composant), scopee a la classe auto-generee du formulaire
(`.drivematic-partner-personal-information-form`) plutot que de partager les selecteurs de
`_forms.scss` (webform-only) — meme raisonnement que `_user-login-form.scss`/
`_user-pass-form.scss`. Contrairement a ces deux precedents, ce formulaire porte sa propre
carte (comme un webform) : le padding de carte et l'espacement externe de page sont separes
sur deux couches (`.personal-information-page`, enveloppe `#prefix`/`#suffix` du `FormBase` /
`.drivematic-partner-personal-information-form`, la carte elle-meme), faute d'un champ de node
pour porter la premiere couche comme le fait `.field--type-webform`.

## Consequences

**Positif**
- Modele de donnees partenaire explicite, reutilisable par F13/F16.
- Aucun contournement possible du perimetre lecture-seule via le formulaire core.
- Page atteignable depuis le menu deja pose par l'utilisatrice, sans doublon.

**Negatif / vigilance**
- Les comptes partenaires deja crees (avant cette tache) ont ces 11 champs **vides** : rien ne
  les retro-remplit automatiquement (pas de mapping webform -> user, decision hors scope,
  cf. rapport d'exploration du plan). A signaler si des comptes reels existent deja en prod.
- Le perimetre lecture-seule (bloc entreprise entier) est **plus large** que le texte PRD
  actuel (qui ne mentionne que l'adresse de facturation) — a refleter dans le PRD au `/sync`.
- `hook_form_user_form_alter()` doit rester en phase avec `_drivematic_partner_profile_field_names()`
  si de nouveaux champs de profil sont ajoutes plus tard : toute liste de champs oubliee y
  resterait editable en auto-edition, recreant la meme faille que celle corrigee ici.

## Alternatives rejetees

Voir Options A ci-dessus (perimetre lecture seule reduit a l'e-mail seul ; absence de champs
User dedies).

## Addendum du 2026-08-25 : habillage et redirection de `/user/{uid}/edit`

Constat utilisateur : le lien de definition de mot de passe de l'e-mail d'activation
(`register_admin_created`) mene a `/user/{uid}/edit` (meme route `user_form`, avec un jeton
`pass-reset-token` qui dispense de saisir le mot de passe actuel) — page non stylisee, portant
encore `user_picture`/`language`/`timezone` (hors perimetre du profil partenaire), sans
redirection utile apres sauvegarde.

**Etendu dans le meme `hook_form_user_form_alter()`** (meme branche self-edit-sans-permission-elevee) :
- `user_picture`, `language`, `timezone` retires (comme les 11 champs de profil).
- `mail` passe en lecture seule (`#attributes: readonly`) — **decision utilisateur explicite**,
  pour rester coherent avec `PersonalInformationForm` : sans ce filtre, `/user/{uid}/edit`
  restait un moyen de contourner le caractere non modifiable de l'e-mail.
- Redirection post-sauvegarde vers `drivematic_partner.personal_information` plutot que la page
  de compte par defaut du cœur.

**⚠️ Piege rencontre** : `EntityForm::actions()` pose `$actions['submit']['#submit'] =
['::submitForm', '::save']` **directement sur le bouton**. Le bouton porte donc son propre
`#submit`, qui prime sur `$form['#submit']` pour l'element declencheur — une premiere tentative
d'ajouter la redirection via `$form['#submit'][] = ...` n'avait **aucun effet** (silencieux,
sans erreur). Corrige en ajoutant le callback directement sur
`$form['actions']['submit']['#submit'][]`.

**Nouvelle fondation** `src/scss/_user-edit-form.scss` (meme derogation ADR-015 §1), scopee a
`.user-form`, habillage minimal sur le modele de `_user-pass-form.scss` (pas de carte). Inclut
le style du widget core `password_confirm` (indicateur de force du mot de passe, classes
injectees par `user.theme.js` : `.password-strength`, `.password-strength__meter`,
`.password-strength__indicator`, `.password-confirm-message`).

Verifie via `curl` (session de session du navigateur automatise instable dans cet
environnement, meme piege que documente ailleurs sur ce projet) : soumission du formulaire ->
`303` avec `Location: /user/mes-informations-personnelles`, mot de passe effectivement change
(verifie via `PasswordInterface::check()`).
