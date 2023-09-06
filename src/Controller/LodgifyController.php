<?php declare(strict_types = 1);

namespace Drupal\lodgify\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\FileRepositoryInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for Lodgify routes.
 */
class LodgifyController extends ControllerBase {

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

  /**
   * @return array
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  private function getProperties(): array {
    $guzzle_client_options = $this->getGuzzleClientOptions();
    $client = new Client();
    // @todo: add pagination support for more than 50 properties
    $response = $client->request('GET', 'https://api.lodgify.com/v2/properties?includeCount=true&includeInOut=false&page=1&size=50', $guzzle_client_options);
    $lodgify_properties = json_decode($response->getBody()->getContents());
    return $lodgify_properties->items;
  }

  /**
   * @param int $property_id
   *
   * @return \Drupal\Core\Entity\EntityInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getOrCreatePropertyById(int $property_id): EntityInterface {
    $lodgify_property_search = $this->entityTypeManager()
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('field_lodgify_id', $property_id)
      ->execute();
    if ($lodgify_property_search) {
      return $this->entityTypeManager()
        ->getStorage('node')
        ->load(reset($lodgify_property_search));
    } else {
      return $this->entityTypeManager()
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

  public function callRefreshProperties($test_mode = false): RedirectResponse {
    $this->refreshProperties($this->getProperties());
    $this->messenger()->addStatus('Lodgify properties successfully refreshed.');
    return $this->redirect('lodgify.settings');
  }

}
