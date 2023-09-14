<?php declare(strict_types = 1);

namespace Drupal\lodgify\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\lodgify\PropertiesSyncService;
use Drupal\lodgify\BookingsSyncService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Lodgify form.
 */
final class SyncForm extends FormBase {

  /**
   * @var \Drupal\lodgify\PropertiesSyncService
   */
  protected $propertiesSyncService;

  /**
   * @var \Drupal\lodgify\BookingsSyncService
   */
  protected $bookingsSyncService;

  /**
   * @param \Drupal\lodgify\PropertiesSyncService $propertiesSyncService
   * @param \Drupal\lodgify\BookingsSyncService $bookingsSyncService
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
      '#options' => ['lodgify_property' => $this->t('Properties'), 'lodgify_booking' => $this->t('Bookings')],
      '#required' => TRUE,
    ];
    $form['sync_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Sync type'),
      '#options' => ['all' => $this->t('All'), 'new' => $this->t('New'), 'existing' => $this->t('Existing')],
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
      $this->propertiesSyncService->syncLodgifyProperties($sync_type);
    }
    if (in_array('lodgify_booking', $record_types)) {
      $this->bookingsSyncService->syncLodgifyBookings($sync_type);
    }
    $form_state->setRedirect('lodgify.settings.sync');
  }

}
