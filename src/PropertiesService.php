<?php declare(strict_types = 1);

namespace Drupal\lodgify;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Psr\Log\LoggerInterface;

final class PropertiesService {
  use StringTranslationTrait;

  /**
   * Constructs a PropertiesService object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly FileRepositoryInterface $fileRepository,
    private readonly MessengerInterface $messenger,
    private readonly LodgifyApiClient $lodgifyApiClient,
    TranslationInterface $stringTranslation,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Creates and/or updates new and/or existing local Lodgify records.
   *
   * @param string $record_type
   * @param string $sync_type
   *
   * @return void
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function syncLodgifyData(string $record_type, string $sync_type): void {
    switch ($record_type) {
      case 'lodgify_property':
        $lodgify_records = $this->lodgifyApiClient->getLodgifyData('properties', '&includeInOut=false');
        break;
    }
    if (!$lodgify_records['success']) {
      return;
    }
    if (empty($lodgify_records['response']['items'])) {
      $this->messenger->addStatus($this->t("No $record_type records found."));
      return;
    }
    $record_count = 0;
    foreach ($lodgify_records['response']['items'] as $key => $value) {
      $lodgify_id = $lodgify_records['response']['items'][$key]['id'];
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
      switch ($record_type) {
        case 'lodgify_property':
          $this->updateLodgifyProperty(
            $lodgify_record_node,
            $lodgify_records['response']['items'][$key]
          );
          $record_count++;
          break;
      }
    }
    $message = "$record_count $record_type records synced using mode: $sync_type.";
    $this->logger->info($message);
    $this->messenger->addStatus($message);
  }

  /**
   * Gets Drupal node that corresponds to lodgify record type and ID; returns
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
    if (!empty($lodgify_property_update_data['image_url'])) {
      $image_url = 'https:' . $lodgify_property_update_data['image_url'];
      $image_data = file_get_contents($image_url);
      if ($image_data) {
        $image_file = $this->fileRepository
          ->writeData($image_data, 'public://' . $lodgify_property_update_data['id'] . '_cover_image.png');
      }
    }
    $lodgify_property_node->set('title', (!empty($lodgify_property_update_data['name'])) ? $lodgify_property_update_data['name'] : 'Property ' . $lodgify_property_update_data['id']);
    $lodgify_property_node->set('field_lodgify_description', (!empty($lodgify_property_update_data['description'])) ? $lodgify_property_update_data['description'] : null);
    $lodgify_property_node->set('field_lodgify_cover_image', (!empty($image_file)) ? [
      'target_id' => $image_file->id(),
      'alt' => $this->t('Lodgify property cover photo'),
      'title' => $this->t('Lodgify property cover photo')
    ] : null);
    $lodgify_property_node->save();
  }

}
