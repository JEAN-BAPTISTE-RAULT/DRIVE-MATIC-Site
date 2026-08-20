# Verification — Footer riche (F2)

## Commandes executees

| Commande | Resultat | Notes |
|---|---|---|
| `drush config:status` (avant/apres seed) | Seul le nouveau menu/blocs en diff | Aucune derive parasite capturee |
| `drush config:export` | OK | `system.menu.footer-solutions`, 2 `block.block.*` |
| `npm run css` (node 24.16.0) | OK | Verifie dans le `.css` genere (`site-footer__logo`, `--youtube`, `__legal`) |
| `npm run lint` | OK (0 erreur) | 2 `stylelint-disable-next-line selector-class-pattern` cibles + commentes (classe core `menu--footer-solutions`) ; reordonnancement pour `no-descending-specificity` |
| `npm run format:check` | OK | |
| `drush cache:rebuild` | OK | |
| Rendu navigateur 1440px et 390px | Conforme aux 2 maquettes | Mesures DOM (`getBoundingClientRect`/`getComputedStyle`), pas a l'oeil |
| `curl` anonyme (sans session admin) | Footer present, 3 `<span>` nolink, 0 lien contextuel admin, 4 icones | Verifie qu'aucun lien de configuration (« Configurer le bloc »...) ne fuite en anonyme |

## Changements comportementaux

- Le footer affiche desormais : logo (lien vers l'accueil), adresse,
  telephone, 3 colonnes de liens (solutions auto-ecole/PMR, assistance),
  4 icones reseaux sociaux, 4 liens legaux. Auparavant : nom du site +
  region `footer` vide + copyright.
- Nouveau menu custom `footer-solutions` visible dans `/admin/structure/menu`.
- Le menu core `footer` (jusque-la vide) porte desormais les 4 liens legaux.
- Aucun changement d'API/endpoint.

## Risques identifies et mitigations

- **Alias qui changent** → mitige par le choix de menus Drupal (menu_link_content
  pointant vers l'entite) plutot que des `href` en dur (cf. [ADR-020](../../../.claude/decisions/020-footer-riche-menus.md)).
- **`<nolink>` mal rendu** → verifie au navigateur (markup anonyme) : rendu en
  `<span>`, pas de lien casse.
- **Contenu (menu_link_content) non versionne** → coherent avec la decision
  projet deja actee (« la base locale part en preprod telle quelle ») ;
  script Drush ponctuel non committe, a rejouer sur un environnement qui ne
  recoit pas le dump.
- **Contraste texte legal (gris metallise sur bleu acier)** → couleurs
  reprises telles quelles de la maquette (tokens deja en usage ailleurs sur
  le site) ; non recalcule ici, risque residuel accepte (identique au reste
  du site).

## Edge cases testes

- **Utilisateur anonyme** : footer identique, aucun lien d'administration
  (« Configurer le bloc », « Modifier le menu ») ne fuite → verifie par
  `curl` sans session.
- **Item de menu sans lien** (colonnes « Nos solutions... », « Assistance ») :
  rendu en texte simple, pas de `<a href="">` casse.
- **Redimensionnement autour du breakpoint (992px)** : bascule colonne ↔
  ligne verifiee a 390px et 1440px (pas de valeur intermediaire testee —
  point de moindre confiance, voir ci-dessous).

## Self-review

1. **Decision la plus difficile** : modeliser les 3 colonnes « solutions »
   comme un seul menu a 2 niveaux (parents `<nolink>`) plutot que 3 menus
   distincts ou des liens en dur — tranche pour rester coherent avec la regle
   du projet sur les alias qui changent, au prix d'un menu custom
   supplementaire (cf. ADR-020).
2. **Alternatives rejetees** : hrefs en dur (rejete : fragile aux
   renommages) ; nouvelles regions Drupal pour separer solutions/legal
   (rejete : `drive_matic.info.yml` n'a pas de cle `regions:`, en ajouter une
   remplacerait le jeu par defaut — risque disproportionne) ; bloc plugin
   custom pour les reseaux sociaux (rejete : sur-ingenierie pour 4 liens
   fixes, cf. simplicite CLAUDE.md).
3. **Point de moindre confiance** : le breakpoint 992px n'a pas ete verifie
   a des largeurs intermediaires (ex. tablette ~800px) ni compare pixel a
   pixel a la maquette a cette taille — seuls 390px et 1440px ont ete
   mesures. Egalement : la hauteur totale du footer (287px mesure vs 324px
   maquette) n'a pas ete resorbee, ecart assume (rythme `--dm-space-block`
   au lieu des paddings exacts de la maquette, pas de ligne de copyright).
