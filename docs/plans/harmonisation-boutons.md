# Plan — Harmonisation boutons / checkboxes / radios (Figma 243:5551)

Valide le 2026-08-26. Voir ADR-029 pour la decision d'architecture (mixin Sass partage).

## 1. Intention

Faire converger tous les boutons CTA (rouge/gris/contour), checkboxes et radios du
site public vers une seule source de verite conforme a la maquette Figma (fichier
`ZmmVBSOWSsHVkok6EU2Ays`, node `243:5551`), pour la coherence visuelle du parcours
grand public + partenaire.

Demande utilisatrice (captures Figma a l'appui) :
1. Boutons fond gris -> fond rouge au survol
2. Boutons fond rouge -> fond bleu acier au survol
3. Boutons fond blanc/contour -> fond bleu acier au survol
4. Tous les boutons a la meme hauteur
5. Checkboxes/radios coches en bleu acier (pas rouge)

## 2. Fichiers impactes

**Nouveau**
- `src/scss/_button-mixins.scss` — mixins Sass partages (couleurs + hover, hauteur)
- `.claude/decisions/029-mixin-boutons-partage.md` — ADR
- `docs/plans/harmonisation-boutons.md` — ce plan

**Build**
- `package.json` — ajout de `--load-path=src/scss` a `css:components` et `css:watch`

**Fondations `src/scss/` (boutons de formulaire + checkbox/radio)**
`_forms.scss` (submit rouge + checkbox/radio coche), `_user-login-form.scss`,
`_user-edit-form.scss`, `_user-pass-form.scss`, `_user-logout-confirm-form.scss`
(submit + `dialog-cancel` contour), `_personal-information-form.scss` (submit +
`password-link` contour), `_configurator-form.scss` (`__add` contour)

**SDC — rouge** : `text-centered`, `image-full`, `site-header` (`__cta`)
**SDC — gris** : `image-text-50`, `image-text-100`, `jumbo-home-element`,
`news-home`, `product-image-element`, `product-video-element`
**SDC — contour** : `login-panel`, `text-left-aligned`, `product-characteristics`,
`site-header` (`__account-trigger`)

**Hors perimetre (non touches)** : boutons pilule FAQ/accordeon, chevrons
pagination/swiper, boutons icone seule (modale d'aide, video, stepper),
`BtnBlanc` Figma (ne correspond a aucune des 3 regles demandees), les 3 slots
`__download` jamais stylés (`text-centered`, `image-text-50`, `image-text-100`).

## 3. Valeurs retenues (source Figma, arbitrages signales)

- **Rouge** (repos `#a00` -> hover `#2f3a45`), **contour** (repos transparent/bordure
  steel -> hover `#2f3a45` plein, texte blanc) : radius 4px, `padding-block: 14px`
  (texte seul).
- **Gris** (repos `#e8e8e8` -> hover `#a00`, texte blanc) : radius 6px **inchange
  au hover** — Figma fait aussi passer le radius de 6 a 4px sur ce variant precis,
  traite comme incoherence Figma (seule famille a le faire), non reproduit.
- **Boutons icone + texte** (« Espace partenaire », `__add` configurateur,
  `text-left-aligned`) : `padding-block: 11px` (icone 24px => hauteur totale
  ~46px, identique a la famille texte-seul). Radius et padding horizontal
  existants conserves.
- Radius specifiques deja en place (`dialog-cancel` 6px, `login-panel` 4px,
  `text-left-aligned` 10px…) **non uniformises** — seuls hover et hauteur le sont.
- Checkbox/radio : 4 lignes a changer dans `_forms.scss` (`:checked` rouge ->
  `#2f3a45`), implementation unique sur tout le site.

## 4. Etapes de verification

1. Mixin + load-path seuls -> `npm run css` OK sans rien casser
2. `_forms.scss` (rouge + checkbox/radio, le plus consomme)
3. 6 autres fondations formulaire
4. 3 SDC rouges
5. 6 SDC gris
6. 4 SDC contour
7. ADR
8. `npm run lint` + `npm run format:check`
9. Verification navigateur : un representant de chaque famille + configurateur
   (checkbox) + webform (radio) + `focus-visible` + etat `disabled` de `__add`
