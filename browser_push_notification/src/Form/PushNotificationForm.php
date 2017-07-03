<?php

namespace Drupal\browser_push_notification\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\browser_push_notification\Model\SubscriptionsDatastorage;

/**
 * Class PushNotificationForm.
 *
 * @package Drupal\browser_push_notification\Form
 */
class PushNotificationForm extends FormBase {

  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'push_notification_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Form constructor.
    $form['sendMessage'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Browser Notification Details'),
    ];
    $form['sendMessage']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Notification Title'),
      '#required' => TRUE,
      '#size' => 100,
      '#description' => $this->t('Enter the Title of the Notification.'),
    ];

    $form['sendMessage']['body'] = [
      '#type' => 'textarea',
      '#required' => TRUE,
      '#title' => $this->t('Notification Message'),
      '#maxlength' => 300,
      '#description' => $this->t('Enter the Message of the Notification.'),
    ];

    $form['sendMessage']['icon'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Notification Image URL'),
      '#description' => $this->t('Enter the Image URL which will show in the Notification.'),
    ];

    $form['sendMessage']['url'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Notification URL'),
      '#description' => $this->t('Enter the URL on which user will redirect after clicking on Notification.Eg.http://example.com/test-contents'),
    ];

    $form['sendMessage']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Send Notification'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!(filter_var($form_state->getValue('url'), FILTER_VALIDATE_URL))) {
      $form_state->setErrorByName('url', $this->t('Not a valid url.'));
    }
    if (!(filter_var($form_state->getValue('icon'), FILTER_VALIDATE_URL))) {
      $form_state->setErrorByName('icon', $this->t('Not a valid image url.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $account = $this->currentUser();
    $entry = [
      'title' => $form_state->getValue('title'),
      'body' => $form_state->getValue('body'),
      'icon' => $form_state->getValue('icon'),
      'url' => $form_state->getValue('url'),
      'created_date' => strtotime(date('Y-m-d H:i:s')),
      'uid' => $account->id(),
    ];
    $notification_data = $entry['title'] . '<br>';
    $notification_data .= $entry['body'] . '<br>';
    $notification_data .= $entry['icon'] . '<br>';
    $notification_data .= $entry['url'] . '<br>';
    $subscriptions = SubscriptionsDatastorage::loadAll();
    $bpn_public_key = $this->config('browser_push_notification.settings')->get('bpn_public_key');
    $bpn_private_key = $this->config('browser_push_notification.settings')->get('bpn_private_key');
    if (empty($bpn_public_key) && empty($bpn_private_key)) {
      drupal_set_message($this->t('Please set public & private key.'), 'error');
    }
    if (!empty($subscriptions) && !empty($bpn_public_key) && !empty($bpn_private_key)) {
      $batch = [
        'title' => $this->t('Sending Push Notification...'),
        'operations' => [
          [
            '\Drupal\browser_push_notification\Model\SubscriptionsDatastorage::sendNotificationStart',
            [$subscriptions, $notification_data],
          ],
        ],
        'finished' => '\Drupal\browser_push_notification\Model\SubscriptionsDatastorage::notificationFinished',
      ];
      batch_set($batch);
      drupal_set_message($this->t('Push notification sent successfully to  @entry users', ['@entry' => print_r(count($subscriptions), TRUE)]));
    }
    else {
      drupal_set_message($this->t('Subscription list is empty.'), 'error');
    }
  }

}
