/**
 * @file
 * Comportement de la modale d'aide (SDC « Aide :: Modale illustrée »).
 *
 * - Le markup serveur enferme la modale dans un <template>, parce que le
 *   composant s'insère dans le <label> du champ : on y clone le <dialog> vers
 *   <body>, où il peut vivre sans casser le HTML ni le contexte d'empilement.
 * - <dialog>.showModal() apporte nativement le piège de focus, la fermeture par
 *   Échap, le retour du focus au déclencheur et ::backdrop : rien à réécrire.
 * - Un clic sur le fond ferme la modale (le clic atterrit sur le <dialog>
 *   lui-même, le contenu étant dans .help-modal__header / __image).
 * - Amélioration progressive : ce fichier ne fait qu'ajouter la classe `is-ready`
 *   qui révèle le déclencheur. Sans JS, le déclencheur reste masqué et la phrase
 *   de repli s'affiche.
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.driveMaticHelpModal = {
    attach(context) {
      once('dm-help-modal', '.help-modal', context).forEach((wrapper) => {
        const trigger = wrapper.querySelector('[data-help-modal-trigger]');
        const template = wrapper.querySelector('[data-help-modal-content]');
        if (!trigger || !template) {
          return;
        }

        const dialog = template.content.firstElementChild.cloneNode(true);
        if (typeof dialog.showModal !== 'function') {
          return;
        }
        document.body.appendChild(dialog);

        trigger.addEventListener('click', () => dialog.showModal());

        dialog
          .querySelector('[data-help-modal-close]')
          .addEventListener('click', () => dialog.close());

        // Clic sur le fond : la cible n'est le <dialog> que hors de son contenu.
        dialog.addEventListener('click', (event) => {
          if (event.target === dialog) {
            dialog.close();
          }
        });

        wrapper.classList.add('is-ready');
      });
    },
  };
})(Drupal, once);
