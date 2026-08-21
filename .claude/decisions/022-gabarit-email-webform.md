# ADR-022 : Gabarit HTML inline pour les e-mails webform

## Statut

Accepte

## Date

2026-08-21

## Contexte

8 e-mails transactionnels (`webform.webform.contact.yml` : `devis_demandeur`,
`devis_interne`, `sav_demandeur`, `sav_interne`, `question_demandeur`,
`question_interne` ; `webform.webform.partner.yml` : `partner_demandeur`,
`partner_interne`) etaient en HTML minimal sans style (texte brut, labels
basiques), sans logo, sans lien avec les maquettes Figma « Modele Email... »
(fichier `ZmmVBSOWSsHVkok6EU2Ays`, nodes 810:9388 et suivants). Demande :
styliser ces 8 e-mails d'apres la maquette, en excluant explicitement le fond
gris a bords arrondis et en imposant l'alignement a gauche — deviation
deliberee de la maquette sur ce seul point.

Contraintes propres au canal e-mail, absentes du reste du site :
- Pas de CSS externe/lie possible : les clients mail (Outlook, Gmail...)
  n'executent que du CSS **inline**, jamais les custom properties CSS.
- SVG souvent bloque (Outlook desktop) : le logo doit etre un raster.
- Le corps vit en YAML de configuration (`handlers.*.settings.body`), pas en
  template Twig : aucune reutilisation possible des fondations SCSS du theme
  ni d'un SDC.

## Options considerees

### Option A : Gabarit HTML inline, sans Twig, un bloc par handler

- Avantages : coherent avec le reglage deja en place sur tous les handlers
  (`html: true` / `twig: false`) ; pas de nouvelle dependance ; chaque
  handler reste autonome et lisible dans son fichier de config.
- Inconvenients : duplication du gabarit entre les 8 handlers (aucune
  reutilisation de composant) ; toute evolution globale (couleur, logo,
  structure) doit etre repercutee handler par handler.

### Option B : Passer les handlers en Twig (`twig: true`) + template partage

- Avantages : un seul template pour les 8 handlers ; permettrait des
  conditions (`{% if %}`) pour les champs optionnels (ex. complement
  d'adresse vide, actuellement gere par une simple concatenation de tokens).
- Inconvenients : `twig: true` change la syntaxe de token
  (`{{ webform_submission.getElementData('x') }}` au lieu de
  `[webform_submission:values:x]`) — un changement d'architecture plus large
  que le perimetre demande (« styliser »), qui toucherait potentiellement
  tous les handlers du fichier, y compris ceux non stylises dans cette
  session.

## Decision

Option A. Le gabarit reste un bloc HTML inline par handler, standardise
comme suit :

- Un seul conteneur `<div style="text-align:left;...">` par e-mail, logo en
  `<img>` (PNG, URL absolue), titres de section en `<h3
  style="text-transform:uppercase">`, pied de page identique sur les 8
  e-mails (« A bientot » + lien rouge + mention automatique).
- Couleurs reprises des tokens du theme en valeurs hex litterales (pas de
  variable CSS, non supportee en e-mail) : `--dm-color-steel` `#2f3a45`
  (titres de section), `--dm-color-anthracite` `#1a1a1a` (texte courant),
  `--dm-color-red` `#aa0000` (lien), `--dm-color-grey-text` `#666666`
  (mention automatique en pied de page).
- Fond gris/bords arrondis de la maquette **non reproduits** (demande
  explicite) ; ordre du bloc identite unifie sur les 8 e-mails : Statut (si
  le formulaire a un champ « Vous etes » — absent du formulaire partenaire)
  - Entreprise - Nom - Adresse - E-mail - Tel, separateur `-` entre
  adresse+complement et code postal+ville.
- **Regle de completude** : un champ reellement collecte par le formulaire
  reste dans l'e-mail meme si la maquette ne le montre pas. Deux cas
  rencontres : la ligne Adresse (absente des maquettes « devenir
  partenaire » et des maquettes « demandeur » de devis/SAV/question, alors
  que les champs sont bien collectes) et la ligne « Piece jointe » (absente
  des maquettes devis/question, presente sur SAV).
- Logo exporte en PNG depuis le SVG existant du theme
  (`web/themes/custom/drive_matic/logo.svg`, version texte noir — la seule
  des deux variantes du theme adaptee a un fond blanc) via ImageMagick
  (`magick logo.svg -background none -resize 633x ...png`), stocke dans
  `web/themes/custom/drive_matic/images/logo-drive-matic-legrand-email.png`,
  reference par URL absolue en dur — un e-mail n'a pas de contexte Twig pour
  resoudre `active_theme_path()`.
- `attachments: true` pose sur `sav_demandeur` et `sav_interne` : le fichier
  uploade par le champ `document` (managed_file) est desormais reellement
  joint aux 2 e-mails SAV (pas seulement mentionne en texte), resolvant
  l'ecart signale au PRD F10.

## Consequences

- Coherence visuelle et de contenu entre les 8 e-mails, mais **duplication
  assumee** : une evolution du gabarit (couleur, logo, structure) doit etre
  repercutee sur chacun des 8 blocs `body` — pas de composant partage. Tout
  nouveau handler d'e-mail webform doit reprendre ce gabarit (cf. CLAUDE.md,
  section « E-mails webform »).
- Limite connue et acceptee : le champ `complement` optionnel, insere sans
  logique conditionnelle (tokens simples, pas de Twig), produit un espace
  HTML redondant dans la source quand il est vide — sans consequence visible
  au rendu (le HTML collapse les espaces multiples), verifie a l'ecran sur
  les 8 e-mails.
- L'URL absolue du logo e-mail est **fixe** sur `drivematiclegrand.com` : un
  changement de domaine (migration, environnement de test) cassera l'image
  sans erreur bloquante — juste une image manquante, silencieuse — a
  surveiller si le domaine change.

## Alternatives rejetees

Voir Option B ci-dessus (passage en Twig).
