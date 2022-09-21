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
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Create a new node entity from a webform submission.
 *
 * @WebformHandler(
 *   id = "Contrat Form",
 *   label = @Translation("Contrat Form"),
 *   category = @Translation("Gepsis webform handler"),
 *   description = @Translation("Prepopulate contrat form"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class FormContratHandler extends WebformHandlerBase {

    /**
     *
     * {@inheritdoc}
     */
    public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        $form['#disable_inline_form_errors'] = TRUE;
        $user = User::load(\Drupal::currentUser()->id());

        $adhOid = $user->get('field_active_adherent_oid') -> value;
        $idTrav = \Drupal::routeMatch()->getParameters()->get('travOid');
        if(empty($idTrav))
            $idTrav = \Drupal::request()->get('travOid');

        $formElements = InternalFunctions::getFlattenedForm($form['elements']);

        $travDecret = GepsisOdataWriteClass::getOdataClassValues('TravDecret2017', "id eq " . $idTrav, 1);
        if(empty($travDecret))
            return;

        $trav = GepsisOdataWriteClass::getOdataClassValues('Trav', "trav eq " . $idTrav, 1);
        if(empty($trav))
            return;

        $formElements['contrat_fonction_occupe']['#default_value'] = $trav -> function;
        $formElements['contrat_type_category_risques_declares']['#default_value'] = $travDecret -> travEmploymentTypeCategory;

        if($travDecret -> editable == 'CAN_BE_CHANGED')
            unset($formElements['contrat_message_fieldset']);

        // trav fieldset
        $allContratsLabels = GetAllFunctions::getAllContratsLabel();
        $formElements['contrat_type']['#options'] = $allContratsLabels;
        $formElements['contrat_type']['#default_value'] = $travDecret -> travEmploymentType;

        if(isset($travDecret -> travStartDate) && !empty($travDecret -> travStartDate)) {
            $formElements['contrat_date_debut']['#default_value'] = InternalFunctions::internalFormatDate($travDecret -> travStartDate);
            $formElements['contrat_date_debut_hidden']['#default_value'] = InternalFunctions::internalFormatDate($travDecret -> travStartDate);
        }

        if(isset($travDecret -> travEndDate) && !empty($travDecret -> travEndDate)) {
            $formElements['contrat_date_fin']['#default_value'] = InternalFunctions::internalFormatDate($travDecret -> travEndDate);
            $formElements['contrat_date_fin_hidden']['#default_value'] = InternalFunctions::internalFormatDate($travDecret -> travEndDate);
        }

        // get mettings
        $meetings = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_MEETINGS', "PERS_O_ID eq " . $trav -> person . " and ENTR_O_ID eq " . $adhOid);
        if(!empty($meetings)) {
            foreach($meetings as $value) {
                $dateJour = strtotime(date("Y-m-d") . 'T00:00:00');
                $meetingDay = strtotime($value -> MEETING_DAY . ':00');
                $meetingHeure = strtotime($value -> MEETING_HEURE);
                if($meetingDay > $dateJour) {
                    $thisMeeting = date('d-m-Y', $meetingDay) . ' à ' . date('h:i', $meetingHeure);
                    $formElements['contrat_rendez_vous']['#default_value'] = $formElements['contrat_rendez_vous']['#default_value'] . "\r\n" . $thisMeeting;
                    $formElements['meetings_dates'][] = $meetingDay;
                }
            }
            $formElements['contrat_persnomprenom']['#default_value'] = $meetings[0] -> PERS_NAME . ' ' . $meetings[0] -> PERS_FIRST_NAME;
        }

        // hidden elements
        $formElements['contrat_trav']['#value'] = $idTrav;
        $formElements['contrat_fire_email']['#default_value'] = InternalFunctions::getEmailAssistante();
        $formElements['contrat_adh_code']['#default_value'] = $user->get('field_active_adherent_code') -> value;
        $viewDetailPerson = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_TRAVS', "TRAV_O_ID eq " . $idTrav, 1);
        $dateNaiss = InternalFunctions::internalFormatDate($viewDetailPerson -> PERS_BIRTH_DATE);
        $formElements['contrat_persnomprenom']['#default_value'] = $viewDetailPerson -> PERS_NAME . ' ' . $viewDetailPerson -> PERS_FIRST_NAME . ' ' . $viewDetailPerson -> PERS_MARITAL_NAME . ' (' . $dateNaiss . ')';

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
        return;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        $values = $webform_submission->getData();
        if(!empty($values['contrat_date_fin']) && $values['contrat_date_fin'] < $values['contrat_date_debut']) {
            $form_state->setErrorByName('contrat_date_fin', 'La date de départ prévisionnelle doit être plus grande ou égale à la date d\'embauche.');
            /*
             $current_path = \Drupal::service('path.current')->getPath();
             $alias = \Drupal::service('path_alias.manager')->getAliasByPath($current_path);
             $current_route_name = \Drupal::service('current_route_match')->getRouteName();
             $url = Url::fromRoute('route.path');
             $form_state->setRedirect($current_path . '#lb-tabs-tabs-2');
             */
        }
        return;
    }

    /**
     *
     * {@inheritdoc}
     */

    // Function to be fired after submitting the Webform.
    public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
        $values = $webform_submission->getData();
        // $entity = $webform_submission->getSourceEntity();
        // $this -> get

        $svc = GepsisOdataWriteClass::prepareWriteClass();
        $query = $svc->Trav()->filter("trav eq " . $values['contrat_trav']);
        try {
            $travContrat = $query->Execute() -> Result[0];
        } catch ( \Throwable $e ) {
            return;
        }
        $travContrat -> function = htmlspecialchars($values['contrat_fonction_occupe']);
        $travContrat -> travEmploymentType = $values['contrat_type'];

        /*
         if(!empty($values['contrat_date_debut']))
         $travContrat -> travStartDate = $values['contrat_date_debut'] . 'T00:00:00';
         if(!empty($values['contrat_date_fin']))
         $travContrat -> travEndDate = $values['contrat_date_fin'] . 'T00:00:00';
         */

        $travContrat -> travStartDate = $values['contrat_date_debut'] ? $values['contrat_date_debut'] . 'T00:00:00' : '';
        $travContrat -> travEndDate = $values['contrat_date_fin'] ? $values['contrat_date_fin'] . 'T00:00:00' : '';

        // $travDecret -> travEmploymentType = $data['contrat_details_fieldset_risques_declares']['employement_layout_risques_declares']['employement_type_risques_declares'];
        // $travDecret -> travEmploymentTypeCategory = $form_state['values']['submitted']['type_de_contrat_category_risques_declares'];

        InternalFunctions::setupTraceInfos($travContrat);
        $svc->UpdateObject($travContrat);
        $svc->SaveChanges();
        return new RedirectResponse('v1-entr-travs');
    }
}
