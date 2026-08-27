/**
 * @file
 * Affichage progressif de l'etape 1 du configurateur (F14).
 *
 * Amelioration progressive (CSS `html.js`, meme pattern que l'accordeon) :
 * les sections « Sélectionner les équipements »/« Quantité » restent
 * masquees tant que marque+modele+motorisation ne sont pas tous les 3
 * renseignes sur un bloc de configuration (classe `has-vehicle` posee sur
 * `.configurator-form__card`) ; le bouton « Voir mon devis » reste
 * desactive tant qu'aucun equipement n'est coche, sur AUCUN bloc. Sans JS,
 * tout reste visible et le bouton reste actif — le controle qui compte
 * (une configuration sans equipement est abandonnee) reste cote serveur,
 * ConfigurationForm::submitForm(), jamais fie a ceci.
 */
(function (Drupal, once) {
  'use strict';

  /**
   * @param {HTMLElement} cascade
   *   Conteneur `[data-vehicle-cascade]`.
   *
   * @return {boolean}
   *   TRUE si marque, modele et motorisation ont tous une valeur.
   */
  function vehicleComplete(cascade) {
    return ['brand', 'model', 'motorisation'].every(function (role) {
      const select = cascade.querySelector(
        '[data-vehicle-role="' + role + '"]',
      );
      return !!select && select.value !== '';
    });
  }

  /**
   * @param {HTMLElement} cascade
   *   Conteneur `[data-vehicle-cascade]`.
   */
  function refreshCard(cascade) {
    const card = cascade.closest('.configurator-form__card');
    if (card) {
      card.classList.toggle('has-vehicle', vehicleComplete(cascade));
    }
  }

  /**
   * @param {HTMLFormElement} form
   *   Le formulaire du configurateur.
   *
   * @return {boolean}
   *   TRUE si au moins un equipement est coche, sur n'importe quel bloc.
   */
  function anyEquipmentChecked(form) {
    return Array.from(
      form.querySelectorAll(
        '.configurator-form__equipment-item input[type="checkbox"]',
      ),
    ).some(function (checkbox) {
      return checkbox.checked;
    });
  }

  /**
   * @param {HTMLFormElement} form
   *   Le formulaire du configurateur.
   */
  function refreshSubmit(form) {
    const submit = form.querySelector('.configurator-form__submit');
    if (submit) {
      submit.disabled = !anyEquipmentChecked(form);
    }
  }

  Drupal.behaviors.drivematicConfiguratorReveal = {
    attach: function (context) {
      // Reevalue a chaque attache (chargement initial ET rechargement AJAX
      // du bloc « configurations », qui remplace la sous-arborescence
      // entiere — chaque cascade survivante ou nouvelle y est retraitee).
      once('dm-configurator-reveal', '[data-vehicle-cascade]', context).forEach(
        function (cascade) {
          const form = cascade.closest('form');
          refreshCard(cascade);
          if (form) {
            refreshSubmit(form);
          }
          ['brand', 'model', 'motorisation'].forEach(function (role) {
            const select = cascade.querySelector(
              '[data-vehicle-role="' + role + '"]',
            );
            if (select) {
              select.addEventListener('change', function () {
                refreshCard(cascade);
              });
            }
          });
        },
      );

      // Ecoute deleguee (le formulaire lui-meme n'est jamais remplace par
      // l'AJAX, contrairement aux blocs de configuration) : couvre aussi
      // bien les cases existantes que celles d'un bloc ajoute ensuite.
      once(
        'dm-configurator-reveal-submit',
        '.configurator-form',
        context,
      ).forEach(function (form) {
        form.addEventListener('change', function (event) {
          if (
            event.target.matches(
              '.configurator-form__equipment-item input[type="checkbox"]',
            )
          ) {
            refreshSubmit(form);
          }
        });
      });
    },
  };
})(Drupal, once);
