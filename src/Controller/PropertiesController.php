<?php declare(strict_types = 1);

namespace Drupal\lodgify\Controller;

use Drupal\Core\Controller\ControllerBase;

final class PropertiesController extends ControllerBase {

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
