<?php

class Tt_MageTest_Xtest_Unit_Catalog extends Codex_Xtest_Xtest_Unit_Frontend
{

  public static function getCatalogConfig($path)
  {
    $path = 'xtest/catalogtest/'.$path;
    $config = Mage::getStoreConfig($path);
    if( $config === NULL ) {
      Mage::throwException( sprintf('Config path %s is null', $path) );
    }
    return $config;
  }

  public function doGeneralAssert($textToCheck)
  {
    $this->assertContains( Mage::getUrl('checkout/cart'), $textToCheck);
    $this->assertContains( Mage::getUrl('checkout'), $textToCheck);
    $this->assertContains( Mage::getUrl('customer/account'), $textToCheck);
    $this->assertContains( "</body>", $textToCheck);
  }

}