/**
 * Facade video generique — RGPD / perf.
 * La ressource tierce (iframe embed) est placee dans un <template> inerte et
 * n'est injectee dans le DOM qu'au clic sur la miniature. Comportement partage
 * par tous les composants video, pilote par des data-attributs (agnostique du
 * markup BEM de chaque composant) :
 *   - [data-dm-video-facade]  : conteneur
 *   - [data-dm-video-play]    : bouton de lecture (miniature)
 *   - template[data-dm-video-embed] : gabarit inerte contenant l'iframe embed
 */
(function (Drupal, once) {
  Drupal.behaviors.driveMaticVideoFacade = {
    attach(context) {
      once('dm-video-facade', '[data-dm-video-facade]', context).forEach(
        (facade) => {
          const button = facade.querySelector('[data-dm-video-play]');
          const template = facade.querySelector(
            'template[data-dm-video-embed]',
          );
          if (!button || !template) {
            return;
          }
          button.addEventListener('click', () => {
            button.replaceWith(template.content.cloneNode(true));
            template.remove();
            const iframe = facade.querySelector('iframe');
            if (iframe) {
              iframe.focus();
            }
          });
        },
      );
    },
  };
})(Drupal, once);
