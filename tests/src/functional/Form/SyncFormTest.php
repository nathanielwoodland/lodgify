<?php declare(strict_types = 1);

namespace Drupal\Tests\lodgify\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * @group lodgify
 */
final class SyncFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'claro';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'views', 'image', 'path', 'file', 'datetime', 'menu_ui', 'lodgify'];

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
    // @todo: implement mock API methods and results
    $settings = [];
    $settings['settings']['lodgify_api_key'] = (object) [
      'value' => 'YXjlubOq1Y4ocAeqS04g6VNWJY3iyg+XsVVoj0TkLXH10ZdNVWQZk0UMzTAVre7n',
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
  }

  /**
   * Test sync properties.
   */
  public function testSyncProperties(): void {
    $user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($user);
    $sync_types = ['all', 'new', 'existing'];
    foreach ($sync_types as $sync_type) {
      $this->drupalGet('/admin/config/system/lodgify/settings/sync');
      $edit = [];
      $edit['record_types[lodgify_property]'] = 'lodgify_property';
      $edit['sync_type'] = $sync_type;
      $this->submitForm($edit, 'Sync');
      $this->drupalGet('/lodgify/properties');
      switch ($sync_type) {
        case 'all':
        case 'new':
          $this->assertSession()->pageTextContains('Huge condo in Keystone, sleeps 14, great location');
          $this->drupalGetNodeByTitle('Huge condo in Keystone, sleeps 14, great location')->delete();
          break;
        case 'existing':
          $this->assertSession()->pageTextNotContains('Huge condo in Keystone, sleeps 14, great location');
          $this->drupalGet('/admin/config/system/lodgify/settings/sync');
          $edit = [];
          $edit['record_types[lodgify_property]'] = 'lodgify_property';
          $edit['sync_type'] = 'new';
          $this->submitForm($edit, 'Sync');
          $this->drupalGet('/lodgify/properties');
          $this->assertSession()->pageTextContains('Huge condo in Keystone, sleeps 14, great location');
          $this->drupalGetNodeByTitle('Huge condo in Keystone, sleeps 14, great location')
            ->set('title', 'Tiny condo in Keystone')
            ->save();
          $this->drupalGet('/lodgify/properties');
          $this->assertSession()->pageTextContains('Tiny condo in Keystone');
          $this->drupalGet('/admin/config/system/lodgify/settings/sync');
          $edit = [];
          $edit['record_types[lodgify_property]'] = 'lodgify_property';
          $edit['sync_type'] = 'existing';
          $this->submitForm($edit, 'Sync');
          $this->drupalGet('/lodgify/properties');
          $this->assertSession()->pageTextContains('Huge condo in Keystone, sleeps 14, great location');
          break;
      }
    }
  }

  /**
   * Test sync bookings.
   */
  public function testSyncBookings(): void {
    $user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($user);
    $this->drupalGet('/admin/config/system/lodgify/settings/sync');
    $edit = [];
    $edit['record_types[lodgify_property]'] = 'lodgify_property';
    $edit['record_types[lodgify_booking]'] = 'lodgify_booking';
    $edit['sync_type'] = 'all';
    $this->submitForm($edit, 'Sync');
    $this->drupalGet('/lodgify/properties');
    $this->click('td.views-field-title a');
    $this->assertSession()->pageTextContains('Booking for Destiny Farr Rodriguez');
  }

}
