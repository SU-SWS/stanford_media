<?php

namespace Drupal\stanford_media\Plugin\views\field;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to link entity usage entities.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("entity_usage_link")
 */
class EntityUsageLink extends FieldPluginBase {

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();
    // Add the field.
    $params = $this->options['group_type'] != 'group' ? ['function' => $this->options['group_type']] : [];

    $this->field_alias = $this->query->addField($this->tableAlias, 'source_type', NULL, $params);
    $this->field_alias_id = $this->query->addField($this->tableAlias, 'source_id', NULL, $params);
    $this->addAdditionalFields();
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $type = $values->{$this->field_alias};
    $id = $values->{$this->field_alias_id};
    if (!$type || !$id) {
      return '';
    }

    $child = $this->entityTypeManager->getStorage($type)->load($id);
    $parent = $this->getParent($child);
    if ($parent->hasLinkTemplate('canonical')) {
      return $parent->toLink()->toRenderable();
    }
    return $parent->getEntityType()->getLabel();
  }

  /**
   * Get the parent entity from a child entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $child
   *   Child entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Parent entity of the child.
   */
  protected function getParent(EntityInterface $child) {
    if (method_exists($child, 'getParentEntity')) {
      if ($sub_parent = $child->getParentEntity()) {
        return $this->getParent($sub_parent);
      }
    }
    return $child;
  }

}
