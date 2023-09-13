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
    $settings = [];
    $settings['settings']['lodgify_api_key'] = (object) [
      'value' => 'YXjlubOq1Y4ocAeqS04g6VNWJY3iyg+XsVVoj0TkLXH10ZdNVWQZk0UMzTAVre7n',
      'required' => TRUE,
    ];
    $this->writeSettings($settings);
  }

  /**
   * Test sync new properties.
   */
  public function testSyncNewProperties(): void {
    $user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($user);
    $new_record_sync_types = ['all', 'new'];
    foreach ($new_record_sync_types as $new_record_sync_type) {
      $this->drupalGet('/admin/config/system/lodgify/settings/sync');
      $edit = [];
      $edit['record_types[lodgify_property]'] = 'lodgify_property';
      $edit['sync_type'] = $new_record_sync_type;
      $this->submitForm($edit, 'Sync');
      $this->drupalGet('/lodgify/properties');
      $this->assertSession()
        ->elementTextEquals('css', 'td.views-field-title', 'Huge condo in Keystone, sleeps 14, great location');
      $this->drupalGetNodeByTitle('Huge condo in Keystone, sleeps 14, great location')->delete();
    }
  }

  /**
   * Test sync existing properties.
   */
  public function testSyncExistingProperties(): void {
    $user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($user);
    $this->drupalGet('/admin/config/system/lodgify/settings/sync');
    $edit = [];
    $edit['record_types[lodgify_property]'] = 'lodgify_property';
    $edit['sync_type'] = 'all';
    $this->submitForm($edit, 'Sync');
    $this->drupalGet('/lodgify/properties');
    $this->assertSession()
      ->elementTextEquals('css', 'td.views-field-title', 'Huge condo in Keystone, sleeps 14, great location');
    $new_record_sync_types = ['all', 'existing'];
    foreach ($new_record_sync_types as $new_record_sync_type) {
      $this->drupalGetNodeByTitle('Huge condo in Keystone, sleeps 14, great location')
        ->set('title', 'Tiny condo in Keystone')
        ->save();
      $this->drupalGet('/lodgify/properties');
      $this->assertSession()
        ->elementTextEquals('css', 'td.views-field-title', 'Tiny condo in Keystone');
      $this->drupalGet('/admin/config/system/lodgify/settings/sync');
      $edit = [];
      $edit['record_types[lodgify_property]'] = 'lodgify_property';
      $edit['sync_type'] = $new_record_sync_type;
      $this->submitForm($edit, 'Sync');
      $this->drupalGet('/lodgify/properties');
      $this->assertSession()
        ->elementTextEquals('css', 'td.views-field-title', 'Huge condo in Keystone, sleeps 14, great location');
    }
  }

}
