<?php

namespace Drupal\exchange_rate\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Configure Exchange Rate settings for this site.
 */
class ExchangeRateSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'exchange_rate_exchange_rate_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['exchange_rate.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\Config $config */
    $config = $this->config('exchange_rate.settings');

    // Grab API credential (Url, email & token) values.
    $form['endpoint'] = [
      '#type' => 'url',
      '#title' => $this->t('Webservice endpoint'),
      '#default_value' => $config->get('endpoint'),
      '#required' => TRUE,
      '#size' => 110,
    ];
    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Authorized email address'),
      '#default_value' => $config->get('email'),
      '#required' => TRUE,
    ];
    $form['token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('A valid Token'),
      '#default_value' => $config->get('token'),
      '#required' => TRUE,
    ];
    $form['documentation'] = [
      '#markup' => $this->t('Official documentation @here. Get your @token.', [
        '@here' => Link::fromTextAndUrl(
          'here',
          Url::fromUri('https://www.bccr.fi.cr/seccion-indicadores-economicos/servicio-web/gu%C3%ADa-de-uso')
        )->toString(),
        '@token' => Link::fromTextAndUrl(
          'Token',
          Url::fromUri('https://www.bccr.fi.cr/seccion-indicadores-economicos/servicio-web')
        )->toString()
      ]),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('exchange_rate.settings')
      ->set('endpoint', $form_state->getValue('endpoint'))
      ->set('email', $form_state->getValue('email'))
      ->set('token', $form_state->getValue('token'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
