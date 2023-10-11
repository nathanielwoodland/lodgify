<?php

declare(strict_types = 1);

namespace Drupal\lodgify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Lodgify settings for this site.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'lodgify_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['lodgify.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['enable_booking'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable booking and reservation functionality (WIP)'),
      '#default_value' => $this->config('lodgify.settings')->get('enable_booking'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->config('lodgify.settings')
      ->set('enable_booking', $form_state->getValue('enable_booking'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
