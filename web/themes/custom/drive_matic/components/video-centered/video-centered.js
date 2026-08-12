/**
 * SDC « Vidéo centrée » — façade.
 * Au clic sur la miniature, on clone le contenu du <template> (l'iframe embed)
 * dans le DOM : la ressource tierce n'est chargée qu'à ce moment (perf + RGPD).
 */
(function (Drupal, once) {
  Drupal.behaviors.driveMaticVideoFacade = {
    attach(context) {
      once('dm-video-facade', '[data-dm-video-facade]', context).forEach(
        (facade) => {
          const button = facade.querySelector('.video-centered__play');
          const template = facade.querySelector(
            'template.video-centered__embed',
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
