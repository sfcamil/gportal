<?php

namespace Drupal\gepsis\Plugin\WebformHandler;

define('inactivatePoste', TRUE);

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use odataPhp\PosteTrav;
use Drupal\gepsis\Controller\GepsisOdataReadClass;
use Drupal\gepsis\Controller\GepsisOdataWriteClass;
use Drupal\gepsis\Utility\GetAllFunctions;
use Drupal\gepsis\Utility\InternalFunctions;
use Drupal\user\Entity\User;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Create a new node entity from a webform submission.
 *
 * @WebformHandler(
 *   id = "Poste Form",
 *   label = @Translation("Poste Form"),
 *   category = @Translation("Gepsis webform handler"),
 *   description = @Translation("Prepopulate poste form"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class FormPosteHandler extends WebformHandlerBase
{

    /**
     *
     * {@inheritdoc}
     */
    public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        $user = User::load(\Drupal::currentUser()->id());
        $form['elements']['poste_adh_code']['#value'] = $user->get('field_active_adherent_code')->value;
        $adhOid = $user->get('field_active_adherent_oid')->value;
        $idTrav = \Drupal::routeMatch()->getParameters()->get('travOid');
        if (empty($idTrav))
            $idTrav = \Drupal::request()->get('travOid');

        $idPoste = \Drupal::request()->get('idPoste');
        $formElements = InternalFunctions::getFlattenedForm($form['elements']);
        $formElements['poste_trav_id']['#default_value'] = $idTrav;

        if (isset($idTrav)) {
            $viewDetailPerson = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_TRAVS', "TRAV_O_ID eq " . $idTrav . ' and ENTR_O_ID eq ' . $adhOid, 1);
            $formElements['poste_start_date']['#default_value'] = InternalFunctions::internalFormatDate($viewDetailPerson->TRAV_START_DATE);
            $formElements['poste_start_date_hidden']['#default_value'] = $formElements['poste_start_date']['#default_value'];
        }

        $formElements['poste_existant_code']['#options'] = GetAllFunctions::getAllEntrPostesPcsFull($adhOid);
        $formElements['poste_new_code']['#options'] = GetAllFunctions::getAllPostesPcsFull();
        unset($formElements['poste_new_code']['#options'][array_search('POSTE GENERIQUE', $formElements['poste_new_code']['#options'])]);

        // dpm($formElements);
        // dpm($form);

        if (isset($idPoste)) { // modify poste
            $formElements['poste_salarie']['#default_value'] = $idPoste;

            $viewDetailPoste = GepsisOdataReadClass::getOdataClassValues('V1_ALL_POSTES', "POSTE_TRAV_O_ID eq " . $idPoste, 1);

            $allpostesPcs = GetAllFunctions::getAllPostesPcs();
            $entrPostesPcsLabel = GetAllFunctions::getAllEntrPostesPcsLabel($adhOid);

            $idPosteSal = array_search($viewDetailPoste->POSTE_LABEL, $entrPostesPcsLabel);
            if (empty($idPosteSal))
                $idPosteSal = array_search($viewDetailPoste->POSTE_CODE, $allpostesPcs);

            if (array_key_exists($idPosteSal, $formElements['poste_existant_code']['#options']))
                $formElements['poste_existant_code']['#default_value'] = $idPosteSal;
            else if (array_key_exists($idPosteSal, $formElements['poste_new_code']['#options'])) {
                $formElements['poste_new_code']['#default_value'] = $idPosteSal;
            }

            // TODO
            // $formElements['poste_start_date']['#default_value'] = InternalFunctions::internalFormatDate($viewDetailPoste -> START_DATE);
            // unset($form['submitted']['postes_trav']['poste_details_trav']);

            if (isset($viewDetailPoste->STATUS))
                $formElements['poste_status']['#text'] = '<p><strong>Statut:<span style="color:#FF0000">&nbsp;' . $viewDetailPoste->STATUS . ' </span></strong></p>';
            $formElements['poste_comment']['#default_value'] = $viewDetailPoste->USER_COMMENT;

            $viewPostesPourTrav = GepsisOdataReadClass::getOdataClassValues('V1_ALL_POSTES', "TRAV_O_ID eq " . $idTrav);
            if ($viewDetailPoste->CAN_ERASE == 'Y') {
                if (count($viewPostesPourTrav) > 1) {
                    // https://git.drupalcode.org/project/webform/blob/HEAD/src/WebformSubmissionForm.php#n739
                    $form['actions']['delete'] = array(
                        '#type' => 'submit',
                        '#submit' => array(
                            [
                                $this,
                                'poste_delete'
                            ]
                        ),
                        '#value' => 'Effacer',
                        '#validate' => [
                            '::noValidate'
                        ],
                        '#weight' => 50
                    );
                } else {
                    // unset($form['actions']['submit']);
                    $formElements['poste_status']['#text'] = '<p><strong>Status:<span style="color:#FF0000">&nbsp;' . 'Vous ne pouvez pas effacer le dernier poste sur ce salarié' . '.</span></strong></p>';
                }
                unset($form['elements']['poste_detail_fieldset']['poste_desactivation']);
            } else {
                unset($form['actions']['submit']);
                $formElements['poste_start_date']['#disabled'] = TRUE;
                unset($form['elements']['poste_detail_fieldset']['poste_existant_code']['#states']);
                unset($form['elements']['poste_detail_fieldset']['poste_new_code']['#states']);
                $formElements['poste_existant_code']['#disabled'] = TRUE;
                $formElements['poste_new_code']['#disabled'] = TRUE;
                $formElements['poste_comment']['#disabled'] = TRUE;
                $formElements['poste_status']['#text'] = '<p><strong>Status:<span style="color:#FF0000">&nbsp;' . 'Vous ne pouvez pas modifier ce poste. Il est validé par le service' . '.</span></strong></p>';

                if (count($viewPostesPourTrav) > 1 && inactivatePoste == TRUE) {
                    $form['actions']['desactiver'] = array(
                        '#type' => 'submit',
                        '#submit' => array(
                            [
                                $this,
                                'poste_desactivate'
                            ]
                        ),
                        '#validate' => array(
                            [
                                $this,
                                'poste_desactivate_validate'
                            ]
                        ),
                        '#value' => t('Désactiver'),
                        '#weight' => 50
                    );
                } else {
                    unset($form['elements']['poste_detail_fieldset']['poste_desactivation']);
                    $formElements['poste_status']['#text'] = '<p><strong>Status:<span style="color:#FF0000">&nbsp;' . 'Vous ne pouvez pas desactiver ce poste.' . '.</span></strong></p>';
                }
            }
        } else {
            unset($form['elements']['poste_detail_fieldset']['poste_desactivation']);
        }

        // hidden field email assitente
        $formElements['poste_fire_email']['#default_value'] = InternalFunctions::getEmailAssistante();
        // $formElements['poste_salarie']['#default_value'] =  $viewDetailPerson->PERS_NAME . ' ' . $viewDetailPerson->PERS_FIRST_NAME;

        // hidden start-date for mail
        // $form['submitted']['postes_trav']['poste_start_date_hidden_trav']['#value'] = str_replace('-', '/', $form['submitted']['postes_trav']['poste_start_date_trav']['#default_value']);

        // save initial values
        // $form_state['initialValues'] = _find_all_children_elements($form['submitted']);
        // dpm($form['submitted']['postes_trav']);

        // Disable caching
        $form['#cache']['max-age'] = 0;
        // $form['#tree'] = TRUE; // When this is set to false, the submit method gets no results through getValues().
        // $form_state->setRedirect('trav-details/'. $idTrav .'#lb-tabs-tabs-3');
        return;
        // dpm($form['submitted']);
        // dpm($form_state['initialValues']);
    }

    /**
     *
     * {@inheritdoc}
     */
    public function poste_delete(&$form, FormStateInterface $form_state) {
        $dataState = $form_state->getUserInput();

        $svc = GepsisOdataWriteClass::prepareWriteClass();
        $query = $svc->PosteTrav()->filter("id eq " . $dataState['poste_salarie']);
        $svc->UsePostTunneling = FALSE;
        try {
            $customer = $query->Execute()->Result[0];
        } catch (\Throwable $e) {
            return;
        }
        $svc->DeleteObject($customer);
        InternalFunctions::setupTraceInfos($customer);
        $svc->UpdateObject($customer);
        $svc->SaveChanges();

        $form_state->setRedirect('view.v1_entr_travs.page_1');
    }

    /**
     *
     * {@inheritdoc}
     */
    public function poste_desactivate_validate(&$form, FormStateInterface $form_state) {
        $dataState = $form_state->getUserInput();

        if (inactivatePoste == TRUE) {
            $posteEndDate = $dataState['poste_date_desactivation'];
            if (empty($posteEndDate))
                $form_state->setErrorByName('poste_date_desactivation', 'Pas de désactivation de poste sans date de fin !');

            $posteStartDate = $dataState['poste_start_date_hidden'];
            if ($posteEndDate != NULL && $posteStartDate != NULL) {
                $interval = date_diff(date_create($posteStartDate), date_create($posteEndDate));
                if ($interval->invert == 1)
                    $form_state->setErrorByName('poste_date_desactivation', 'Date de désactivation doit y etre apres la date de debut de poste !');
            }
        }
        return;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function poste_desactivate(&$form, FormStateInterface $form_state) {
        $dataState = $form_state->getUserInput();
        if (inactivatePoste == TRUE) {
            if (!empty($dataState['poste_date_desactivation'])) {
                $svc = GepsisOdataWriteClass::prepareWriteClass();
                $query = $svc->PosteTravEnd()->filter("id eq " . $dataState['poste_salarie']);
                try {
                    $customer = $query->Execute()->Result[0];
                } catch (\Throwable $e) {
                    return;
                }
                $customer->endDate = $dataState['poste_date_desactivation'] . 'T00:00:00';
                InternalFunctions::setupTraceInfos($customer);
                $svc->UpdateObject($customer);
                $svc->SaveChanges();
            }
        }
        $form_state->setRedirect('view.v1_entr_travs.page_1');
        return;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        $dataState = $webform_submission->getData();

        $pcsExist = $dataState['poste_existant_code'];
        $pcsNew = $dataState['poste_new_code'];
        if (empty($pcsExist) && empty($pcsNew)) {
            $form_state->setErrorByName('poste_existant_code', 'Aucune code PCS !');
            $form_state->setErrorByName('poste_new_code', 'Aucune code PCS !');
        }
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

    /**
     *
     * {@inheritdoc}
     */

    // Function to be fired after submitting the Webform.
    public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
        $values = $webform_submission->getData();
        $svc = GepsisOdataWriteClass::prepareWriteClass();

        // if($form_state->getTriggeringElement()['#id'] == 'edit-actions-retour-submit')

        $user = User::load(\Drupal::currentUser()->id());
        $allpostesPcs = GetAllFunctions::getAllPostesPcs();
        $allpostesPcsLabel = GetAllFunctions::getAllPostesPcsLabel();
        $entrPostesPcs = GetAllFunctions::getAllEntrPostesPcs($user->get('field_active_adherent_oid')->value);
        $entrPostesPcsLabel = GetAllFunctions::getAllEntrPostesPcsLabel($user->get('field_active_adherent_oid')->value);
        // UPDATE
        if (!empty($values['poste_salarie'])) {
            $query = $svc->PosteTrav()->filter("id eq " . $values['poste_salarie']);
            try {
                $customer = $query->Execute()->Result[0];
            } catch (\Throwable $e) {
                return;
            }

            //
            $customer->trav = $values['poste_trav_id'];
            $posteCode = self::checkPoste($values, $values['poste_salarie']);
            $customer->pcsLabel = !empty($allpostesPcsLabel[$posteCode]) ? htmlspecialchars($allpostesPcsLabel[$posteCode]) : htmlspecialchars($entrPostesPcsLabel[$posteCode]);
            $customer->pcsCode = !empty($allpostesPcs[$posteCode]) ? $allpostesPcs[$posteCode] : $entrPostesPcs[$posteCode];
            $customer->comment = htmlspecialchars($values['poste_comment']);
            $customer->startDate = $values['poste_start_date'] . 'T00:00:00';
            InternalFunctions::setupTraceInfos($customer);
            $svc->UpdateObject($customer);
        } else { // no  initial poste so it's new
            $customer = PosteTrav::CreatePosteTrav(null);
            $customer->trav = $values['poste_trav_id'];
            $posteCode = self::checkPoste($values, $values['poste_salarie']);
            $customer->pcsLabel = !empty($allpostesPcsLabel[$posteCode]) ? htmlspecialchars($allpostesPcsLabel[$posteCode]) : htmlspecialchars($entrPostesPcsLabel[$posteCode]);
            $customer->pcsCode = !empty($allpostesPcs[$posteCode]) ? $allpostesPcs[$posteCode] : $entrPostesPcs[$posteCode];
            $customer->comment = htmlspecialchars($values['poste_comment']);
            $customer->startDate = $values['poste_start_date'] . 'T00:00:00';
            InternalFunctions::setupTraceInfos($customer);
            $svc->AddToPosteTrav($customer);
        }
        $svc->SaveChanges();

        // $redirect_to_thankyou = new RedirectResponse(Url::fromUserInput('/trav-details/' . $values['poste_trav_id'] . '#lb-tabs-tabs-3')->toString());
        // $redirect_to_thankyou->send();
        return;
        // TODO
        // mark all changed values
        /*
        $changedValues = search_value_changed_recursive($form_state['initialValues'], $form_state['values']['submitted']);
        replaceKeyValuesPoste($form_state, $account);

        // append asterics on changed values
        mark_value_changed_recursive($changedValues, $form_state['values']['submitted']);
        // compare contrat start date with initial values
        $travStartDate = tarbesFormatDateShortReverse($data['postes_trav']['poste_start_date_trav']);
        if($form_state['initialValues']['poste_start_date_hidden_trav'] != $travStartDate)
            $form_state['values']['submitted']['postes_trav']['poste_start_date_hidden_trav'] = $travStartDate . ' (*)';

        $viewDetailPerson = views_get_view_result('v1_entr_travs', 'page_1', $form_state['poste_trav']['trav'], $account -> field_adherent_oid['und'][0]['value']);
        $maritalName = isset($viewDetailPerson[0] -> PERS_MARITAL_NAME) ? $viewDetailPerson[0] -> PERS_MARITAL_NAME : '';

        $form['#node'] -> webform['redirect_url'] = 'trav-details/' . $form_state['poste_trav']['trav'];
        */
    }

    public static function checkPoste($data, $initialPoste) {
        if (!empty($data['poste_existant_code']) && $data['poste_existant_code'] != $initialPoste)
            $posteCode = $data['poste_existant_code'];
        else if (!empty($data['poste_new_code']) && $data['poste_new_code'] != $initialPoste)
            $posteCode = $data['poste_new_code'];
        else
            $posteCode = $initialPoste;
        return $posteCode;
    }
}

