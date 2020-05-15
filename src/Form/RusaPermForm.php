<?php

/**
 * @file
 *  RusaPermForm.php
 *
 * @Created 
 *  2020-05-14 - Paul Lieberman
 *
 * Provide a form for selecting permanents
 * Possibly as part of a Perm registration system
 *
 * ----------------------------------------------------------------------------------------
 * 
 */

namespace Drupal\rusa_perm_select\Form;

use Drupal\rusa_api\RusaPermanents;
use Drupal\rusa_api\RusaStates;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * RusaPermForm
 *
 * This is the Drupal Perm class.
 * All of the form handling is within this class.
 *
 */
class RusaPermForm extends FormBase {

  // Instance variables

  // We have other classes to hold the data from the backend tables
  protected $stateobj;  // State object
  protected $permobj;   // Event object

  /**
   * @getFormID
   *
   * Required
   *
   */
  public function getFormId() {
    return 'rusa_perm_select_form';
  }

  /**
   * @Constructor
   *
   * Initialize our region data before we do anything else
   */
  public function __construct(){

    // Get the states from the database
    $this->stateobj = new RusaStates();
  } 


  /**
   * @buildForm
   *
   * Required
   *
   * This is the Drupal form builder. It is the heart of the whole thing.
   *
   * We are implimenting a  multistep form. This is probably a little different than most Drupal forms. 
   * The submit handler just sets the rebuild flag and returns control to the build function.
   * We keep tracdk of the form state and branch accordingly.
   * Most of the code to actually define the form fields is in subroutines.
   * This function mostly adds the structure for multistep form handling.
   *
   */
  public function buildForm(array $form, FormStateInterface $form_state) {   

    $this->display_states($form);

    // Attach the Javascript and CSS, defined in rusa_perm_select.libraries.yml.
    $form['#attached']['library'][] = 'rusa_api/chosen';
    $form['#attached']['library'][] = 'rusa_api/rusa_script';
    $form['#attached']['library'][] = 'rusa_api/rusa_style';

    return $form;
  }

 /**
   * @validateForm
   *
   * Required
   *
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $action     = $form_state->getTriggeringElement();

   } // End function verify


  /**
   * @submitForm
   *
   * Required
   *
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
      // Set the rebuild flag and go on to the next state
      $form_state->setRebuild();
  }

  //  Private functions
  // ------------------------------------------------------------

  /**
   * Display regions, RBA id, and Club id form inputs
   *
   */
  private function display_states(&$form) {
    $options = [];

    $states = $this->stateobj->getStates();

    // Some instructions at the top
    $form['instruct'] = [
      '#type'    => 'item',
      '#markup'  => $this->t("<p>Select the state where you want to ride.<p>"), 
    ];

    // Build a select list of regions
    $form['states'] = [
      '#type'    => 'select',
      '#title'   => $this->t('Select States'),
      '#options' => $states,
    ];

    // Actions wrapper
    $form['actions'] = [
      '#type' => 'actions'
    ];

    // Default submit button 
    $form['actions']['submit'] = [
      '#type'   => 'submit',
      '#value'  => $this->t('Find permanents'),
    ];
  }


/* ------------------------------ Route selection -------------------------------------------- */
  /**
   * display_events
   *
   * This is the main table of events and routes.
   * 
   * @param $edit  Boolean
   *
   * We passased in an $edit flag to tell us to display it
   * with form fileds, or read only for confirmation.
   *
   */
  private function display_perms(&$form, $edit) {

    // $perms = $this->permobj->getPermanents();    
  }
  
} // End of class  
