/**
 * @file
 * Repli mobile des configurations de l'écran Devis (F14, étape 2/3).
 *
 * Amélioration progressive : le panneau de détail (`.quote-form__details`)
 * reste visible par défaut (desktop, et mobile sans JS — CSS `html.js` +
 * media query, cf. _quote-form.scss). Sous JS et en dessous du palier
 * mobile, il ne se replie que si l'utilisateur clique le résumé — chaque
 * configuration se déplie indépendamment des autres.
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.drivematicQuoteToggle = {
    attach: function (context) {
      once('dm-quote-toggle', '[data-quote-toggle]', context).forEach(
        function (summary) {
          const trigger = summary.querySelector('.quote-form__summary-trigger');
          const configuration = summary.closest('.quote-form__configuration');
          if (!trigger || !configuration) {
            return;
          }
          trigger.addEventListener('click', function () {
            const expanded = configuration.classList.toggle('is-expanded');
            trigger.setAttribute('aria-expanded', expanded ? 'true' : 'false');
          });
        },
      );
    },
  };
})(Drupal, once);
