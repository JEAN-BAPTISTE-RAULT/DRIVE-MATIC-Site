/**
 * Fondu discret a l'arrivee au scroll.
 * Sous `html.js` uniquement (amelioration progressive : le contenu reste
 * visible sans JS) : bascule `.is-visible` sur chaque `[data-dm-reveal]`
 * selon qu'il est dans le viewport ou non (rejoue a chaque passage, pas
 * seulement au premier), pour que le CSS du composant anime l'opacite/le
 * deplacement. Desactive sous `prefers-reduced-motion` (toujours visible,
 * sans observer).
 */
(function (Drupal, once) {
  Drupal.behaviors.driveMaticReveal = {
    attach(context) {
      const reduce = window.matchMedia(
        '(prefers-reduced-motion: reduce)',
      ).matches;

      once('dm-reveal', '[data-dm-reveal]', context).forEach((el) => {
        if (reduce || typeof IntersectionObserver === 'undefined') {
          el.classList.add('is-visible');
          return;
        }

        const observer = new IntersectionObserver(
          (entries) => {
            entries.forEach((entry) => {
              el.classList.toggle('is-visible', entry.isIntersecting);
            });
          },
          { threshold: 0.15 },
        );
        observer.observe(el);
      });
    },
  };
})(Drupal, once);
