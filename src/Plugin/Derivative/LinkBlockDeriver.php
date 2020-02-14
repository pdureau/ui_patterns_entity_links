<?php

namespace Drupal\ui_patterns_entity_links\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\EntityContextDefinition;
use Drupal\Core\Plugin\Context\ContextDefinition;

/**
 * Provides entity field block definitions for every entity links.
 *
 * @internal
 *   Plugin derivers are internal.
 */
class LinkBlockDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new LinkBlockDeriver object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  final public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {

      // Only process fieldable entity types.
      if (!$entity_type->entityClassImplements(FieldableEntityInterface::class)) {
        continue;
      }

      foreach (array_keys($entity_type->getLinkTemplates()) as $rel) {

        // Init derivative.
        $derivative = $base_plugin_definition;
        $derivative['category'] = $this->t('@entity entity links', ['@entity' => $entity_type->getLabel()]);
        $label = ucfirst(str_replace('-', ' ', $rel));
        $derivative['admin_label'] = $label;

        // Add context.
        $context_definition = EntityContextDefinition::fromEntityTypeId($entity_type_id)->setLabel($entity_type->getLabel());
        $derivative['context_definitions'] = [
          'entity' => $context_definition,
          'view_mode' => new ContextDefinition('string'),
        ];

        // Set.
        $derivative_id = $entity_type_id . PluginBase::DERIVATIVE_SEPARATOR . $rel;
        $this->derivatives[$derivative_id] = $derivative;
      }

    }

    return $this->derivatives;
  }

}
