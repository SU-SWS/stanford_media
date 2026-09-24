<?php

namespace Drupal\Tests\media_duplicate_validation\Unit\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\media_duplicate_validation\Hook\MediaDuplicateValidationHooks;
use Drupal\media_duplicate_validation\Plugin\MediaDuplicateValidationManager;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test the media duplicate validation hooks.
 */
#[Group('media_duplicate_validation')]
class MediaDuplicateValidationHooksTest extends UnitTestCase {

  /**
   * Test the help text is only provided on the module's help page.
   */
  public function testHelp(): void {
    $hooks = new MediaDuplicateValidationHooks($this->createMock(MediaDuplicateValidationManager::class));
    $hooks->setStringTranslation($this->getStringTranslationStub());
    $route_match = $this->createMock(RouteMatchInterface::class);

    $this->assertNull($hooks->help('foo.bar', $route_match));
    $this->assertStringContainsString('prevent duplication of media items', $hooks->help('help.page.media_duplicate_validation', $route_match));
  }

  /**
   * Test plugin schemas are built when modules are installed.
   */
  public function testModulesInstalled(): void {
    $manager = $this->createMock(MediaDuplicateValidationManager::class);
    $manager->expects($this->once())->method('buildPluginSchemas');
    (new MediaDuplicateValidationHooks($manager))->modulesInstalled(['foo']);
  }

  /**
   * Test only the uninstalled module's plugin schemas are removed.
   */
  public function testModulePreuninstall(): void {
    $manager = $this->createMock(MediaDuplicateValidationManager::class);
    $manager->method('getDefinitions')->willReturn([
      'foo' => ['id' => 'foo', 'provider' => 'foo_module'],
      'bar' => ['id' => 'bar', 'provider' => 'bar_module'],
    ]);
    $manager->expects($this->once())->method('removeSchemas')->with('foo');
    (new MediaDuplicateValidationHooks($manager))->modulePreuninstall('foo_module');
  }

}
