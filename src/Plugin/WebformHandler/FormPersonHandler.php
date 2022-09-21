<?php

namespace Drupal\gepsis\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\gepsis\Controller\GepsisOdataReadClass;
use Drupal\gepsis\Controller\GepsisOdataWriteClass;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\gepsis\Utility\GetAllFunctions;
use Drupal\gepsis\Utility\InternalFunctions;

/**
 * Create a new node entity from a webform submission.
 *
 * @WebformHandler(
 *   id = "Person Form",
 *   label = @Translation("Person form"),
 *   category = @Translation("Gepsis webform handler"),
 *   description = @Translation("Prepopulate person form"),
 *   cardinality =
 *     \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *     results =
 *     \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *     submission =
 *     \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class FormPersonHandler extends WebformHandlerBase {

    /**
     *
     * {@inheritdoc}
     */
    public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        $form['elements']['sal_adh_code']['#value'] = $user->get('field_active_adherent_code')->value;

        $current_parameters = \Drupal::routeMatch()->getParameters();
        $travOid = $current_parameters->get('travOid');
        $form['elements']['sal_trav_oid_details']['#value'] = $travOid;

        $viewDetailPerson = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_TRAVS', "TRAV_O_ID eq " . $travOid, TRUE);
        if (!empty($viewDetailPerson)) {
            // dpm($viewDetailPerson);
            $formElements = InternalFunctions::getFlattenedForm($form['elements']);
            $formElements['sal_title']['#options'] = GetAllFunctions::getAllTitres();
            $formElements['sal_title']['#default_value'] = $viewDetailPerson->PERS_TITLE;
            $formElements['sal_title']['#disabled'] = TRUE;
            $formElements['sal_sexe']['#default_value'] = $viewDetailPerson->PERS_SEXE;
            $formElements['sal_sexe']['#access'] = FALSE;
            $formElements['sal_birth_date']['#default_value'] = \Drupal::service('date.formatter')
                ->format(strtotime($viewDetailPerson->PERS_BIRTH_DATE), 'html_date');
            $formElements['sal_nom_usage']['#default_value'] = $viewDetailPerson->PERS_NAME;
            $formElements['sal_nom_usage']['#disabled'] = TRUE;
            $formElements['sal_first_name']['#default_value'] = $viewDetailPerson->PERS_FIRST_NAME;
            $formElements['sal_first_name']['#disabled'] = TRUE;
            $formElements['sal_marital_name']['#default_value'] = $viewDetailPerson->PERS_NOM_JF;
            $formElements['sal_marital_name']['#disabled'] = TRUE;

            // trav fieldset - HIDDEN INFO
            $allContratsLabel = GetAllFunctions::getAllContratsLabel();
            $formElements['sal_employement_type']['#options'] = $allContratsLabel;
            $formElements['sal_employement_type']['#default_value'] = array_search($viewDetailPerson->TRAV_EMPL_TYPE_LABEL, $allContratsLabel);
            $formElements['sal_examen_type']['#default_value'] = $viewDetailPerson->EXAM_TYPE_LABEL;

            if (!empty($viewDetailPerson->TRAV_START_DATE)) // trav START date
            {
                $form['elements']['sal_trav_start_date']['#default_value'] = \Drupal::service('date.formatter')
                    ->format(strtotime($viewDetailPerson->TRAV_START_DATE), 'html_date');
            }

            if (!empty($viewDetailPerson->TRAV_EXAM_EMBAUCHE)) // trav END date
            {
                $form['elements']['#default_value'] = \Drupal::service('date.formatter')
                    ->format(strtotime($viewDetailPerson->TRAV_EXAM_EMBAUCHE), 'html_date');
            }

            $form['elements']['sal_person_details_fieldset']['sal_travailleur_details']['#access'] = FALSE;
            // END - trav fieldset

            // get info sup for write class - direct from Geps
            $viewAddressPerson = GepsisOdataWriteClass::getOdataClassValues('TravPersonAddress', "trav eq " . $viewDetailPerson->TRAV_O_ID, 1);
            if (!empty($viewAddressPerson)) {
                // dpm($viewAddressPerson);
                $formElements['sal_numero_securite_social']['#default_value'] = $viewAddressPerson->ssn;
                $formElements['sal_numero_tel_portable']['#default_value'] = $viewAddressPerson->gsmPhone;
                $formElements['sal_e_mail_professionel']['#default_value'] = $viewAddressPerson->email_pro;
                $formElements['sal_no_adresse']['#default_value'] = $viewAddressPerson->addressVoieNo;
                $vTyp = GetAllFunctions::getAllVoieTyp();
                $formElements['sal_type_voie']['#options'] = $vTyp;
                $formElements['sal_type_voie']['#default_value'] = array_search($viewAddressPerson->addressVoieTyp, $vTyp);
                $formElements['sal_nom_adresse']['#default_value'] = $viewAddressPerson->addressVoieNom;
                $formElements['sal_batiment']['#default_value'] = $viewAddressPerson->addressBatiment;
                $formElements['sal_escalier']['#default_value'] = $viewAddressPerson->addressEscalier;
                $formElements['sal_etage']['#default_value'] = $viewAddressPerson->addressEtage;
                $formElements['sal_porte']['#default_value'] = $viewAddressPerson->addressPorte;
                $formElements['sal_complement_1']['#default_value'] = $viewAddressPerson->addressCompl1;
                $formElements['sal_complement_2']['#default_value'] = $viewAddressPerson->addressCompl2;
                $allCountries = GetAllFunctions::getAllCountries();
                $formElements['sal_country']['#options'] = $allCountries;
                $formElements['sal_country']['#default_value'] = $viewAddressPerson->adrCountry;
                $formElements['sal_country']['#disabled'] = TRUE;

                if (!empty($viewAddressPerson->adrCity)) {
                    $ville = GetAllFunctions::getCityByOid($viewAddressPerson->adrCity);
                    $formElements['sal_city_et_code']['#default_value'] = $ville[$viewAddressPerson->adrCity];
                    $formElements['sal_city_et_code_value']['#default_value'] = $viewAddressPerson->adrCity;
                }

                // autocomplete city
                $form['#attached']['library'][] = 'gepsis/gepsis.replace-autocomplete';
                $formElements['sal_city_et_code']['#attributes']['class'] = [
                    'replaceAutocomplete',
                ];
                $formElements['sal_city_et_code']['#autocomplete_route_name'] = 'gepsis.autocomplete';
                $formElements['sal_city_et_code']['#autocomplete_route_parameters'] = [
                    'key' => 'CITY_O_ID',
                    'class' => 'V1_ALL_CITYES',
                    'searchValues' => [
                        'CITY_CODE',
                        'CITY_LABEL',
                    ],
                ];
                $form['elements']['sal_city_et_code_value'] = [
                    '#type' => 'hidden',
                ];
            }
        }

        // hidden field email assitente
        $form['elements']['sal_fire_email_person']['#default_value'] = InternalFunctions::getEmailAssistante();

        /*
         * if($form_state['rebuild'] != TRUE)
         * $form_state['initialValues'] = _find_all_children_elements($form['submitted']);
         */
        // Disable caching
        $form['#cache']['max-age'] = 0;

        $form['actions']['cancel'] = [
            '#type' => 'submit',
            '#submit' => [[$this, 'person_details_cancel']],
            '#value' => 'Retour sans modifications',
            '#weight' => 50,
            '#limit_validation_errors' => [] //no validation for back button
        ];

        // dpm($form);
    }

    /**
     *
     * {@inheritdoc}
     */
    public function person_details_cancel(&$form, FormStateInterface $form_state) {
        // return new RedirectResponse(Url::fromRoute('v1-entr-travs')->toString());
        // return new RedirectResponse(\Drupal::url('internal:/v1-entr-travs', [], ['absolute' => TRUE]));
        // $form_state->setRedirect('v1-entr-travs');
        // return new RedirectResponse('v1-entr-travs');

        // return $this->redirect('view.MACHINE_NAME_VIEW.MACHINE_NAME_DISPLAY');
        // MACHINE_NAME_VIEW -> Located in the list of your Views.
        // MACHINE_NAME_DISPLAY -> Located in the Advanced tab and look at "Other".
        $form_state->setRedirect('view.v1_entr_travs.page_1');

    }

    /**
     *
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        if ($form_state->getTriggeringElement()['#id'] == 'edit-actions-retour-submit') {
            return new RedirectResponse('v1-entr-travs');
        }

        $data = $form_state->getUserInput();
        if (!empty($data['sal_numero_tel_portable']) && preg_match('/^[0-9]{10}$/m', $data['sal_numero_tel_portable']) === 0) {
            $form_state->setErrorByName('sal_numero_tel_portable', 'Le format de Mobile transmis par adh�rent n\'est pas respect�: %value.', [
                '%value' => $data['sal_numero_tel_portable'],
            ]);
        }

        if (!empty($data['sal_numero_securite_social'])) {
            $res = InternalFunctions::checkNumSecu($data['sal_numero_securite_social']);
            if (!$res) {
                \Drupal::logger('nss')
                    ->notice('NSS INVALID: ' . $data['sal_numero_securite_social']);
                $form_state->setErrorByName('sal_numero_securite_social', 'Le num�ro de s�curit� sociale n\'est pas valide ! <p><b>Vous pouvez laisser la zone vide et continuer la modification du salari�.</b>: %value</p>.', [
                    '%value' => $data['sal_numero_securite_social'],
                ]);
                return;
            }
        }

        if (empty($data['sal_birth_date'])) {
            $form_state->setErrorByName('sal_birth_date', $this->t('La date de naissance n\'est pas complete'));
        }
        // return;

        return;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        // $userInput = $form_state->getUserInput();
        $form_state->setRedirect('<front>');
        if ($form_state->getTriggeringElement()['#id'] == 'edit-actions-retour-submit') {
            return new RedirectResponse('v1-entr-travs');
        }
        return;
    }

    /**
     *
     * {@inheritdoc}
     */

    // Function to be fired after submitting the Webform.
    public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
        // You need to examine the webform submission state by calling \Drupal\webform\WebformSubmissionInterface::isDraft
        // $values = $webform_submission->getData();
        $data = $webform_submission->getRawData();
        // dpm($data);

        // $original_data = $webform_submission->getOriginalData();

        $svc = GepsisOdataWriteClass::prepareWriteClass();
        $query = $svc->TravPersonAddress()
            ->filter("trav eq " . $data['sal_trav_oid_details']);
        try {
            $customer = $query->Execute()->Result[0];
        } catch (\Throwable $e) {
            return;
        }

        // person names
        // $customer -> personTitle = $data['sal_title'];
        // $customer -> sexe = $data['sal_sexe'];
        // $customer -> lastName = $data['sal_nom_usage'];
        // $customer -> firstName = $data['sal_first_name'];
        // $customer -> maritalName = $data['sal_marital_name'];
        $customer->birthDate = $data['sal_birth_date'] . 'T00:00:00';
        $customer->ssn = $data['sal_numero_securite_social'];
        $customer->gsmPhone = $data['sal_numero_tel_portable']?$data['sal_numero_tel_portable']:'';
        $customer->email_pro = $data['sal_e_mail_professionel'];

        $vTyp = GetAllFunctions::getAllVoieTyp();
        $customer->addressVoieNo = $data['sal_no_adresse'];
        $customer->addressVoieNom = $data['sal_nom_adresse'];
        $customer->addressVoieTyp = $data['sal_type_voie']?$vTyp[$data['sal_type_voie']]:'';
        $customer->addressBatiment = $data['sal_batiment'];
        $customer->addressEscalier = $data['sal_escalier'];
        $customer->addressEtage = $data['sal_etage'];
        $customer->addressPorte = $data['sal_porte'];
        $customer->addressCompl1 = $data['sal_complement_1'];
        $customer->addressCompl2 = $data['sal_complement_2'];
        $customer->adrCity = $data['sal_city_et_code_value'];
        // $customer -> adrCountry = $data['sal_country'];
        // $customer -> fixePhone = $data['sal_country'];
        // $customer -> gsmPhone = $data['sal_country'];
        // $customer -> email = $data['sal_country'];

        //
        InternalFunctions::setupTraceInfos($customer);
        $svc->UpdateObject($customer);
        $svc->SaveChanges();

        return new RedirectResponse('v1-entr-travs');

        // mark all changed values
        // $changedValues = search_value_changed_recursive($form_state['initialValues'], $form_state['values']['submitted']);

        // replaceKeyValuesPersonDetails($form_state);
        // mark_value_changed_recursive($changedValues, $form_state['values']['submitted']);
    }

}

