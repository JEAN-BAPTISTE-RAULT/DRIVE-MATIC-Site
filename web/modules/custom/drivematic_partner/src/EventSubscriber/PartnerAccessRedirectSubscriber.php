<?php

declare(strict_types=1);

namespace Drupal\drivematic_partner\EventSubscriber;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirige un anonyme refuse sur une route reservee aux partenaires.
 *
 * Sans ce correctif, une route `_role: 'partenaire'` (personal_information,
 * configurateur) renvoie un 403 brut a un visiteur anonyme — y compris
 * lorsqu'il arrive depuis un lien public (ex. le bloc « Configurez votre
 * véhicule » de la home et des pages produit, qui pointe vers /configurer
 * sans reserve d'acces). On redirige plutot vers la connexion, avec le lien
 * d'origine en `destination` (deja honore nativement par
 * `RedirectResponseSubscriber` apres connexion reussie).
 *
 * Scope volontairement etroit : seule l'exigence de routing `_role:
 * partenaire` declenche la redirection, pas un `_custom_access`/
 * `_permission` qui refuserait pour une autre raison (ex. un admin sans le
 * role sur une page qui n'est pas censee lui etre proposee).
 */
final class PartnerAccessRedirectSubscriber implements EventSubscriberInterface {

  public function __construct(
    private readonly AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Doit s'executer avant le rendu du 403 (MainContentViewSubscriber /
    // FinalExceptionSubscriber, priorites negatives) : toute priorite
    // positive suffit, `RequestEvent::setResponse()` stoppe la propagation.
    return [KernelEvents::EXCEPTION => ['onAccessDenied', 40]];
  }

  /**
   * Redirige vers /user/login si l'acces refuse concerne une route partenaire.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   L'evenement d'exception du kernel.
   */
  public function onAccessDenied(ExceptionEvent $event): void {
    if (!$this->currentUser->isAnonymous()) {
      return;
    }
    if (!$event->getThrowable() instanceof AccessDeniedHttpException) {
      return;
    }

    $request = $event->getRequest();
    $route = $request->attributes->get('_route_object');
    if (!$route || $route->getRequirement('_role') !== 'partenaire') {
      return;
    }

    $login_url = Url::fromRoute('user.login', [], [
      'query' => ['destination' => $request->getRequestUri()],
    ]);
    $event->setResponse(new RedirectResponse($login_url->toString()));
  }

}
