<?php

namespace Drupal\ui_patterns_entity_links\Plugin\UiPatterns\Source;

use Drupal\ui_patterns\Plugin\PatternSourceBase;

/**
 * Defines Field values source plugin.
 *
 * @UiPatternsSource(
 *   id = "entity_link",
 *   label = @Translation("Entity link"),
 *   tags = {
 *     "entity_link"
 *   }
 * )
 */
class EntityLinkSource extends PatternSourceBase {

  /**
   * {@inheritdoc}
   */
  public function getSourceFields() {
    $sources = [];
    $sources[] = $this->getSourceField('url', 'URL');
    $sources[] = $this->getSourceField('label', 'Label');
    return $sources;
  }

}
