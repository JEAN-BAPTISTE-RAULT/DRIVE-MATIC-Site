# ADR-025 : Rôles back-office (Admin/Partenaire) et e-mail d'activation via mailer_override

## Statut

Accepte

## Date

2026-08-25

## Contexte

Suite à la confirmation des specs (p.28, "Espace partenaires") : les comptes partenaires sont
créés manuellement par Drive Matic en back-office après étude d'une demande (webform
`account_request`/`partner`), jamais automatiquement. Il fallait :

1. Un rôle pour les personnes du client qui gèrent le contenu (sans accès à l'administration
   système : utilisateurs, rôles, modules, config).
2. Un rôle "Partenaire" purement authentifiant, sans aucun accès back-office.
3. Un e-mail d'activation de compte (lien de définition du mot de passe, 72h) conforme à la
   maquette Figma 810:10544, alors que Drupal core n'envoie ce type d'e-mail qu'en texte brut.

## Options considerees

### E-mail d'activation

**Option A (retenue) : activer `mailer_policy` + `mailer_override` (sous-modules de
`symfony_mailer`, deja vendorises, pas encore actives)**
- Avantages : reutilise une infrastructure deja presente sur le disque plutot que d'en batir
  une nouvelle ; l'edition du contenu HTML se fait ensuite comme une config classique
  (`mailer_policy.mailer_policy.user.register_admin_created`), pas de code PHP a maintenir.
- Inconvenients : convertit **tous** les e-mails `user.mail.*` (password_reset,
  status_blocked, cancel_confirm, etc.) au nouveau mecanisme en un seul geste (le plugin
  `UserOverride` couvre tout le module `user`, pas un sous-type a la fois) — pas de
  regression de contenu (les 8 autres emails recuperent leur texte francais actuel, juste
  enveloppe en HTML basique), mais la surface d'effet depasse le seul e-mail demande.

### Option B : `hook_mail_alter()` custom

- Avantages : portee strictement limitee au seul e-mail vise.
- Inconvenients : reinvente un mecanisme de formatage HTML (sur le modele de
  `WebformPhpMail::format()`) alors que le projet a deja `symfony_mailer` installe pour ça.

## Decision

**Option A.** Activation de `mailer_policy` + `mailer_override`, puis
`OverrideManagerInterface::action('user', 'import')` (une seule action : elle active *et*
importe en un temps — ne jamais enchainer avec `action('user', 'enable')`, qui **reinitialise**
la policy aux valeurs generiques du module et efface l'import, piege rencontre en seance).

Contenu de `mailer_policy.mailer_policy.user.register_admin_created` reecrit selon la maquette
810:10544 (meme gabarit HTML inline que les e-mails webform, ADR-022), token
`[user:one-time-login-url]` resolu nativement dans le corps HTML (confirme par test reel via
Mailpit).

`user.settings.password_reset_timeout` : 86400 → 259200 (72h). Reglage global Drupal (pas de
granularite par type d'e-mail) : impacte aussi le "mot de passe perdu" classique — accepte
explicitement, aucun autre usage de ce flux sur le site.

### Rôles

- **Super admin** = role `administrator` existant (`is_admin: true`), aucune modification.
- **Admin** = role `content_editor` existant, **etoffe** (pas de nouveau role, pour eviter deux
  roles qui se recoupent) : label renomme "Admin" (id technique `content_editor` conserve, meme
  contrainte que le renommage de bundle en ADR-024), permissions CRUD (`create`/`edit any`/
  `delete any`) ajoutees pour les 16 types de contenu existants, plus gestion des medias
  (`create/update any/delete any media`, `access media overview`) et `delete terms in tags`.
  Ajoute egalement en `view_any` sur les 3 webforms (`partner`, `contact`, `account_request`)
  pour pouvoir consulter les demandes et decider de la creation d'un compte — necessaire au
  workflow mais non explicitement demande au depart, signale et valide en cours de tache.
  Aucune permission d'administration systeme (utilisateurs, roles, modules, configuration, vues).
- **Partenaire** (nouveau role `partenaire`) : **aucune permission**, purement authentifiant.

## Consequences

**Positif**
- Contenu du back-office desormais gerable par un role dedie sans exposer l'administration
  systeme.
- L'e-mail d'activation suit le meme gabarit visuel que le reste du site (coherence ADR-022),
  sans code PHP custom a maintenir — juste de la config.

**Negatif / vigilance**
- ⚠️ **Aucun controle d'acces cote code n'existe encore sur le configurateur ni un
  "dashboard"** (verifie : aucun `_custom_access` ni permission dans le routing des modules
  custom). Le role `partenaire` ne cloisonne donc rien par lui-meme — c'est le chantier F12
  (deja `[INFERE]` dans `docs/PRD.md`), explicitement hors scope de cette tache.
- Activer `mailer_override` bascule **tous** les e-mails `user.mail.*` vers le nouveau
  mecanisme, pas seulement `register_admin_created`. Si un futur besoin touche un de ces 8
  autres e-mails (ex. `password_reset`), il faudra editer sa policy `mailer_policy.mailer_policy
  .user.<sub_type>`, plus le fichier `user.mail.yml` d'origine (devenu inerte pour l'envoi mais
  laisse en config pour reference/rollback).
- Le module `mailer_override`'s commande drush (`mailer:override`) a un bug PHP 8.3
  (`preg_replace()` appele sur un objet `TranslatableMarkup`) — contourner en appelant le
  service `Drupal\mailer_override\OverrideManagerInterface` directement plutot que la commande
  drush, jusqu'a une eventuelle mise a jour du module.

## Alternatives rejetees

Voir Option B ci-dessus (hook_mail_alter).
