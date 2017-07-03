<?php

namespace Drupal\browser_push_notification\Model;

use Minishlink\WebPush\WebPush;

/**
 * Class SubscriptionsDatastorage.
 *
 * @package Drupal\browser_push_notification\Model
 */
class SubscriptionsDatastorage {
  public static $browserSubscriptionTable = 'browser_subscriptions';
  public static $browserSubscriptionCount = 5;

  /**
   * Save an entry in the database.
   *
   * @param array $entry
   *   An array containing all the fields of the database record.
   *
   * @return int
   *   The number of updated rows.
   *
   * @throws \Exception
   *   When the database insert fails.
   */
  public static function insert(array $entry) {
    $return_value = NULL;
    $arguments = [];
    $arguments[':endpoint'] = $entry['subscription_endpoint'];
    $subscription_exist = db_select(self::$browserSubscriptionTable)
      ->fields('browser_subscriptions')
      ->where('subscription_endpoint=:endpoint', $arguments)
      ->execute()
      ->fetchAll();
    if ($subscription_exist) {
      return $subscription_exist;
    }
    try {
      $return_value = db_insert('browser_subscriptions')
        ->fields($entry)
        ->execute();
    }
    catch (\Exception $e) {
      drupal_set_message(t('db_insert failed. Message = %message, query= %query',
      ['%message' => $e->getMessage(), '%query' => $e->query_string]), 'error');
    }
    return $return_value;
  }

  /**
   * Load all client subscription details to send notification.
   */
  public static function loadAll() {
    // Read all fields from the browser_subscriptions table.
    $select = db_select(self::$browserSubscriptionTable, 'browser_subscriptions');
    $select->fields('browser_subscriptions');
    return $select->execute()->fetchAll();
  }

  /**
   * Batch process to start subscription.
   *
   * @param array $subscriptionData
   *   Array of subscription data.
   * @param string $notification_data
   *   String of subscription data.
   */
  public static function sendNotificationStart(array $subscriptionData, $notification_data) {
    if (!empty($subscriptionData) && !empty($notification_data)) {
      foreach ($subscriptionData as $subscription) {
        $subscription_data = unserialize($subscription->subscription_data);
        $subscription_endpoint = $subscription->subscription_endpoint;
        $key = $subscription_data['key'];
        $token = $subscription_data['token'];
        $bpn_public_key = \Drupal::config('browser_push_notification.settings')->get('bpn_public_key');
        $bpn_private_key = \Drupal::config('browser_push_notification.settings')->get('bpn_private_key');
        if (!empty($key) && !empty($token) && !empty($subscription_endpoint)) {
          $auth = [
            'VAPID' => [
              'subject' => 'https://github.com/Minishlink/web-push-php-example/',
              'publicKey' => $bpn_public_key,
              'privateKey' => $bpn_private_key,
            ],
          ];
          $webPush = new WebPush($auth);
          $webPush->sendNotification(
          $subscription_endpoint,
          $notification_data,
          $key,
          $token,
          TRUE
          );
        }

      }
    }
  }

  /**
   * Batch End process.
   */
  public static function notificationFinished() {
    return TRUE;
  }

}
