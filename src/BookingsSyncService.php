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
    $title = !empty($lodgify_record_api_data['guest']['name']) ? 'Booking for ' . $lodgify_record_api_data['guest']['name'] : 'Booking ' . $lodgify_record_api_data['id'];
    $field_lodgify_arrival = !empty($lodgify_record_api_data['arrival']) ? $lodgify_record_api_data['arrival'] : NULL;
    $field_lodgify_source = !empty($lodgify_record_api_data['source']) ? $lodgify_record_api_data['source'] : NULL;
    $field_lodgify_booking_status = !empty($lodgify_record_api_data['status']) ? $lodgify_record_api_data['status'] : NULL;
    $field_lodgify_departure = !empty($lodgify_record_api_data['departure']) ? $lodgify_record_api_data['departure'] : NULL;
    $field_for_lodgify_property_id = !empty($lodgify_record_api_data['property_id']) ? $lodgify_record_api_data['property_id'] : NULL;
    $field_lodgify_guest_name = !empty($lodgify_record_api_data['guest']['name']) ? $lodgify_record_api_data['guest']['name'] : NULL;
    $lodgify_record_node->set('title', $title);
    $lodgify_record_node->set('field_lodgify_arrival', $field_lodgify_arrival);
    $lodgify_record_node->set('field_lodgify_source', $field_lodgify_source);
    $lodgify_record_node->set('field_lodgify_booking_status', $field_lodgify_booking_status);
    $lodgify_record_node->set('field_lodgify_departure', $field_lodgify_departure);
    $lodgify_record_node->set('field_for_lodgify_property_id', $field_for_lodgify_property_id);
    $lodgify_record_node->set('field_lodgify_guest_name', $field_lodgify_guest_name);
    $lodgify_record_node->save();
  }

}
