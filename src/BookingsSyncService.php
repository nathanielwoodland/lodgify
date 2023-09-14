<?php declare(strict_types = 1);

namespace Drupal\lodgify;

final class BookingsSyncService extends SyncServiceBase {

  /**
   * @param string $sync_type
   *
   * @return void
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function syncLodgifyBookings(string $sync_type): void {
    $lodgify_properties = $this->lodgifyApiClient->getLodgifyData('reservations/bookings', 'includeExternal=true');
    if (empty($lodgify_properties['response']['items'])) {
      $this->messenger->addError($this->t("No Lodgify bookings found."));
    }
    // @todo: call once method implemented
    // $this->syncLodgifyRecordsByType('lodgify_booking', $sync_type, $lodgify_properties['response']['items']);
  }

  /**
   * @inheritDoc
   *
   * Updates local Lodgify booking node from API data array.
   *
   * @param $lodgify_property_node
   * @param $lodgify_property_api_data
   *
   * @return void
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function updateLodgifyNode($lodgify_record_node, $lodgify_record_api_data): void {
    // $lodgify_record_node->set('title', (!empty($lodgify_record_api_data['name'])) ? $lodgify_record_api_data['name'] : 'Booking ' . $lodgify_record_api_data['id']);
    // $lodgify_record_node->save();
  }

}
