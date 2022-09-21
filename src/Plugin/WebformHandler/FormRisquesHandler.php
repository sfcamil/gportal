<?php

namespace Drupal\gepsis\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\gepsis\Controller\GepsisOdataReadClass;
use Drupal\gepsis\Controller\GepsisOdataWriteClass;
use Drupal\gepsis\Utility\InternalFunctions;
use Drupal\user\Entity\User;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\gepsis\Utility\GetAllFunctions;

/**
 * Create a new node entity from a webform submission.
 *
 * @WebformHandler(
 *   id = "Risques Form",
 *   label = @Translation("Risques Form"),
 *   category = @Translation("Gepsis webform handler"),
 *   description = @Translation("Prepopulate risques form"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class FormRisquesHandler extends WebformHandlerBase {

    /**
     *
     * {@inheritdoc}
     */
    public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        $form['#disable_inline_form_errors'] = TRUE;
        $user = User::load(\Drupal::currentUser()->id());

        // $adhOid = $user->get('field_active_adherent_oid') -> value;
        $idTrav = \Drupal::routeMatch()->getParameters()->get('travOid');
        if(empty($idTrav))
            $idTrav = \Drupal::request()->get('travOid');

        $formElements = InternalFunctions::getFlattenedForm($form['elements']);

        $travDecret = GepsisOdataWriteClass::getOdataClassValues('TravDecret2017', "id eq " . $idTrav, 1);
        if(empty($travDecret))
            return;

        $formElements['risques_contrat_type_category_risques_declares']['#default_value'] = $travDecret -> travEmploymentTypeCategory;

        $allDecretCriteria = GetAllFunctions::getAllDecretCriteria();
        $formElements['risques_risques']['#options'] = $allDecretCriteria;
        $formElements['risques_risques']['#default_value'] = explode(',', $travDecret -> categories);

        $form['#attached']['library'][] = 'gepsis/calculPeriodicite';
        $form['#attached']['drupalSettings']['gepsis']['age'] = InternalFunctions::calculateAge($travDecret -> birthDate);
        $form['#attached']['drupalSettings']['gepsis']['contrat_type'] = $travDecret -> travEmploymentTypeCategory;
        $form['#attached']['drupalSettings']['gepsis']['type'] = 'details';

        // hidden elements
        $formElements['risques_trav']['#default_value'] = $idTrav;
        $formElements['risques_fire_email']['#default_value'] = InternalFunctions::getEmailAssistante();
        $formElements['risques_adh_code']['#default_value'] = $user->get('field_active_adherent_code') -> value;
        $viewDetailPerson = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_TRAVS', "TRAV_O_ID eq " . $idTrav, 1);
        $dateNaiss = InternalFunctions::internalFormatDate($viewDetailPerson -> PERS_BIRTH_DATE);
        $formElements['risques_persnomprenom']['#default_value'] = $viewDetailPerson -> PERS_NAME . ' ' . $viewDetailPerson -> PERS_FIRST_NAME . ' ' . $viewDetailPerson -> PERS_MARITAL_NAME . ' (' . $dateNaiss . ')';

        // $form_state['person'] = $trav -> person;
        // dpm($formElements);

        // Disable caching
        $form['#cache']['max-age'] = 0;
        // $form['#tree'] = TRUE; // When this is set to false, the submit method gets no results through getValues().

        return;
        // dpm($form['submitted']);
        // dpm($form_state['initialValues']);
    }

    /**
     *
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    }

    /**
     *
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    }

    /**
     *
     * {@inheritdoc}
     */

    // Function to be fired after submitting the Webform.
    public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
        $values = $webform_submission->getData();

        $svc = GepsisOdataWriteClass::prepareWriteClass();
        $query = $svc->TravDecret2017()->filter("id eq " . $values['risques_trav']);
        try {
            $travRisques = $query->Execute() -> Result[0];
        } catch ( \Throwable $e ) {
            return;
        }

        $i = 0;
        $allRisques = null;
        foreach($values['risques_risques'] as $value) {
            if(!empty($value)) {
                if($i == 0)
                    $allRisques = $value;
                    else
                        $allRisques = $allRisques . ',' . $value;
            }
            $i++;
        }

        $travRisques -> categories = $allRisques;
        InternalFunctions::setupTraceInfos($travRisques);
        $svc->UpdateObject($travRisques);
        $svc->SaveChanges();
    }
}
