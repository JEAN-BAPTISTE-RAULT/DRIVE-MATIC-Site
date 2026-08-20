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
