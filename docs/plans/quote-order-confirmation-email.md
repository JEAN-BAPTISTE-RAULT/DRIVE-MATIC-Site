# Plan : e-mail de confirmation de commande

## 1. Intention

Envoyer automatiquement un e-mail au partenaire quand il clique sur
« Commander » (`DeliveryForm::orderSubmit()`), jamais sur « Enregistrer le
devis » (`saveDraftSubmit()`), pour confirmer la prise en compte de sa
commande avec son vrai numéro de devis.

## 2. Fichiers impactés

- `web/modules/custom/drivematic_configurator/drivematic_configurator.module`
  — `hook_mail()`, `hook_token_info()`, `hook_tokens()`
- `web/modules/custom/drivematic_configurator/src/Form/DeliveryForm.php`
  — DI de `MailManagerInterface`, `persistQuote()` retourne le `Quote`,
  nouvelle méthode d'envoi appelée uniquement depuis `orderSubmit()`
- `config/sync/mailer_policy.mailer_policy.drivematic_configurator.quote_ordered.yml`
  (nouveau)
- `.claude/decisions/036-email-confirmation-commande.md` (nouveau)

## 3. Interfaces publiques

Nouvelle clé `hook_mail` (`drivematic_configurator`/`quote_ordered`),
nouveau type de jeton Drupal (`quote:reference`). Internes au module, pas
d'export cross-fichier.

## 4. Sécurité

Destinataire = e-mail du partenaire authentifié courant (jamais une valeur
soumise). Corps HTML fixe (config), seule variable dynamique = la référence
du devis déjà persisté. Aucun nouvel accès touché.

## 5. Risques et décisions actées avec l'utilisatrice

- Mécanisme `hook_mail()` + `mailer_policy` déjà utilisé sur ce site
  (`user.register_admin_created`, l'e-mail « Votre compte personnel ») —
  vérifié dans le code source de `mailer_override`/`mailer_policy` que ce
  chemin (`LegacyMailer`/`LegacyOverride`) fonctionne génériquement pour
  n'importe quel module avec un `hook_mail()` classique, pas seulement
  `user`. Première utilisation pour un module custom du site.
- Résolution du numéro de devis dans le gabarit HTML (`[quote:reference]`) :
  implémentée via un `hook_token_info()`/`hook_tokens()` custom (API Token
  de Drupal **core**, pas de dépendance contrib supplémentaire) plutôt que de
  compter sur le comportement générique du module contrib `token` pour les
  entités custom (non vérifié avec certitude) — plus explicite et plus sûr.
- **Échec d'envoi (SMTP down, etc.)** : la commande est déjà enregistrée en
  base au moment de l'envoi. Décidé avec l'utilisatrice : encapsuler l'appel
  dans un `try/catch`, erreur journalisée, message de confirmation et
  redirection inchangés dans tous les cas — un problème mail ne doit jamais
  faire échouer une commande déjà persistée.
- **Périmètre** : le PRD (§359, F15) décrit cet e-mail avec un PDF du devis
  en pièce jointe et une copie interne à `info@drivematiclegrand.com`.
  Décidé avec l'utilisatrice : on implémente uniquement l'e-mail au
  partenaire aujourd'hui — PDF et copie interne restent `[ ]` non
  implémentés, à traiter séparément.

## 6. Cohérence PRD

Aligné avec PRD §359 (F15), portée réduite explicitement actée ci-dessus.
Aucune décision verrouillée contredite.

## 7. Étapes

1. `hook_token_info()`/`hook_tokens()` (type `quote`, jeton `reference`).
2. `hook_mail()` (repli texte + `token_data`).
3. `DeliveryForm` : DI du mail manager, retour de `persistQuote()`, envoi
   depuis `orderSubmit()`, `try/catch` autour de l'envoi.
4. `mailer_policy.mailer_policy.drivematic_configurator.quote_ordered.yml`
   (gabarit validé par l'utilisatrice) + `drush cim`.
5. Test manuel de bout en bout via Mailpit (déjà configuré en local,
   `localhost:8025`).
6. `/sync` en fin de session.

## 8. Vérification

Parcours réel `/configurer` → Commander : e-mail reçu dans Mailpit avec la
vraie référence. Puis reprise avec « Enregistrer le devis » : vérifier
l'**absence** d'e-mail. `npm run lint` + `php -l` sur les fichiers modifiés.

**Réalisé (2026-09-02)** : `npm run lint`/`php -l` propres, `drush cst`
propre après import. Test réel via `drush php:eval` (mêmes paramètres
exacts que `DeliveryForm::sendOrderConfirmationEmail()`) sur un devis
existant (`W20260901-001`) — e-mail reçu dans Mailpit, sujet et corps HTML
conformes au gabarit validé, `[quote:reference]` correctement résolu. Un
bug a été trouvé puis corrigé au passage (voir ADR-036, section « Piège »)
— le corps sorti était le texte de repli, pas le gabarit configuré tant
que `hook_mail()` posait `$message['body']`. « Enregistrer le devis » ne
peut pas envoyer d'e-mail : aucun appel à `sendOrderConfirmationEmail()`
n'existe sur ce chemin (`saveDraftSubmit()`), vérifié par lecture du code.
DI de `DeliveryForm` (nouvelle dépendance `plugin.manager.mail`) confirmée
sans erreur via `DeliveryForm::create()`. Test navigateur complet du
parcours (Configuration → Devis → Livraison → Commander) non rejoué —
jugé à faible risque au vu de la simplicité du câblage ajouté
(`orderSubmit()` : 2 lignes) sur un mécanisme d'envoi déjà vérifié de bout
en bout avec les mêmes paramètres exacts.

## Addendum : copie interne (même session, même jour)

Périmètre étendu à la demande de l'utilisatrice, juste après la livraison
ci-dessus : copie interne à Drive Matic Legrand (`quote_ordered_internal`),
même gabarit que les notifications internes existantes (« Demandeur » +
bloc identité), validée dans un artifact avant implémentation (même
méthode que l'e-mail partenaire). 6 jetons `quote` supplémentaires
(`raison-sociale`, `adresse`, `complement`, `code-postal`, `ville` — gelés
sur le devis ; `civilite`, `prenom`, `nom`, `email`, `telephone` — comptes
partenaire courant). `DeliveryForm::sendInternalOrderNotification()`,
appelée depuis `orderSubmit()` juste après l'e-mail partenaire, avec son
propre `try/catch` indépendant.

**Bug trouvé en testant avec un compte réel** (pas les données de test du
premier tour) : `t()` lève un `TypeError` sur un placeholder `NULL`
(`field_phone`/`billing_complement` vides) — corrigé en castant chaque
valeur en `(string)`, dans `hook_mail()` et `hook_tokens()`. Détail complet
dans [[mailer-policy-legacy-body-collision]] (mémoire auto) et
ADR-036. Vérifié via Mailpit sur le même devis réel (`W20260901-001`),
identité du compte de test correctement affichée y compris avec le
complément d'adresse vide.

Destinataire temporaire `audrey@passerelle.com`, comme toutes les autres
notifications internes du site — à restaurer sur
`info@drivematiclegrand.com` avant la mise en prod (hors périmètre de
cette session).
