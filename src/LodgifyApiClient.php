<?php declare(strict_types = 1);

namespace Drupal\lodgify;

use Drupal\Core\Config\ConfigFactoryInterface;
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
    private readonly ConfigFactoryInterface $configFactory,
    private readonly Client $httpClient,
    private readonly MessengerInterface $messenger,
    TranslationInterface $stringTranslation,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * Gets data from Lodgify API.
   *
   * @param string $record_type
   * @param string $query_params
   *
   * @return array
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getLodgifyData(string $record_type, string $query_params): array {
    $headers = $this->getRequestHeaders();
    if (!$headers) {
      return [];
    }
    // @todo: add pagination support
    $page_number = 1;
    try {
      $response = $this->httpClient->request(
        'GET',
        "https://api.lodgify.com/v2/$record_type?includeCount=true&page=$page_number&size=50$query_params",
        $headers
      );
    }
    catch (GuzzleException $e) {
      $error_code = $e->getCode();
      $error_message = $this->t("API request for Lodgify $record_type records failed with HTTP status code: $error_code.");
      $this->messenger->addError($error_message);
      $this->logger->error($error_message);
      return [
        'success' => false,
      ];
    }
    $response = json_decode($response->getBody()->getContents(), true);
    return [
      'success' => true,
      'response' => $response,
    ];
  }

  /**
   * Gets request headers including API key for authentication.
   *
   * @return bool|array[]
   */
  private function getRequestHeaders(): array|bool {
    $api_key = $this->configFactory->get('lodgify.settings')->get('api_key');
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
