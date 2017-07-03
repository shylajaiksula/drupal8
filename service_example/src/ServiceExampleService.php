<?php
 
/**
 * @file
 * Contains \Drupal\service_example\ServiceExampleService.
 */
 
namespace Drupal\service_example;
 
class ServiceExampleService {
 
  protected $service_example_value;
 
  /**
   * When the service is created, set a value for the example variable.
   */
  public function __construct() {
    $this->service_example_value = 'Student';
  }
 
  /**
   * Return the value of the example variable.
   */
  public function getServiceExampleValue() {
    return $this->service_example_value;
  }
 
}
