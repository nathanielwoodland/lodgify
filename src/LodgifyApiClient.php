<?php declare(strict_types = 1);

namespace Drupal\lodgify;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Client;

/**
 * Service class to manage interactions with Lodgify API.
 */
final class LodgifyApiClient {

  /**
   * Constructs a LodgifyApiClient object.
   */
  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
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
    $client = new Client();
    // @todo: add pagination support for more than 50 results
    // @todo: use Drupal HTTP client instead of Guzzle
    $page_number = 1;
    $response = $client->request('GET', "https://api.lodgify.com/v2/$record_type?includeCount=true&page=$page_number&size=50$query_params", $this->getGuzzleRequestHeaders());
    return json_decode($response->getBody()->getContents())->items;
  }

  /**
   * Gets Guzzle headers including API key for authentication.
   *
   * @return array[]
   */
  private function getGuzzleRequestHeaders(): array {
    // @todo: validate if API key is set.
    $api_key = $this->configFactory->get('lodgify.settings')->get('api_key');
    return [
      'headers' => [
        'X-ApiKey' => $api_key,
        'accept' => 'application/json',
      ],
    ];
  }
}
