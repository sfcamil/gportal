<?php

namespace Drupal\gepsis\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\gepsis\Controller\GepsisOdataReadClass;
use Drupal\gepsis\Utility\InternalFunctions;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Create a new node entity from a webform submission.
 *
 * @WebformHandler(
 *   id = "Demande Rendez-vous Form",
 *   label = @Translation("Message Demande Meeting form"),
 *   category = @Translation("My webform handler"),
 *   description = @Translation("Message Demande Rendez-vous Form"),
 *   cardinality =
 *     \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *     results =
 *     \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *     submission =
 *     \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class FormDemandeRDVHandler extends WebformHandlerBase {

    /**
     *
     * {@inheritdoc}
     */
    public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        $adherentOid = $user->get('field_active_adherent_oid')->value;
        $formElements = InternalFunctions::getFlattenedForm($form['elements']);

        $travOid = \Drupal::routeMatch()->getParameters()->get('travOid');
        if (empty($travOid)) {
            $formElements['selectionner_un_salarie']["#required"] = TRUE;
            $formElements['selectionner_un_salarie']['#access'] = TRUE;
            $travsLista = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_TRAVS', "ENTR_O_ID eq " . $adherentOid);
            $travs = [];
            foreach ($travsLista as $value) {
                $travs[$value->TRAV_O_ID] = $value->PERS_NAME . ' ' . $value->PERS_FIRST_NAME;
            }
            $formElements['selectionner_un_salarie']['#options'] = $travs;

            $formElements['selectionner_un_salarie']['#ajax'] = [
                'callback' => [$this, 'rdvAjaxCallback'],
                'disable-refocus' => FALSE,
                // Or TRUE to prevent re-focusing on the triggering element.
                'event' => 'change',
                'wrapper' => 'edit-output',
                'progress' => [
                    'type' => 'throbber',
                ],
            ];
            $formElements['rdv_subject_reprise']['#prefix'] = '<div id="edit-output">';
            $formElements['rdv_subject_reprise']['#suffix'] = '</div>';
            // $formElements['rdv_subject_reprise']['#suffix'] = '<div id="edit-output"></div>';
        } else {
            $formElements['selectionner_un_salarie']["#required"] = FALSE;
            $formElements['selectionner_un_salarie']['#access'] = FALSE;
            $travOid = \Drupal::request()->get('travOid');
        }

        if (!empty($travOid)) {
            $viewDetailPerson = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_TRAVS', "TRAV_O_ID eq " . $travOid, TRUE);
            if (!empty($viewDetailPerson)) {
                $bDate = \Drupal::service('date.formatter')
                    ->format(strtotime($viewDetailPerson->PERS_BIRTH_DATE), 'html_date');
                $formElements['rdv_subject_reprise']['#disabled'] = TRUE;
                // @formatter:off
                $formElements['rdv_subject_reprise']['#default_value'] =
                    'Je souhaite un rendez-vous pour le salarié ' . $viewDetailPerson->PERS_NAME . ' ' . $viewDetailPerson->PERS_FIRST_NAME .
                    ' (' . $viewDetailPerson->PERS_MARITAL_NAME . ')  né le ' . $bDate . ' en contrat ' .
                    $viewDetailPerson->TRAV_EMPL_TYPE_CODE . ' (' . $viewDetailPerson->TRAV_EMPL_TYPE_LABEL . ') en suivi de santé ' .
                    $viewDetailPerson->EXAM_TYPE_LABEL . '.';
                // @formatter:on
            }

            $viewEntr = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_INFO', "ENTR_O_ID eq " . $viewDetailPerson->ENTR_O_ID, TRUE);
            $formElements['rdv_adh_code']['#default_value'] = $viewEntr->ENTR_CODE . ' - ' . $viewEntr->ENTR_NOM;

            // hidden field email assitente also send to adherent himself
            $formElements['rdv_fire_email_adresse']['#value'] = InternalFunctions::getEmailAssistante() . ',' . $user->getEmail();

            // icone help
            // $link_help = file_create_url('content/aide-demande-visite-de-reprise');
            // $form['submitted']['demande_de_visite_de_reprise']['comment_rdv']['#prefix'] = '<div class="webform-container-inline">';
            // $form['submitted']['demande_de_visite_de_reprise']['comment_rdv']['#suffix'] = '<a class="external-help" href="' . $link_help . '" onclick="window.open(this.href); return false;" onkeypress="window.open(this.href); return false;">&nbsp</a></div>';
        }
        $formElements['rdv_message_reprise']['#title'] = $formElements['rdv_message_reprise']['#title'] . ' sera envoyé à: '.InternalFunctions::getEmailAssistante();
        // Disable caching

        $form['#cache']['max-age'] = 0;
        // $form['#tree'] = TRUE; // When this is set to false, the submit method gets no results through getValues().
    }

    public function rdvAjaxCallback(array &$form, FormStateInterface $form_state) {
        if ($selectedValue = $form_state->getValue('selectionner_un_salarie')) {
            $viewDetailPerson = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_TRAVS', "TRAV_O_ID eq " . $selectedValue, TRUE);
            if (!empty($viewDetailPerson)) {
                $bDate = \Drupal::service('date.formatter')
                    ->format(strtotime($viewDetailPerson->PERS_BIRTH_DATE), 'html_date');
                // @formatter:off
                $form['elements']['rdv_demande_visite_reprise']['rdv_subject_reprise']['#value'] =
                    'Je souhaite un rendez-vous pour le salarié ' . $viewDetailPerson->PERS_NAME . ' ' . $viewDetailPerson->PERS_FIRST_NAME .
                    ' (' . $viewDetailPerson->PERS_MARITAL_NAME . ')  né le ' . $bDate . ' en contrat ' .
                    $viewDetailPerson->TRAV_EMPL_TYPE_CODE . ' (' . $viewDetailPerson->TRAV_EMPL_TYPE_LABEL . ') en suivi de santé ' .
                    $viewDetailPerson->EXAM_TYPE_LABEL . '.';
                // @formatter:on
            }
        }
        return $form['elements']['rdv_demande_visite_reprise']['rdv_subject_reprise'];
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

}
