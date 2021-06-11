<?php

class StanfordMediaCest {

  /**
   * ArcGis Embeddables can be saved.
   */
  public function testArcGis(AcceptanceTester $I){
    $I->logInWithRole('administrator');
    $I->amOnPage('/media/add/embeddable');
    $I->fillField('Name','ArcGis');
    $I->fillField('oEmbed URL', 'https://storymaps.arcgis.com/stories/4586c60dc91744cbae9967442f990468');
    $I->click('Save');
    $I->canSee('Embeddable ArcGis has been created.');
  }

  /**
   * After editing an embeddable, the name shouldn't change.
   */
  public function testMediaNameSave(AcceptanceTester $I) {
    $media = $I->createEntity([
      'bundle' => 'embeddable',
      'name' => 'Foo',
      'field_media_embeddable_code' => '<iframe width="560" height="315" src="https://www.youtube.com/embed/-DYSucV1_9w" title="YouTube video player"></iframe>',
    ], 'media');
    $I->logInWithRole('administrator');
    $I->amOnPage('/admin/content/media');
    $I->canSeeLink('Foo');

    $I->amOnPage($media->toUrl('edit-form')->toString());
    $I->canSeeInField('Embed Code', '<iframe width="560" height="315" src="https://www.youtube.com/embed/-DYSucV1_9w" title="YouTube video player"></iframe>');
    $I->canSeeInField('oEmbed URL', '');
    $I->fillField('Embed Code', '<iframe width="565" height="310" src="https://www.youtube.com/embed/-DYSucV1_9w" title="YouTube video player"></iframe>');
    $I->click('Save');
    $I->canSee('has been updated.');

    $I->amOnPage('/admin/content/media');
    $I->cantSee('has been updated');
    $I->canSeeLink('Foo');
  }

}
