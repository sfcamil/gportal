<?php

namespace Drupal\gepsis\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\gepsis\Controller\GepsisOdataReadClass;
use Drupal\gepsis\Controller\GepsisOdataWriteClass;
use Drupal\gepsis\Utility\GetAllFunctions;
use Drupal\gepsis\Utility\InternalFunctions;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Create a new node entity from a webform submission.
 *
 * @WebformHandler(
 *   id = "Pre-Fill changement situation Form",
 *   label = @Translation("Pre-Fill changement situation Form"),
 *   category = @Translation("Gepsis webform handler"),
 *   description = @Translation("Pre-Fill changement situation Form avec data"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class FormChangeSituationHandler extends WebformHandlerBase {

	/**
	 *
	 * {@inheritdoc}
	 */
	public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission){
		$user = User::load(\Drupal::currentUser()->id());
		$adherentOid = $user->get('field_active_adherent_oid')->value;
		$adherentCode = $user->get('field_active_adherent_code')->value;
		$adherentName = $user->get('field_active_adherent_name')->value;
		$formElements = InternalFunctions::getFlattenedForm($form['elements']);
        $formElements['change_sit_message_service']['#title'] = $formElements['change_sit_message_service']['#title'] . ': ' . InternalFunctions::getEmailAssistante();

		$formElements['change_sit_titre1']['#text'] = '<table align="center" border="0" cellpadding="10" cellspacing="1" style="width:100%"><tbody><tr><td class="rtecenter" style="background-color:rgb(153, 153, 153)"><span style="font-family:arial,helvetica,sans-serif"><span style="color:#FFFFFF"><span style="font-size:15px">Merci de nous signaler un changement de situation concernant ' . $adherentCode . ' : ' . $adherentName . '</span></span></span></td> </tr></tbody></table><p class="rtecenter"> </p>';

        $formElements['adrss_fire_email_change_sit']['#default_value'] = InternalFunctions::getEmailAssistante();

		// Disable caching
		$form['#cache']['max-age'] = 0;
		// $form['#tree'] = TRUE; // When this is set to false, the submit method gets no results through getValues().
	}

	/**
	 *
	 * {@inheritdoc}
	 */
	public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission){

	}

	/**
	 *
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission){

	}

	/**
	 *
	 * {@inheritdoc}
	 */

	// Function to be fired before submitting the Webform.
	public function preSave(WebformSubmissionInterface $webform_submission, $update = TRUE){

	}

	/**
	 *
	 * {@inheritdoc}
	 */

	// Function to be fired after submitting the Webform.
	public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE){
        return new RedirectResponse('/adherent#lb-tabs-tabs-4');
	}
}
