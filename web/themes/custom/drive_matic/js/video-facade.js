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
  /**
   * Leve le mute impose par video_embed_field au clic.
   *
   * Le module contrib force `mute=1` (YouTube) / `muted=1` (Vimeo) des que
   * l'autoplay est actif : sans interaction utilisateur, les navigateurs
   * bloquent de toute facon l'autoplay sonore, donc le rendu serveur reste
   * muet par securite. Le clic sur la façade EST cette interaction : on peut
   * donc lever le mute a ce moment precis, sans patcher le module contrib.
   * Ne s'applique que si le serveur a deja autorise l'autoplay (absent pour
   * les roles avec la permission « never autoplay videos »), pour respecter
   * ce choix editorial/accessibilite plutot que le contourner.
   */
  function unmuteIfAutoplaying(iframe) {
    if (!iframe || !iframe.src) {
      return;
    }
    let url;
    try {
      url = new URL(iframe.src, window.location.href);
    } catch {
      return;
    }
    if (url.searchParams.get('autoplay') !== '1') {
      return;
    }
    url.searchParams.delete('mute');
    url.searchParams.delete('muted');
    iframe.setAttribute(
      'allow',
      'autoplay; encrypted-media; picture-in-picture; fullscreen',
    );
    iframe.src = url.toString();
  }

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
            const fragment = template.content.cloneNode(true);
            unmuteIfAutoplaying(fragment.querySelector('iframe'));
            button.replaceWith(fragment);
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
