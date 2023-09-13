<?php declare(strict_types = 1);

namespace Drupal\lodgify\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\lodgify\PropertiesService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Lodgify form.
 */
final class SyncForm extends FormBase {

  /**
   * @var \Drupal\lodgify\PropertiesService
   */
  protected $propertiesService;

  /**
   * @param \Drupal\lodgify\PropertiesService $propertiesService
   */
  public function __construct(PropertiesService $propertiesService) {
    $this->propertiesService = $propertiesService;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('lodgify.properties_service')
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
    $record_types = $form_state->getValue('record_types');
    $sync_type = $form_state->getValue('sync_type');
    $this->propertiesService->syncLodgifyRecordTypes($record_types, $sync_type);
    $form_state->setRedirect('lodgify.settings.sync');
  }

}
