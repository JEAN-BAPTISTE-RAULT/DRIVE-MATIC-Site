/**
 * @file
 * Bascule d'affichage du mot de passe (SDC « Page :: Connexion »).
 *
 * Le bouton est rendu par `_drivematic_forms_add_password_toggle()`
 * (drivematic_forms.module) via `#field_suffix` : masque en CSS tant que
 * `.login-panel` ne porte pas `is-ready`, il reste inerte sans JS et le champ
 * fonctionne comme un mot de passe standard, sans bouton qui ne ferait rien.
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.driveMaticLoginPanel = {
    attach(context) {
      once('dm-login-panel', '.login-panel', context).forEach((panel) => {
        const toggle = panel.querySelector('[data-password-toggle]');
        const input = panel.querySelector('input[type="password"]');
        const label = toggle ? toggle.querySelector('span') : null;
        if (!toggle || !input) {
          return;
        }

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
      });
    },
  };
})(Drupal, once);
