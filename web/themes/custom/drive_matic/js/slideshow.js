/**
 * Slideshow accessible — wrapper Swiper.
 * Sous `html.js` uniquement, initialise Swiper sur chaque `[data-dm-slideshow]`
 * comportant au moins 2 diapositives (sinon on laisse la liste statique — cf.
 * degradation « liste si un seul item »). Modules : navigation (fleches) + a11y
 * (inclus dans le bundle). `prefers-reduced-motion` supprime l'animation.
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
        new Swiper(el, {
          speed: reduce ? 0 : 400,
          slidesPerView: 1,
          spaceBetween: 24,
          navigation: {
            prevEl: el.querySelector('[data-dm-slideshow-prev]'),
            nextEl: el.querySelector('[data-dm-slideshow-next]'),
          },
          a11y: {
            enabled: true,
          },
        });
      });
    },
  };
})(Drupal, once);
