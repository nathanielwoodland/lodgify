<?php declare(strict_types = 1);

namespace Drupal\lodgify\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\lodgify\LodgifyApiClient;
use Drupal\lodgify\LodgifyDataManager;

/**
 * Controller for all Lodgify module routes.
 */
final class LodgifyController extends ControllerBase {

  /**
   * @var \Drupal\lodgify\LodgifyApiClient
   */
  protected LodgifyApiClient $lodgifyApiClient;

  /**
   * @var \Drupal\lodgify\LodgifyDataManager
   */
  protected LodgifyDataManager $lodgifyDataManager;

  /**
   * The controller constructor.
   */
  public function __construct(
    LodgifyApiClient $lodgifyApiClient,
    LodgifyDataManager $lodgifyDataManager,
  ) {
    $this->lodgifyApiClient = $lodgifyApiClient;
    $this->lodgifyDataManager = $lodgifyDataManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('lodgify.lodgify_api_client'),
      $container->get('lodgify.lodgify_data_manager'),
    );
  }

  /**
   * Returns the Lodgify properties listing view.
   */
  public function propertiesListingPage(): array {
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
  public function syncProperties(bool $sync_new_records = true, bool $sync_existing_records = true): RedirectResponse {
    $this->lodgifyDataManager->syncLodgifyData('lodgify_property', $this->lodgifyApiClient->getLodgifyData('properties', '&includeInOut=false'), $sync_new_records, $sync_existing_records);
    $this->messenger()->addStatus('Lodgify properties successfully synced.');
    return $this->redirect('lodgify.settings');
  }

}
