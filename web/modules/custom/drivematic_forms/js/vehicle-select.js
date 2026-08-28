/**
 * @file
 * Cascade marque -> modele -> motorisation (webform contact, configurateur).
 *
 * Filtre les options de « modele » selon la « marque » choisie, puis celles de
 * « motorisation » selon le « modele » choisi. Les correspondances sont fournies
 * par drupalSettings.drivematicForms. Sans JS, les listes restent completes.
 *
 * Chaque cascade est un conteneur `[data-vehicle-cascade]` portant 3 selects
 * `[data-vehicle-role="brand|model|motorisation"]`. Le ciblage par attribut
 * (plutot que par `name` + `closest('form')`) permet plusieurs cascades
 * independantes sur une meme page (configurateur, un bloc par vehicule) — un
 * ciblage par `name` unique ne le permettrait pas, puisque `name` se repete
 * ou varie selon l'imbrication (`configurations[0][vehicle][brand]`, etc.).
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
    // Preserve la valeur courante si elle reste valide dans la nouvelle
    // liste — necessaire au premier attach d'un formulaire pre-rempli
    // (brouillon tempstore, configurateur etape 1) : sans ca, cette
    // reconstruction (appelee inconditionnellement a l'attache, pas
    // seulement sur un changement utilisateur) effacait modele/motorisation
    // deja valides, y compris quand la marque n'avait pas change.
    const currentValue = select.value;
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
    select.value =
      allowed.indexOf(parseInt(currentValue, 10)) !== -1 ? currentValue : '';
  }

  Drupal.behaviors.drivematicVehicleSelect = {
    attach: function (context) {
      const settings = drupalSettings.drivematicForms || {};
      const modelsByBrand = settings.modelsByBrand || {};
      const motosByModel = settings.motosByModel || {};

      once('dm-vehicle', '[data-vehicle-cascade]', context).forEach(
        function (cascade) {
          const brandSelect = cascade.querySelector(
            '[data-vehicle-role="brand"]',
          );
          const modelSelect = cascade.querySelector(
            '[data-vehicle-role="model"]',
          );
          const motoSelect = cascade.querySelector(
            '[data-vehicle-role="motorisation"]',
          );
          if (!brandSelect || !modelSelect || !motoSelect) {
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
