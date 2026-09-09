/**
 * @file
 * Comportement du menu 3 points (SDC « Menu :: Actions devis »).
 *
 * Simple bouton-divulgation (pas un menu ARIA complet avec navigation au
 * clavier par flèches) : chaque lien reste un élément nativement focusable/
 * activable, suffisant pour 1 à 3 liens. Un seul menu ouvert à la fois sur
 * la page (plusieurs lignes de devis peuvent porter ce composant) ; fermeture
 * au clic extérieur, à Échap (le focus revient alors au déclencheur), ou à
 * l'activation d'un lien.
 */
(function (Drupal, once) {
  'use strict';

  function closeMenu(toggle, menu) {
    menu.hidden = true;
    toggle.setAttribute('aria-expanded', 'false');
  }

  function closeAllMenus() {
    document
      .querySelectorAll('.quote-row-actions__menu:not([hidden])')
      .forEach((menu) => {
        closeMenu(menu.previousElementSibling, menu);
      });
  }

  Drupal.behaviors.driveMaticQuoteRowActions = {
    attach(context) {
      once(
        'dm-quote-row-actions',
        '.quote-row-actions__toggle',
        context,
      ).forEach((toggle) => {
        const menu = toggle.nextElementSibling;
        if (!menu) {
          return;
        }

        toggle.addEventListener('click', (event) => {
          event.stopPropagation();
          const isOpen = !menu.hidden;
          closeAllMenus();
          if (isOpen) {
            closeMenu(toggle, menu);
          } else {
            menu.hidden = false;
            toggle.setAttribute('aria-expanded', 'true');
          }
        });

        menu.addEventListener('keydown', (event) => {
          if (event.key === 'Escape') {
            closeMenu(toggle, menu);
            toggle.focus();
          }
        });
      });

      once('dm-quote-row-actions-global', 'body', context).forEach(() => {
        document.addEventListener('click', () => closeAllMenus());
        document.addEventListener('keydown', (event) => {
          if (event.key === 'Escape') {
            closeAllMenus();
          }
        });
      });
    },
  };
})(Drupal, once);
