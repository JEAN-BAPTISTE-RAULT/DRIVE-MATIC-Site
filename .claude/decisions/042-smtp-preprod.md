# ADR-042 : Transport SMTP preprod (mails.passerelle.com)

## Statut
Accepte

## Date
2026-09-02

## Contexte
La preprod n'avait aucun transport mail reellement fonctionnel (`mailer_transport.settings.default_transport` verse a `sendmail`, qui suppose un MTA local configure sur le serveur). Besoin : router les e-mails de la preprod via un vrai serveur SMTP (`mails.passerelle.com`, compte `no-reply@passerelle.com`, port 465/SSL), sans jamais commiter le mot de passe, et sans que le mot de passe soit efface a chaque `drush deploy` (`config:import` fait partie du script `scripts/deploy-preprod.sh`, ADR-039).

Contrainte supplementaire : le local doit continuer a utiliser Mailpit sans regression (mecanisme deja en place, `$config['mailer_transport.settings']['default_transport'] = 'mailpit';` dans le `settings.php` local, gitignore).

## Options considerees

### Option A : stocker le mot de passe dans l'entite `mailer_transport` versionnee
- Avantages : aucune etape manuelle sur le serveur.
- Inconvenients : fuite de secret dans `config/sync/` (git) — inacceptable (CLAUDE.md, "Committer des secrets").

### Option B : creer l'entite directement en base preprod (drush php:eval/UI), jamais exportee
- Avantages : mot de passe jamais dans le repo.
- Inconvenients : **`drush config:import` (present dans `drush deploy`) supprimerait cette entite au prochain deploiement**, puisqu'elle n'existe pas dans `config/sync/` — exactement le risque signale par l'utilisatrice. Rejetee.

### Option C (retenue) : entite versionnee sans secret + surcharge `$config[...]` dans le `settings.php` de la preprod
- Avantages : reproduit a l'identique un mecanisme deja valide sur ce projet pour la cle secrete reCAPTCHA (README, section Anti-spam) — la partie non sensible (host, user, port, plugin) est versionnee et survit a chaque `config:import` ; seul le mot de passe vit dans `settings.php` (gitignore, jamais synchronise par `rsync` puisque non suivi par git, cf. ADR-039). La surcharge `$config[...]` est appliquee par `ConfigFactory` **au-dessus** du stockage de config, a la lecture — un `config:import` qui re-ecrit `pass: ''` en stockage n'a donc aucun effet observable au runtime.
- Inconvenients : une etape manuelle unique sur le serveur (edition du `settings.php` preprod), a documenter pour ne pas l'oublier en cas de reinstallation.

## Decision
Nouvelle entite versionnee `mailer_transport.mailer_transport.smtp_passerelle`
(`config/sync/mailer_transport.mailer_transport.smtp_passerelle.yml`) : plugin
`smtp`, `host: mails.passerelle.com`, `user: no-reply@passerelle.com`, `port: 465`,
`query.verify_peer: true` (TLS implicite, port 465), **`pass: ''`** — jamais le
vrai mot de passe.

Sur le serveur preprod uniquement (`settings.php`, gitignore) :
```php
$config['mailer_transport.mailer_transport.smtp_passerelle']['configuration']['pass'] = '<mot de passe reel>';
$config['mailer_transport.settings']['default_transport'] = 'smtp_passerelle';
```

**Portee du defaut, confirmee avec l'utilisatrice** : `mailer_transport.settings.yml`
versionne reste a `sendmail` (neutre, inchange) — la selection de
`smtp_passerelle` comme transport par defaut ne vit QUE dans le `settings.php` de
la preprod, jamais dans la config versionnee. Une future prod devra faire son
propre choix explicite (son propre `settings.php`, potentiellement son propre
transport) plutot que d'heriter silencieusement de celui de la preprod.

Verifie localement : import propre (`drush config:import`, seule cette entite
creee, aucun autre diff), lecture de l'entite conforme, et simulation de la
surcharge (`$GLOBALS['config'][...]`) confirmant que la valeur surchargee est bien
celle lue par `\Drupal::config(...)->get('configuration')`, pas celle du
stockage.

## Consequences
- **Aucune modification de `scripts/deploy-preprod.sh`** : le risque anticipe
  (le mot de passe/la selection de transport « ecrases » a chaque deploiement)
  ne se pose pas avec ce mecanisme — `rsync` ne touche jamais `settings.php`
  (non suivi par git), et `config:import` n'efface jamais une surcharge runtime.
- Fichier impacte : `config/sync/mailer_transport.mailer_transport.smtp_passerelle.yml`
  (nouveau, sans secret).
- **Etape manuelle restante, hors depot** : ajouter les 2 lignes ci-dessus dans
  le `settings.php` du serveur preprod (mot de passe fourni separement par
  l'utilisatrice, jamais passe par le depot ni par un fichier local).
- Si le mot de passe change un jour, seule cette ligne de `settings.php` preprod
  est a modifier — aucun impact sur la config versionnee ni sur un futur
  deploiement.
