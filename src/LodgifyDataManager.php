<?php declare(strict_types = 1);

namespace Drupal\lodgify;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\FileRepositoryInterface;

/**
 * @todo Add class description.
 */
final class LodgifyDataManager {

  /**
   * Constructs a LodgifyDataManager object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly FileRepositoryInterface $fileRepository,
  ) {}

  /**
   * @param int $property_id
   *
   * @return \Drupal\Core\Entity\EntityInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getOrCreatePropertyById(int $property_id): EntityInterface {
    $lodgify_property_search = $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('field_lodgify_id', $property_id)
      ->execute();
    if ($lodgify_property_search) {
      return $this->entityTypeManager
        ->getStorage('node')
        ->load(reset($lodgify_property_search));
    } else {
      return $this->entityTypeManager
        ->getStorage('node')
        ->create([
          'type' => 'lodgify_property',
        ]);
    }
  }

  public function refreshProperties($lodgify_properties): bool {
    foreach ($lodgify_properties as $key => $value) {
      $property_id = $lodgify_properties[$key]->id;
      $lodgify_property_node = $this->getOrCreatePropertyById($property_id);
      // Create image file from API.
      $image_url = 'https:' . $lodgify_properties[$key]->image_url;
      $image_data = file_get_contents($image_url);
      $image_file = $this->fileRepository
        ->writeData($image_data, "public://$property_id.cover_image.png");
      // Create or update node from API data.
      $lodgify_property_node->set('title', $lodgify_properties[$key]->name);
      $lodgify_property_node->set('field_lodgify_description', $lodgify_properties[$key]->description);
      $lodgify_property_node->set('field_lodgify_id', $property_id);
      $lodgify_property_node->set('field_lodgify_cover_image', [
        'target_id' => $image_file->id(),
        'alt' => 'Lodgify Property',
        'title' => 'Lodgify Property'
      ]);
      $lodgify_property_node->save();
    }
    return true;
  }

}
