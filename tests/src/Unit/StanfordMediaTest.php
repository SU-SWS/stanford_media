<?php

namespace Drupal\Tests\stanford_media\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Core\Utility\UnroutedUrlAssemblerInterface;
use Drupal\stanford_media\StanfordMedia;
use Drupal\Tests\UnitTestCase;

/**
 * Class StanfordMediaTest.
 *
 * @coversDefaultClass \Drupal\stanford_media\StanfordMedia
 * @group stanford_media
 */
class StanfordMediaTest extends UnitTestCase {

  /**
   * {@inheritDoc}
   */
  public function setup(): void {
    parent::setUp();
    $url_assembler = $this->createMock(UnroutedUrlAssemblerInterface::class);
    $link_generator = $this->createMock(LinkGeneratorInterface::class);
    $link_generator->method('generate')->willReturn('<a href="http://foobar.com">Foobar</a>');
    $container = new ContainerBuilder();
    $container->set('unrouted_url_assembler', $url_assembler);
    $container->set('link_generator', $link_generator);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Test the trusted callbacks.
   */
  public function testCallbacks() {
    $this->assertNotEmpty(StanfordMedia::trustedCallbacks());

    $element = [
      '#alt_field_required' => FALSE,
      'alt' => ['#access' => TRUE, '#description' => 'Foo Bar Baz'],
      '#default_value' => 'Foo',
      '#parents' => [],
    ];
    $form_state = new FormState();
    $form = [];

    $new_element = StanfordMedia::imageWidgetProcess($element, $form_state, $form);
    $this->assertNotEmpty($new_element);
    $this->assertStringContainsString('Leave blank if image is decorative', (string) $new_element['alt']['#description']);

    $form_state->setValue(['decorative'], FALSE);
    $this->assertEquals('Foo', StanfordMedia::imageAltValue($element, FALSE, $form_state));
    $this->assertEquals('Foo', StanfordMedia::imageAltValue($element, 'Foo', $form_state));

    $form_state->setValue(['decorative'], TRUE);
    $this->assertEquals('', StanfordMedia::imageAltValue($element, 'Foo', $form_state));
  }

}
