<?php

class StanfordMediaCest {

  public function testSomething(AcceptanceTester $I){
    $I->amOnPage('/');
    $I->canSeeResponseCodeIs(200);
  }

}
