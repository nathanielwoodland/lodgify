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
    $request_result = $this->lodgifyApiClient->getLodgifyData('reservations/bookings', 'includeExternal=true');
    if ($request_result['success']) {
      $this->syncLodgifyRecordsByType('lodgify_booking', $sync_type, $request_result['records']);
    }
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
    $lodgify_record_node->set('title', (!empty($lodgify_record_api_data['guest']['name'])) ? 'Booking for ' . $lodgify_record_api_data['guest']['name'] : 'Booking ' . $lodgify_record_api_data['id']);
    $lodgify_record_node->set('field_lodgify_arrival', $lodgify_record_api_data['arrival']);
    $lodgify_record_node->set('field_lodgify_source', (!empty($lodgify_record_api_data['source'])) ? $lodgify_record_api_data['source'] : null);
    $lodgify_record_node->set('field_lodgify_booking_status', (!empty($lodgify_record_api_data['status'])) ? $lodgify_record_api_data['status'] : null);
    $lodgify_record_node->set('field_lodgify_departure', $lodgify_record_api_data['departure']);
    $lodgify_record_node->set('field_for_lodgify_property_id', $lodgify_record_api_data['property_id']);
    $lodgify_record_node->set('field_lodgify_guest_name', (!empty($lodgify_record_api_data['guest']['name'])) ? $lodgify_record_api_data['guest']['name'] : null);
    $lodgify_record_node->save();
  }

}
