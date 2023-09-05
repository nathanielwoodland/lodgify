<?php declare(strict_types = 1);

namespace Drupal\lodgify\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\FileRepositoryInterface;
use GuzzleHttp\Exception\GuzzleException;
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
   * Builds the response.
   */
  public function listingPage(): array {
    $api_key = $this->config('lodgify.settings')->get('api_key');
    $this->getStrProperties($api_key);

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('List of available Lodgify properties.'),
    ];

    return $build;
  }

  /**
   * @param string $api_key
   * @return null
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   * @throws EntityStorageException
   * @throws GuzzleException
   */
  public function getStrProperties (string $api_key) {
    $client = new \GuzzleHttp\Client();

    $response = $client->request('GET', 'https://api.lodgify.com/v2/properties?includeCount=false&includeInOut=false&page=1&size=50', [
      'headers' => [
        'X-ApiKey' => $api_key,
        'accept' => 'application/json',
      ],
    ]);

    $lodgify_properties = json_decode($response->getBody()->getContents());

    foreach ($lodgify_properties->items as $key => $value) {
      // @todo: Check if property already exists
      $property_id = $lodgify_properties->items[$key]->id;

      // Create file object from remote URL.
      $image_url = 'https:' . $lodgify_properties->items[$key]->image_url;
      $image_data = file_get_contents($image_url);
      $image_file = $this->fileRepository->writeData($image_data, "public://$property_id.cover_image.png");

      // Create node object with attached file.
      $node = $this->entityTypeManager()->getStorage('node')->create([
        'type' => 'lodgify_property',
        'title' => $lodgify_properties->items[$key]->name,
        'field_lodgify_description' => $lodgify_properties->items[$key]->description,
        'field_lodgify_id' => $property_id,
        'field_lodgify_cover_image' => [
          'target_id' => $image_file->id(),
          'alt' => 'Lodgify Property',
          'title' => 'Lodgify Property'
        ],
      ]);
      $node->save();
    }

    return null;
  }

}
