<?php

declare(strict_types=1);

namespace Drupal\drivematic_partner\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Url;
use Drupal\drivematic_configurator\Entity\Quote;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tableau de bord partenaire (maquettes 491-13703 desktop / 604-34427 mobile).
 *
 * Page d'accueil de l'espace partenaire : raccourcis vers le configurateur
 * et compteurs de devis par groupe de statut, chacun scopé au partenaire
 * courant (`uid`) — jamais un identifiant transmis par le client. Les 3
 * compteurs pointent vers `drivematic_partner.my_quotes` (onglet sélectionné
 * par `?onglet=`), groupés pour correspondre exactement au contenu de
 * chaque onglet (ADR-051, qui corrige le groupement initial de l'ADR-046).
 */
final class DashboardController extends ControllerBase {

  public function __construct(
    private readonly ThemeExtensionList $themeExtensionList,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('extension.list.theme'),
    );
  }

  /**
   * Construit la page.
   */
  public function build(): array {
    $uid = (int) $this->currentUser()->id();
    /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
    $storage = $this->entityTypeManager()->getStorage('quote');

    $count_a_finaliser = $this->countQuotes($storage, $uid, [Quote::STATUS_A_FINALISER]);
    $count_en_cours = $this->countQuotes($storage, $uid, [Quote::STATUS_A_COMMANDER, Quote::STATUS_COMMANDE]);
    $count_archives = $this->countQuotes($storage, $uid, [Quote::STATUS_ARCHIVE]);

    $configurator_url = Url::fromRoute('drivematic_configurator.configuration')->toString();
    $image_url = base_path() . $this->themeExtensionList->getPath('drive_matic') . '/images/dashboard-vehicle.webp';

    return [
      // Le decompte varie par partenaire connecte : sans le contexte
      // `user`, le Dynamic Page Cache servirait les chiffres d'un premier
      // partenaire a tous les suivants.
      '#cache' => [
        'contexts' => ['user'],
        'tags' => $this->entityTypeManager()->getDefinition('quote')->getListCacheTags(),
      ],
      'actions' => [
        '#type' => 'component',
        '#component' => 'drive_matic:dashboard-actions',
        '#slots' => [
          'items' => [
            [
              '#type' => 'component',
              '#component' => 'drive_matic:dashboard-action-card',
              '#props' => [
                'variant' => 'action',
                'label' => (string) $this->t('Créer un nouveau devis'),
                'count' => NULL,
                'icon' => 'plus-circle',
                'href' => $configurator_url,
              ],
            ],
            [
              '#type' => 'component',
              '#component' => 'drive_matic:dashboard-action-card',
              '#props' => [
                'variant' => 'counter',
                'label' => (string) $this->t('Mes devis à finaliser'),
                'count' => $count_a_finaliser,
                'icon' => 'folder-edit',
                'href' => Url::fromRoute('drivematic_partner.my_quotes', [], ['query' => ['onglet' => 'a-finaliser']])->toString(),
              ],
            ],
            [
              '#type' => 'component',
              '#component' => 'drive_matic:dashboard-action-card',
              '#props' => [
                'variant' => 'counter',
                'label' => (string) $this->t('Mes devis / commandes en cours'),
                'count' => $count_en_cours,
                'icon' => 'folder-clock',
                'href' => Url::fromRoute('drivematic_partner.my_quotes', [], ['query' => ['onglet' => 'en-cours']])->toString(),
              ],
            ],
            [
              '#type' => 'component',
              '#component' => 'drive_matic:dashboard-action-card',
              '#props' => [
                'variant' => 'counter',
                'label' => (string) $this->t('Mes devis / commandes archivés'),
                'count' => $count_archives,
                'icon' => 'archive',
                'href' => Url::fromRoute('drivematic_partner.my_quotes', [], ['query' => ['onglet' => 'archives']])->toString(),
              ],
            ],
          ],
        ],
      ],
      'cta' => [
        '#type' => 'component',
        '#component' => 'drive_matic:dashboard-quote-cta',
        '#props' => [
          'href' => $configurator_url,
          'image_url' => $image_url,
        ],
      ],
    ];
  }

  /**
   * Compte les devis du partenaire courant pour un ou plusieurs statuts.
   *
   * `accessCheck(FALSE)` : la requête est déjà bornée à `$uid` (l'utilisateur
   * courant, jamais un identifiant transmis par le client) et ne renvoie
   * qu'un agrégat — aucune donnée de devis individuelle n'est exposée ici.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   Le storage de l'entité quote.
   * @param int $uid
   *   L'identifiant du partenaire courant.
   * @param string[] $statuses
   *   Un ou plusieurs statuts (Quote::STATUS_*) à additionner.
   *
   * @return int
   *   Le nombre de devis correspondants.
   */
  private function countQuotes(EntityStorageInterface $storage, int $uid, array $statuses): int {
    return (int) $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('uid', $uid)
      ->condition('status', $statuses, 'IN')
      ->count()
      ->execute();
  }

}
