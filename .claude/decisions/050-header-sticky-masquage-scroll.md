# ADR-050 : Header sticky qui se masque au scroll vers le bas

## Statut
Accepte

## Date
2026-09-09

## Contexte
Demande de l'utilisatrice : le menu doit se retracter/disparaitre quand on
scrolle vers le bas, et reapparaitre vers le haut. Le header (`site-header`,
F2/ADR-021) etait jusque-la en `position: relative` — il defilait avec la
page, aucun mecanisme sticky n'existait dans le theme (premiere occurrence,
verifiee par recherche exhaustive). Un tel comportement de masquage n'a de
sens que si le header est d'abord fixe en haut d'ecran ; confirme
explicitement aupres de l'utilisatrice avant implementation.

## Options considerees

### Option A : `position: sticky`
- Avantages : l'element garde sa place dans le flux normal du document —
  aucune compensation de hauteur (padding-top sur `<body>` ou equivalent) a
  poser ailleurs sur le site, contrairement a `position: fixed`.
- Inconvenients : aucun pour ce cas d'usage (un seul header, en tete de
  page).

### Option B : `position: fixed` + compensation
- Avantages : aucun avantage specifique ici.
- Inconvenients : sort l'element du flux, exige une compensation (padding
  ou marge egale a la hauteur du header, elle-meme variable selon le
  palier/l'etat de la Toolbar) sur tout le reste de la page — risque de
  regression a chaque evolution de la hauteur du header.

## Decision
**Option A.** `.site-header` passe en `position: sticky; top: ...`, avec un
nouveau comportement `driveMaticSiteHeaderScroll` (`site-header.js`,
independant des flyouts/tiroir deja geres dans ce fichier) qui bascule
`.is-hidden` (`transform: translateY(-100%)`, transition 0.3s) selon le sens
du scroll :
- Reste visible tant que le scroll n'a pas depasse la propre hauteur du
  header (evite un clignotement pres du haut de page).
- Scroll vers le bas au-dela de ce seuil → masque ; vers le haut → reaffiche
  immediatement.
- `prefers-reduced-motion: reduce` neutralise la transition (bascule
  instantanee plutot qu'un glissement anime, comportement fonctionnel
  conserve).
- Amelioration progressive : sans JS, le header reste sticky mais ne se
  masque jamais (`.is-hidden` n'est jamais posee, aucun listener attache).

⚠️ **Effet de bord decouvert et corrige dans la meme session** : la barre
d'admin Drupal (Toolbar, active pour les utilisateurs authentifies) est
elle-meme `position: fixed; top: 0`, independamment de notre header — les
deux se disputaient le meme `top: 0` et se chevauchaient pendant le scroll.
Resolu en calant `top` sur `var(--drupal-displace-offset-top, 0)`, le
mecanisme **officiel** de Drupal core (`core/misc/displace.js` + attribut
`data-offset-top` pose par le module `toolbar` sur `.toolbar-bar`) qui
reflete en temps reel la hauteur de tout element deplaçant potentiellement
le contenu — 0 en secours pour un visiteur anonyme (Toolbar non charge,
variable jamais posee). Tout futur element `sticky`/`fixed` en haut d'ecran
doit suivre la meme regle.

## Consequences
- Fichiers : `components/site-header/site-header.scss` (sticky, `.is-hidden`,
  `--drupal-displace-offset-top`), `components/site-header/site-header.js`
  (nouveau `Drupal.behaviors.driveMaticSiteHeaderScroll`).
- z-index du header pose a 30 (rien d'autre sur le site ne depasse 5 en
  dehors des propres enfants du header — flyouts a 20, tiroir mobile a 100,
  tous des DESCENDANTS du header, non concernes par ce choix).
- **Limite de verification connue** : impossible de se connecter en admin
  dans le Browser MCP de cette session (`drush uli` renvoie systematiquement
  « Acces refuse », limite deja documentee sur les liens de connexion a usage
  unique dans ce sandbox) — le calage sous la Toolbar n'a pu etre verifie
  qu'en simulant la custom property via JS, pas avec une vraie session
  authentifiee. A reverifier par l'utilisatrice.
- Meme limite que ADR-049 pour le glissement anime lui-meme
  (`requestAnimationFrame` gele dans ce sandbox, `document.hidden` toujours
  vrai) : la logique de decision (sens du scroll → classe posee) est
  verifiee par simulation directe, pas le rendu visuel.
