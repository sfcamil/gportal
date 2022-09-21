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

		return;

		$result = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_INFO', "ENTR_O_ID eq " . $adherentOid, TRUE);

		$formElements['adh_code_entreprise']['#default_value'] = $result->ENTR_CODE;
		$formElements['adh_code_entreprise']['#disabled'] = TRUE;
		$formElements['adh_nom_entreprise']['#default_value'] = $result->ENTR_NOM;
		// $formElements['adh_nom_entreprise']['#disabled'] = TRUE;
		$formElements['adh_date_d_inscription_de_l_adherent']['#default_value'] = InternalFunctions::internalFormatDate($result->START_CTR_DATE);
		$formElements['adh_date_d_inscription_de_l_adherent']['#disabled'] = TRUE;
		$formElements['adh_description_entreprise']['#default_value'] = $result->ENTR_DESCR;
		$formElements['adh_siren_entreprise']['#default_value'] = $result->ENTR_SIREN;
		$formElements['adh_siret_entreprise']['#default_value'] = $result->ENTR_SIRET;
		$listaFullNaf = GetAllFunctions::getFinalListeFullNaf();
		$formElements['adh_full_naf_entreprise']['#options'] = $listaFullNaf;
		$formElements['adh_full_naf_entreprise']['#default_value'] = $result->ENTR_NAF_O_ID;
        $formElements['change_sit_message_service']['#title'] = $formElements['adh_votre_message_atext']['#title'] . ': ' . InternalFunctions::getEmailAssistante();

		// hidden values
		$formElements['adh_entreprise_oid_details']['#default_value'] = $result->ENTR_O_ID;
		$formElements['adh_code_adherent']['#default_value'] = $result->ENTR_CODE;
		$formElements['adh_fire_email_entreprise']['#default_value'] = InternalFunctions::getEmailAssistante();

		// Disable caching
		$form['#cache']['max-age'] = 0;
		// $form['#tree'] = TRUE; // When this is set to false, the submit method gets no results through getValues().
	}

	/**
	 *
	 * {@inheritdoc}
	 */
	public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission){
		$userInput = $form_state->getUserInput();
		if (!$userInput['adh_nom_entreprise'] || empty($userInput['adh_nom_entreprise'])) {
			$form_state->setErrorByName('adh_nom_entreprise', 'Raison sociale est obligatoire.');
		}
		return;

		if ($value = $form_state->getValue('element')) {
			$form_state->setErrorByName('element', $this->t('The element must be empty. You entered %value.', [
					'%value' => $value
			]));
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission){
		// $values = $webform_submission->getData();

		// $test =array_keys(DiffArray::diffAssocRecursive($node->toArray(), $node->original->toArray()));
		return;
	}

	/**
	 *
	 * {@inheritdoc}
	 */

	// Function to be fired before submitting the Webform.
	public function preSave(WebformSubmissionInterface $webform_submission, $update = TRUE){
		// $values = $webform_submission->getData();
		// $node = $this -> nodeStorage;
		return;

		$source = $webform_submission->getSourceEntity();
		$nid = $source->id();
		kint($nid);
	}

	/**
	 *
	 * {@inheritdoc}
	 */

	// Function to be fired after submitting the Webform.
	public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE){
		$values = $webform_submission->getData();

		$svc = GepsisOdataWriteClass::prepareWriteClass();
		$query = $svc->Entreprise()->filter("id eq " . $values['adh_entreprise_oid_details']);
		try {
			$result = $query->Execute()->Result[0];
		} catch ( \Throwable $e ) {
			return;
		}

		$now = (new \DateTime())->format('d-m-Y H:i');
		// $result -> enseigneCommercial = htmlspecialchars($values['adh_description_entreprise']);
		$result->enseigneCommercial = htmlspecialchars($values['adh_description_entreprise'] . ' (' . $now . ')');
		$result->siren = htmlspecialchars($values['adh_siren_entreprise']);
		$result->siret = htmlspecialchars($values['adh_siret_entreprise']);
		$result->naf = $values['adh_full_naf_entreprise']['select'];
		InternalFunctions::setupTraceInfos($result);
		$svc->UpdateObject($result);
		$svc->SaveChanges();
	}
}
