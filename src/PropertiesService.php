<?php declare(strict_types = 1);

namespace Drupal\lodgify;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\Core\Messenger\MessengerInterface;

final class PropertiesService {

  /**
   * Constructs a PropertiesService object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly FileRepositoryInterface $fileRepository,
    private readonly MessengerInterface $messenger,
    private readonly LodgifyApiClient $lodgifyApiClient,
  ) {}

  /**
   * Creates and/or updates new and/or existing local Lodgify records.
   *
   * @param string $record_type
   * @param string $sync_type
   *
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function syncLodgifyData(string $record_type, string $sync_type): bool {
    // @todo add support for booking records
    switch ($record_type) {
      case 'lodgify_property':
        $lodgify_records = $this->lodgifyApiClient->getLodgifyData('properties', '&includeInOut=false');
        break;
    }
    // @todo add support for no results
    foreach ($lodgify_records as $key => $value) {
      $lodgify_id = $lodgify_records[$key]->id;
      $existing_lodgify_property_node = $this->getLocalLodgifyRecord($record_type, $lodgify_id);
      // Skip if record already exists and sync type is 'new'
      if ($existing_lodgify_property_node && $sync_type === 'new') {
        continue;
      }
      // Skip if record doesn't yet exist and sync type is 'existing'
      if (!$existing_lodgify_property_node && $sync_type === 'existing') {
        continue;
      }
      if ($existing_lodgify_property_node) {
        $lodgify_record_node = $existing_lodgify_property_node;
      }
      else {
        $lodgify_record_node = $this->entityTypeManager
          ->getStorage('node')
          ->create([
            'type' => $record_type,
            'field_lodgify_id' => $lodgify_id,
          ]);
      }
      // @todo add support for booking records
      switch ($record_type) {
        case 'lodgify_property':
          $this->updateLodgifyProperty($lodgify_record_node, $lodgify_records[$key]);
          $this->messenger->addStatus('Lodgify properties successfully synced.');
          break;
      }
    }
    return true;
  }

  /**
   * Gets Drupal node the corresponds to lodgify record type and ID, and returns
   * false if not found.
   *
   * @param string $record_type
   * @param int $lodgify_id
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getLocalLodgifyRecord(string $record_type, int $lodgify_id): mixed {
    $lodgify_property_search = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', $record_type)
      ->condition('field_lodgify_id', $lodgify_id)
      ->execute();
    if ($lodgify_property_search) {
      return $this->entityTypeManager
        ->getStorage('node')
        ->load(reset($lodgify_property_search));
    }
    return false;
  }

  /**
   * Updates local Lodgify property node from API data array.
   *
   * @param $lodgify_property_node
   * @param $lodgify_property_update_data
   *
   * @return void
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function updateLodgifyProperty($lodgify_property_node, $lodgify_property_update_data) {
    // Create image file from API data
    $image_url = 'https:' . $lodgify_property_update_data->image_url;
    $image_data = file_get_contents($image_url);
    $image_file = $this->fileRepository
      ->writeData($image_data, "public://$lodgify_property_update_data->id.cover_image.png");
    // Update node from API data
    $lodgify_property_node->set('title', $lodgify_property_update_data->name);
    $lodgify_property_node->set('field_lodgify_description', $lodgify_property_update_data->description);
    $lodgify_property_node->set('field_lodgify_cover_image', [
      'target_id' => $image_file->id(),
      'alt' => 'Lodgify property cover photo',
      'title' => 'Lodgify property cover photo'
    ]);
    $lodgify_property_node->save();
  }

}
