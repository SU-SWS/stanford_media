<?php

class MediaFunctionalCest {

  /**
   * ArcGis Embeddables can be saved.
   */
  public function testArcGis(FunctionalTester $I){
    $I->logInWithRole('administrator');
    $I->amOnPage('/media/add/embeddable');
    $I->fillField('Name','ArcGis');
    $I->fillField('oEmbed URL', 'https://storymaps.arcgis.com/stories/4586c60dc91744cbae9967442f990468');
    $I->click('Save');
    $I->canSee('Embeddable ArcGis has been created.');
  }

}
