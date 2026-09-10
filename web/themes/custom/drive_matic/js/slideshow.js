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
 *   - data-dm-slideshow-edge-gutter : la piste occupe TOUTE la largeur du
 *     viewport pendant le defilement — la gouttiere n'apparait qu'aux deux
 *     extremites (retour utilisatrice, 2026-09-10) : a gauche uniquement
 *     quand la 1re diapositive y est (repos `isBeginning`), a droite
 *     uniquement quand la derniere y est (repos `isEnd`). Implemente via
 *     `slidesOffsetBefore`/`slidesOffsetAfter` (Swiper) plutot qu'un padding
 *     CSS sur le conteneur : un padding serait un cadre FIXE, visible a
 *     chaque position de defilement, alors que l'offset Swiper ne se
 *     retrouve visible qu'a la position de repos correspondante — au milieu
 *     du defilement, il a deja glisse hors champ. Valeur lue depuis
 *     `--dm-gutter` (plus de `max-width`/centrage a mesurer sur l'ecran
 *     depuis le retrait du `max-width` de ces paragraphes, meme date : la
 *     piste est nue, sans inset a compenser). Recalcule au
 *     redimensionnement.
 *   - data-dm-slideshow-edge-gutter-min-width : restreint l'option
 *     ci-dessus a un palier (px) — absent = s'applique a toute largeur.
 *
 * ⚠️ Piste plus etroite que son conteneur + les deux gouttieres (peu de
 * diapositives sur un tres large ecran — ex. jumbo_home avec seulement 2 des
 * 1 a 3 elements possibles, a partir de ~1920px) : ajouter les deux offsets
 * dans ce cas cree un faux surplus a faire defiler pour Swiper (isEnd jamais
 * atteint au repos), qui casse la navigation des le premier clic (translate
 * partiel, activeIndex bloque, fleche « suivant » masquee sans avoir rien
 * revele). `getEdgeOffsets()` ne decale donc les deux bords que si la piste
 * deborde reellement son conteneur une fois les deux gouttieres ajoutees —
 * sinon les deux valent 0 et Swiper se verrouille nativement
 * (isBeginning/isEnd vrais, les deux fleches masquees via la regle
 * `.swiper-button-disabled` deja en place), au prix d'une piste sans aucune
 * gouttiere dans ce cas rare plutot qu'une gouttiere incorrecte.
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

        const spaceBetween = Number(el.dataset.dmSlideshowSpace || 24);
        const edgeGutter = 'dmSlideshowEdgeGutter' in el.dataset;
        const edgeGutterMinWidth = Number(
          el.dataset.dmSlideshowEdgeGutterMinWidth || 0,
        );
        const getEdgeOffsets = () => {
          if (!edgeGutter || window.innerWidth < edgeGutterMinWidth) {
            return { before: 0, after: 0 };
          }
          const gutter =
            parseFloat(getComputedStyle(el).getPropertyValue('--dm-gutter')) ||
            0;
          const slideEls = el.querySelectorAll('.swiper-slide');
          const contentWidth =
            Array.from(slideEls).reduce(
              (sum, slide) => sum + slide.getBoundingClientRect().width,
              0,
            ) +
            spaceBetween * Math.max(slideEls.length - 1, 0);
          if (contentWidth + gutter * 2 <= el.getBoundingClientRect().width) {
            return { before: 0, after: 0 };
          }
          return { before: gutter, after: gutter };
        };
        const initialOffsets = getEdgeOffsets();

        new Swiper(el, {
          speed: reduce ? 0 : 500,
          slidesPerView: perView === 'auto' ? 'auto' : Number(perView),
          spaceBetween,
          slidesOffsetBefore: initialOffsets.before,
          slidesOffsetAfter: initialOffsets.after,
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
              if (edgeGutter) {
                const offsets = getEdgeOffsets();
                instance.params.slidesOffsetBefore = offsets.before;
                instance.params.slidesOffsetAfter = offsets.after;
                instance.update();
              }
            },
          },
        });
      });
    },
  };
})(Drupal, once);
