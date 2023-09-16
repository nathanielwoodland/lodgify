<?php declare(strict_types = 1);

namespace Drupal\lodgify;

use Drupal\Core\Site\Settings;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Psr\Log\LoggerInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

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
   * @param string $query_params
   *
   * @return array
   */
  public function getLodgifyData(string $route, string $query_params): array {
    $headers = $this->getRequestHeaders();
    if (!$headers) {
      return [
        'success' => false,
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
      $error_message = $this->t("API request for Lodgify $route records failed with HTTP status code: $error_code.");
      $this->messenger->addError($error_message);
      $this->logger->error($error_message);
      return [
        'success' => false,
      ];
    }
    $response = json_decode($response->getBody()->getContents(), true);
    if (empty($response['items'])) {
      $this->messenger->addError($this->t("No $route records found."));
      return [
        'success' => false,
      ];
    }
    return [
      'success' => true,
      'records' => $response['items'],
    ];
  }

  /**
   * Gets request headers including API key for authentication.
   *
   * @return bool|array[]
   */
  private function getRequestHeaders(): array|bool {
    $api_key = $this->settings->get('lodgify_api_key');
    if (!$api_key) {
      $this->messenger->addError($this->t('Lodgify API key not found.'));
      return false;
    }
    return [
      'headers' => [
        'X-ApiKey' => $api_key,
        'accept' => 'application/json',
      ],
    ];
  }

}
