# Archive

Traces d'audit de features **livrees**, conservees pour memoire et **non maintenues** :
elles decrivent l'etat du code au moment de la recette, pas l'etat courant.

Ne pas s'y fier pour comprendre le systeme aujourd'hui. Les sources a jour sont
`docs/PRD.md` (specifications), `README.md` (technique), `.claude/decisions/` (ADR) et
`CLAUDE.md` (regles).

Un chantier en cours vit dans `docs/active/<feature>/` (`progress.md`, `verification.md`) ;
il atterrit ici quand la feature est livree et que son suivi n'a plus d'usage.

| Fichier | Feature | Recette | Reserve connue |
|---|---|---|---|
| `content-types-verification.md` | Brique content-types (T0 → T6), ADR-002 | 2026-08-17 | ⚠️ Ecrite sous la **convention ADR-011** (`field_title` porte le titre affiche), **remplacee par l'ADR-014** : le `title` est desormais la source unique. Le fragment `document` qu'elle cite a ete supprime le 2026-08-18. |
| `home-f3-verification.md` | F3 Home page + shell minimal (`site-header` / `site-footer`) | 2026-08-13 | La home a ete **recomposee d'apres la maquette** le 2026-08-17 : cette trace ne decrit pas son contenu actuel. Le nettoyage du bloc « Powered by Drupal » qu'elle signale releve de F2. |
| `legals-body-verification.md` | `legals` : paragraphes → body + metatags, [ADR-019](../../.claude/decisions/019-legals-body-metatags.md) | 2026-08-20 | Les 3 nouvelles pages (CGU, mentions legales, donnees personnelles) ont un `body` **vide** : contenu juridique a rediger par l'editrice. Le footer qui les liera releve de F2 (a venir). |
| `footer-f2-verification.md` | Footer riche (F2), [ADR-020](../../.claude/decisions/020-footer-riche-menus.md) | 2026-08-20 | Contenu des menus (`menu_link_content`) non versionne, script Drush ponctuel a rejouer sur un environnement qui ne recoit pas le dump de base. |
| `login-page-verification.md` | Page de connexion `/user/login` (F2/F12), [ADR-024](../../.claude/decisions/024-mutualisation-formulaire-simple.md) | 2026-08-25 | Les 2 nodes `simple_form` partagent temporairement le meme webform (`partner`) ; icone `eye.svg` reconstruite (absente des maquettes fournies) ; resolution des 3 cartes d'action par bundle + titre, fragile a un renommage de node. |
| `configurateur-livraison-verification.md` | Configurateur de devis, ecran 3 « Livraison » (F14 3/3), [ADR-033](../../.claude/decisions/033-entites-devis-livraison.md)/[ADR-034](../../.claude/decisions/034-modale-drupal-core.md)/[ADR-035](../../.claude/decisions/035-recap-adresses-livraison-admin.md) | 2026-09-01 | Gabarit desktop de l'ecran lui-meme (508:13965) construit par extrapolation depuis le mobile, jamais remesure specifiquement (les 3 modales, elles, ont ete verifiees au pixel pres). E-mail de confirmation de commande + PDF restent hors perimetre (F15). |
