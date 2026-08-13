/**
 * @file
 * Comportement de l'accordéon (SDC « Bloc :: Accordéon »).
 *
 * - Pattern disclosure ARIA : chaque en-tête est un <button aria-expanded> lié à
 *   son panneau (aria-controls / role="region" / aria-labelledby).
 * - « L'ouverture d'un accordéon ferme le précédent » (F3/F4/F9) : un seul
 *   panneau ouvert à la fois.
 * - Fermés par défaut : au chargement, tous les panneaux sont repliés.
 * - Accessibilité : le <button> gère nativement Entrée/Espace et le focus ;
 *   `hidden` retire le contenu replié du flux et des lecteurs d'écran.
 * - Amélioration progressive : sans JS, les panneaux restent ouverts et lisibles
 *   (le markup serveur ne pose ni `aria-expanded` ni `hidden`).
 */
(function (Drupal, once) {
  'use strict';

  /**
   * Applique l'état ouvert/fermé à un couple bouton/panneau.
   *
   * @param {HTMLElement} trigger - Le bouton d'en-tête.
   * @param {HTMLElement} panel - Le panneau associé.
   * @param {boolean} open - true pour ouvrir, false pour fermer.
   */
  function setState(trigger, panel, open) {
    trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    panel.classList.toggle('is-open', open);
    if (open) {
      panel.removeAttribute('hidden');
    } else {
      panel.setAttribute('hidden', 'hidden');
    }
  }

  Drupal.behaviors.driveMaticAccordion = {
    attach(context) {
      once('dm-accordion', '.accordion', context).forEach((accordion) => {
        const items = Array.from(
          accordion.querySelectorAll('.accordion-element'),
        );

        items.forEach((item) => {
          const trigger = item.querySelector('.accordion-element__trigger');
          const panel = item.querySelector('.accordion-element__panel');
          if (!trigger || !panel) {
            return;
          }

          // État initial : fermé.
          setState(trigger, panel, false);

          trigger.addEventListener('click', () => {
            const willOpen = trigger.getAttribute('aria-expanded') !== 'true';

            // Ouverture : fermer d'abord tous les autres panneaux.
            if (willOpen) {
              items.forEach((other) => {
                if (other === item) {
                  return;
                }
                const otherTrigger = other.querySelector(
                  '.accordion-element__trigger',
                );
                const otherPanel = other.querySelector(
                  '.accordion-element__panel',
                );
                if (otherTrigger && otherPanel) {
                  setState(otherTrigger, otherPanel, false);
                }
              });
            }

            setState(trigger, panel, willOpen);
          });
        });
      });
    },
  };
})(Drupal, once);
