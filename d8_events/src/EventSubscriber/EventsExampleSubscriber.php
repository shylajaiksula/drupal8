<?php

namespace Drupal\d8_events\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\d8_events\Event\IncidentEvents;
use Drupal\d8_events\Event\IncidentReportEvent;
use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\Entity\EntityTypeEvent;
use Drupal\Core\Entity;
use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;

/**
 * Subscribe to IncidentEvents::NEW_REPORT events and react to new reports.
 *
 * In this example we subscribe to all IncidentEvents::NEW_REPORT events and
 * point to two different methods to execute when the event is triggered. In
 * each method we have some custom logic that determines if we want to react to
 * the event by examining the event object, and the displaying a message to the
 * user indicating whether or not that method reacted to the event.
 *
 * By convention, classes subscribing to an event live in the
 * Drupal/{module_name}/EventSubscriber namespace.
 *
 * @ingroup events_example
 */
class EventsExampleSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Return an array of events that you want to subscribe to mapped to the
    // method on this class that you would like called whenever the event is
    // triggered. A single class can subscribe to any number of events. For
    // organization purposes it's a good idea to create a new class for each
    // unique task/concept rather than just creating a catch-all class for all
    // event subscriptions.
    //
    // See EventSubscriberInterface::getSubscribedEvents() for an explanation
    // of the array's format.
    //
    // The array key is the name of the event your want to subscribe to. Best
    // practice is to use the constant that represents the event as defined by
    // the code responsible for dispatching the event. This way, if, for
    // example, the string name of an event changes your code will continue to
    // work. You can get a list of event constants for all events triggered by
    // core here:
    // https://api.drupal.org/api/drupal/core%21core.api.php/group/events/8.2.x.
    //
    // Since any module can define and trigger new events there may be
    // additional events available in your application. Look for classes with
    // the special @Event docblock indicator to discover other events.
    //
    // For each event key define an array of arrays composed of the method names
    // to call and optional priorities. The method name here refers to a method
    // on this class to call whenever the event is triggered.
    $events[IncidentEvents::NEW_REPORT][] = array('notifyMario');

    // Subscribers can optionally set a priority. If more than one subscriber is
    // listening to an event when it is triggered they will be executed in order
    // of priority. If no priority is set the default is 0.
    $events[IncidentEvents::NEW_REPORT][] = array('notifyBatman', -100);
    $events[ConfigEvents::SAVE][] = array('onSavingConfig', 800);
    $events[RoutingEvents::ALTER][] = array('onRouteBuilding', 5000);
    //$events[EntityTypeEvents::CREATE][] = array('create_entity', 800);
    //$events[EntityTypeEvents::SAVE][] = array('create_entity', 800);
    //EntityTypeEvents

    return $events;
  }
public function onRouteBuilding(RouteBuildEvent $event) { 
   drupal_set_message($this->t('Route Building '));
    foreach ($event->getRouteCollection() as $route) {
      $methods = $route->getMethods();
      if (empty($methods)) {
        $route->setMethods(array('GET', 'POST'));
      }
    }
  }
  /**
   * If this incident is about a missing princess notify Mario.
   *
   * Per our configuration above, this method is called whenever the
   * IncidentEvents::NEW_REPORT event is dispatched. This method is where you
   * place any custom logic that you want to perform when the specific event is
   * triggered.
   *
   * These responder methods receive an event object as their argument. The
   * event object is usually, but not always, specific to the event being
   * triggered and contains data about application state and configuration
   * relative to what was happening when the event was triggered.
   *
   * For example, when responding to an event triggered by saving a
   * configuration change you'll get an event object that contains the relevant
   * configuration object.
   *
   * @param \Drupal\events_example\Event\IncidentReportEvent $event
   *   The event object containing the incident report.
   */
  
  public function create_entity(Entity $event) {
    drupal_set_message("d8_events Entity saved:You have saved a configuration of " . $event->getConfig()->getName());
  }
  public function notifyMario(IncidentReportEvent $event) {
    // You can use the event object to access information about the event passed
    // along by the event dispatcher.
    if ($event->getType() == 'stolen_princess') {
      drupal_set_message($this->t('d8_events:Mario has been alerted. Thank you. This message was set by an event subscriber. See \Drupal\d8_events\EventSubscriber\EventsExampleSubscriber::notifyMario()'), 'status');
    }
  }

  /**
   * Let Batman know about any events involving the Joker.
   *
   * @param \Drupal\events_example\Event\IncidentReportEvent $event
   *   The event object containing the incident report.
   */
  public function notifyBatman(IncidentReportEvent $event) {
    if ($event->getType() == 'joker') {
      drupal_set_message($this->t('d8_events:Batman has been alerted. Thank you. This message was set by an event subscriber. See \Drupal\d8_events\EventSubscriber\EventsExampleSubscriber::notifyBatman()'), 'status');
      // Optionally use the event object to stop propagation.
      // If there are other subscribers that have not been called yet this will
      // cause them to be skipped.
      $event->stopPropagation();
    }
  }
  
  /**
   * Subscriber Callback for the event.
   * @param ConfigCrudEvent $event
   */
  public function onSavingConfig(ConfigCrudEvent $event) {
    drupal_set_message("d8_events:You have saved a configuration of " . $event->getConfig()->getName());
  }

}
