<?php

namespace Drupal\ui_patterns_entity_links\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ui_patterns\UiPatternsSourceManager;
use Drupal\ui_patterns\UiPatternsManager;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Drupal\ui_patterns\Form\PatternDisplayFormTrait;

/**
 * Provides a block that renders a entity link with Ui Patterns.
 *
 * @Block(
 *   id = "link_block",
 *   deriver = "\Drupal\ui_patterns_entity_links\Plugin\Derivative\LinkBlockDeriver",
 * )
 *
 * @internal
 *   Plugin classes are internal.
 */
class LinkBlock extends BlockBase implements ContextAwarePluginInterface, ContainerFactoryPluginInterface {

  use PatternDisplayFormTrait;

  /**
   * UI Patterns manager.
   *
   * @var \Drupal\ui_patterns\UiPatternsManager
   */
  protected $patternsManager;

  /**
   * UI Patterns source manager.
   *
   * @var \Drupal\ui_patterns\UiPatternsSourceManager
   */
  protected $sourceManager;

  /**
   * A module manager object.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;


  /**
   * A strings expressing the relationship of the link.
   *
   * @var string
   */
  protected $rel;

  /**
   * {@inheritdoc}
   */
  final public function __construct(array $configuration, $plugin_id, $plugin_definition, UiPatternsManager $patterns_manager, UiPatternsSourceManager $source_manager, ModuleHandlerInterface $module_handler) {
    $this->patternsManager = $patterns_manager;
    $this->sourceManager = $source_manager;
    $this->moduleHandler = $module_handler;

    // Get the entity type and link rel from the plugin ID.
    list (, , $rel) = explode(static::DERIVATIVE_SEPARATOR, $plugin_id, 3);
    $this->rel = $rel;
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.ui_patterns'),
      $container->get('plugin.manager.ui_patterns_source'),
      $container->get('module_handler')
    );
  }

  /**
   * Gets the entity that has the field.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface
   *   The entity.
   */
  protected function getEntity() {
    return $this->getContextValue('entity');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $config = $this->getConfiguration();
    $rel = $this->rel;

    // Set pattern fields.
    $fields = [];
    $mapping = $config['pattern_mapping'];
    $pattern = $config['pattern'];
    if (!$pattern || $pattern === '_none') {
      return [];
    }
    $mapping = $mapping[$pattern]['settings'];

    foreach ($mapping as $source => $field) {
      if ($field['destination'] === '_hidden') {
        continue;
      }
      // Get rid of the source tag.
      $source = explode(":", $source)[1];
      if ($source === 'url') {
        $url = '';
        $entity = $this->getEntity();
        $absolute_url = $config['absolute_url'];
        // On layout builder page with content preview, the entities don't have
        // id.
        if ($entity->id()) {
          try {
            $url = $entity->toUrl($rel, ['absolute' => $absolute_url])->toString();
          }
          catch (RouteNotFoundException $e) {
            $url = $entity->getEntityType()->getLinkTemplate($rel);
          }
        }
        $fields[$field['destination']] = $url;
      }
      if ($source === 'label') {
        $label = ucfirst(str_replace('-', ' ', $rel));
        if ($config['label_override'] !== '') {
          $label = $config['label_override'];
        }
        $fields[$field['destination']] = $label;
      }
    }

    $build = [
      '#type' => 'pattern',
      '#id' => $config['pattern'],
      '#fields' => $fields,
    ];

    // Set the variant.
    if (!empty($config['variants']) && !empty($config['variants'][$pattern])) {
      $variant = $config['variants'][$pattern];
      $build['#variant'] = $variant;
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'label_override' => '',
      'absolute_url' => FALSE,
      'pattern' => '',
      'variants' => '',
      'pattern_mapping' => [],
      // Used by ui_patterns_settings.
      'pattern_settings' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    // Allow label override.
    $form['label_override'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label override'),
      '#default_value' => $config['label_override'],
    ];

    // Absolute URL.
    $form['absolute_url'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Absolute URL'),
      '#default_value' => $config['absolute_url'],
    ];

    // Add UI Patterns form elements.
    $context = [];
    $pattern = $config['pattern'];
    $config['pattern_variant'] = $config['variants'][$pattern];
    $this->buildPatternDisplayForm($form, 'entity_link', $context, $config);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration = $form_state->getValues();
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultValue(array $configuration, $field_name, $value) {
    // Some modifications to make 'destination' default value working.
    $pattern = $configuration['pattern'];
    if (isset($configuration['pattern_mapping'][$pattern]['settings'][$field_name][$value])) {
      return $configuration['pattern_mapping'][$pattern]['settings'][$field_name][$value];
    }
    return NULL;
  }

}
