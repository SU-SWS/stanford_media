<?php

namespace Drupal\stanford_media\Plugin\views\field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Field handler to link entity usage entities.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("entity_usage_link")
 */
class EntityUsageLink extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    // Add the field.
    $params = $this->options['group_type'] != 'group' ? ['function' => $this->options['group_type']] : [];

    $this->field_alias = $this->query->addField($this->tableAlias, 're_type', NULL, $params);
    $this->field_alias_id = $this->query->addField($this->tableAlias, 're_id', NULL, $params);
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $type = $values->{$this->field_alias};
    $id = $values->{$this->field_alias_id};
    if(!$type || !$id){
      return '';
    }

    $child = \Drupal::entityTypeManager()->getStorage($type)->load($id);
    $parent = $this->getParent($child);
    return $parent->toLink()->toRenderable();
  }

  /**
   * @param \Drupal\Core\Entity\EntityInterface $child
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  private function getParent(EntityInterface $child) {
    if (method_exists($child, 'getParentEntity')) {
      $sub_parent = $child->getParentEntity();
      return $this->getParent($sub_parent);
    }
    return $child;
  }

}
