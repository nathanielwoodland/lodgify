<?php

declare(strict_types = 1);

namespace Drupal\lodgify;

/**
 * Service for syncing booking records from Lodgify.
 */
final class BookingsSyncService extends SyncServiceBase {

  /**
   * {@inheritDoc}
   */
  public function syncLodgifyRecords(string $sync_type): void {
    $request_result = $this->lodgifyApiClient->getLodgifyData('reservations/bookings', 'includeExternal=true');
    if ($request_result['success']) {
      $this->syncLodgifyRecordsByType('lodgify_booking', $sync_type, $request_result['records']);
    }
  }

  /**
   * {@inheritDoc}
   */
  protected function updateLodgifyNode($lodgify_record_node, $lodgify_record_api_data): void {
    $lodgify_record_node->set('title', (!empty($lodgify_record_api_data['guest']['name'])) ? 'Booking for ' . $lodgify_record_api_data['guest']['name'] : 'Booking ' . $lodgify_record_api_data['id']);
    $lodgify_record_node->set('field_lodgify_arrival', $lodgify_record_api_data['arrival']);
    $lodgify_record_node->set('field_lodgify_source', (!empty($lodgify_record_api_data['source'])) ? $lodgify_record_api_data['source'] : NULL);
    $lodgify_record_node->set('field_lodgify_booking_status', (!empty($lodgify_record_api_data['status'])) ? $lodgify_record_api_data['status'] : NULL);
    $lodgify_record_node->set('field_lodgify_departure', $lodgify_record_api_data['departure']);
    $lodgify_record_node->set('field_for_lodgify_property_id', $lodgify_record_api_data['property_id']);
    $lodgify_record_node->set('field_lodgify_guest_name', (!empty($lodgify_record_api_data['guest']['name'])) ? $lodgify_record_api_data['guest']['name'] : NULL);
    $lodgify_record_node->save();
  }

}
