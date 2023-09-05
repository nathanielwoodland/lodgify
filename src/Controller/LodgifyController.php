<?php declare(strict_types = 1);

namespace Drupal\lodgify\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\FileRepositoryInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Lodgify routes.
 */
final class LodgifyController extends ControllerBase {

  /**
   * The controller constructor.
   */
  public function __construct(
    private readonly FileRepositoryInterface $fileRepository,
    EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('file.repository'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * @return array[]
   */
  private function getGuzzleClientOptions(): array {
    // @todo: validate if API is set.
    $api_key = $this->config('lodgify.settings')->get('api_key');
    return [
      'headers' => [
        'X-ApiKey' => $api_key,
        'accept' => 'application/json',
      ],
    ];
  }

  /**
   * Returns the Lodgify properties listing page.
   */
  public function propertiesListPage(): array {
    $build['content']['view'] = [
      '#type' => 'view',
      '#name' => 'lodgify_properties',
    ];
    return $build;
  }

  private function getProperties(): array {
    $guzzle_client_options = $this->getGuzzleClientOptions();
    $client = new Client();
    // @todo: add pagination support for more than 50 properties
    $response = $client->request('GET', 'https://api.lodgify.com/v2/properties?includeCount=true&includeInOut=false&page=1&size=50', $guzzle_client_options);
    $lodgify_properties = json_decode($response->getBody()->getContents());
    return $lodgify_properties->items;
  }
  public function refreshProperties() {
    $lodgify_properties = $this->getProperties();
    foreach ($lodgify_properties as $key => $value) {
      // @todo: Check if property already exists
      $property_id = $lodgify_properties[$key]->id;

      // Create file object from remote URL.
      $image_url = 'https:' . $lodgify_properties[$key]->image_url;
      $image_data = file_get_contents($image_url);
      $image_file = $this->fileRepository->writeData($image_data, "public://$property_id.cover_image.png");

      // Create node object with attached file.
      $node = $this->entityTypeManager()->getStorage('node')->create([
        'type' => 'lodgify_property',
        'title' => $lodgify_properties[$key]->name,
        'field_lodgify_description' => $lodgify_properties[$key]->description,
        'field_lodgify_id' => $property_id,
        'field_lodgify_cover_image' => [
          'target_id' => $image_file->id(),
          'alt' => 'Lodgify Property',
          'title' => 'Lodgify Property'
        ],
      ]);
      $node->save();
    }

    return $this->propertiesListPage();
  }

}
