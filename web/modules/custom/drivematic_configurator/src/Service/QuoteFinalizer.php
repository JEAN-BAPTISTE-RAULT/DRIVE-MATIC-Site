<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\drivematic_configurator\Entity\DeliveryAddress;
use Drupal\drivematic_configurator\Entity\Quote;
use Drupal\user\UserInterface;

/**
 * Finalise un devis : persistance, PDF et e-mails (clic « Commander »).
 *
 * Extrait de DeliveryForm (ADR-056) : deux points d'entree distincts ont
 * besoin exactement de la meme sequence — le clic « Commander » sans
 * changement de catalogue detecte (DeliveryForm::orderSubmit()) et la
 * confirmation explicite d'un changement detecte
 * (OrderCatalogChangeConfirmForm::submitForm()). Le PDF/les e-mails ne sont
 * declenches que pour Quote::STATUS_A_COMMANDER (jamais pour
 * STATUS_A_FINALISER, « Enregistrer le devis »).
 */
final class QuoteFinalizer {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly QuotePersister $quotePersister,
    private readonly QuotePdfGenerator $pdfGenerator,
    private readonly MailManagerInterface $mailManager,
    private readonly FileSystemInterface $fileSystem,
    private readonly LoggerChannelFactoryInterface $loggerFactory,
  ) {}

  /**
   * Persiste le devis et, pour une commande, genere le PDF et les e-mails.
   *
   * @param array $draft
   *   Brouillon `PrivateTempStore` (structure ConfigurationForm).
   * @param string $status
   *   Quote::STATUS_A_FINALISER ou Quote::STATUS_A_COMMANDER.
   * @param \Drupal\user\UserInterface $account
   *   Le partenaire proprietaire du devis.
   * @param \Drupal\drivematic_configurator\Entity\DeliveryAddress $address
   *   L'adresse de livraison choisie.
   * @param \Drupal\drivematic_configurator\Entity\Quote|null $editingQuote
   *   Le devis « à finaliser » a resauvegarder en place (Modifier/Dupliquer,
   *   ADR-052), ou NULL pour une nouvelle creation.
   *
   * @return \Drupal\drivematic_configurator\Entity\Quote
   *   Le devis persiste.
   */
  public function finalize(
    array $draft,
    string $status,
    UserInterface $account,
    DeliveryAddress $address,
    ?Quote $editingQuote,
  ): Quote {
    $quote = $editingQuote
      ? $this->quotePersister->update($editingQuote, $draft, $status, $account, $address)
      : $this->quotePersister->persist($draft, $status, $account, $address);

    if ($status === Quote::STATUS_A_COMMANDER) {
      $attachments = $this->generatePdfAttachment($quote);
      $this->sendOrderConfirmationEmail($quote, $attachments);
      $this->sendInternalOrderNotification($quote, $attachments);
    }

    return $quote;
  }

  /**
   * Génère le PDF du devis et construit la pièce jointe pour hook_mail().
   *
   * Un échec de génération ne doit jamais empêcher l'envoi des e-mails de
   * confirmation ni faire échouer une commande déjà enregistrée en base —
   * seuls les e-mails partent alors sans pièce jointe.
   *
   * @return array[]
   *   Tableau `$message['params']['attachments']` (format attendu par
   *   symfony_mailer/LegacyMailerHelper::emailFromArray()), vide en cas
   *   d'échec.
   */
  private function generatePdfAttachment(Quote $quote): array {
    try {
      $uri = $this->pdfGenerator->generate($quote);
    }
    catch (\Throwable $e) {
      $this->loggerFactory->get('drivematic_configurator')->error('Échec de la génération du PDF pour le devis @reference : @message', [
        '@reference' => $quote->get('reference')->value,
        '@message' => $e->getMessage(),
      ]);
      return [];
    }

    $attachment = [
      'filepath' => $this->fileSystem->realpath($uri),
      'filename' => $quote->get('reference')->value . '.pdf',
      'filemime' => 'application/pdf',
    ];

    return [$attachment];
  }

  /**
   * Envoie l'e-mail de confirmation (clic « Commander » uniquement).
   *
   * Un probleme d'envoi (SMTP, etc.) ne doit jamais faire echouer la
   * confirmation d'une commande deja enregistree en base — l'erreur est
   * seulement journalisee.
   */
  private function sendOrderConfirmationEmail(Quote $quote, array $attachments): void {
    /** @var \Drupal\user\UserInterface $account */
    $account = $this->entityTypeManager->getStorage('user')->load($quote->getOwnerId());

    try {
      $this->mailManager->mail(
        'drivematic_configurator',
        'quote_ordered',
        $account->getEmail(),
        $account->getPreferredLangcode(),
        ['quote' => $quote, 'attachments' => $attachments],
      );
    }
    catch (\Throwable $e) {
      $this->loggerFactory->get('drivematic_configurator')->error('Échec de l’envoi de l’e-mail de confirmation de commande pour le devis @reference : @message', [
        '@reference' => $quote->get('reference')->value,
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Notifie Drive Matic Legrand de la commande (clic « Commander » uniquement).
   *
   * Adresse temporaire (comme toutes les autres notifications internes du
   * site, cf. mémoire mail-interne-audrey-temporaire) — à restaurer sur
   * info@drivematiclegrand.com avant la mise en prod. Independant de
   * sendOrderConfirmationEmail() : un echec ici ne doit ni empecher l'envoi
   * au partenaire ni faire echouer la confirmation d'une commande deja
   * enregistree en base.
   */
  private function sendInternalOrderNotification(Quote $quote, array $attachments): void {
    /** @var \Drupal\user\UserInterface $account */
    $account = $this->entityTypeManager->getStorage('user')->load($quote->getOwnerId());

    try {
      $this->mailManager->mail(
        'drivematic_configurator',
        'quote_ordered_internal',
        'audrey@passerelle.com',
        $account->getPreferredLangcode(),
        ['quote' => $quote, 'attachments' => $attachments],
      );
    }
    catch (\Throwable $e) {
      $this->loggerFactory->get('drivematic_configurator')->error('Échec de l’envoi de la notification interne de commande pour le devis @reference : @message', [
        '@reference' => $quote->get('reference')->value,
        '@message' => $e->getMessage(),
      ]);
    }
  }

}
