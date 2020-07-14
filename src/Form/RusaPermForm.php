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
use Drupal\rusa_perm_reg\RusaPermReg;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * RusaPermForm
 *
 * This is the Drupal Perm class.
 * All of the form handling is within this class.
 *
 */
class RusaPermForm extends ConfirmFormBase {

    // Instance variables
    protected $step;
    protected $uinfo;
    protected $perms;
    protected $pid; 
    
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
    public function __construct(AccountProxy $current_user) {
    
        // Don't continue unless user has valid program registration
        if (! RusaPermReg::progRegIsValid($current_user->id() )) {
            $this->messenger()->addWarning($this->t("You are not registered for the perm program."));
            return $this->redirect('rusa.home');                                
        }
    
        $this->uinfo = $this->get_user_info($current_user);
        $this->step = 'search';
    } 


    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('current_user'),
        );
    }

   /**
    * {@inheritdoc}
    */
    public function getCancelUrl() {        
        return new Url('rusa_perm_select.form');
    }

    /**
    * {@inheritdoc}
    */
    public function getQuestion() {       
        return $this->t("Is this the perm you want to ride?");
    }
    
    /**
    * {@inheritdoc}
    */
    public function getDescription() {
        return $this->t("Please confirm your selection.");
    }
    

    /**
     * @buildForm
     *
     * Required
     *
     */
    public function buildForm(array $form, FormStateInterface $form_state) { 
    
        // PID can be passed as a query parameter
        $pid =  \Drupal::request()->query->get('pid');
        if (!empty($pid)) {
            $this->pid  = $pid;
            $this->step = 'confirm';
        }
        
        /**
         * Search form
         *
         */
        if ($this->step === 'search') {            
        
            // Get the states from the database
            $stateobj = new RusaStates();
            $states   = $stateobj->getStates(3);
            
            $form['state'] = [
                '#type'     => 'select',
                '#title'    => $this->t('Starting location'),
                '#options'  => $states,
                '#required' => TRUE,
            ];

            // Distance select
            $form['dist'] = [
                '#type'     => 'select',
                '#title'    => $this->t('Distance'),
                '#options'  => [
                    '0' => 'All distances',
                    '100' => '100-199 km', 
                    '200' => '200-299 km', 
                    '300' => '300-399 km', 
                    '400' => '400-499 km'],
            ];

            // Name
            $form['name'] = [
                '#type'     => 'textfield',
                '#title'    => $this->t('Name includes'),
                '#size'     => '40',
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
        
        /**
         * Select form
         *
         */
        elseif ($this->step === 'select') {
                
            // build a table of perms 
            foreach ($this->perms as $pid => $perm) {
                $row = [
                    $perm->pid,
                    $perm->startstate . ': ' . $perm->startcity,
                    $perm->dist,
                    $perm->climbing,
                    $perm->name,
                    $perm->statelist,
                ];
                
                $links['select'] = [
				    'title' => $this->t('Ride this'),
				    'url'  => Url::fromRoute('rusa_perm_select.form', ['pid' => $perm->pid]),
			    ];
            
			    // Add operations links
			    $row[] = [ 
				    'data' => [
					    '#type' => 'operations', 
					    '#links' => $links,
			        ],
			    ];
                
                $rows[] = $row;
            }
            
            $form['select'] = [
                '#type'     => 'table',
			    '#header'   => ['Route #', 'Location', 'Km', 'Climb (ft.)', 'Name', 'States'],
			    '#rows'     => $rows,
			    '#responsive' => TRUE,
			    '#attributes' => ['class' => ['rusa-table']],
			];
           
        }
        
        // Confirmation step
        elseif ($this->step === 'confirm') {
            $form = parent::buildForm($form, $form_state);
            
            // Display the selected perm
            $form['perm'] = $this->get_perm($this->pid);
                        
            $form['remember'] = [
                '#type'   => 'item',
                '#markup' => $this->t('Remember the Route #, you’ll need to re-enter it on the waiver.'),
            ];
            
            $form['actions']['submit']['#value'] = $this->t('Sign the waiver');            
        }
        

        // Set class and attach the Javascript and CSS
        $form['#attributes']['class'][] = 'rusa-form';
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
    
      
    } // End function verify


  /**
   * @submitForm
   *
   * Required
   *
   */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $action = $form_state->getTriggeringElement();
        $values = $form_state->getValues();
        
        // Don't submit the form until after confirmation
        if ($this->step === 'select') {
            $form_state->setRebuild();
            $this->step = 'confirm';
            return;
        }
        elseif ($this->step == 'search') {
        
            $params = [];
            // State is the only valid query param we can use with the gdbm2json gateway.
            if (! empty($values['state'])) {
                $params = ['key' => 'startstate', 'val' => $values['state'] ];
            }
        
            // Get all active perms
            $permobj = new RusaPermanents($params);            
            
            // Now set the filters
            $filters['active'] = TRUE; // Only active perms
            $filters['nosr']   = TRUE; // No SR600s
            
            if ( ! empty($values['dist'])) {
                $filters['dist'] = $values['dist'];
            }
            if ( ! empty($values['name'])) {
                $filters['name'] = $values['name'];
            }
            
            // Now get the perms
            $this->perms = $permobj->getPermanentsQuery($filters);

            $this->step = 'select';
            $form_state->setRebuild();
            return;
        }
        elseif ($this->step === 'confirm') {
            // Route has been selected redirect to SmartWaiver
            $url = $this->smartwaiver_url($this->pid);
            $response = new TrustedRedirectResponse($url);
            $form_state->setResponse($response);        
            
        }               
    }
  
  
  
    /* Private Functions */ 

    
    /** 
     * Get user info
     *
     */
    protected function get_user_info($current_user) {
        $user_id   = $current_user->id(); 
        $user      = User::load($user_id);

        $uinfo['uid']   = $user_id;
        $uinfo['name']  = $user->get('field_display_name')->getValue()[0]['value'];
        $uinfo['fname'] = $user->get('field_first_name')->getValue()[0]['value'];
        $uinfo['lname'] = $user->get('field_last_name')->getValue()[0]['value'];
        $uinfo['dob']   = str_replace('-', '', $user->get('field_date_of_birth')->getValue()[0]['value']);
        $uinfo['mid']   = $user->get('field_rusa_member_id')->getValue()[0]['value'];
        return($uinfo);
    }

   
    /**
     * Generate Smartwaiver URl
     *
     */
    protected function smartwaiver_url($pid) { 
        // Get URL from settings
        $swurl = RusaPermReg::getSwUrl();        
        $swurl .= '?wautofill_firstname='   . $this->uinfo['fname'];
        $swurl .= '&wautofill_lastname='    . $this->uinfo['lname'];
        $swurl .= '&wautofill_dobyyyymmdd=' . $this->uinfo['dob'];
        $swurl .= '&wautofill_tag='         . $this->uinfo['mid'] . ':' . $pid;

        return $swurl;
        
    }
    
    /**
	 * Get a table of current registrations
     *
     */
     protected function get_perm($pid) {        
		// We have to go back and get it again
		$permobj = new RusaPermanents(['key' => 'pid', 'val' => $pid]);
		$perm    = $permobj->getPermanent($pid);
		
		$row = [
            'pid'       => $pid,
            'pname'     => $perm->name,
            'pdist'     => $perm->dist, 
            'pclimb'    => $perm->climbing, 
            'pdesc'     => $perm->description,
        ];
		
		return [
			'#type'    => 'table',
			'#header'   => ['Route #', 'Name', 'Km', 'Climb (ft.)', 'Description' ],
			'#rows'     => [$row],
			'#responsive' => TRUE,
			'#attributes' => ['class' => ['rusa-table']],
		];
	}
    
} // End of class  
