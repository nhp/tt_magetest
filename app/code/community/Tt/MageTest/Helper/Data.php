<?php

class Tt_MageTest_Helper_Data extends Mage_Core_Helper_Abstract
{

  /**
   * @param $config
   * @param mixed $testObject
   */
  public function doGeneralTest(array $config, $testObject)
  {
    if ( !method_exists($testObject, 'doGeneralAssert') )
    {
      //just let the test fail
      $testObject->assertContains('Wrong object given: '.get_class($testObject), '');
    }

    foreach ($config as $configEntry)
    {
      if ( !isset($configEntry['url']) )
      {
        $configEntry['url'] = $this->fetchUrlFromConfig($configEntry);
      }

      error_log( $this->__('Running tests for URL %s', $configEntry['url']) );

      if ( isset($configEntry['clickon']) && $testObject instanceof Codex_Xtest_Xtest_Selenium_TestCase)
      {
        $this->doPageTest($configEntry, $testObject);
      }
      else
      {
        $this->doRegularTest($configEntry, $testObject);
      }
    }
  }

  protected function doPageTest(array $configEntry, Codex_Xtest_Xtest_Selenium_TestCase $testObject)
  {
    /**
     * @var Codex_Xtest_Xtest_Pageobject_Frontend_Homepage $page
     */
    $page = $testObject->getPageObject('xtest/pageobject_frontend_homepage');

    $baseUrl = Mage::getBaseUrl();
    $baseUrl = trim($baseUrl, '/');
    $page->url($baseUrl.$configEntry['url']);

    $elements = $page->findElementsByCssSelector($configEntry['clickon']);

    foreach ($elements as $element)
    {
      $element->click();
    }

    $responseBody = $page->source();

    if ( is_array($configEntry['assert']) )
    {
      foreach ($configEntry['assert'] as $assert)
      {
        $assert = $this->assertParser($assert);
        $page->assertContains($assert, $responseBody, 'Assert failed: '.$assert);
      }
    }
    else
    {
      $assert = $this->assertParser($configEntry['assert']);
      $page->assertContains($assert, $responseBody, 'Assert failed: '.$assert);
    }

    $page->takeResponsiveScreenshots($configEntry['rendername']);

    $testObject->doGeneralAssert($responseBody);
  }

  protected function doRegularTest(array $configEntry, Codex_Xtest_Xtest_Unit_Abstract $testObject, $omitScreenshot = false)
  {
    $testObject->dispatch($configEntry['url']);
    $responseBody = $testObject->getResponseBody();

    if ( !$omitScreenshot )
    {
      $testObject->renderHtml($configEntry['rendername'], $responseBody);
    }

    if ( is_array($configEntry['assert']) )
    {
      foreach ($configEntry['assert'] as $assert)
      {
        $assert = $this->assertParser($assert);
        $testObject->assertContains($assert, $responseBody);
      }
    }
    else
    {
      $assert = $this->assertParser($configEntry['assert']);
      $testObject->assertContains($assert, $responseBody);
    }

    $testObject->doGeneralAssert($responseBody);
  }

  public function fetchUrlFromConfig(array $configEntry)
  {
    $url = null;

    if ( isset($configEntry['sku']) )
    {
      $prod = Mage::getModel('catalog/product')->getCollection()
        ->addFieldToFilter('sku', $configEntry['sku']);
      $prod = $prod->getFirstItem();

      if ( $prod && $prod->getId() )
      {
        $url = $prod->getProductUrl();
      }
    }
    else if ( isset($configEntry['catid']) )
    {
      $cat = Mage::getModel('catalog/category')->load($configEntry['catid']);

      if ( $cat && $cat->getId() )
      {
        $url = $cat->getUrl();
      }
    }

    if ( stripos($url, 'http') !== false )
    {
      $url = str_replace(Mage::getStoreConfig('web/unsecure/base_url'), '', $url);
      if ( Mage::getStoreConfig('xtest/force/index') == true )
      {
        $url = str_replace('index.php', '', $url);
      }

      $url = trim($url, '/');
    }

    return $url;
  }

  public function assertParser($assertString)
  {
    $assertString = str_replace(
        '[[unsecure_base_url]]',
        Mage::getStoreConfig('web/unsecure/base_url'). (Mage::getStoreConfig('xtest/force/index') ? 'index.php/' : ''),
        $assertString);

    $assertString = str_replace(
        '[[secure_base_url]]',
        Mage::getStoreConfig('web/secure/base_url'). (Mage::getStoreConfig('xtest/force/index') ? 'index.php/' : ''),
        $assertString);

    return $assertString;
  }
}