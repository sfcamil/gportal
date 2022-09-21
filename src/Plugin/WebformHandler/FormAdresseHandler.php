<?php

namespace Drupal\gepsis\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\gepsis\Controller\GepsisOdataReadClass;
use Drupal\gepsis\Controller\GepsisOdataWriteClass;
use Drupal\gepsis\Utility\GetAllFunctions;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\gepsis\Utility\InternalFunctions;
use Drupal\user\Entity\User;
use odataPhp\EntAddress;

/**
 * Create a new node entity from a webform submission.
 *
 * @WebformHandler(
 *   id = "Modifier Adresse Form",
 *   label = @Translation("Modify address form"),
 *   category = @Translation("My webform handler"),
 *   description = @Translation("Modifier Adresse Form"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class FormAdresseHandler extends WebformHandlerBase {

	/**
	 *
	 * {@inheritdoc}
	 */
	public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission){
		// $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
		$formElements = InternalFunctions::getFlattenedForm($form['elements']);

		// valeurs des champs de selection
		$allTypesAdresses = GetAllFunctions::getAllTypesAdresses();
		$formElements['adrss_type_adresse']['#options'] = $allTypesAdresses;
		$formElements['adrss_country']['#options'] = GetAllFunctions::getAllCountries();
		$formElements['adrss_country']['#disabled'] = TRUE;
		$formElements['adrss_country']['#default_value'] = 21224; // force to France
		$formElements['adrss_type_voie']['#options'] = GetAllFunctions::getAllVoieTyp();
		$formElements['adrss_message_fieldset']['#access'] = FALSE;
		$addrSiegeSocial = array_search('Siège social', $allTypesAdresses);
		$addrExplitation = array_search('Adresse exploitation', $allTypesAdresses);

		$adresseOid = \Drupal::request()->get('idAdresse');
		if ($adresseOid) {
			$result = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_ADRESSES', "ADR_O_ID eq " . $adresseOid, 1);
			$complement = GepsisOdataWriteClass::getOdataClassValues('EntAddress', "id eq " . $adresseOid, 1);

			$formElements['adrss_type_adresse']['#default_value'] = $result->ADR_TYPE_O_ID;
			// $formElements['adrss_country']['#default_value'] = $complement->country;
			$formElements['adrss_city_et_code']['#default_value'] = $result->ADR_CITY_LABEL . ' - ' . $result->ADR_CITY_CODE;
			$formElements['adrss_no_adresse']['#default_value'] = $complement->VOIE_NO;
			$formElements['adrss_type_voie']['#default_value'] = array_search($complement->VOIE_TYP, GetAllFunctions::getAllVoieTyp());
			$formElements['adrss_nom_adresse']['#default_value'] = $complement->VOIE_NOM;
			$formElements['adrss_batiment']['#default_value'] = $complement->BATIMENT;
			$formElements['adrss_escalier']['#default_value'] = $complement->ESCALIER;
			$formElements['adrss_etage']['#default_value'] = $complement->ETAGE;
			$formElements['adrss_porte']['#default_value'] = $complement->PORTE;
			$formElements['adrss_complement_1']['#default_value'] = $complement->COMPL1;
			$formElements['adrss_complement_2']['#default_value'] = $complement->COMPL2;

			// hidden values
			$formElements['adrss_adresse_oid_details']['#default_value'] = $result->ADR_O_ID;
			$formElements['adrss_adh_code']['#default_value'] = $result->ADR_CODE;
			$formElements['adrss_city_et_code_value']['#default_value'] = $complement->city;

			if ($result->ADR_TYPE_O_ID == $addrSiegeSocial || $result->ADR_TYPE_O_ID == $addrExplitation) {
				$formElements['adrss_message_fieldset']['#access'] = TRUE;
				$formElements['adrss_type_adresse']['#disabled'] = TRUE;
				$formElements['adrss_city_et_code']['#disabled'] = TRUE;
				$formElements['adrss_no_adresse']['#disabled'] = TRUE;
				$formElements['adrss_type_voie']['#disabled'] = TRUE;
				$formElements['adrss_nom_adresse']['#disabled'] = TRUE;
				$formElements['adrss_batiment']['#disabled'] = TRUE;
				$formElements['adrss_escalier']['#disabled'] = TRUE;
				$formElements['adrss_etage']['#disabled'] = TRUE;
				$formElements['adrss_porte']['#disabled'] = TRUE;
				$formElements['adrss_complement_1']['#disabled'] = TRUE;
				$formElements['adrss_complement_2']['#disabled'] = TRUE;
			} else {
				$form['actions']['delete'] = array (
						'#type' => 'submit',
						'#value' => t(utf8_encode('Supprimer')),
						'#validate' => array (
								[
										$this,
										'gportal_adresse_validate_delete'
								]
						),
						'#submit' => array (
								[
										$this,
										'gportal_adresse_delete_delete'
								]
						),
						'#weight' => 100
				);
			}
		} else {
			$form['#title'] = $this->t('Nouvelle adresse');
			unset($formElements['adrss_type_adresse']['#options'][$addrSiegeSocial]);
			unset($formElements['adrss_type_adresse']['#options'][$addrExplitation]);
		}

		// autocomplete city field
		$form['#attached']['library'][] = 'gepsis/gepsis.replace-autocomplete';
		$formElements['adrss_city_et_code']['#attributes']['class'] = array (
				'replaceAutocomplete'
		);
		$formElements['adrss_city_et_code']['#autocomplete_route_name'] = 'gepsis.autocomplete';
		$formElements['adrss_city_et_code']['#autocomplete_route_parameters'] = array (
				'key' => 'CITY_O_ID',
				'class' => 'V1_ALL_CITYES',
				'searchValues' => array (
						'CITY_CODE',
						'CITY_LABEL'
				)
		);

		$formElements['adh_fire_email_entreprise']['#default_value'] = InternalFunctions::getEmailAssistante();
        $formElements['adrss_votre_message']['#title'] = $formElements['adrss_votre_message']['#title'] . ' sera envoyé à: '.InternalFunctions::getEmailAssistante();
		// Disable caching
		$form['#cache']['max-age'] = 0;
		// $form['#tree'] = TRUE; // When this is set to false, the submit method gets no results through getValues().
	}

	/**
	 *
	 * {@inheritdoc}
	 */
	public function gportal_adresse_validate_delete(&$form, FormStateInterface $form_state){
		$data = $form_state->getUserInput();
		$contact = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_ADHERENT_CONTACT', "ADR_O_ID eq " . $data['adrss_adresse_oid_details'], 1);

		if (empty($contact))
			return;
		else {
			$utilisateur = $contact->PERS_NAME . ' ' . $contact->PERS_FIRST_NAME;
			$form_state->setErrorByName('', t(utf8_encode('Adresse affect�e �: ' . $utilisateur . '. Vous ne pouvez pas l\'effacer !')));
		}
	}

	/**
	 *
	 * {@inheritdoc}
	 */
	public function gportal_adresse_delete_delete(&$form, FormStateInterface $form_state){
		$data = $form_state->getUserInput();

		$allTypesAdresses = GetAllFunctions::getAllTypesAdresses();
		$addrSiegeSocial = array_search(utf8_encode('Si�ge social'), $allTypesAdresses);
		$addrExplitation = array_search(utf8_encode('Adresse exploitation'), $allTypesAdresses);

		$tpAdr = $data['adrss_type_adresse'];
		if ($tpAdr != $addrSiegeSocial && $tpAdr != $addrExplitation) {
			$svc = GepsisOdataWriteClass::prepareWriteClass();
			$query = $svc->EntAddress()->filter("id eq " . $data['adrss_adresse_oid_details']);
			$svc->UsePostTunneling = FALSE;
			try {
				$customer = $query->Execute()->Result[0];
			} catch ( \Throwable $e ) {
				return;
			}
			InternalFunctions::setupTraceInfos($customer);
			$svc->DeleteObject($customer);
			$svc->SaveChanges();
		} else
			$form_state->setErrorByName('', $this->t(utf8_encode('Vous ne pouvais pas effacer cette adresse!')));
		return;
	}

	/**
	 *
	 * {@inheritdoc}
	 */
	public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission){
		// $userInput = $form_state->getUserInput();
	}

	/**
	 *
	 * {@inheritdoc}
	 */
	public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission){
		// $data = $form_state->getUserInput();
	}

	/**
	 *
	 * {@inheritdoc}
	 */

	// Function to be fired after submitting the Webform.
	public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE){
		$data = $webform_submission->getData();
		$user = User::load(\Drupal::currentUser()->id());
		$adherentOid = $user->get('field_active_adherent_oid')->value;

		$svc = GepsisOdataWriteClass::prepareWriteClass();
		if (!empty($data['adrss_adresse_oid_details'])) { // UPDATE
			$query = $svc->EntAddress()->filter("id eq " . $data['adrss_adresse_oid_details']);
			try {
				$customer = $query->Execute()->Result[0];
			} catch ( \Throwable $e ) {
				return;
			}
			self::setAdressValues($customer, $data);
			InternalFunctions::setupTraceInfos($customer);
			$svc->UpdateObject($customer);
		} else { // INSERT
			$customer = EntAddress::CreateEntAddress(null);
			self::setAdressValues($customer, $data);
			InternalFunctions::setupTraceInfos($customer);
			$customer->entreprise = $adherentOid;
			$svc->AddToEntAddress($customer);
		}
		$svc->SaveChanges();
	}

	private function setAdressValues($customer, $data){
		$customer->type = $data['adrss_type_adresse'];
		$customer->country = $data['adrss_country'];
		$customer->city = $data['adrss_city_et_code_value'];
		$customer->VOIE_NO = $data['adrss_no_adresse'];

		$vTyp = GetAllFunctions::getAllVoieTyp();
		$customer->VOIE_TYP = $vTyp[$data['adrss_type_voie']];
		$customer->VOIE_NOM = $data['adrss_nom_adresse'];

		$customer->BATIMENT = $data['adrss_batiment'];
		$customer->ESCALIER = $data['adrss_escalier'];
		$customer->ETAGE = $data['adrss_etage'];
		$customer->PORTE = $data['adrss_porte'];

		$customer->COMPL1 = $data['adrss_complement_1'];
		$customer->COMPL2 = $data['adrss_complement_2'];
	}
}
