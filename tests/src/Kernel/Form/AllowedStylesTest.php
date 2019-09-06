<?php

namespace Drupal\Tests\stanford_media\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\image\Entity\ImageStyle;
use Drupal\KernelTests\KernelTestBase;

/**
 * Class AllowedStylesTest.
 *
 * @group stanford_media
 * @coversDefaultClass \Drupal\stanford_media\Form\AllowedStyles
 */
class AllowedStylesTest extends KernelTestBase {

  /**
   * {@inheritDoc}
   */
  protected static $modules = ['system', 'stanford_media', 'image'];

  /**
   * Testing form namespace argument.
   *
   * @var string
   */
  protected $formArg = '\Drupal\stanford_media\Form\AllowedStyles';

  /**
   * Array of image styles created.
   *
   * @var \Drupal\image\Entity\ImageStyle[]
   */
  protected $styles;

  /**
   * {@inheritDoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('image_style');
    for ($i = 0; $i <= 2; $i++) {
      $name = $this->randomMachineName();
      $style = ImageStyle::create([
        'name' => $name,
        'label' => $this->randomString(),
      ]);
      $style->save();
      $this->styles[$name] = $style;
    }
  }

  /**
   * Test form structure and saving.
   */
  public function testForm() {
    $form = \Drupal::formBuilder()->getForm($this->formArg);
    $this->assertArrayHasKey('allowed_styles', $form);
    $this->assertCount(26, $form);

    $chosen_styles = array_slice(array_keys($this->styles), 0, 2);
    $form_state = new FormState();
    $form_state->setValue('allowed_styles', $chosen_styles);
    \Drupal::formBuilder()->submitForm($this->formArg, $form_state);

    $setting = \Drupal::config('stanford_media.settings')->get('embeddable_image_styles');
    $this->assertEquals($chosen_styles, $setting);

    $form = \Drupal::formBuilder()->getForm($this->formArg);
    $this->assertEquals($chosen_styles, $form['allowed_styles']['#default_value']);
  }

}
