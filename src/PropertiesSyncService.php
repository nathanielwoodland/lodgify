<?php declare(strict_types = 1);

namespace Drupal\lodgify;

final class PropertiesSyncService extends SyncServiceBase {

  /**
   * @param string $sync_type
   *
   * @return void
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function syncLodgifyProperties(string $sync_type): void {
    $lodgify_properties = $this->lodgifyApiClient->getLodgifyData('properties', '&includeInOut=false');
    if (empty($lodgify_properties['response']['items'])) {
      $this->messenger->addError($this->t("No Lodgify properties found."));
    }
    $this->syncLodgifyRecordsByType('lodgify_property', $sync_type, $lodgify_properties['response']['items']);
  }

  /**
   * @inheritDoc
   *
   * Updates local Lodgify property node from API data array.
   *
   * @param $lodgify_property_node
   * @param $lodgify_property_api_data
   *
   * @return void
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function updateLodgifyNode($lodgify_property_node, $lodgify_property_api_data): void {
    if (!empty($lodgify_property_api_data['image_url'])) {
      $image_url = 'https:' . $lodgify_property_api_data['image_url'];
      $image_data = file_get_contents($image_url);
      if ($image_data) {
        $image_file = $this->fileRepository
          ->writeData($image_data, 'public://' . $lodgify_property_api_data['id'] . '_cover_image.png');
      }
    }
    $lodgify_property_node->set('title', (!empty($lodgify_property_api_data['name'])) ? $lodgify_property_api_data['name'] : 'Property ' . $lodgify_property_api_data['id']);
    $lodgify_property_node->set('field_lodgify_description', (!empty($lodgify_property_api_data['description'])) ? $lodgify_property_api_data['description'] : null);
    $lodgify_property_node->set('field_lodgify_cover_image', (!empty($image_file)) ? [
      'target_id' => $image_file->id(),
      'alt' => $this->t('Lodgify property cover photo'),
      'title' => $this->t('Lodgify property cover photo')
    ] : null);
    $lodgify_property_node->save();
  }

}
