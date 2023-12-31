<?php

declare(strict_types = 1);

namespace Drupal\lodgify;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Service class to manage interactions with Lodgify API.
 */
final class LodgifyApiClient {
  use StringTranslationTrait;

  /**
   * Constructs a LodgifyApiClient object.
   */
  public function __construct(
    private readonly Settings $settings,
    private readonly Client $httpClient,
    private readonly MessengerInterface $messenger,
    TranslationInterface $stringTranslation,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Gets data from Lodgify API.
   *
   * @param string $route
   *   The Lodgify V2 API route.
   *     - see: https://docs.lodgify.com/reference/getallpropertiesasync.
   * @param string $query_params
   *   The Lodgify API Query Params.
   *     - see https://docs.lodgify.com/reference/getallpropertiesasync.
   *
   * @return array
   *   Returns array in following format:
   *     [
   *       'success' => TRUE|FALSE,
   *       'records' => array, // Only if success = TRUE
   *     ]
   */
  public function getLodgifyData(string $route, string $query_params): array {
    $headers = $this->getRequestHeaders();
    if (!$headers) {
      return [
        'success' => FALSE,
      ];
    }
    try {
      $response = $this->httpClient->request(
        'GET',
        "https://api.lodgify.com/v2/$route?$query_params",
        $headers
      );
    }
    catch (GuzzleException $e) {
      $error_code = $e->getCode();
      $error_message = $this->t('API request for Lodgify') . " $route " . $this->t('records failed with HTTP status code') . ": $error_code.";
      $this->messenger->addError($error_message);
      $this->logger->error($error_message);
      return [
        'success' => FALSE,
      ];
    }
    $response = json_decode($response->getBody()->getContents(), TRUE);
    if (empty($response['items'])) {
      $error_message = $this->t('No records found of type:') . " $route";
      $this->messenger->addError($error_message);
      $this->logger->error($error_message);
      return [
        'success' => FALSE,
      ];
    }
    return [
      'success' => TRUE,
      'records' => $response['items'],
    ];
  }

  /**
   * Gets request headers including API key for authentication.
   *
   * @return false|array[]
   *   Either returns the request headers array, or FALSE.
   */
  private function getRequestHeaders(): array|bool {
    $api_key = $this->settings->get('lodgify_api_key');
    if (!$api_key) {
      $error_message = $this->t('Lodgify API key not found.');
      $this->messenger->addError($error_message);
      $this->logger->error($error_message);
      return FALSE;
    }
    return [
      'headers' => [
        'X-ApiKey' => $api_key,
        'accept' => 'application/json',
      ],
    ];
  }

}
