<?php

/**
 * @file
 * Contains Drupal\node_limit\Form\NodeLimitForm.
 */

namespace Drupal\node_limit\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Url;
use Drupal\Core\Render\Element\RenderElement;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity;
use Symfony\Component\HttpFoundation\Session\SessionInterface as session;
class NodeLimitForm extends Formbase {

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('database'), $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(Connection $database, ModuleHandlerInterface $module_handler) {
    $this->database = $database;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'node_limit_form';
  }

  public function preSave(EntityStorageInterface $storage) {
  // Check if this is an entity bundle.
    die;
  if ($this->getEntityType()->getBundleOf()) {
    // Throw an exception if the bundle ID is longer than 32 characters.
    if (Unicode::strlen($this->id()) > EntityTypeInterface::BUNDLE_MAX_LENGTH) {
      throw new ConfigEntityIdLengthException("Attempt to create a bundle with an ID longer than " . EntityTypeInterface::BUNDLE_MAX_LENGTH . " characters: $this->id().");
    }
  }
}
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $node_limit_id = $request->get('node_limit');
    
    print_r($session);
    if ($node_limit_id) {
      $select = db_select('node_limit', 'nlimit');

      $select->fields('nlimit')
          ->condition('lid', $node_limit_id);

      $info = $select->execute()->fetchAssoc();
    }

    $form['role']['lid'] = [
      '#type' => 'hidden',
      '#default_value' => $info['lid'],
    ];

    $form['role']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#maxlength' => 255,
      '#default_value' => $info['title'],
      '#description' => $this->t("The description for this Node Limit"),
      '#required' => TRUE,
    ];
    $form['role']['limit'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Limit'),
      '#maxlength' => 10,
      '#size' => 10,
      '#default_value' => $info['nlimit'],
      '#description' => $this->t("The number of nodes for this limit. Must be an integer greater than 0 or -1 for no limit"),
      '#required' => TRUE,
    ];

    $elements = $this->moduleHandler->invokeAll('node_limit_element', array($node_limit_id));

    foreach ($elements as $module => $element) {
      if ($module != 'node_limit_interval') {
        $form[$module]['element'][$module] = ($element);
      }
    }
    $form['button']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
      '#weight' => 50
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    /*
     * Saving data to node_limit table
     */
    if ($form_state->getValue('lid'))
      $lid = $form_state->getValue('lid');
    else
      $lid = NULL;
    if (empty($lid))
      $node_limit_id = db_insert('node_limit')
          ->fields(array(
            'lid' => NULL,
            'nlimit' => $form_state->getValue('limit'),
            'title' => $form_state->getValue('label'),
            'type' => $form_state->getValue('node_limit_type'),
          ))
          ->execute();
    else {

      $node_limit_id = db_update('node_limit')
          ->fields(array(
            'lid' => $lid,
            'nlimit' => $form_state->getValue('limit'),
            'title' => $form_state->getValue('label'),
            'type' => $form_state->getValue('node_limit_type'),
          ))
          ->condition('lid', $lid)
          ->execute();
      $node_limit_id = $form_state->getValue('lid');
    }
    // Saving data to  dependent table if node_limit_id created successfully.

    if ($node_limit_id) {
      // Saving data to node_limit_role table if role checkbox selected.
      if ($form_state->getValue('chk_node_limit_role')) {
        $node_limit_role = $form_state->getValue('node_limit_role');
        // Delete previous values
        db_delete('node_limit_role')
            ->condition('lid', $node_limit_id)
            ->execute();
        $node_limit_role_id = db_insert('node_limit_role')
            ->fields(array(
              'lid' => $node_limit_id,
              'role' => $node_limit_role,
            ))
            ->execute();
      }
      // Saving data to node_limit_type table if node_limit_type checkbox 
      // selected.
      if ($form_state->getValue('chk_node_limit_type')) {
        $node_limit_type = $form_state->getValue('node_limit_type');

        db_delete('node_limit_type')
            ->condition('lid', $node_limit_id)
            ->execute();

        $node_limit_type_id = db_insert('node_limit_type')
            ->fields(array(
              'lid' => $node_limit_id,
              'type' => $node_limit_type,
            ))
            ->execute();
      }
      // Saving data to node_limit_user table if node_limit_user table
      // checked.
      if ($form_state->getValue('chk_node_limit_user')) {
        $node_limit_userids = $form_state->getValue('node_limit_user');
        if (!empty($node_limit_userids)) {
          db_delete('node_limit_user')
              ->condition('lid', $node_limit_id)
              ->execute();
          foreach ($node_limit_userids as $key => $useridArray) {
            if (!empty($useridArray['target_id'])) {
              $node_limit_user_id = db_insert('node_limit_user')
                  ->fields(array(
                    'lid' => $node_limit_id,
                    'uid' => $useridArray['target_id'],
                  ))
                  ->execute();
            }
          }
        }
      }
      // Saving data to node_limit_userofrole table if node_limit_userofrole
      //  checkbox selected.
      if ($form_state->getValue('chk_node_limit_userofrole')) {
        $node_limit_userofrole = $form_state->getValue('node_limit_userofrole');
        db_delete('node_limit_userofrole')
            ->condition('lid', $node_limit_id)
            ->execute();
        $node_limit_userofrole_id = db_insert('node_limit_userofrole')
            ->fields(array(
              'lid' => $node_limit_id,
              'role' => $node_limit_userofrole,
            ))
            ->execute();
      }
    }
    if (!empty($node_limit_id)) {
      drupal_set_message($this->t('Successfully saved'));
    }
  }

}
