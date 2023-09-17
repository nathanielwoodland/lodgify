<?php

declare(strict_types = 1);

namespace Drupal\lodgify;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\file\FileRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 *
 */
class SyncServiceBase {
  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected FileRepositoryInterface $fileRepository;

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * @var \Drupal\lodgify\LodgifyApiClient
   */
  protected LodgifyApiClient $lodgifyApiClient;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructs a PropertiesSyncService object.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    FileRepositoryInterface $fileRepository,
    MessengerInterface $messenger,
    LodgifyApiClient $lodgifyApiClient,
    TranslationInterface $stringTranslation,
    LoggerInterface $logger,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->fileRepository = $fileRepository;
    $this->messenger = $messenger;
    $this->lodgifyApiClient = $lodgifyApiClient;
    $this->stringTranslation = $stringTranslation;
    $this->logger = $logger;
  }

  /**
   * Creates and/or updates new and/or existing local Lodgify records.
   *
   * @param string $record_type
   * @param string $sync_type
   * @param array $lodgify_records
   *
   * @return void
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function syncLodgifyRecordsByType(string $record_type, string $sync_type, array $lodgify_records): void {
    $record_count = 0;
    foreach ($lodgify_records as $lodgify_record) {
      $lodgify_id = $lodgify_record['id'];
      $existing_lodgify_property_node = $this->getLocalLodgifyRecord($record_type, $lodgify_id);
      // Skip if record already exists and sync type is 'new'.
      if ($existing_lodgify_property_node && $sync_type === 'new') {
        continue;
      }
      // Skip if record doesn't yet exist and sync type is 'existing'.
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
      $this->updateLodgifyNode(
        $lodgify_record_node,
        $lodgify_record
      );
      $record_count++;
    }
    $message = "$record_count $record_type records synced using mode: $sync_type.";
    $this->messenger->addStatus($message);
    $this->logger->info($message);
  }

  /**
   * Updates local Lodgify node from API data array.
   *
   * @param $lodgify_record_node
   *   The local node to update.
   *
   * @param $lodgify_record_api_data
   *   The data to update the local node with.
   *
   * @return void
   *   Errors saving data caught and printed by core modules.
   */
  protected function updateLodgifyNode($lodgify_record_node, $lodgify_record_api_data) {}

  /**
   * Sync records from Lodgify.
   *
   * @param string $sync_type
   *   Expects 'new', 'existing', or 'all'.
   *
   * @return void
   *   Success and errors are handles in called methods.
   */
  public function syncLodgifyRecords(string $sync_type): void {}

  /**
   * Gets Drupal node that corresponds to lodgify record type and ID; returns
   * false if not found.
   *
   * @param string $record_type
   * @param int $lodgify_id
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getLocalLodgifyRecord(string $record_type, int $lodgify_id): mixed {
    $lodgify_record_search = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', $record_type)
      ->condition('field_lodgify_id', $lodgify_id)
      ->execute();
    if ($lodgify_record_search) {
      return $this->entityTypeManager
        ->getStorage('node')
        ->load(reset($lodgify_record_search));
    }
    return FALSE;
  }

}
