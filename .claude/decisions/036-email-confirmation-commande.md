# ADR-036 : E-mail de confirmation de commande via hook_mail() + mailer_policy

## Statut
Accepte

## Date
2026-09-01

## Contexte
Au clic « Commander » (`DeliveryForm::orderSubmit()`, jamais « Enregistrer
le devis »), le partenaire doit recevoir un e-mail de confirmation reprenant
le gabarit deja utilise pour l'e-mail « Votre compte personnel »
(`mailer_policy.mailer_policy.user.register_admin_created.yml`) : logo,
salutation, corps, signature, mention legale. Seule variable dynamique :
le numero de devis (`Quote::reference`), connu uniquement au moment de
l'envoi.

Aucun `hook_mail()` custom n'existait encore dans ce codebase — les e-mails
webform (F10/F11, contact/partner) passent par le mecanisme propre de
Webform, independant de `hook_mail()`/mailer_policy. Celui-ci est le
premier module custom du site a envoyer un e-mail transactionnel hors
Webform.

## Options considerees

### Option A : construire le HTML final directement dans le code PHP
- Avantages : simple a ecrire, aucune dependance au pipeline
  `mailer_policy`/Token.
- Inconvenients : duplique en dur un gabarit deja gere ailleurs par
  `mailer_policy` (le contenu HTML devient alors non modifiable depuis
  `/admin/config/system/mailer-policy` comme tous les autres e-mails
  transactionnels du site) ; incoherent avec le seul precedent existant
  (`user.register_admin_created`).

### Option B (retenue) : `hook_mail()` + `mailer_policy` + jeton `quote` custom
- Avantages : reproduit exactement le mecanisme deja en place et deja
  verifie en profondeur (code source de `mailer_override`/`mailer_policy` :
  `LegacyMailer`/`LegacyOverride` fonctionnent generiquement pour n'importe
  quel module avec un `hook_mail()` classique, pas seulement `user`) ; le
  gabarit final reste editable en back-office comme tous les autres ;
  coherent avec ADR-022.
- Inconvenients : necessite de comprendre un pipeline en plusieurs etapes
  (hook_mail → override mailer_policy → resolution de jeton apres coup via
  `ReplacementEmailProcessor`) avant de l'utiliser correctement.

## Decision
Option B. `drivematic_configurator_mail()` fournit un texte de repli en
clair (utilise si le mailer_policy est desactive) et alimente
`$message['params']['token_data']['quote']`. La resolution de
`[quote:reference]` dans le gabarit HTML passe par un
`hook_token_info()`/`hook_tokens()` custom (type `quote`, jeton
`reference`) — API Token de Drupal **core**, pas le module contrib `token`
(dont le comportement generique pour une entite custom n'a pas ete
verifie avec certitude, et n'aurait ajoute aucune simplicite reelle pour un
seul jeton).

Un echec d'envoi (SMTP, etc.) est capture (`try/catch` autour de l'appel
`MailManagerInterface::mail()`, erreur journalisee) : la commande est deja
persistee en base a ce moment, un probleme mail ne doit jamais faire
echouer la confirmation de commande.

## Piege rencontre en cours d'implementation

`BodyEmailAdjuster` (mailer_policy) sauvegarde tout corps HTML DEJA present
sur l'e-mail dans une variable Twig nommee `body`, relue par le gabarit
d'habillage par defaut (`email-wrap.html.twig`). Puisque `hook_mail()` est
TOUJOURS invoque (chemin `LegacyMailer`, contrairement a `user.*` qui passe
par `UserMailer` et n'appelle jamais `hook_mail()`), y poser un corps dans
`$message['body']` ecrasait silencieusement le gabarit configure par ce
texte de repli. Diagnostique par instrumentation temporaire du code contrib
(jamais committee, `web/modules/contrib/` n'etant pas suivi par git sur ce
projet). Fix : le texte de repli passe par `$message['plain']` (alternative
texte du MIME) uniquement, jamais `$message['body']`. Detail complet :
[[mailer-policy-legacy-body-collision]] (memoire auto).

## Consequences
- Nouveau type de jeton Drupal sitewide : `quote:*` (reference, et depuis
  la copie interne — raison-sociale/adresse/complement/code-postal/ville/
  civilite/prenom/nom/email/telephone). Reutilisable pour de futurs e-mails
  du configurateur (ex. F15, PDF) sans nouvelle plomberie.
- Le contenu HTML de ces e-mails est desormais editable depuis
  `/admin/config/system/mailer-policy`, comme tous les autres e-mails
  transactionnels du site.
- **Mise a jour du 2026-09-02** : copie interne implementee (destinataire
  Drive Matic Legrand, cle `quote_ordered_internal`, meme gabarit que les
  notifications internes existantes — logo, Bonjour, intro, `<h3>Demandeur
  </h3>`, bloc identite, À bientot). Envoyee independamment de l'e-mail
  partenaire (`sendInternalOrderNotification()`, propre `try/catch`) —
  l'echec de l'une ne bloque jamais l'autre ni la confirmation de commande.
  Destinataire temporaire `audrey@passerelle.com`, comme toutes les autres
  notifications internes du site (a restaurer sur
  `info@drivematiclegrand.com` avant la mise en prod).
  ⚠️ **Piege NULL rencontre en testant** : `t()` rejette (`TypeError`) un
  placeholder dont la valeur est `NULL` (ex. `field_phone`/
  `billing_complement` vides sur un compte reel) — chaque valeur passee en
  `@placeholder` dans `hook_mail()`, et chaque valeur retournee par
  `hook_tokens()`, doit etre explicitement `(string)`-castee. Sans ce cast,
  l'envoi echoue en 500 pour tout partenaire dont un champ optionnel est
  vide — pas un cas rare.
- **Perimetre volontairement reduit** (decide avec l'utilisatrice) : le PRD
  (§359, F15) decrit ces e-mails avec un PDF du devis joint. Non implemente
  ici. Un futur ajout de piece jointe passera par
  `$message['params']['attachments']` (deja supporte generiquement par
  `LegacyMailerHelper::emailFromArray()`, verifie en lisant son code).
- Fichiers impactes : `drivematic_configurator.module`, `DeliveryForm.php`,
  `mailer_policy.mailer_policy.drivematic_configurator.quote_ordered.yml`,
  `mailer_policy.mailer_policy.drivematic_configurator.quote_ordered_internal.yml`.
