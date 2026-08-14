/**
 * Slideshow accessible — wrapper Swiper.
 * Sous `html.js` uniquement, initialise Swiper sur chaque `[data-dm-slideshow]`
 * comportant au moins 2 diapositives (sinon on laisse la liste statique — cf.
 * degradation « liste si un seul item »). Modules : navigation (fleches),
 * pagination (si un element [data-dm-slideshow-pagination] est present dans le
 * conteneur du composant) + a11y. `prefers-reduced-motion` supprime l'animation.
 * Fleches et pagination sont cherchees dans le conteneur du composant, ce qui
 * autorise les deux placements de la maquette : superposees a la piste
 * (`jumbo_home`) ou dans l'en-tete du bloc, hors `.swiper` (`history`).
 *
 * Options par data-attribut (sur l'element `[data-dm-slideshow]`) :
 *   - data-dm-slideshow-per-view : nombre ou "auto" (defaut 1)
 *   - data-dm-slideshow-space    : espace entre diapositives en px (defaut 24)
 */
(function (Drupal, once) {
  Drupal.behaviors.driveMaticSlideshow = {
    attach(context) {
      once('dm-slideshow', '[data-dm-slideshow]', context).forEach((el) => {
        const slides = el.querySelectorAll('.swiper-slide');
        if (slides.length < 2 || typeof Swiper === 'undefined') {
          return;
        }
        const reduce = window.matchMedia(
          '(prefers-reduced-motion: reduce)',
        ).matches;
        const perView = el.dataset.dmSlideshowPerView || '1';
        const scope = el.parentElement || el;
        const paginationEl = scope.querySelector(
          '[data-dm-slideshow-pagination]',
        );

        new Swiper(el, {
          speed: reduce ? 0 : 400,
          slidesPerView: perView === 'auto' ? 'auto' : Number(perView),
          spaceBetween: Number(el.dataset.dmSlideshowSpace || 24),
          navigation: {
            prevEl: scope.querySelector('[data-dm-slideshow-prev]'),
            nextEl: scope.querySelector('[data-dm-slideshow-next]'),
          },
          pagination: paginationEl
            ? { el: paginationEl, clickable: true }
            : false,
          a11y: {
            enabled: true,
          },
        });
      });
    },
  };
})(Drupal, once);
