<?php declare(strict_types = 1);

namespace Drupal\lodgify\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\lodgify\PropertiesSyncService;

final class PropertiesController extends ControllerBase {

  /**
   * @var \Drupal\lodgify\PropertiesSyncService
   */
  protected PropertiesSyncService $propertiesSyncService;

  /**
   * The controller constructor.
   */
  public function __construct(
    PropertiesSyncService $propertiesSyncService,
  ) {
    $this->propertiesSyncService = $propertiesSyncService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self(
      $container->get('lodgify.properties_sync_service'),
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

}
