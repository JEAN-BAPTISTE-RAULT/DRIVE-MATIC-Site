<?php

declare(strict_types=1);

namespace Drupal\drivematic_page_title\Plugin\Condition;

use Drupal\Core\Condition\Attribute\Condition;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Bundle de node courant, tolérant à l'absence de node (routes hors node).
 *
 * Remplace `entity_bundle:node` (core) sur la visibilité du bloc
 * `drive_matic_page_title` : ce dernier refuse TOUJOURS l'accès (quel que
 * soit `negate`) dès que la route courante n'a pas de node (formulaire ou
 * contrôleur custom, ex. `/user/tableau-de-bord`) — comportement documenté
 * explicitement dans `\Drupal\block\BlockAccessControlHandler::checkAccess()`
 * (« the node type condition will have a missing context on any non-node
 * route »), pas un bug ponctuel de configuration : `NodeRouteContext`
 * fournit un contexte présent mais dont la VALEUR est NULL hors route de
 * node, et le gestionnaire d'accès du bloc traite systématiquement une
 * valeur de contexte manquante comme un refus, sans repasser par la logique
 * de négation.
 *
 * Cette condition ne déclare aucun contexte de plugin (le node est résolu
 * directement depuis la route courante via `current_route_match`) : jamais
 * de valeur de contexte manquante, donc jamais refusée pour cette raison.
 * Même forme de configuration que `entity_bundle:node` (`bundles`,
 * checkboxes des types de contenu) pour rester un remplacement direct.
 */
#[Condition(
  id: 'drivematic_node_bundle',
  label: new TranslatableMarkup('Bundle de node (tolérant hors node)'),
)]
final class NodeBundleCondition extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly RouteMatchInterface $routeMatch,
    private readonly EntityTypeBundleInfoInterface $entityTypeBundleInfo,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_route_match'),
      $container->get('entity_type.bundle.info'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $bundle_info = $this->entityTypeBundleInfo->getBundleInfo('node');
    $form['bundles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Types de contenu'),
      '#options' => array_map(static fn (array $info): string => (string) $info['label'], $bundle_info),
      '#default_value' => $this->configuration['bundles'],
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['bundles'] = array_filter($form_state->getValue('bundles'));
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $bundles = implode(', ', $this->configuration['bundles']);
    return $this->isNegated()
      ? $this->t("Le type de contenu n'est pas @bundles", ['@bundles' => $bundles])
      : $this->t('Le type de contenu est @bundles', ['@bundles' => $bundles]);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['bundles' => []] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    $node = $this->routeMatch->getParameter('node');
    if (!$node instanceof NodeInterface) {
      // Aucun node sur cette route : jamais un des bundles configurés,
      // quel que soit `negate`.
      return FALSE;
    }

    return !empty($this->configuration['bundles'][$node->bundle()]);
  }

}
