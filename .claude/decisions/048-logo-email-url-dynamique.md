# ADR-048 : URL du logo e-mail rendue dynamique (`[site:url]`)

## Statut

Accepté

## Date

2026-09-07

## Contexte

ADR-022 avait fixé le logo des 8 e-mails transactionnels (+ 2 gabarits
`mailer_policy` ajoutés depuis, ADR-036/042) en `<img>` avec une URL
absolue codée en dur : `https://www.drivematiclegrand.com/themes/custom/
drive_matic/images/logo-drive-matic-legrand-email.png`. Nécessaire (un
e-mail ne peut pas résoudre de chemin relatif), mais cette URL pointe vers
le domaine de **production finale**, qui ne sert pas encore ce site
aujourd'hui (préprod sur `drivematic.passerelle.com`, cf. [[trusted-host-patterns-preprod]]).
Conséquence vérifiée : `www.drivematiclegrand.com` répond bien (HTTP 200)
mais sert un **autre site** (Apache/PHP 7.3, Varnish — pas cette
installation Drupal) ; le même chemin sur le domaine de préprod sert
correctement l'image (`image/png`, 84 948 octets). Logo cassé sur tout
e-mail envoyé avant le bascule DNS définitif.

Signalé par l'utilisatrice, qui a demandé une URL dépendante de
l'environnement (même esprit que le transport SMTP/la clé reCAPTCHA —
[[smtp-preprod-transport]]).

## Options considérées

### Reproduire le pattern `settings.php` (comme SMTP/reCAPTCHA)

Écartée : ce pattern surcharge une **valeur scalaire unique** par
environnement. Ici, l'URL est une sous-chaîne noyée dans un bloc HTML
complet, dupliqué dans **13 endroits** (3 `mailer_policy` + 10 handlers
webform). Une surcharge par environnement obligerait à dupliquer
l'intégralité de chaque corps HTML dans `settings.php`, par environnement —
toute future édition du texte devrait alors être répercutée à 2 endroits
(config versionnée + surcharge), risque de divergence silencieuse bien plus
élevé que pour un secret scalaire.

### Token core `[site:url]`, résolu à l'envoi (retenue)

Les corps HTML utilisent déjà la résolution de jetons (`\Drupal::token()->
replace()`, confirmé par l'usage existant de `[webform_submission:values:x]`
et `[quote:reference]` dans ces mêmes gabarits). `[site:url]` est un jeton
**core**, sans dépendance ni code custom : il résout l'URL absolue de la
page d'accueil du site courant, dérivée de la requête HTTP réelle au
moment de l'envoi — donc automatiquement `drivematic.passerelle.com` en
préprod, `www.drivematiclegrand.com` une fois le DNS basculé, sans aucune
configuration par environnement à maintenir.

## Décision

Remplacement, dans les 13 occurrences (3 `mailer_policy` + 10 handlers
webform), de `https://www.drivematiclegrand.com/themes/custom/drive_matic/
images/logo-drive-matic-legrand-email.png` par `[site:url]themes/custom/
drive_matic/images/logo-drive-matic-legrand-email.png` (`[site:url]` se
résout avec un `/` final, vérifié — pas de double slash).

Appliqué via l'API de configuration (`\Drupal::configFactory()->
getEditable(...)->set(...)->save()`), jamais un YAML édité à la main —
cf. piège rencontré la session précédente ([[drush-cex-scope-drift]]) : un
fichier `config/sync/*.yml` modifié directement sans passer par la config
active est silencieusement écrasé par le `drush cex` suivant.

## Conséquences

- **Positif** : le logo fonctionnera automatiquement en préprod dès
  maintenant, et en prod sans aucun changement de code au bascule DNS.
  Un seul endroit (le chemin du fichier image) à maintenir si le nom du
  fichier logo change un jour ; le domaine ne dépend plus jamais d'une
  chaîne codée en dur.
- **Découverte incidente pendant l'isolement de ce changement** : la
  config active portait déjà, avant cette session, `from_mail`/`system.
  site.mail` sur `info@drivematiclegrand.com` (au lieu de `no-reply@`),
  sur system.site.yml ET les 10 mêmes handlers webform — jamais exporté.
  Confirmé volontaire par l'utilisatrice, exporté et commité dans le même
  mouvement (sans lien avec le token du logo, simple coïncidence
  d'emplacement).
- Fichiers modifiés : `mailer_policy.mailer_policy.drivematic_configurator.
  quote_ordered.yml`, `..._internal.yml`, `mailer_policy.mailer_policy.user.
  register_admin_created.yml`, `webform.webform.contact.yml`,
  `webform.webform.account_request.yml`, `webform.webform.partner.yml`,
  `system.site.yml`.
