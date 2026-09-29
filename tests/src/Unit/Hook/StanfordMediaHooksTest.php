<?php

namespace Drupal\Tests\stanford_media\Unit\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\media\MediaInterface;
use Drupal\stanford_media\Hook\StanfordMediaHooks;
use Drupal\stanford_media\Plugin\MediaEmbedDialogInterface;
use Drupal\stanford_media\Plugin\MediaEmbedDialogManager;
use Drupal\stanford_media\StanfordMedia;
use Drupal\stanford_media\StanfordMediaInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test the entity and form hooks.
 */
#[Group('stanford_media')]
class StanfordMediaHooksTest extends UnitTestCase {

  /**
   * Hook class being tested.
   *
   * @var \Drupal\stanford_media\Hook\StanfordMediaHooks
   */
  protected $hooks;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $applicable = $this->createMock(MediaEmbedDialogInterface::class);
    $applicable->method('isApplicable')->willReturn(TRUE);
    $applicable->method('embedAlter')->willReturnCallback(function (array &$build) {
      $build['#altered'] = TRUE;
    });
    $not_applicable = $this->createMock(MediaEmbedDialogInterface::class);
    $not_applicable->method('isApplicable')->willReturn(FALSE);
    $not_applicable->expects($this->never())->method('embedAlter');

    $dialog_manager = $this->createMock(MediaEmbedDialogManager::class);
    $dialog_manager->method('getDefinitions')
      ->willReturn(['foo' => [], 'bar' => []]);
    $dialog_manager->method('createInstance')
      ->willReturnCallback(fn($id) => $id == 'foo' ? $applicable : $not_applicable);

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->hooks = new StanfordMediaHooks(
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(AccountProxyInterface::class),
      $this->createMock(StanfordMediaInterface::class),
      $dialog_manager,
      $this->createMock(MessengerInterface::class),
      $this->createMock(ConfigFactoryInterface::class),
      $this->createMock(FileSystemInterface::class),
      $this->createMock(LoggerChannelFactoryInterface::class),
    );
    $this->hooks->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * Test image widgets get the additional process callback.
   */
  public function testFieldWidgetSingleElementFormAlter(): void {
    $element = ['#process' => []];
    $form_state = new FormState();

    $this->hooks->fieldWidgetSingleElementFormAlter($element, $form_state, ['items' => $this->getFieldItems('string')]);
    $this->assertEmpty($element['#process']);

    $this->hooks->fieldWidgetSingleElementFormAlter($element, $form_state, ['items' => $this->getFieldItems('image')]);
    $this->assertEquals([[StanfordMedia::class, 'imageWidgetProcess']], $element['#process']);
  }

  /**
   * Test only embedded media is altered by the applicable dialog plugins.
   */
  public function testMediaViewAlter(): void {
    $media = $this->createMock(MediaInterface::class);
    $display = $this->createMock(EntityViewDisplayInterface::class);

    $build = [];
    $this->hooks->mediaViewAlter($build, $media, $display);
    $this->assertEmpty($build);

    $build = ['#embed' => TRUE];
    $this->hooks->mediaViewAlter($build, $media, $display);
    $this->assertTrue($build['#altered']);
    $this->assertContains('stanford_media/display', $build['#attached']['library']);
  }

  /**
   * Get a mocked field item list of the given type.
   *
   * @param string $type
   *   Field type.
   *
   * @return \Drupal\Core\Field\FieldItemListInterface
   *   Mocked field items.
   */
  protected function getFieldItems(string $type): FieldItemListInterface {
    $definition = $this->createMock(FieldDefinitionInterface::class);
    $definition->method('getType')->willReturn($type);
    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getFieldDefinition')->willReturn($definition);
    return $items;
  }

}
