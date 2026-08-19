# ADR-010 : Metatags — defauts a tokens + champ de surcharge

## Statut

Accepte

## Date

2026-08-17

## Contexte

Le modele editorial demande, par type de node, de mapper **titre → meta title**
et **body → meta description** (tokens), sauf `legals`
(`docs/content-model.md`).

Le module Metatag etait installe et des defauts a tokens configures, mais
**aucun champ** n'existait sur les types de contenu : les balises partaient bien
dans le `<head>`, sans que rien ne soit visible ni surchargeable en back-office.
L'utilisatrice a signale l'absence du champ.

## Options considerees

### Option A : defauts Metatag seuls (etat initial)

- Avantages : zero saisie, zero champ, la regle du modele est respectee a la
  lettre par les tokens.
- Inconvenients : aucune surcharge possible. Le jour ou une page a besoin d'une
  description redigee (page d'accueil, page produit phare), il faut passer par
  la configuration globale du site — hors de portee d'un editeur.

### Option B : champ metatag par type, sans defauts

- Avantages : tout est editorial, rien d'implicite.
- Inconvenients : chaque node doit etre renseigne a la main, sinon **aucune**
  balise. Regression SEO garantie sur le contenu existant et sur tout oubli.

### Option C : defauts a tokens **+** champ de surcharge

- Avantages : remplissage automatique par defaut (rien a saisir, pas de trou),
  surcharge possible au cas par cas. C'est le fonctionnement standard de Metatag.
- Inconvenients : deux endroits ou une valeur peut naitre — il faut que
  l'interface dise clairement que « vide = automatique ».

## Decision

**Option C.**

1. **Defauts** : `node` porte `title: [node:title] | [site:name]` et
   `description: [node:summary]`. `[node:summary]` (et non `[node:body]`) : le
   resume s'il est saisi, sinon un extrait tronque du corps — evite une
   description a rallonge. Le defaut `node__homepage` utilisait `[node:body]`,
   harmonise.
2. **Surcharge** : champ `field_meta_tags` (libelle « Balises meta »), widget
   `metatag_firehose` en barre laterale du formulaire, **masque au rendu** (les
   balises partent dans le `<head>`, pas dans le contenu). Texte d'aide :
   « vide = remplissage automatique ». Pose sur les nodes publics existants
   (`homepage`, `news`, `contact`, `partner`) ; **a ajouter a chaque nouveau
   node public**, jamais sur `legals`, les fragments, ni le bac a sable.

## Consequences

- ⚠️ **Cas de la page d'accueil** : Metatag applique sur `<front>` son defaut
  special **`front`**, qui **remplace** les defauts `node` et `node__homepage`
  (`metatag.module` : la branche `getSpecialMetatags()` court-circuite le `else`
  de la branche entite). La page d'accueil sortait donc **sans meta
  description**, alors que sa configuration semblait correcte. Le mapping a ete
  recopie dans le defaut `front`, en y **conservant `canonical_url: [site:url]`**
  — la canonique de l'accueil doit rester la racine, pas `/node/<id>`.
  La surcharge par le champ du node, elle, s'applique bien sur l'accueil : elle
  est fusionnee plus loin, hors de cette branche.
- Toute modification du mapping doit etre faite **a deux endroits** : le defaut
  `node` (ou `node__<bundle>`) **et** le defaut `front` pour l'accueil.
- La longueur des descriptions n'est pas bornee (elle suit la troncature du
  champ corps de texte) — trace comme point ouvert dans le PRD F18.
- Fichiers : `field.storage.node.field_meta_tags`,
  `field.field.node.{homepage,news,contact,partner}.field_meta_tags`, les form
  displays correspondants, `metatag.metatag_defaults.{front,node__homepage}`.
