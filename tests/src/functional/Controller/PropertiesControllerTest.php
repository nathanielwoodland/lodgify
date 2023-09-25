<?php

declare(strict_types = 1);

namespace Drupal\Tests\lodgify\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the Properties Controller.
 *
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
  protected static $modules = [
    'node',
    'views',
    'image',
    'path',
    'file',
    'datetime',
    'menu_ui',
    'lodgify',
  ];

  /**
   * Test properties index page.
   */
  public function testThatIndexPageLoads(): void {
    $user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($user);
    $this->drupalGet('lodgify/properties');
    $this->assertSession()->elementTextEquals('css', 'h1', 'Lodgify properties');
  }

}
