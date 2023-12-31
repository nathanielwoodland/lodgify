<?php

declare(strict_types = 1);

namespace Drupal\lodgify;

/**
 * Service for syncing property records from Lodgify.
 */
final class PropertiesSyncService extends SyncServiceBase {

  /**
   * {@inheritDoc}
   */
  public function syncLodgifyRecords(string $sync_type): void {
    $request_result = $this->lodgifyApiClient->getLodgifyData('properties', 'includeInOut=false');
    if ($request_result['success']) {
      $this->syncLodgifyRecordsByType('lodgify_property', $sync_type, $request_result['records']);
    }
  }

  /**
   * {@inheritDoc}
   */
  protected function updateLodgifyNode($lodgify_record_node, $lodgify_record_api_data): void {
    if (!empty($lodgify_record_api_data['image_url'])) {
      $image_url = 'https:' . $lodgify_record_api_data['image_url'];
      $image_data = file_get_contents($image_url);
      if ($image_data) {
        $image_file = $this->fileRepository
          ->writeData($image_data, 'public://' . $lodgify_record_api_data['id'] . '_cover_image.png');
      }
    }
    $title = !empty($lodgify_record_api_data['name']) ? $lodgify_record_api_data['name'] : 'Property ' . $lodgify_record_api_data['id'];
    $field_lodgify_description = !empty($lodgify_record_api_data['description']) ? $lodgify_record_api_data['description'] : NULL;
    $field_lodgify_cover_image = !empty($image_file) ? [
      'target_id' => $image_file->id(),
      'alt' => $this->t('Lodgify property cover photo'),
      'title' => $this->t('Lodgify property cover photo'),
    ] : NULL;
    $lodgify_record_node->set('title', $title);
    $lodgify_record_node->set('field_lodgify_description', $field_lodgify_description);
    $lodgify_record_node->set('field_lodgify_cover_image', $field_lodgify_cover_image);
    $lodgify_record_node->save();
  }

}
