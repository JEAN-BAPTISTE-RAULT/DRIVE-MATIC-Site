/**
 * @file
 * Bascule la classe racine `no-js` -> `js` le plus tôt possible.
 *
 * Chargé en <head> (lib `drive_matic/js-detect`, header: true) : la classe est
 * posée avant le rendu du <body>, ce qui permet aux composants à amélioration
 * progressive (accordéon…) de s'afficher fermés « JS actif » sans flash, tout
 * en restant entièrement lisibles si le JavaScript est absent ou échoue.
 */
document.documentElement.classList.remove('no-js');
document.documentElement.classList.add('js');
