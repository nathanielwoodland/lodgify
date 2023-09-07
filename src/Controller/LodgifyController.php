<?php declare(strict_types = 1);

namespace Drupal\lodgify\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\file\FileRepositoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\lodgify\LodgifyApiClient;
use Drupal\lodgify\LodgifyDataManager;

/**
 * Returns responses for Lodgify routes.
 */
final class LodgifyController extends ControllerBase {

  protected $lodgifyApiClient;
  protected $lodgifyDataManager;

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
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function callRefreshProperties($test_mode = false): RedirectResponse {
    $this->lodgifyDataManager->refreshProperties($this->lodgifyApiClient->getLodgifyData('properties', '&includeInOut=false'));
    $this->messenger()->addStatus('Lodgify properties successfully refreshed.');
    return $this->redirect('lodgify.settings');
  }

}
