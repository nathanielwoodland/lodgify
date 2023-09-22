<?php

declare(strict_types = 1);

namespace Drupal\lodgify;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\node\Entity\Node;
use Psr\Log\LoggerInterface;

/**
 * Base class for syncing a specific Lodgify record type.
 */
class SyncServiceBase {
  use StringTranslationTrait;

  /**
   * CRUD service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * File management service.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected FileRepositoryInterface $fileRepository;

  /**
   * Messaging service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * Lodgify API service.
   *
   * @var \Drupal\lodgify\LodgifyApiClient
   */
  protected LodgifyApiClient $lodgifyApiClient;

  /**
   * Logging service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructs a SyncServiceBase object.
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
   * Updates local Lodgify node from API data array.
   *
   * @param \Drupal\node\Entity\Node $lodgify_record_node
   *   The local node to update.
   * @param array $lodgify_record_api_data
   *   The data to update the local node with.
   *
   * @return void
   *   Errors saving data caught and printed by core modules.
   */
  protected function updateLodgifyNode(Node $lodgify_record_node, array $lodgify_record_api_data) {}

  /**
   * Creates and/or updates new and/or existing local Lodgify records.
   *
   * @param string $record_type
   *   The local node type, e.g. 'lodgify_property' or 'lodgify_booking'.
   * @param string $sync_type
   *   Expects 'new', 'existing', or 'all'.
   * @param array $records_from_lodgify_api
   *   Records from Lodgify API used to create / update local records.
   *
   * @return void
   *   Syncing errors printed and logged by called methods.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function syncLodgifyRecordsByType(string $record_type, string $sync_type, array $records_from_lodgify_api): void {
    $record_count = 0;
    foreach ($records_from_lodgify_api as $lodgify_record) {
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
   * Gets the Drupal node that corresponds to lodgify record type and ID.
   *
   * @param string $record_type
   *   The local node type, e.g. 'lodgify_property' or 'lodgify_booking'.
   * @param int $lodgify_id
   *   The record ID from Lodgify.
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   *   Returns matching node, or false if not found.
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
