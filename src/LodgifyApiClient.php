<?php declare(strict_types = 1);

namespace Drupal\lodgify;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\Client;

/**
 * @todo Add class description.
 */
final class LodgifyApiClient {

  /**
   * Constructs a LodgifyApiClient object.
   */
  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
  ) {}

  /**
   * @return array[]
   */
  private function getGuzzleClientOptions(): array {
    // @todo: validate if API is set.
    $api_key = $this->configFactory->get('lodgify.settings')->get('api_key');
    return [
      'headers' => [
        'X-ApiKey' => $api_key,
        'accept' => 'application/json',
      ],
    ];
  }

  /**
   * @return array
   * @throws \GuzzleHttp\Exception\GuzzleException
   */
  public function getProperties(): array {
    $guzzle_client_options = $this->getGuzzleClientOptions();
    $client = new Client();
    // @todo: add pagination support for more than 50 properties
    $response = $client->request('GET', 'https://api.lodgify.com/v2/properties?includeCount=true&includeInOut=false&page=1&size=50', $guzzle_client_options);
    $lodgify_properties = json_decode($response->getBody()->getContents());
    return $lodgify_properties->items;
  }
}
