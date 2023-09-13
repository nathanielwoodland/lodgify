<?php declare(strict_types = 1);

namespace Drupal\Tests\lodgify\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * @group lodgify
 */
final class PropertiesControllerTest extends BrowserTestBase {

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
  }

  /**
   * Test properties index page.
   */
  public function testIndexViewLoads(): void {
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);
    $this->drupalGet('lodgify/properties');
    $this->assertSession()->elementTextEquals('css', 'h1.page-title', 'Lodgify properties');
  }
}