/*
sal_title => string (5) "29687"
sal_sexe => string (1) "M"
sal_birth_date => string (10) "1982-08-16"
sal_numero_securite_social => string (15) "178082B03306748"
sal_numero_tel_portable => string (0) ""
sal_nom_usage => string (8) "BACHELET"
sal_first_name => string (7) "ANTHONY"
sal_marital_name => string (7) "NMNAISS"
sal_no_adresse => string (1) "5"
sal_type_voie => string (10) "4094912300"
sal_nom_adresse => string (3) "nom"
sal_batiment => string (1) "1"
sal_escalier => string (1) "2"
sal_etage => string (1) "3"
sal_porte => string (1) "4"
sal_complement_1 => string (13) "31 RUE LETORT"
sal_complement_2 => string (6) "compl2"
sal_country => string (5) "21224"
sal_city_et_code => string (34) "PARIS 18EME ARRONDISSEMENT - 75018"
sal_employement_type => integer 35190279
sal_examen_type => string (2) "SI"
sal_trav_start_date => string (10) "2018-04-12"
sal_trav_end_date => string (0) ""
sal_start_date_hidden => string (0) ""
sal_end_date_hidden => string (0) ""
sal_last_visite_date_hidden => string (0) ""
sal_trav_oid_details => string (10) "4526028100"
sal_fire_email_person => string (29) "aep13@objectifsantetravail.fr"
sal_adh_code => string (7) "0950156"
*/
