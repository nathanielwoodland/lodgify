<?php

declare(strict_types = 1);

namespace Drupal\lodgify\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\lodgify\BookingsSyncService;
use Drupal\lodgify\PropertiesSyncService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Lodgify record sync form.
 */
final class SyncForm extends FormBase {

  /**
   * Properties sync service.
   *
   * @var \Drupal\lodgify\PropertiesSyncService
   */
  protected $propertiesSyncService;

  /**
   * Bookings sync service.
   *
   * @var \Drupal\lodgify\BookingsSyncService
   */
  protected $bookingsSyncService;

  /**
   * Builds class with required services.
   *
   * @param \Drupal\lodgify\PropertiesSyncService $propertiesSyncService
   *   Properties sync service.
   * @param \Drupal\lodgify\BookingsSyncService $bookingsSyncService
   *   Bookings sync service.
   */
  public function __construct(
    PropertiesSyncService $propertiesSyncService,
    BookingsSyncService $bookingsSyncService,
  ) {
    $this->propertiesSyncService = $propertiesSyncService;
    $this->bookingsSyncService = $bookingsSyncService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('lodgify.properties_sync_service'),
      $container->get('lodgify.bookings_sync_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'lodgify_sync';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['record_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Record types'),
      '#options' => [
        'lodgify_property' => $this->t('Properties'),
      ],
      '#required' => TRUE,
    ];
    if ($this->config('lodgify.settings')->get('enable_booking')) {
      $form['record_types']['#options']['lodgify_booking'] = $this->t('Bookings');
    }
    $form['sync_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Sync type'),
      '#options' => [
        'all' => $this->t('All'),
        'new' => $this->t('New'),
        'existing' => $this->t('Existing'),
      ],
      '#required' => TRUE,
    ];
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Sync'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $sync_type = $form_state->getValue('sync_type');
    $record_types = $form_state->getValue('record_types');
    if (in_array('lodgify_property', $record_types)) {
      $this->propertiesSyncService->syncLodgifyRecords($sync_type);
    }
    if ($this->config('lodgify.settings')->get('enable_booking') && in_array('lodgify_booking', $record_types)) {
      $this->bookingsSyncService->syncLodgifyRecords($sync_type);
    }
    $form_state->setRedirect('lodgify.settings.sync');
  }

}
