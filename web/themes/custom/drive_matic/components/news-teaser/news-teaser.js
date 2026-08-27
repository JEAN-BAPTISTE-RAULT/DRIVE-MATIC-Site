/**
 * @file
 * Rend l'image de la carte news-teaser cliquable (liste des actualités).
 *
 * Le bloc de texte (`.news-teaser__body`) est déjà cliquable en entier via un
 * `::before` étiré sur le lien "Lire la suite" (cf. SCSS). L'image, elle, est
 * une SŒUR de ce lien — pas une descendante — et ne peut donc pas hériter son
 * clic par un pseudo-élément : ce fichier complète le seul morceau que le CSS
 * seul ne peut pas couvrir. Amélioration progressive : sans JS, seul le bloc
 * de texte reste cliquable, l'image ne promet pas un curseur qu'elle ne tient
 * pas (cf. `.news-teaser.is-ready` en SCSS).
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.drivematicNewsTeaserMedia = {
    attach(context) {
      once('dm-news-teaser-media', '.news-teaser', context).forEach(
        (teaser) => {
          const media = teaser.querySelector('.news-teaser__media');
          const link = teaser.querySelector('.news-teaser__more');
          if (!media || !link) {
            return;
          }

          media.addEventListener('click', () => link.click());
          teaser.classList.add('is-ready');
        },
      );
    },
  };
})(Drupal, once);
