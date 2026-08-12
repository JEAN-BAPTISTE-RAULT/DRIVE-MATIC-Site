# ADR-005 : Configuration spécifique à l'environnement (mail, secrets)

## Statut
Accepte

## Date
2026-08-12

## Contexte
Certaines configurations dependent de l'environnement (transport mail local vs prod) ou sont sensibles (cle secrete reCAPTCHA). Il ne faut ni imposer le reglage local en prod, ni committer de secret. Le projet n'a pas encore de `config_split`.

## Options considerees

### Option A : tout committer (config/sync) tel quel
- Avantages : simple.
- Inconvenients : impose le local en prod (mail vers 127.0.0.1) et **committe des secrets** (cle secrete) → inacceptable.

### Option B : config_split (split dev/prod)
- Avantages : propre, versionne par environnement.
- Inconvenients : module + config supplementaires ; sur-dimensionne au stade actuel.

### Option C : defaut neutre versionne + override par `settings.php` (gitignore)
- Avantages : leger, secrets hors depot, aucun reglage local impose en prod.
- Inconvenients : les overrides ne sont pas versionnes (a documenter pour l'equipe).

## Decision
**Option C.** Le `config/sync` porte un **defaut neutre** ; les valeurs specifiques a l'environnement / sensibles sont posees via `$config[...]` dans `web/sites/default/settings.php` (gitignore).

- **Mail** : defaut versionne `mailer_transport.settings:default_transport = sendmail` ; override local `= mailpit` (Mailpit SMTP 127.0.0.1:1025). Le transport `mailpit` est versionne (inoffensif) mais selectionne uniquement par l'override.
- **reCAPTCHA v3** : `site_key` versionnee (publique) ; `secret_key` vide en config, posee via override `settings.php`.
- **trusted_host_patterns** : hotes locaux (vhost `drivematic`, localhost) dans `settings.php`.

## Consequences
- **Aucun secret dans le depot** ; aucun reglage local impose en prod.
- La **prod** definit son propre transport SMTP + sa cle secrete via ses propres `settings.php`/variables d'env.
- Les overrides locaux ne sont pas versionnes → documentes dans le README (§ E-mails / Anti-spam). A migrer vers `config_split` si le besoin d'environnements versionnes grandit.
- Rappel : les config runtime overrides (`$config`) s'appliquent au rendu ; `drush cim` ne les ecrase pas.
