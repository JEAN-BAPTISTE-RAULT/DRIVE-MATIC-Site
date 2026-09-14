<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\drivematic_configurator\Entity\Quote;
use Drupal\drivematic_configurator\Service\QuotePdfGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Action « Télécharger le devis » du menu 3 points (page « Mes devis »).
 *
 * Sert le même fichier que `QuoteDetailController::pdf()` (back-office),
 * en `DISPOSITION_ATTACHMENT` plutôt qu'`INLINE` : le libellé partenaire
 * (« Télécharger », pas « Voir ») appelle un enregistrement direct, pas une
 * ouverture dans l'onglet courant. Distinct de cette route admin (space URL
 * `/user/mes-devis/...`, pas `/admin/...`) même si `_entity_access:
 * 'quote.view'` (QuoteAccessControlHandler) accorderait de toute façon
 * l'accès au propriétaire sur l'une ou l'autre.
 */
final class QuotePdfDownloadController extends ControllerBase {

  public function __construct(
    protected QuotePdfGenerator $pdfGenerator,
    protected FileSystemInterface $fileSystem,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('drivematic_configurator.quote_pdf_generator'),
      $container->get('file_system'),
    );
  }

  /**
   * Force le téléchargement du PDF du devis.
   */
  public function download(Quote $quote): BinaryFileResponse {
    $uri = $this->pdfGenerator->getUri($quote);
    if (!file_exists($uri)) {
      throw new NotFoundHttpException();
    }

    $response = new BinaryFileResponse($this->fileSystem->realpath($uri));
    $response->headers->set('Content-Type', 'application/pdf');
    $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $quote->get('reference')->value . '.pdf');

    return $response;
  }

}
