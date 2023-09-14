<?php declare(strict_types = 1);

namespace Drupal\lodgify\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\lodgify\PropertiesSyncService;
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
   * @param \Drupal\lodgify\PropertiesSyncService $propertiesSyncService
   */
  public function __construct(PropertiesSyncService $propertiesSyncService) {
    $this->propertiesSyncService = $propertiesSyncService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('lodgify.properties_sync_service')
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
    // @todo: add support for bookings record type
    $record_types = $form_state->getValue('record_types');
    $this->propertiesSyncService->syncLodgifyProperties($sync_type);
    $form_state->setRedirect('lodgify.settings.sync');
  }

}
