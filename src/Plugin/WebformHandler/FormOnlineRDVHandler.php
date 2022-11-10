<?php

namespace Drupal\gepsis\Plugin\WebformHandler;

use DateTime;
use Drupal\Core\Form\FormStateInterface;
use Drupal\gepsis\Controller\GepsisOdataReadClass;
use Drupal\gepsis\Controller\GepsisOdataWriteClass;
use Drupal\gepsis\Utility\InternalFunctions;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use odataPhp\MeetingDetail;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Create a new node entity from a webform submission.
 *
 * @WebformHandler(
 *   id = "Online Rendez-vous Form",
 *   label = @Translation("Online Demande Meeting form"),
 *   category = @Translation("My webform handler"),
 *   description = @Translation("Online Demande Rendez-vous Form"),
 *   cardinality =
 *     \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *     results =
 *     \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *     submission =
 *     \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class FormOnlineRDVHandler extends WebformHandlerBase
{

    /**
     *
     * {@inheritdoc}
     */
    public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        $adherentOid = $user->get('field_active_adherent_oid')->value;
        $formElements = InternalFunctions::getFlattenedForm($form['elements']);

        $mtgId = \Drupal::request()->get('mtgId');
        $mtgDetId = \Drupal::request()->get('mtgDetId');
        $mtgDay = \Drupal::request()->get('mtgDay');
        $mtgTime = \Drupal::request()->get('mtgTime');

        /*
        if (!empty($mtgId) && !empty($mtgDetId)) {
            $formElements['mtgid_online_hidden']['#default_value'] = $mtgId;
            $formElements['mtgdetid_online_hidden']['#default_value'] = $mtgDetId;
            $formElements['mtgday_online_hidden']['#default_value'] = $mtgDay;
            $formElements['mtgtime_online_hidden']['#default_value'] = $mtgTime;
*/
        $travsLista = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_TRAVS', "ENTR_O_ID eq " . $adherentOid);
        $travs = [];
        foreach ($travsLista as $value) {
            $travs[$value->TRAV_O_ID] = array($value->PERS_NAME . ' ' . $value->PERS_FIRST_NAME);
        }

        $rdvLista = GepsisOdataReadClass::getOdataClassValues('V1_ALL_RLP_MEETINGS_AVAILABLE', "MEETING_DETAIL_ID eq " . 17794371100);
        // V1_ALL_RHP_MEETINGS_AVAILABLE

        $formElements['selectionner_un_salarie_online']['#options'] = $travs;
        $formElements['rendez_vous']['#options'] = $travs;
        $formElements['rendez_vous']['#multiple'] = FALSE;
        $formElements['rendez_vous']['#sticky'] = TRUE;
        $formElements['rendez_vous']['#type'] = 'table';
        //  }

        // Disable caching
        $form['#cache']['max-age'] = 0;
        // $form['#tree'] = TRUE; // When this is set to false, the submit method gets no results through getValues().
    }

    /**
     *
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        $userInput = $form_state->getUserInput();
        return;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        $userInput = $form_state->getUserInput();
        return;
    }

    // Function to be fired after submitting the Webform.
    public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
        $values = $webform_submission->getData();
        $svc = GepsisOdataWriteClass::prepareWriteClass();

        /*
        $query = $svc->MeetingDetail()->filter("id eq " . $values['mtgdetid_online_hidden']);
        try {
            $meeting = $query->Execute()->Result;
        } catch (\Throwable $e) {
            return;
        }
        */

        $customer = MeetingDetail::CreateMeetingDetail(null);
        $customer->id = $values['mtgdetid_online_hidden'];
        $customer->meeting = $values['mtgid_online_hidden'];
        $customer->trav = $values['selectionner_un_salarie_online'];
        $customer->comment = $values['commentaire_rv_online'];
        $dateMeeting = DateTime::createFromFormat('d-m-Y', $values['mtgday_online_hidden']);
        $customer->day = $dateMeeting->format('Y-m-d') . 'T00:00:00';
        $startTime = (int)str_replace(':', '', $values['mtgtime_online_hidden']);
        $customer->startTime = substr($startTime, 0, 4);
// $customer->commentWhenCanceling
        InternalFunctions::setupTraceInfos($customer);
        $svc->AddToMeetingDetail($customer);
        $svc->SaveChanges();

        return new RedirectResponse('/rendez-vous#lb-tabs-tabs-2');

    }

}
