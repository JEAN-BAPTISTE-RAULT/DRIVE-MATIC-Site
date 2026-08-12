/**
 * @file
 * Cascade marque -> modele -> motorisation du webform contact.
 *
 * Filtre les options de « modele » selon la « marque » choisie, puis celles de
 * « motorisation » selon le « modele » choisi. Les correspondances sont fournies
 * par drupalSettings.drivematicForms. Sans JS, les listes restent completes.
 */

(function (Drupal, once, drupalSettings) {
  'use strict';

  /**
   * Memorise les options non vides d'un select.
   *
   * @param {HTMLSelectElement} select
   *   Le select a lire.
   *
   * @return {Array<{value: string, text: string}>}
   *   Les options (hors option vide).
   */
  function cacheOptions(select) {
    return Array.from(select.options)
      .filter(function (option) {
        return option.value !== '';
      })
      .map(function (option) {
        return { value: option.value, text: option.textContent };
      });
  }

  /**
   * Reconstruit les options d'un select a partir d'une liste autorisee.
   *
   * @param {HTMLSelectElement} select
   *   Le select a reconstruire.
   * @param {Array<{value: string, text: string}>} cached
   *   Toutes les options possibles.
   * @param {Array<number>} allowed
   *   Les identifiants autorises.
   */
  function rebuild(select, cached, allowed) {
    const emptyOption = select.querySelector('option[value=""]');
    select.innerHTML = '';
    if (emptyOption) {
      select.appendChild(emptyOption.cloneNode(true));
    }
    cached.forEach(function (option) {
      if (allowed.indexOf(parseInt(option.value, 10)) !== -1) {
        const node = document.createElement('option');
        node.value = option.value;
        node.textContent = option.text;
        select.appendChild(node);
      }
    });
    select.value = '';
  }

  Drupal.behaviors.drivematicVehicleSelect = {
    attach: function (context) {
      const settings = drupalSettings.drivematicForms || {};
      const modelsByBrand = settings.modelsByBrand || {};
      const motosByModel = settings.motosByModel || {};

      once('dm-vehicle', 'select[name="marque"]', context).forEach(
        function (brandSelect) {
          const form = brandSelect.form;
          const modelSelect = form.querySelector('select[name="modele"]');
          const motoSelect = form.querySelector('select[name="motorisation"]');
          if (!modelSelect || !motoSelect) {
            return;
          }

          const allModels = cacheOptions(modelSelect);
          const allMotos = cacheOptions(motoSelect);

          function refreshMotos() {
            const allowed = motosByModel[modelSelect.value] || [];
            rebuild(motoSelect, allMotos, modelSelect.value ? allowed : []);
          }

          function refreshModels() {
            const allowed = modelsByBrand[brandSelect.value] || [];
            rebuild(modelSelect, allModels, brandSelect.value ? allowed : []);
            refreshMotos();
          }

          brandSelect.addEventListener('change', refreshModels);
          modelSelect.addEventListener('change', refreshMotos);
          refreshModels();
        },
      );
    },
  };
})(Drupal, once, drupalSettings);
