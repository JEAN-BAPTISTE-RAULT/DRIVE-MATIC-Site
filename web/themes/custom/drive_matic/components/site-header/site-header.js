/**
 * @file
 * Comportement du header (SDC « Site :: En-tête », F2).
 *
 * - Flyouts desktop (rubriques de nav + dropdown « Espace partenaire ») :
 *   meme pattern disclosure ARIA que l'accordéon (aria-expanded / classe
 *   `is-open`), un seul panneau ouvert a la fois, fermeture au clic exterieur
 *   et a Échap.
 * - Tiroir mobile : <dialog>.showModal() apporte nativement le piège de
 *   focus, la fermeture par Échap et le retour du focus au déclencheur (même
 *   pattern que le SDC `help-modal`). La navigation entre le panneau racine
 *   et les sous-panneaux (retour/fermer) est géré ici par une classe
 *   `is-active` sur le panneau ciblé.
 * - Le déclencheur « Espace partenaire » sert les deux mondes : dropdown
 *   inline en desktop, panneau du tiroir en mobile — le choix se fait au
 *   clic, selon le point de rupture actif (992px, « lg »).
 * - Amélioration progressive : le markup serveur ne pose ni `aria-expanded`
 *   ni classe d'état ; ce fichier les pose à l'attache. Sans JS, tout reste
 *   visible (cf. site-header.scss).
 */
(function (Drupal, once) {
  'use strict';

  var DESKTOP_QUERY = '(width >= 992px)';

  /**
   * Applique l'état ouvert/fermé à un couple déclencheur/panneau.
   *
   * @param {HTMLElement} trigger - Le bouton d'en-tête.
   * @param {HTMLElement} panel - Le panneau associé.
   * @param {boolean} open - true pour ouvrir, false pour fermer.
   */
  function setExpanded(trigger, panel, open) {
    trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    panel.classList.toggle('is-open', open);
  }

  Drupal.behaviors.driveMaticSiteHeader = {
    attach(context) {
      once('dm-site-header', '.site-header', context).forEach((header) => {
        const desktopMedia = window.matchMedia(DESKTOP_QUERY);

        // --- Flyouts desktop : nav + dropdown compte, un seul ouvert. ---
        const closables = [];

        function closeAll() {
          closables.forEach(({ trigger, panel }) =>
            setExpanded(trigger, panel, false),
          );
        }

        function registerFlyout(trigger, panel) {
          if (!trigger || !panel) {
            return;
          }
          setExpanded(trigger, panel, false);
          closables.push({ trigger, panel });
        }

        header
          .querySelectorAll('.site-header__nav-trigger')
          .forEach((trigger) => {
            const panel = header.querySelector(
              `#${trigger.getAttribute('aria-controls')}`,
            );
            registerFlyout(trigger, panel);
            trigger.addEventListener('click', () => {
              const willOpen = trigger.getAttribute('aria-expanded') !== 'true';
              closeAll();
              if (willOpen) {
                setExpanded(trigger, panel, true);
              }
            });
          });

        const accountTrigger = header.querySelector('[data-account-trigger]');
        const loggedIn =
          accountTrigger &&
          accountTrigger.getAttribute('data-logged-in') === '1';
        const accountPanel = loggedIn
          ? header.querySelector(
              `#${accountTrigger.getAttribute('aria-controls')}`,
            )
          : null;

        if (loggedIn) {
          registerFlyout(accountTrigger, accountPanel);
        }

        document.addEventListener('click', (event) => {
          if (!header.contains(event.target)) {
            closeAll();
          }
        });

        header.addEventListener('keydown', (event) => {
          if (event.key === 'Escape') {
            closeAll();
          }
        });

        // --- Tiroir mobile (<dialog>). ---
        const drawer = header.querySelector('.site-header__drawer');
        const canUseDialog = drawer && typeof drawer.showModal === 'function';

        function showDrawerPanel(name) {
          drawer.querySelectorAll('[data-drawer-panel]').forEach((panel) => {
            panel.classList.toggle(
              'is-active',
              panel.getAttribute('data-drawer-panel') === name,
            );
          });
        }

        function openDrawer(name) {
          if (!canUseDialog) {
            return;
          }
          showDrawerPanel(name);
          if (!drawer.open) {
            drawer.showModal();
          }
        }

        if (canUseDialog) {
          showDrawerPanel('root');

          drawer.querySelectorAll('[data-drawer-open]').forEach((trigger) => {
            trigger.addEventListener('click', () =>
              openDrawer(trigger.getAttribute('data-drawer-open')),
            );
          });

          drawer.querySelectorAll('[data-drawer-back]').forEach((trigger) => {
            trigger.addEventListener('click', () => showDrawerPanel('root'));
          });

          drawer.querySelectorAll('[data-drawer-close]').forEach((trigger) => {
            trigger.addEventListener('click', () => drawer.close());
          });

          // Reinitialise sur le panneau racine a la fermeture, quelle qu'en
          // soit la cause (bouton, Échap natif, clic sur le fond ci-dessous).
          drawer.addEventListener('close', () => showDrawerPanel('root'));

          // Clic sur le fond : la cible n'est le <dialog> que hors de son
          // contenu (meme pattern que help-modal.js).
          drawer.addEventListener('click', (event) => {
            if (event.target === drawer) {
              drawer.close();
            }
          });

          header
            .querySelector('.site-header__burger')
            .addEventListener('click', () => openDrawer('root'));
        }

        // --- Déclencheur « Espace partenaire » (utilisateur authentifié
        // uniquement — sinon c'est un simple lien de connexion, sans JS a
        // cabler). ---
        if (loggedIn) {
          accountTrigger.addEventListener('click', () => {
            if (desktopMedia.matches) {
              const willOpen =
                accountTrigger.getAttribute('aria-expanded') !== 'true';
              closeAll();
              if (willOpen) {
                setExpanded(accountTrigger, accountPanel, true);
              }
            } else {
              closeAll();
              openDrawer('account');
            }
          });
        }
      });
    },
  };
})(Drupal, once);
