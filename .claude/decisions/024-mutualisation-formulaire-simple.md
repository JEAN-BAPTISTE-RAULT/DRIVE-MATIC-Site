# ADR-024 : Mutualisation du bundle `partner` en `simple_form`

## Statut

Accepte

## Date

2026-08-25

## Contexte

La page d'authentification (`/user/login`, maquettes 472:12636 / 602:33089)
porte un lien « Créer un compte » qui doit renvoyer vers une nouvelle page
« Demande de création de compte », avec un webform (celui de « Devenir
partenaire », en attendant la creation de son propre formulaire).

Le bundle `partner` existait deja et etait, structurellement, exactement ce
qu'il fallait pour porter cette seconde page : un titre, un `body` masque a
l'affichage, un `field_webform` obligatoire, rien d'autre. La demande etait
de le **generaliser** ("Page :: Devenir partenaire" → "Page :: Formulaire
simple", nom technique `simple_form`) plutot que de dupliquer sa config pour
un second bundle presque identique.

Drupal ne permet pas de renommer le nom technique d'un type de contenu
existant, ni de deplacer un node d'un bundle a un autre par une action
standard du back-office.

## Options considerees

### Option A : garder `partner`, ne renommer que le libelle humain

Le plus simple : aucune migration, aucun risque sur le node existant. Mais ne
respecte pas la consigne (nom technique demande : `simple_form`), et laisse
un nom `partner` trompeur pour porter aussi la page de demande de compte.

### Option B (retenue) : creer `simple_form`, migrer le node, supprimer `partner`

Respecte la consigne a la lettre. Cout : un `hook_update_N` qui bascule le
node existant vers le nouveau bundle avant de supprimer l'ancien.

## Decision

1. Nouveau bundle `simple_form` (config `config/sync/*.simple_form.*`),
   copie fidele de la structure `partner` : memes onglets `field_group`
   (Informations generales / Contenu), meme widget `field_webform`, meme
   motif Pathauto `/[node:title]`, mêmes defauts metatag, meme reglage
   sitemap (`index: true`).
2. `drivematic_forms_update_11001()` (nouveau `drivematic_forms.install`) :
   - bascule le node existant "Devenir partenaire" (`type: partner` →
     `type: simple_form`) — meme storage partage pour `body`/`field_webform`/
     `field_meta_tags`, aucune perte de donnee ;
   - cree le node "Demande de création de compte" (`field_webform` = webform
     `partner`, temporaire) ;
   - supprime le bundle `partner` (config + champs + displays), devenu vide.
   Idempotent : si `partner` n'existe plus, la mise a jour ne fait rien.
3. **Le bundle devient multi-instance** : consequence directe, son motif
   Pathauto **doit rester actif** (`/[node:title]`, un alias different par
   node). `docs/active/maquette-integration/progress.md` classait `partner`
   parmi les types "a exemplaire unique" candidats a un alias en dur — ce
   classement devient faux et a ete corrige dans le meme mouvement.
4. Les 3 cartes d'action de la page login ("Créer un compte" / "Devenir
   partenaire" / "Demander un devis") pointent vers ces nodes en les
   recherchant par **bundle + titre** (`_drive_matic_simple_form_node_url()`,
   `drive_matic.theme`) : la recherche par bundle seul, utilisee ailleurs sur
   le site pour les types a exemplaire unique (`_drive_matic_devis_cta_url()`),
   ne suffit plus puisque `simple_form` porte desormais 2 pages.

## Consequences

**Positif**

- Un seul bundle a maintenir pour "page portant un webform unique" au lieu
  de deux presque identiques.
- Aucune perte de donnee sur le node "Devenir partenaire" (nid, alias,
  historique de revision preserves).

**Negatif / vigilance**

- La recherche par **titre** (`_drive_matic_simple_form_node_url()`) casse
  silencieusement si le titre d'un des deux nodes est renomme en
  back-office — meme classe de fragilite que la recherche par bundle deja
  acceptee ailleurs sur le site, mais avec un point de defaillance
  supplementaire (le titre, modifiable sans toucher au bundle).
- Les 2 pages partagent temporairement le **meme webform** (`partner`) : les
  soumissions de "Demande de création de compte" arrivent avec les libelles
  et destinataires du formulaire "Devenir partenaire". Accepte explicitement
  par la tache (creation du vrai webform reportee) — a ne pas prendre pour un
  bug tant qu'il n'est pas cree.
- ⚠️ **Trouve en verifiant la migration, pas cause par elle** : le node
  "Devenir partenaire" avait deja `field_webform` vide en base avant toute
  intervention (une seule revision existante, la migration n'a touche que
  `type`). Restaure a `partner` au meme moment.

## Alternatives rejetees

Voir Option A ci-dessus.

## Notes

Le webform propre a "Demande de création de compte" reste a creer — quand ce
sera fait, mettre a jour `field_webform` du node 124 et retirer la note de
webform partage ci-dessus.
