<?php declare(strict_types = 1);

namespace Drupal\Tests\lodgify\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test description.
 *
 * @group lodgify
 */
final class LodgifyTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'views', 'image', 'path', 'file', 'lodgify'];

  /**
   * Set to TRUE to strict check all configuration saved.
   *
   * @see \Drupal\Core\Config\Development\ConfigSchemaChecker
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Set up the test here.
  }

  /**
   * Test callback.
   */
  public function testPropertiesListingPage(): void {
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);
    $this->drupalGet('lodgify-properties');
    $this->assertSession()->elementExists('xpath', '//h1[text() = "Lodgify properties"]');
  }

//  public function testSyncNewProperty(): void {
//    $lodgify_mock_class = $this
//      ->createPartialMock() ('Drupal\lodgify\Controller\LodgifyController')
//      ->disableOriginalConstructor()
//      ->onlyMethods('getProperties')
//      ->getMock();
//  }

//  public function testSyncExistingProperty(): void {
//  }

}
