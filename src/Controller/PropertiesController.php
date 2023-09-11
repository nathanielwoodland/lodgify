<?php declare(strict_types = 1);

namespace Drupal\lodgify\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\lodgify\LodgifyApiClient;
use Drupal\lodgify\PropertiesService;

final class PropertiesController extends ControllerBase {

  /**
   * @var \Drupal\lodgify\LodgifyApiClient
   */
  protected LodgifyApiClient $lodgifyApiClient;

  /**
   * @var \Drupal\lodgify\PropertiesService
   */
  protected PropertiesService $propertiesService;

  /**
   * The controller constructor.
   */
  public function __construct(
    LodgifyApiClient $lodgifyApiClient,
    PropertiesService $propertiesService,
  ) {
    $this->lodgifyApiClient = $lodgifyApiClient;
    $this->propertiesService = $propertiesService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('lodgify.lodgify_api_client'),
      $container->get('lodgify.properties_service'),
    );
  }

  /**
   * Returns the Lodgify properties listing view.
   */
  public function index(): array {
    $build['content']['view'] = [
      '#type' => 'view',
      '#name' => 'lodgify_properties',
    ];
    return $build;
  }

  /**
   * Calls sync properties methods and redirects to Lodgify settings page.
   *
   * @param bool $sync_new_records
   * @param bool $sync_existing_records
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function syncProperties(string $sync_type): RedirectResponse {
    $this->propertiesService->syncLodgifyData('lodgify_property', $this->lodgifyApiClient->getLodgifyData('properties', '&includeInOut=false'), $sync_type);
    $this->messenger()->addStatus('Lodgify properties successfully synced.');
    return $this->redirect('lodgify.settings');
  }

}
