<?php

namespace Drupal\gepsis\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\gepsis\Controller\GepsisOdataReadClass;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Create a new node entity from a webform submission.
 *
 * @WebformHandler(
 *   id = "Rendez-vous Form",
 *   label = @Translation("Message Meeting form"),
 *   category = @Translation("My webform handler"),
 *   description = @Translation("Message de changement de Rendez-vous Form"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class FormRDVHandler extends WebformHandlerBase {

    /**
     *
     * {@inheritdoc}
     */
    public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        // $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        $meetingOid = \Drupal::request()->get('idMeeting');
        $meeting = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_MEETINGS', "MEETING_O_ID eq " . $meetingOid, TRUE);

        $meetingPersBirthFormatedDate = date("d-m-Y", strtotime($meeting -> PERS_BIRTH_DATE));
        $meetingDateFormatedDate = date("d-m-Y", strtotime($meeting -> MEETING_DAY));
        $script_tz = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $meetingTimeFormatedDate = date("G:i", strtotime($meeting -> MEETING_HEURE));
        date_default_timezone_set($script_tz);
        $dateHeureMeeting = $meetingDateFormatedDate . ' à ' . $meetingTimeFormatedDate;

        // valeurs des champs de selection
        $form['elements']['rdv_modif_fieldset']['rdv_modif_subject']['#disabled'] = TRUE;
        // @formatter:off
    $form['elements']['rdv_modif_fieldset']['rdv_modif_subject']['#default_value'] = 'Concernant le rdv (' .
      $meeting -> TYPE_MEETING . ') du ' . $dateHeureMeeting . ' de ' . $meeting -> PERS_FIRST_NAME . ' ' .
      $meeting -> PERS_NAME . ', né(e) le ' . $meetingPersBirthFormatedDate;


    // Disable caching
    $form['#cache']['max-age'] = 0;
    // $form['#tree'] = TRUE; // When this is set to false, the submit method gets no results through getValues().
  }

  /**
   *
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    // $userInput = $form_state->getUserInput();
    return;
  }

  /**
   *
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    // $userInput = $form_state->getUserInput();
    return;
  }

}
