/**
 * @file
 * Bascule d'affichage du mot de passe + hauteur des cartes d'action
 * (SDC « Page :: Connexion »).
 *
 * Le bouton de bascule est rendu par `_drivematic_forms_add_password_toggle()`
 * (drivematic_forms.module) via `#field_suffix` : masque en CSS tant que
 * `.login-panel` ne porte pas `is-ready`, il reste inerte sans JS et le champ
 * fonctionne comme un mot de passe standard, sans bouton qui ne ferait rien.
 *
 * L'egalisation de hauteur des 3 cartes `.login-panel__action` est necessaire
 * uniquement en mobile : en desktop, les 3 cartes partagent la meme ligne
 * d'une grille CSS (`grid-template-columns: repeat(3, ...)`), et l'etirement
 * par defaut du Grid (`align-items: stretch`) les egalise nativement. En
 * mobile, elles sont empilees — chacune dans sa PROPRE ligne de grille — et
 * aucun mecanisme CSS declaratif ne peut egaliser la hauteur d'elements qui
 * ne partagent pas une ligne commune : leur hauteur naturelle depend alors du
 * texte (1 a 3 lignes selon la carte), d'ou l'ecart observe sans JS. Sans
 * JS, les cartes gardent donc leur hauteur naturelle (degradation
 * gracieuse, pas de casse visuelle).
 */
(function (Drupal, once) {
  'use strict';

  const DESKTOP_QUERY = '(width >= 992px)';

  function equalizeActionHeights(panel) {
    const actions = Array.from(panel.querySelectorAll('.login-panel__action'));
    if (actions.length < 2) {
      return;
    }

    actions.forEach((action) => {
      action.style.minHeight = '';
    });

    if (window.matchMedia(DESKTOP_QUERY).matches) {
      return;
    }

    const tallest = Math.max(...actions.map((action) => action.offsetHeight));
    actions.forEach((action) => {
      action.style.minHeight = `${tallest}px`;
    });
  }

  Drupal.behaviors.driveMaticLoginPanel = {
    attach(context) {
      once('dm-login-panel', '.login-panel', context).forEach((panel) => {
        const toggle = panel.querySelector('[data-password-toggle]');
        const input = panel.querySelector('input[type="password"]');
        const label = toggle ? toggle.querySelector('span') : null;
        if (toggle && input) {
          toggle.addEventListener('click', () => {
            const reveal = input.type === 'password';
            input.type = reveal ? 'text' : 'password';
            toggle.setAttribute('aria-pressed', String(reveal));
            toggle.classList.toggle(
              'login-panel__password-toggle--visible',
              reveal,
            );
            if (label) {
              label.textContent = reveal
                ? Drupal.t('Masquer le mot de passe')
                : Drupal.t('Afficher le mot de passe');
            }
          });

          panel.classList.add('is-ready');
        }

        equalizeActionHeights(panel);
        window.addEventListener('resize', () => equalizeActionHeights(panel));
      });
    },
  };
})(Drupal, once);
