/**
 * @file
 * Comportement de la liste FAQ (SDC « Liste :: FAQ »).
 *
 * - Pattern disclosure ARIA : chaque en-tête est un <button aria-expanded> lié à
 *   son panneau (aria-controls / role="region" / aria-labelledby).
 * - « L'ouverture d'une question ferme la précédente » (F9) : une seule réponse
 *   ouverte à la fois.
 * - Fermées par défaut : au chargement, tous les panneaux sont repliés.
 * - Accessibilité : le <button> gère nativement Entrée/Espace et le focus ;
 *   `hidden` retire la réponse repliée du flux et des lecteurs d'écran.
 * - Amélioration progressive : sans JS, les réponses restent ouvertes et
 *   lisibles (le markup serveur ne pose ni `aria-expanded` ni `hidden`).
 *
 * Jumeau volontaire de `accordion.js` : la FAQ est une liste de contenus
 * filtrable, pas le paragraphe éditorial `accordion`. Les deux composants
 * doivent pouvoir évoluer séparément.
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

  Drupal.behaviors.driveMaticFaqList = {
    attach(context) {
      once('dm-faq-list', '.faq-list', context).forEach((list) => {
        const items = Array.from(list.querySelectorAll('.faq-question'));

        items.forEach((item) => {
          const trigger = item.querySelector('.faq-question__trigger');
          const panel = item.querySelector('.faq-question__panel');
          if (!trigger || !panel) {
            return;
          }

          // État initial : fermé.
          setState(trigger, panel, false);

          trigger.addEventListener('click', () => {
            const willOpen = trigger.getAttribute('aria-expanded') !== 'true';

            // Ouverture : fermer d'abord toutes les autres réponses.
            if (willOpen) {
              items.forEach((other) => {
                if (other === item) {
                  return;
                }
                const otherTrigger = other.querySelector(
                  '.faq-question__trigger',
                );
                const otherPanel = other.querySelector('.faq-question__panel');
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
