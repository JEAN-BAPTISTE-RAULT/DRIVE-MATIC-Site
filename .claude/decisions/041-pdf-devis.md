# ADR-041 : PDF du devis

## Statut
Accepte

## Date
2026-09-02

## Contexte
Le PDF de devis (PRD §359, F15) était explicitement hors périmètre de l'ADR-036
(e-mail de confirmation de commande) et de la livraison initiale du configurateur
(F14). Besoin exprimé par l'utilisatrice : un PDF conforme à la maquette Figma
(714:9296), attaché à l'entité `Quote` en back-office (ouvrable depuis
`/admin/content/devis/{id}` dans une nouvelle fenêtre), joint aux 2 e-mails de
commande, stocké sur le serveur dans un répertoire dédié, et ré-éditable (écrase le
fichier d'origine) quand le devis change après coup.

Aucune librairie PDF n'était vendorisée. Aucun champ de `QuoteEquipmentLine` ne
portait la référence catalogue affichée par la maquette (colonne « Référence »).

## Options considerees

### Librairie de génération

#### Option A (retenue) : `dompdf/dompdf`
- Avantages : pur PHP, aucun binaire externe (contrairement à wkhtmltopdf), conversion
  HTML/CSS → PDF directe. Le rendu de la page détail devis (`QuoteDetailController`)
  est déjà 100 % custom (pas de formatters de champ ni de view mode) : une lib légère
  évite une couche d'abstraction supplémentaire.
- Inconvenients : pas de moteur configurable, support CSS limité (pas de flexbox
  fiable) — composé avec des layouts `display: table`/tableaux classiques, bien
  supportés.

#### Option B : module contrib `entity_print`
- Avantages : intégration Drupal (formulaire d'export, permissions dédiées),
  pense pour imprimer des entity view modes.
- Inconvenients : abstraction non justifiée ici (aucun view mode a exporter, tout
  le rendu est déjà construit à la main) — écartée (CLAUDE.md §2, simplicité
  d'abord).

**Décision** : Option A, confirmée avec l'utilisatrice.

### Colonne « Référence » du tableau d'équipements

`equipment_price.reference` (catalogue F17) existe déjà, mais `QuoteEquipmentLine`
n'en gardait jamais de copie gelée — la maquette montre pourtant une référence par
ligne (ex. `W1234567`).

**Décision, confirmée avec l'utilisatrice** : nouveau champ gelé
`QuoteEquipmentLine::reference` (string, copié depuis `equipment_price.reference` à
la création, même mécanisme que `unit_price`), **non affiché ailleurs que dans le
PDF pour l'instant** — `QuoteDetailController` reste inchangé. Un 6e
`hook_update_N` (`drivematic_configurator_update_11006`) installe le champ ; les
lignes déjà persistées restent vides (même limite assumée qu'ADR-040 pour
`quote_discount_change` : pas de backfill rétroactif possible).

### Portée de la génération/régénération

**Décision, confirmée avec l'utilisatrice** : le PDF est généré **uniquement** au
clic « Commander » (`DeliveryForm::orderSubmit()`), jamais à « Enregistrer le
devis ». Il est **régénéré** (fichier écrasé, même nom) à chaque remise Drive Matic
enregistrée sur un devis existant (`QuoteDiscountForm::submitForm()`) — seul autre
événement qui change les prix après la commande. Un devis resté « À finaliser » n'a
donc jamais de PDF, et le lien « Voir le PDF du devis » sur la page de détail
n'apparaît que si le fichier existe réellement sur le disque
(`file_exists($generator->getUri($quote))`) — pas de condition sur le statut,
gracieux si une génération a échoué.

### Date affichée sur le document

Le champ « Date » de la maquette n'a pas d'équivalent gelé univoque sur `Quote`
(`created` vs `date_commande` auraient des lectures différentes selon le statut).

**Décision, confirmée avec l'utilisatrice** : toujours la date du jour de
génération/régénération (`date('d/m/Y')`), pas une date gelée sur le devis — le
document réaffiche sa date d'édition à chaque écrasement, y compris lors d'une
remise DM ultérieure.

### Totaux par véhicule / par configuration

Ces bandeaux (« Tarif par véhicule »/« Tarif total véhicules ») n'existent nulle
part en stock (seuls les totaux GÉNÉRAUX du devis, `Quote::total_*`, sont tenus à
jour par `QuoteDiscountForm::recalculateTotals()`). `QuotePdfGenerator` les
recalcule à la génération à partir des lignes gelées, via
`QuoteEquipmentLine::getEffectiveDiscountedUnitPrice()`/`getEffectiveDiscountedHt()`
(remise partenaire PUIS remise DM, ADR-038) puis division par `vehicle_count` — de
l'arithmétique sur des valeurs déjà gelées, jamais une relecture du catalogue.
Contrairement à `QuoteForm` (écran 2, un seul bandeau si `vehicle_count == 1`), la
maquette du PDF affiche **toujours les deux bandeaux**, y compris quand ils sont
identiques (véhicule unique) — reproduit tel quel (« respecter EXACTEMENT la
maquette »), divergence assumée entre les deux écrans.

### Stockage et route de téléchargement

**Décision** : fichier écrit sur `private://devis-pdf/{reference}.pdf` (répertoire
dédié, écrasé à chaque régénération via `FileSystemInterface::saveData(...,
FileExists::Replace)`) — jamais de File entity managée (pas de champ/upload
utilisateur, un fichier système régénérable). Servi par une route contrôleur dédiée
(`QuoteDetailController::pdf()`, `_entity_access: quote.view`, même permission que
la page de détail) plutôt que par `system.private_file_download` +
`hook_file_download()` — évite de dépendre d'un hook facultatif qui, oublié,
refuserait silencieusement l'accès. Réponse en `Content-Disposition: inline` (pas
de téléchargement forcé) pour s'ouvrir dans l'onglet ciblé par le lien
`target="_blank"`.

### Pièce jointe aux e-mails

`$message['params']['attachments']` (format `filepath`/`filename`/`filemime`, déjà
supporté par `LegacyMailerHelper::emailFromArray()`, cf. ADR-036) — construit une
seule fois dans `DeliveryForm::orderSubmit()` (génération faite une fois, jointe
identique aux 2 e-mails). Un échec de génération ne bloque ni la commande déjà
enregistrée ni l'envoi des e-mails : ceux-ci partent alors sans pièce jointe,
erreur journalisée (même posture que les échecs d'envoi déjà en place, ADR-036).

## Decision
`dompdf/dompdf` (composer), nouveau service `QuotePdfGenerator`
(`src/Service/QuotePdfGenerator.php`) + gabarit Twig autonome
(`templates/quote-pdf.html.twig`, CSS inline, logo en data URI — Dompdf ne charge
aucune ressource distante). Toutes les valeurs sont pré-formatées en PHP (jamais de
logique dans le gabarit, même convention que les e-mails webform). Police de
substitution DejaVu Sans (native Dompdf) à la place d'« Inter » (maquette) — aucune
police web embarquée, mise en page/couleurs/bordures reproduites à l'identique.

## Consequences
- Nouveau champ `QuoteEquipmentLine::reference`, 6e `hook_update_N` — `drush updb`
  à rejouer sur tout environnement avant déploiement.
- Fichiers impactés : `Entity/QuoteEquipmentLine.php`, `Service/QuoteCalculator.php`,
  `Service/QuotePersister.php`, `Service/QuotePdfGenerator.php` (nouveau),
  `templates/quote-pdf.html.twig` (nouveau), `Controller/QuoteDetailController.php`,
  `Form/DeliveryForm.php`, `Form/QuoteDiscountForm.php`,
  `drivematic_configurator.{install,module,routing.yml,services.yml}`,
  `composer.json`/`composer.lock`.
- Vérifié de bout en bout sur un devis réel (`W20260902-001`, quote 12) : génération
  au clic Commander simulée directement via le service, téléchargement authentifié
  (200, PDF valide 1 page) et anonyme (403), régénération sur remise DM confirmée
  (mtime/contenu/totaux changés), devis restauré à son état d'origine après le test.
- **Limite assumée** : les lignes d'équipement créées avant ce champ n'ont pas de
  référence rétroactive (colonne vide dans leur PDF, y compris s'il est régénéré
  plus tard).
