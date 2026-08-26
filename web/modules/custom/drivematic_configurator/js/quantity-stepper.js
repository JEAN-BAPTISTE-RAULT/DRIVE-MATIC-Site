/**
 * @file
 * Boutons -/+ d'un champ quantite (configurateur, F14).
 *
 * Amelioration progressive : les boutons sont masques en CSS tant que le
 * conteneur `[data-quantity-stepper]` ne porte pas `is-ready` (pose ici, une
 * fois le comportement attache). Sans JS, le champ `number` reste utilisable
 * via les fleches natives du navigateur — meme logique que le declencheur de
 * `help-modal.js`.
 */
(function (Drupal, once) {
  'use strict';

  /**
   * Ramene une valeur dans les bornes [min, max].
   *
   * @param {number} value
   *   Valeur a contraindre.
   * @param {number} min
   *   Borne basse.
   * @param {number|null} max
   *   Borne haute, ou null si non bornee.
   *
   * @return {number}
   *   Valeur contrainte.
   */
  function clamp(value, min, max) {
    if (Number.isNaN(value)) {
      return min;
    }
    if (value < min) {
      return min;
    }
    if (max !== null && value > max) {
      return max;
    }
    return value;
  }

  /**
   * Active/desactive les boutons -/+ selon la valeur courante.
   *
   * @param {HTMLElement} wrapper
   *   Conteneur `[data-quantity-stepper]`.
   * @param {HTMLInputElement} input
   *   Le champ `number`.
   * @param {number} min
   *   Borne basse.
   * @param {number|null} max
   *   Borne haute, ou null si non bornee.
   */
  function updateButtons(wrapper, input, min, max) {
    const value = parseInt(input.value, 10);
    const decrement = wrapper.querySelector('[data-quantity-decrement]');
    const increment = wrapper.querySelector('[data-quantity-increment]');
    if (decrement) {
      decrement.disabled = value <= min;
    }
    if (increment) {
      increment.disabled = max !== null && value >= max;
    }
  }

  Drupal.behaviors.drivematicQuantityStepper = {
    attach: function (context) {
      once('dm-quantity-stepper', '[data-quantity-stepper]', context).forEach(
        function (wrapper) {
          const input = wrapper.querySelector('input[type="number"]');
          const decrement = wrapper.querySelector('[data-quantity-decrement]');
          const increment = wrapper.querySelector('[data-quantity-increment]');
          if (!input || !decrement || !increment) {
            return;
          }

          const min = input.min !== '' ? parseInt(input.min, 10) : 0;
          const max = input.max !== '' ? parseInt(input.max, 10) : null;

          function step(delta) {
            input.value = clamp(
              (parseInt(input.value, 10) || 0) + delta,
              min,
              max,
            );
            input.dispatchEvent(new Event('change', { bubbles: true }));
            updateButtons(wrapper, input, min, max);
          }

          decrement.addEventListener('click', function () {
            step(-1);
          });
          increment.addEventListener('click', function () {
            step(1);
          });
          input.addEventListener('input', function () {
            updateButtons(wrapper, input, min, max);
          });

          updateButtons(wrapper, input, min, max);
          wrapper.classList.add('is-ready');
        },
      );
    },
  };
})(Drupal, once);
