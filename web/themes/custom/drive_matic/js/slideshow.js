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
 *   - data-dm-slideshow-autoplay : delai en ms entre deux glissements
 *     automatiques (absent = pas de defilement automatique). Glissement
 *     rapide et fluide (pas de saut brusque), rewind en douceur en fin de
 *     piste. Le defilement manuel (fleches, glisser) reste actif ; desactive
 *     sous `prefers-reduced-motion`.
 *   - data-dm-slideshow-offset-after-gutter : espace apres la derniere
 *     diapositive egal a la gouttiere de droite de la home pour un
 *     paragraphe qui NE deborde PAS (symetrise une piste qui deborde
 *     volontairement a droite, jumbo_home/news_home, ADR-008). Mesure sur
 *     l'ecran plutot que lu depuis `--dm-gutter` : au-dela du point ou le
 *     paragraphe atteint son `max-width` et se centre (`margin-inline:
 *     auto`), l'ecart reel au bord de fenetre depasse la seule gouttiere
 *     (ex. mesure sur `.grid`, un paragraphe non debordant : 40px a 1440px
 *     de large, 280px a 1920px — jamais une valeur fixe). La propre bordure
 *     GAUCHE de l'element `[data-dm-slideshow]` porte deja exactement cette
 *     distance (gouttiere + marge de centrage eventuelle) : recopiee telle
 *     quelle a droite, sans recalculer la formule de centrage separement.
 *     Recalcule au redimensionnement.
 *   - data-dm-slideshow-offset-after-min-width : restreint l'option
 *     ci-dessus a un palier (px) — absent = s'applique a toute largeur.
 *
 * ⚠️ Ne pas combiner l'autoplay avec `freeMode`/`loop` pour un rendu
 * "continu" : ca laisse Swiper en etat `animating` permanent, ce qui lui
 * fait ignorer les clics sur les fleches (garde interne anti-double-clic).
 * Deja tente et corrige (defilement marques partenaires, 09/09/2026).
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
        const autoplayDelay = el.dataset.dmSlideshowAutoplay;
        const autoplaying = !!autoplayDelay && !reduce;

        const offsetAfterGutter = 'dmSlideshowOffsetAfterGutter' in el.dataset;
        const offsetAfterMinWidth = Number(
          el.dataset.dmSlideshowOffsetAfterMinWidth || 0,
        );
        const getOffsetAfter = () => {
          if (!offsetAfterGutter || window.innerWidth < offsetAfterMinWidth) {
            return 0;
          }
          return el.getBoundingClientRect().left;
        };

        new Swiper(el, {
          speed: reduce ? 0 : 500,
          slidesPerView: perView === 'auto' ? 'auto' : Number(perView),
          spaceBetween: Number(el.dataset.dmSlideshowSpace || 24),
          slidesOffsetAfter: getOffsetAfter(),
          rewind: autoplaying,
          navigation: {
            prevEl: scope.querySelector('[data-dm-slideshow-prev]'),
            nextEl: scope.querySelector('[data-dm-slideshow-next]'),
          },
          pagination: paginationEl
            ? { el: paginationEl, clickable: true }
            : false,
          autoplay: autoplaying
            ? {
                delay: Number(autoplayDelay),
                disableOnInteraction: false,
                pauseOnMouseEnter: true,
              }
            : false,
          a11y: {
            enabled: true,
          },
          on: {
            resize(instance) {
              if (offsetAfterGutter) {
                instance.params.slidesOffsetAfter = getOffsetAfter();
                instance.update();
              }
            },
          },
        });
      });
    },
  };
})(Drupal, once);
