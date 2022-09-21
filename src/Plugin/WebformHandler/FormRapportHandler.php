<?php

namespace Drupal\gepsis\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\gepsis\Utility\InternalFunctions;
use Drupal\user\Entity\User;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Create a new node entity from a webform submission.
 *
 * @WebformHandler(
 *   id = "Rapport Jasper Form",
 *   label = @Translation("Rapport Jasper form"),
 *   category = @Translation("Rapport Jasper handler"),
 *   description = @Translation("Rapport Jasper Form"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class FormRapportHandler extends WebformHandlerBase {

	public function encodeURIComponent($str){
		$revert = array (
				'%21' => '!',
				'%2A' => '*',
				'%27' => "'",
				'%28' => '(',
				'%29' => ')'
		);
		return strtr(rawurlencode($str), $revert);
	}




	/**
	 *
	 * {@inheritdoc}
	 */
	public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission){
		$userDrupal = User::load(\Drupal::currentUser()->id());
		$adhCode = $userDrupal->get('field_active_adherent_code')->value;
		$formElements = InternalFunctions::getFlattenedForm($form['elements']);

		$url = "https://gportal.ocara.com/stats";
		// $user = 'portal_intern_user';
		$user = 'portal';
		$pass = 'sndcsndc';
		$role = 'OCARA_PORT';
		$organisation = 'organization_1';
		$expire = '202212312300';
		$resource = '/OCARA/PORTAIL/TBords/test_TB_suivi_adherent';
		// token config /opt/jasperreports-server-7.2.0/apache-tomcat/webapps/stats/WEB-INF/applicationContext-externalAuth-preAuth-mt.xml ligne 64
		$token = self::encodeURIComponent("u=" . $user . "|r=" . $role . "|o=" . $organisation . "|exp=" . $expire);
		dpm($token);
		// $key = "sndcsndc";
		// $enc =  encrypt($token,$key);
		dpm($token);
		$token = '0NS49O30FvbuOvN_bm9ysqXFss4LjQl2rxKClqjb5HB9ASnBEftwKLg734JzKBCr68lGL6r7-eOAFsahJF625hL3Kw==';

		_drupal_flush_css_js();
		$form['#attached']['library'][] = 'gepsis/includeJasperForm';
		$form['#attached']['drupalSettings']['gepsis'] = array (
				'url' => $url,
				'user' => $user,
				'pass' => $pass,
				'organisation' => $organisation,
				'resource' => $resource,
				'adhCode' => $adhCode,
				'token' => $token
		);


		$form['actions']['submit']['#limit_validation_errors'] = array();

		// $formElements['processed_rapport']['#prefix'] = '<div class="spinner">';
		$formElements['processed_rapport']['#suffix'] = '<span style="display:none" class="throbber">Loading...</span>';

		// Disable caching
		$form['#cache']['max-age'] = 0;
		// $form['#tree'] = TRUE; // When this is set to false, the submit method gets no results through getValues().
	}

	/**
	 *
	 * {@inheritdoc}
	 */
	public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission){
		// $userInput = $form_state->getUserInput();
	}

	/*
	 * try {
	 * $jasperClient = new Client($url, $user, $pass, $organisation);
	 * $info = $jasperClient->serverInfo();
	 * dpm($info);
	 *
	 * $criteria = new RepositorySearchCriteria();
	 * // $criteria -> q = "Liste des";
	 * $criteria->folderUri = '/OCARA/PORTAIL/Rapports';
	 * $results = $jasperClient->repositoryService()->searchResources($criteria);
	 * // $controls = $jasperClient->reportService()->getReportInputControls($resource);
	 *
	 * $reportLista = array ();
	 * foreach ( $results->items as $value ) {
	 * // $controls = $jasperClient->reportService()->getReportInputControls($value -> uri);
	 * $reportLista[] = array (
	 * 'uri' => $value->uri,
	 * 'label' => $value->label,
	 * 'description' => $value->label
	 * // 'controls' => $controls
	 * );
	 * }
	 *
	 * foreach ( $reportLista as $value ) {
	 * $stringSelectList[$value['uri']] = $value['label'];
	 * }
	 * $formElements['selectionner_un_rapport']['#options'] = $stringSelectList;
	 * } catch ( RESTRequestException $e ) {
	 * echo 'RESTRequestException:';
	 * echo 'Exception message: ', $e->getMessage(), "\n";
	 * echo 'Set parameters: ', $e->parameters, "\n";
	 * echo 'Expected status code:', implode($e->expectedStatusCodes), "\n";
	 * echo 'Error code: ', $e->errorCode, "\n";
	 * }
	 */
}
