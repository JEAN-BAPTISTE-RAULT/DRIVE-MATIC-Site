# Verification — F3 Home page + shell minimal

> Trace d'audit. Feature : type de contenu `homepage` + template dedie + SDC shell (`site-header`/`site-footer`) + front page a `/`. Plan : `docs/plans/home-f3.md`.

## Commandes executees

| Commande | Resultat | Notes |
|---|---|---|
| `drush cim -y` (×2) | success | Creation type homepage (6 configs) + maj visibilite blocs breadcrumb/page_title |
| `drush cex -y` | identical | Config canonique, aucun ecart apres import |
| `npm run css` | success | CSS co-localise site-header/site-footer genere |
| `npm run lint` | pass | ESLint + Stylelint + PHPCS (252/252) verts |
| `npm run format:check` | pass | Prettier clean |
| `npm test` | pass (placeholder) | Pas de harness de test defini |
| `drush config:status` | `system.site` Different | **Attendu** : front page `/node/31` posee en base, non versionnee (ref env-specifique) |
| `curl https://drivematic:9899/` | HTTP 200 | Home rendue, verif markup (voir edge cases) |

## Changements comportementaux

- Nouveau type de contenu **`homepage`** (« Page :: Accueil ») : body obl + metatags, allowlist paragraphes du perimetre home.
- La **page d'accueil** (`/`) affiche desormais un vrai node home (template `node--homepage`, plein largeur, sans titre de node).
- **Ossature de page** : header (logo + menu) et footer (bandeau sobre) via SDC `site-header`/`site-footer`, sur toutes les pages du theme.
- **Fil d'Ariane** et **titre de page** masques sur `<front>`, presents sur les pages internes.

## Risques identifies et mitigations

- **Front page = ID de node non portable** → mitige : reference posee en base pour le local, **non versionnee** dans `config/sync` ; wiring front a finaliser au seed/deploiement (option : redirect `/node/N` → `<front>` pour garantir la home uniquement a `/`). Risque residuel accepte : `config:status` affiche `system.site` Different.
- **Cache / fuite** : page 100 % publique, aucune donnee partenaire ; cache tags `node_list:*` des blocs a Vue deja reattaches par `drive_matic_preprocess_paragraph` (acquis V4). Aucun risque de cloisonnement.
- **RGAA/WCAG** : `role=banner`/`contentinfo`, skip-link preserve, logo avec alt (bloc branding). A confirmer au design F2 (contrastes menu).

## Edge cases testes

| Cas | Attendu | Obtenu |
|---|---|---|
| Home `/` — SDC header/footer | presents | ✅ (4 / 5 occurrences) |
| Home `/` — fil d'Ariane | absent | ✅ 0 |
| Home `/` — titre de node (page-title) | absent | ✅ 0 |
| Home `/` — template dedie | `node--type-homepage` | ✅ 1 |
| Home `/` — paragraphes (intro + accordion SEO) | rendus | ✅ |
| Home `/` — metatag title | `Accueil \| DRIVE-MATIC` | ✅ |
| Page interne `/node/32` — fil d'Ariane | present | ✅ 4 |
| Page interne `/node/32` — titre + header/footer | presents | ✅ |

**Non teste (base sans media)** : slideshows `jumbo_home`/`news_home`/`brands_home` dans le node homepage (plaçabilite confirmee via allowlist ; rendu valide en V4, inchange). Runtime JS accordeon non rejoue ici (composant V2 valide, markup + behavior attach presents).

## Self-review

1. **Decision la plus difficile** : la portabilite de la front page (node unique impose par ADR-002 vs. non-portabilite de l'ID de node vs. refus d'un alias `/accueil`). Tranche : reference en base non versionnee, finalisation au seed.
2. **Alternatives rejetees** : reutiliser `page` (semantique/allowlist distinctes) ; masquer titre/breadcrumb via preprocess PHP (prefere visibilite de bloc, zero code) ; header/footer en dur dans `page.html.twig` (violerait decision #10 SDC) ; front `/accueil` (consigne utilisatrice « juste / »).
3. **Point de moindre confiance** : verification sans media → slideshows home non rejoues dans le node homepage ; footer rend la region `footer` (bloc « Powered by Drupal » par defaut) a nettoyer en F2.
