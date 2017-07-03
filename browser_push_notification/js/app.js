/**
 * @file
 * Notification popup will show if the browser support serviceWorker and browser notification permission is allowed.
 */

(function ($, window, Drupal, drupalSettings) {
  'use strict';
  var public_key = drupalSettings.browser_push_notification.bpn_public_key;
  var baseUrl = (window.location.protocol + '//' + window.location.host) + (drupalSettings.path.baseUrl);
  Drupal.behaviors.browser_push_notification = { // The name of our behavior.
    attach: function (context, settings) {
      var applicationServerKey = public_key;
      if (!(applicationServerKey)) {
        return;
      }
      if (!('serviceWorker' in navigator)) {
        return;
      }
      if (!('PushManager' in window)) {
        return;
      }

      if (!('showNotification' in ServiceWorkerRegistration.prototype)) {
        return;
      }

    // Check the current Notification permission.
    // If its denied, the popup should not appears as such, until the user changes the permission manually.
      if (Notification.permission === 'denied') {
        return;
      }

      navigator.serviceWorker.register(baseUrl + 'serviceWorker.js')
      .then(() => {
      }, e => {
      });

      function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - base64String.length % 4) % 4);
        const base64 = (base64String + padding)
        .replace(/\-/g, '+')
        .replace(/_/g, '/');

        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
          outputArray[i] = rawData.charCodeAt(i);
        }
        return outputArray;
      }
     // Sending push subscription.
      function push_subscribe() {
        navigator.serviceWorker.ready
        .then(serviceWorkerRegistration => serviceWorkerRegistration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(applicationServerKey)
        }))
        .then(subscription => {
             // Subscription was successful
            // create subscription on your server.
          return push_sendSubscriptionToServer(subscription, 'POST');
        })
        .then(subscription => subscription && changePushButtonState('enabled')) // Update your UI.
        .catch(e => {
          if (Notification.permission === 'denied') {
                // The user denied the notification permission which
                // means we failed to subscribe and the user will need
                // to manually change the notification permission to
                // subscribe to push messages.
                // console.warn('Notifications are denied by the user.');
          }
          else {
                // A problem occurred with the subscription; common reasons
                // include network errors or the user skipped the permission.
                // console.error('Impossible to subscribe to push notifications', e);
                // changePushButtonState('disabled');.
          }
        });
      }
      function push_updateSubscription() {
        navigator.serviceWorker.ready.then(serviceWorkerRegistration => serviceWorkerRegistration.pushManager.getSubscription())
        .then(subscription => {
          if (!subscription) {
                // We aren't subscribed to push, so enable subscription.
            push_subscribe();
            return;
          }
          // Return push_sendSubscriptionToServer(subscription, 'PUT');.
        })
        .then(subscription => subscription) // Set your UI to show they have subscribed for push messages.
        .catch(e => {
            // console.error('Error when updating the subscription', e);
        });
      }
    // Sending push subscription to server for storing endpoint,key and token.
      function push_sendSubscriptionToServer(subscription, method) {
        const key = subscription.getKey('p256dh');
        const token = subscription.getKey('auth');
        // console.log(btoa(String.fromCharCode.apply(null, new Uint8Array(key))));
        // console.log( btoa(String.fromCharCode.apply(null, new Uint8Array(token))));
        // console.log(subscription.endpoint);
        var subcribe_url = baseUrl + 'subscribe';
        return fetch(subcribe_url, {
          method,
          body: JSON.stringify({
            endpoint: subscription.endpoint,
            key: key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : null,
            token: token ? btoa(String.fromCharCode.apply(null, new Uint8Array(token))) : null
          })
        }) .then((resp) => resp.json()) // Transform the data into json.
            .then(function (data) {
            // Create and append the li's to the ul
            // console.log(data);
            }).catch(function (err) {
                                           // console.log(err);
            });
      }

         // Notification popup will appear when user allowed notification permission.
      var confirmationDialog = Drupal.dialog('<div class="bpn_message_div" style="display: none !important;"></div>', {
        title: Drupal.t('Get the latest updates through website notifications?'),
        dialogClass: 'bpn-model-popup',
        resizable: false,
        buttons: [
          {
            text: Drupal.t('Allow'),
            class: 'button button--allow',
            click: function () {
              push_updateSubscription();
              confirmationDialog.close();
            }
          },
          {
            text: Drupal.t('Later'),
            class: 'button button--cancel',
            click: function () {
              confirmationDialog.close();
            }
          }
        ],
          // Prevent this modal from being closed without the user making a choice
          // as per http://stackoverflow.com/a/5438771.
        closeOnEscape: false,
        create: function () {

        },
        beforeClose: false,
        close: function (event) {
            // Automatically destroy the DOM element that was used for the dialog.
            // $(event.target).remove();
        }
      });
        // Checking if the user is subcribed for notification, if not popup will appear.
      navigator.serviceWorker.ready.then(serviceWorkerRegistration => serviceWorkerRegistration.pushManager.getSubscription())
        .then(subscription => {
          if (!subscription) {
                // We aren't subscribed to push, so enable subscription.
            confirmationDialog.showModal();
                // return;.
          }
        })
        .then(subscription => subscription)
        .catch(e => {
            // console.error('Error when updating the subscription', e);
        });

    }
  };
})(jQuery, window, Drupal, drupalSettings);
