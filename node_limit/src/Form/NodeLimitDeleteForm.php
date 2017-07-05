<?php

/**
 * @file
 * Contains Drupal\node_limit\Form\NodeLimitDeleteForm.
 */

namespace Drupal\node_limit\Form;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
/**
 * Builds the form to delete a NodeLimit.
 */
class NodeLimitDeleteForm extends ConfirmFormBase {

  protected $id;

  function getFormId() {
    return 'node_limit_delete';
  }

  function getQuestion() {
    return t('Are you sure you want to delete node limit %id?', array('%id' => $this->id));
  }

  function getConfirmText() {
    return t('Delete');
  }

  function getCancelUrl() {
    return new Url('entity.node_limit.list');
  }

  function buildForm(array $form, FormStateInterface $form_state) {
   
    $this->id = \Drupal::request()->get('node_limit'); 
    return parent::buildForm($form, $form_state);
  }

  function submitForm(array &$form, FormStateInterface $form_state) {
    $node_limit_id = \Drupal::request()->get('node_limit');
    if ($node_limit_id) {
      // Delete node limit related  values
      db_delete('node_limit_role')
          ->condition('lid', $node_limit_id)
          ->execute();
      db_delete('node_limit_type')
          ->condition('lid', $node_limit_id)
          ->execute();
      db_delete('node_limit_user')
          ->condition('lid', $node_limit_id)
          ->execute();
      db_delete('node_limit_userofrole')
          ->condition('lid', $node_limit_id)
          ->execute();
      db_delete('node_limit')
          ->condition('lid', $node_limit_id)
          ->execute();
    }
    drupal_set_message(t('Node limit %id has been deleted.', array('%id' => $node_limit_id)));
    $form_state->setRedirect('entity.node_limit.list');
  }

}
