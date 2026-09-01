<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Form;

/**
 * Detecte si la requete courante ouvre le formulaire en modale.
 *
 * Partage entre DeliveryAddressForm et DeliveryAddressDeleteForm : les deux
 * omettent leur `<h1>` manuel quand la reponse est destinee a une modale
 * Drupal core (`use-ajax`/`data-dialog-type: modal`, `_wrapper_format:
 * drupal_modal` en query string), pour ne pas dupliquer le titre deja porte
 * par le dialogue — uniquement necessaire en degradation sans JS (page
 * complete, jamais de modale).
 */
trait ModalRequestTrait {

  /**
   * Determine si la requete courante rend le formulaire pour une modale.
   *
   * `->get()` (pas seulement `->query`) : le cycle AJAX d'ouverture de la
   * modale peut passer par une sous-requete POST (soumission du formulaire
   * de dialogue Drupal core), auquel cas `_wrapper_format` reste accessible
   * via ce parametre generique meme s'il n'est plus dans la query string au
   * sens strict.
   */
  protected function isModalRequest(): bool {
    return \Drupal::request()->get('_wrapper_format') === 'drupal_modal';
  }

}
