<?php

namespace Drupal\Tests\stanford_media\Unit\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\GeneratedLink;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\entity_usage\EntityUsageInterface;
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
   * Warning messages that were added.
   *
   * @var array
   */
  protected $warnings = [];

  /**
   * Number of entities using the media item.
   *
   * @var array
   */
  protected $usageSources = [];

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $messenger = $this->createMock(MessengerInterface::class);
    $messenger->method('addWarning')->willReturnCallback(function ($message) use ($messenger) {
      $this->warnings[] = (string) $message;
      return $messenger;
    });

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

    $entity_usage = $this->createMock(EntityUsageInterface::class);
    $entity_usage->method('listSources')->willReturnCallback(fn() => $this->usageSources);

    $link_generator = $this->createMock(LinkGeneratorInterface::class);
    $link_generator->method('generate')
      ->willReturn((new GeneratedLink())->setGeneratedLink('<a href="/usage">link</a>'));

    $container = new ContainerBuilder();
    $container->set('entity_usage.usage', $entity_usage);
    $container->set('link_generator', $link_generator);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->hooks = new StanfordMediaHooks(
      $this->createMock(EntityTypeManagerInterface::class),
      $this->createMock(AccountProxyInterface::class),
      $this->createMock(StanfordMediaInterface::class),
      $dialog_manager,
      $messenger,
      $this->createMock(ConfigFactoryInterface::class),
      $this->createMock(FileSystemInterface::class),
      $this->createMock(LoggerChannelFactoryInterface::class),
    );
    $this->hooks->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * Test the usage operation is only added to media the user can update.
   */
  public function testEntityOperation(): void {
    $node = $this->createMock(EntityInterface::class);
    $node->method('getEntityTypeId')->willReturn('node');
    $this->assertEmpty($this->hooks->entityOperation($node));

    $url = $this->createMock(Url::class);
    $media = $this->createMock(MediaInterface::class);
    $media->method('getEntityTypeId')->willReturn('media');
    $media->method('toUrl')->with('usage')->willReturn($url);
    $media->method('access')
      ->willReturnOnConsecutiveCalls(AccessResult::allowed()->addCacheTags(['foo']), AccessResult::forbidden());

    $cacheability = new CacheableMetadata();
    $operations = $this->hooks->entityOperation($media, $cacheability);
    $this->assertArrayHasKey('usage', $operations);
    $this->assertSame($url, $operations['usage']['url']);
    $this->assertContains('foo', $cacheability->getCacheTags());

    $this->assertEmpty($this->hooks->entityOperation($media));
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
   * Test the warning message when the media is used in other content.
   */
  public function testMediaPrepareForm(): void {
    $media = $this->createMock(MediaInterface::class);
    $media->method('toUrl')->willReturn($this->createMock(Url::class));
    $form_state = new FormState();

    $this->hooks->mediaPrepareForm($media, 'edit', $form_state);
    $this->assertEmpty($this->warnings);

    $this->usageSources = ['node' => [1 => [], 2 => []], 'paragraph' => [3 => []]];
    $this->hooks->mediaPrepareForm($media, 'edit', $form_state);
    $this->assertCount(1, $this->warnings);
    $this->assertStringContainsString('Changing this media will affect', $this->warnings[0]);
    $this->assertStringContainsString('<a href="/usage">link</a>', $this->warnings[0]);
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
