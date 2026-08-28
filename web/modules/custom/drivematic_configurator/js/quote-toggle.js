/**
 * @file
 * Repli mobile des configurations de l'écran Devis (F14, étape 2/3).
 *
 * Amélioration progressive : le panneau de détail (`.quote-form__card-details`)
 * reste visible par défaut (desktop, et mobile sans JS — CSS `html.js` +
 * media query, cf. _quote-form.scss). Sous JS et en dessous du palier
 * mobile, il ne se replie que si l'utilisateur clique le bandeau
 * véhicule — chaque configuration se déplie indépendamment des autres.
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.drivematicQuoteToggle = {
    attach: function (context) {
      once('dm-quote-toggle', '[data-quote-toggle]', context).forEach(
        function (card) {
          const trigger = card.querySelector('.quote-form__card-trigger');
          if (!trigger) {
            return;
          }
          trigger.addEventListener('click', function () {
            const expanded = card.classList.toggle('is-expanded');
            trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
          });
        },
      );
    },
  };
})(Drupal, once);
