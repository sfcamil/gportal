<?php

namespace Drupal\gepsis\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\gepsis\Controller\GepsisOdataReadClass;
use Drupal\gepsis\Controller\GepsisOdataWriteClass;
use Drupal\user\Entity\User;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use odataPhp\Person;
use odataPhp\PosteTrav;
use odataPhp\Trav;
use Drupal\gepsis\Utility\GetAllFunctions;
use Drupal\gepsis\Utility\InternalFunctions;
use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;

/**
 * Create a new node entity from a webform submission.
 *
 * @WebformHandler(
 *   id = "NouveauTrav Form",
 *   label = @Translation("NouveauTrav form"),
 *   category = @Translation("Gepsis webform handler"),
 *   description = @Translation("NouveauTrav form"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
/*
 * Titre
 *Type de contrat
 *Type de voie
 * Pays
 * Codes PCS existant dans l’entreprise
 * Cochez la (les) situation(s) qui concerne(nt) votre salarié
 * form-N8iKZ5sQoyyqPEGi2LZuohl-rUAe2Ww6bftZzGtwC3g
 */

class FormNouveauTravHandler extends WebformHandlerBase
{

    /**
     *
     * {@inheritdoc}
     */
    public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        $user = User::load(\Drupal::currentUser()->id());
        $adhOid = $user->get('field_active_adherent_oid')->value;
        $formElements = InternalFunctions::getFlattenedForm($form['elements']);
        $values = $webform_submission->getData();

        $form['actions']['draft']['#value'] = 'Enregistrer le brouillon';

        $travOid = \Drupal::request()->get('travOid');
        if ($travOid) { // reactivate an trav
            $viewAddressPerson = GepsisOdataWriteClass::getOdataClassValues('TravPersonAddress', "trav eq " . $travOid, TRUE);
            $viewDetailPerson = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_TRAVS', "PERS_O_ID eq " . $viewAddressPerson->person, TRUE);
        }

        $storage = $form_state->getStorage(); // $form_state['storage'],  you can later access on the next steps.
        $page = $storage['current_page'];
        switch ($page) {
            case 'new_sal_person_details_wp' : // 1 Preson
                $formElements['new_sal_title']['#options'] = GetAllFunctions::getAllTitres();
                $formElements['new_sal_sexe']['#default_value'] = '';
                $formElements['new_sal_sexe']['#access'] = FALSE;

                // if parameters then URSAF insert go to page 1
                if (isset($_GET['due'])) {
                    $formElements['new_sal_birth_date']['#default_value'] = trim(date("d-M-Y", strtotime($_GET['birthDate'])));
                    $formElements['new_sal_sexe']['#default_value'] = trim($viewDetailPerson->PERS_BIRTH_DATE);
                    $formElements['new_sal_nom_usage']['#default_value'] = trim($viewDetailPerson->PERS_NAME);
                    $formElements['new_sal_first_name']['#default_value'] = trim($viewDetailPerson->PERS_FIRST_NAME);
                    $formElements['new_sal_nom_naissance']['#default_value'] = trim($viewDetailPerson->PERS_NOM_JF);
                } else if (isset($viewDetailPerson)) {
                    $formElements['new_sal_title']['#default_value'] = trim($viewDetailPerson->PERS_TITLE);
                    $formElements['new_sal_birth_date']['#default_value'] = InternalFunctions::internalFormatDate($viewDetailPerson->PERS_BIRTH_DATE);
                    $formElements['new_sal_sexe']['#default_value'] = trim($viewDetailPerson->PERS_SEXE);
                    $formElements['new_sal_title']['#default_value'] = trim($viewDetailPerson->PERS_TITLE) == '29702' ? 'Madame' : 'Monsieur';
                    $formElements['new_sal_nom_usage']['#default_value'] = trim($viewDetailPerson->PERS_NAME);
                    $formElements['new_sal_first_name']['#default_value'] = trim($viewDetailPerson->PERS_FIRST_NAME);
                    $formElements['new_sal_nom_naissance']['#default_value'] = trim($viewDetailPerson->PERS_NOM_JF);
                    $formElements['new_sal_numero_securite_social']['#default_value'] = trim($viewAddressPerson->ssn);
                }
                // echo "this is 1";
                break;
            case 'new_sal_break_create_trav_wp' : // 2 Fonction
                $contratsLabel = GetAllFunctions::getAllContratsLabel();
                $contratsCodes = GetAllFunctions::getAllContratsCode();
                $delCodes = array(
                    'ADEF',
                    'AUT',
                    'MULT',
                    'MULTSAIS',
                    'PRINC',
                    'PRINCSAIS',
                    'MULT'
                );
                foreach ($delCodes as $value) {
                    $idDelCode = array_search($value, $contratsCodes);
                    unset($contratsLabel[$idDelCode]);
                }

                $formElements['new_sal_employement_type']['#options'] = $contratsLabel;
                /*
                 *
                 *         if(!empty($_GET['due'])) {
                 $form['submitted']['create_travailleur_person_new']['trav_start_date_new']['#default_value'] = date("d-M-Y", strtotime($_GET['dateEntree']));
                 if(!empty($_GET['dateFin']))
                 $form['submitted']['create_travailleur_person_new']['trav_end_date_new']['#default_value'] = date("d-M-Y", strtotime($_GET['dateFin']));
                 $form['submitted']['create_travailleur_person_new']['employement_type_new_trav']['#default_value'] = getTypeContratDue($_GET['codeContrat']);
                 }
                 */

                // echo "this is 2";
                break;
            case 'new_sal_break_trav_details_wp' : // 3 Adresse
                $vTyp = GetAllFunctions::getAllVoieTyp();
                $formElements['new_sal_type_voie']['#options'] = $vTyp;
                $allCountries = GetAllFunctions::getAllCountries();
                $formElements['new_sal_country']['#options'] = $allCountries;
                $formElements['new_sal_country']['#default_value'] = 21224; // 21224 = France
                $formElements['new_sal_country']['#disabled'] = TRUE;

                // autocomplete city
                $form['#attached']['library'][] = 'gepsis/gepsis.replace-autocomplete';
                $formElements['new_sal_city_et_code']['#attributes']['class'] = array(
                    'replaceAutocomplete'
                );
                $formElements['new_sal_city_et_code']['#autocomplete_route_name'] = 'gepsis.autocomplete';
                $formElements['new_sal_city_et_code']['#autocomplete_route_parameters'] = array(
                    'key' => 'CITY_O_ID',
                    'class' => 'V1_ALL_CITYES',
                    'searchValues' => array(
                        'CITY_CODE',
                        'CITY_LABEL'
                    )
                );
                $form['elements']['new_sal_city_et_code_value'] = array(
                    '#type' => 'hidden'
                );

                if (!empty($viewAddressPerson)) {
                    $formElements['new_sal_no_adresse']['#default_value'] = $viewAddressPerson->addressVoieNo;
                    $formElements['new_sal_type_voie']['#default_value'] = array_search($viewAddressPerson->addressVoieTyp, $vTyp);
                    $formElements['new_sal_nom_adresse']['#default_value'] = $viewAddressPerson->addressVoieNom;
                    $formElements['new_sal_batiment']['#default_value'] = $viewAddressPerson->addressBatiment;
                    $formElements['new_sal_escalier']['#default_value'] = $viewAddressPerson->addressEscalier;
                    $formElements['new_sal_etage']['#default_value'] = $viewAddressPerson->addressEtage;
                    $formElements['new_sal_porte']['#default_value'] = $viewAddressPerson->addressPorte;
                    $formElements['new_sal_complement_1']['#default_value'] = $viewAddressPerson->addressCompl1;
                    $formElements['new_sal_complement_2']['#default_value'] = $viewAddressPerson->addressCompl2;

                    if (!empty($viewAddressPerson->adrCity)) {
                        $ville = GetAllFunctions::getCityByOid($viewAddressPerson->adrCity);
                        $formElements['new_sal_city_et_code']['#default_value'] = $ville[$viewAddressPerson->adrCity];
                        $formElements['new_sal_city_et_code_value']['#default_value'] = $viewAddressPerson->adrCity;
                    }
                }
                break;
            case 'new_sal_break_trav_poste_wp' : // 4 Postes
                $allpostesPcsFull = GetAllFunctions::getAllPostesPcsFull();

                for ($i = 1; $i < 4; $i++) {
                    $formElements['new_sal_poste_new_code_' . $i]['#options'] = $allpostesPcsFull;
                    $formElements['new_sal_poste_start_date_' . $i]['#default_value'] = InternalFunctions::internalFormatDate($values['new_sal_start_date']);
                    $entrPostesPcs = GetAllFunctions::getAllEntrPostesPcsFull($adhOid);
                    if (!empty($entrPostesPcs)) {
                        $formElements['new_sal_poste_codes_pcs_' . $i]['#options'] = $entrPostesPcs;
                    } else {
                        unset($formElements['new_sal_poste_codes_pcs_' . $i]);
                    }
                }
                break;
            case 'new_sal_break_trav_risques_wp' : // 5 Risques
                $formElements['new_sal_risques']['#options'] = GetAllFunctions::getAllDecretCriteria();
                $allContratsVersCategory = GetAllFunctions::getAllContratsVersCategory();
                $travEmploymentTypeCategory = $allContratsVersCategory[$values['new_sal_employement_type']];

                $form['#attached']['library'][] = 'gepsis/calculPeriodicite';
                $form['#attached']['drupalSettings']['gepsis']['age'] = InternalFunctions::calculateAge($values['new_sal_birth_date'] . 'T00:00');
                $form['#attached']['drupalSettings']['gepsis']['contrat_type'] = $travEmploymentTypeCategory;
                $form['#attached']['drupalSettings']['gepsis']['type'] = 'new';
                break;
            case 'webform_preview' : // 6 Preview

                break;
        }

        // hidden field email assitente
        $formElements['new_sal_fire_email']['#default_value'] = InternalFunctions::getEmailAssistante();
        $formElements['new_sal_adh_code']['#default_value'] = $user->get('field_active_adherent_code')->value;

        /*
         * if($form_state['rebuild'] != TRUE)
         * $form_state['initialValues'] = _find_all_children_elements($form['submitted']);
         */
        // Disable caching
        $form['#cache']['max-age'] = 0;

        // dpm($form['elements']);
    }

    /**
     *
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        $user = User::load(\Drupal::currentUser()->id());
        $adhOid = $user->get('field_active_adherent_oid')->value;
        $formElements = InternalFunctions::getFlattenedForm($form['elements']);
        $values = $webform_submission->getData();

        $storage = $form_state->getStorage();
        $page = $storage['current_page'];
        switch ($page) {
            case 'new_sal_person_details_wp' : // 1 Preson
                $age = date_diff(date_create($values['new_sal_birth_date']), date_create(date("Y-m-d")))->y;
                if ($age < 14) {
                    $form_state->setErrorByName('new_sal_birth_date', $this->t(utf8_encode('Erreur de date de naissance (salarie ag� de moins de 14 ans)')));
                    return;
                }

                if (!empty($values['new_sal_numero_tel_portable']) && !preg_match('/^[0-9]{10}$/', $values['new_sal_numero_tel_portable'])) {
                    $form_state->setErrorByName('new_sal_numero_tel_portable', $this->t(utf8_encode('Le format de Mobile transmis par adh�rent n\'est pas respect�')));
                    return;
                }

                if (!empty($values['new_sal_numero_securite_social'])) {
                    $res = InternalFunctions::checkNumSecu($values['new_sal_numero_securite_social']);
                    if (!$res) {
                        // Logs a notice
                        \Drupal::logger('gepsis')->notice('NSS INVALID: ' . $values['new_sal_numero_securite_social']);
                        $form_state->setErrorByName('new_sal_numero_securite_social', $this->t(utf8_encode('Le num�ro de s�curit� sociale n\'est pas valide ! <p><b>Vous pouvez laisser la zone vide et continuer la cr�ation du salari�.</b></p>')));
                        return;
                    }
                }
                $travActive = self::checkPersonHomonym($form, $form_state, $webform_submission);

                if ($travActive) {
                    $response = new TrustedRedirectResponse(Url::fromUserInput('/trav-details/' . $travActive)->toString());
                    $response->send();
                }
                break;
            case 'new_sal_break_create_trav_wp' : // 2 Fonction
                if (!empty($values['new_sal_end_date']) && $values['new_sal_end_date'] < $values['new_sal_start_date'])
                    $form_state->setErrorByName('new_sal_end_date', $this->t(utf8_encode('La date de fin ne peut �tre ant�rieure � la date de d�but.')));
                break;
            case 'new_sal_break_trav_details_wp' : // 3 Adresse
                if (!empty($values['new_sal_no_adresse']) && !preg_match('/^[a-zA-Z0-9]*$/', $values['new_sal_no_adresse'])) {
                    $form_state->setErrorByName('new_sal_no_adresse', $this->t(utf8_encode('Encodez que des caract�res et num�ros valides dans N� ')));
                    return;
                }
                break;
            case 'new_sal_break_trav_poste_wp' : // 4 Postes
                $mesPostes = array();
                for ($i = 1; $i < 4; $i++) {
                    if (!empty($values['new_sal_poste_codes_pcs_' . $i]))
                        $mesPostes[] = $values['new_sal_poste_codes_pcs_' . $i];
                    if (!empty($values['new_sal_poste_new_code_' . $i]))
                        $mesPostes[] = $values['new_sal_poste_new_code_' . $i];
                }

                $tabUniques = array_unique($mesPostes);
                if (count($tabUniques) < count($mesPostes)) {
                    $form_state->setErrorByName('', $this->t(utf8_encode('Vous avez encod� deux fois le meme poste !</b></p>')));
                    return;
                }

                if (empty($values['new_sal_poste_codes_pcs_1']) && empty($values['new_sal_poste_new_code_1'])) {
                    $form_state->setErrorByName('', $this->t(utf8_encode('Aucun code PCS encod�!</b></p>')));
                    return;
                }
                break;
            case 'new_sal_break_trav_risques_wp' : // 5 Risques
                $dataL = $values['new_sal_risques'];
                foreach ($dataL as $key => $value) {
                    if (empty($value))
                        unset($dataL[$key]);
                }

                if (count($dataL) == 0) {
                    $form_state->setErrorByName('new_sal_risques', $this->t(utf8_encode('Aucun risque encod� !</b></p>')));
                    return;
                }

                // TODO
                // $form_state->setSubmitted();
                $titres = GetAllFunctions::getAllTitres();
                $form_state->setValue('new_sal_title_hidden', $titres[$values['new_sal_title']]);

                $contratsLabel = GetAllFunctions::getAllContratsLabel();
                // $formElements['new_sal_employement_type_hidden']['#default_value'] = $contratsLabel[$values['new_sal_employement_type']];
                $form_state->setValue('new_sal_employement_type_hidden', $contratsLabel[$values['new_sal_employement_type']]);

                $voieTyp = GetAllFunctions::getAllVoieTyp();
                // $formElements['new_sal_type_voie_hidden']['#default_value'] = $voieTyp[$values['new_sal_type_voie']];
                $form_state->setValue('new_sal_type_voie_hidden', $voieTyp[$values['new_sal_type_voie']]);

                $allCountries = GetAllFunctions::getAllCountries();
                // $formElements['new_sal_country_hidden']['#default_value'] = $allCountries[$values['new_sal_country']];
                $form_state->setValue('new_sal_country_hidden', $allCountries[$values['new_sal_country']]);

                $allpostesPcsFull = GetAllFunctions::getAllPostesPcsFull();
                $entrPostesPcs = GetAllFunctions::getAllEntrPostesPcsFull($adhOid);
                for ($i = 1; $i < 4; $i++) {
                    if (!empty($values['new_sal_poste_codes_pcs_' . $i]))
                        // $formElements['new_sal_poste_codes_pcs_' . $i . '_hidden']['#default_value'] = $entrPostesPcs[$values['new_sal_poste_codes_pcs_' . $i]];
                        $form_state->setValue('new_sal_poste_codes_pcs_' . $i . '_hidden', $entrPostesPcs[$values['new_sal_poste_codes_pcs_' . $i]]);
                    else if (!empty($values['new_sal_poste_new_code_' . $i]))
                        // $formElements['new_sal_poste_new_code_' . $i . '_hidden']['#default_value'] = $allpostesPcsFull[$values['new_sal_poste_new_code_' . $i]];
                        $form_state->setValue('new_sal_poste_new_code_' . $i . '_hidden', $allpostesPcsFull[$values['new_sal_poste_new_code_' . $i]]);
                }

                $allDecretCriteria = GetAllFunctions::getAllDecretCriteria();
                $stringRisques = array();
                foreach ($values['new_sal_risques'] as $key => $value) {
                    $stringRisques[] = $allDecretCriteria[$value];
                }
                //$formElements['new_sal_risques_hidden']['#default_value'] = implode(', ', $stringRisques);
                $form_state->setValue('new_sal_risques_hidden', implode(', ', $stringRisques));
                // dpm($formElements);
                break;
        }
        return;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        $current_page = $webform_submission->getCurrentPage();
        if (!empty($current_page) && $current_page != 'webform_confirmation') {
            return;
        }
        // $values = $webform_submission->getData();
        // $input = &$form_state->getUserInput();
        // $arbitrary_value = $form_state->get('arbitrary_key');
        // $storage = $form_state->getStorage();
        return;
    }

    public function preSave(WebformSubmissionInterface $webform_submission) {
        $current_page = $webform_submission->getCurrentPage();
        if (!empty($current_page) && $current_page != 'webform_confirmation') {
            return;
        }
        // $values = $webform_submission->getData();
        return;
    }

    /**
     *
     * {@inheritdoc}
     */

    // Function to be fired after submitting the Webform.
    public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
        $current_page = $webform_submission->getCurrentPage();
        if (!empty($current_page) && $current_page != 'webform_confirmation') {
            return;
        }
        $values = $webform_submission->getData();

        // create person if not exact
        if (!isset($values['new_pers_multistep_values']['exact']))
            $person = self::createNewPerson($webform_submission);
        $trav = self::createNewTrav($webform_submission, $person);
        self::insertAdressePerson($webform_submission, $trav);
        self::createPostesTrav($webform_submission, $trav);

        return new RedirectResponse('v1-entr-travs');

        // if DUE then set to Y
        if (isset($_GET['due'])) {
            $svc = prepareWriteClass();
            $query = $svc->DueStatus()->filter("id eq " . $_GET['due']);
            $customer = $query->Execute()->Result[0];
            $customer->status = 'Y';
            setupTraceInfos($customer);
            $svc->UpdateObject($customer);
            $svc->SaveChanges();
        }

        // sleep(300);
        lock_release('geps_new_trav');
        // $form['#node'] -> webform['redirect_url'] = 'v1-entr-travs';
        // $form['#node'] -> webform['redirect_url'] = 'trav-details/' . $form_state['multistep_values']['trav'];
        // replacePostesKeyValuesPersonNew($form, $form_state, $account);

        // mark all changed values
        // $changedValues = search_value_changed_recursive($form_state['initialValues'], $form_state['values']['submitted']);

        // replaceKeyValuesPersonDetails($form_state);
        // mark_value_changed_recursive($changedValues, $form_state['values']['submitted']);

        return new RedirectResponse('v1-entr-travs');
    }

    private function createNewPerson(WebformSubmissionInterface &$webform_submission) {
        $values = $webform_submission->getData();
        $svc = GepsisOdataWriteClass::prepareWriteClass();
        $customer = Person::CreatePerson(null);
        $customer->personTitle = $values['new_sal_title'];
        $persTitles = GetAllFunctions::getAllTitres();
        if ($persTitles[$values['new_sal_title']] == 'Monsieur') // male
            $customer->sexe = 'M';
        else if ($persTitles[$values['new_sal_title']] == 'Madame') // femme
            $customer->sexe = 'F';
        $customer->ssn = $values['new_sal_numero_securite_social'];
        $customer->nomJf = $values['new_sal_nom_naissance'];
        $customer->firstName = $values['new_sal_first_name'];
        $customer->lastName = strtoupper($values['new_sal_nom_usage']);
        $customer->firstName2 = $values['new_sal_nom_naissance'] ? $values['new_sal_nom_naissance'] : '';
        $customer->birthDate = $values['new_sal_birth_date'] . 'T00:00:00';
        InternalFunctions::setupTraceInfos($customer);
        $svc->AddToPerson($customer);
        $svc->SaveChanges();
        return $customer->person;
    }

    private function createNewTrav(WebformSubmissionInterface &$webform_submission, $person) {
        $user = User::load(\Drupal::currentUser()->id());
        $values = $webform_submission->getData();
        $svc = GepsisOdataWriteClass::prepareWriteClass();
        $customer = Trav::CreateTrav(null);
        $customer->person = $person;
        $customer->travStartDate = $values['new_sal_start_date'] . 'T00:00:00';
        $customer->travEndDate = $values['new_sal_end_date'] ? $values['new_sal_end_date'] . 'T00:00:00' : '';
        $customer->travEmploymentType = $values['new_sal_employement_type'];
        $customer->entreprise = $user->get('field_active_adherent_oid')->value;
        if (isset($values['person_exists_select_list'])) {
            $stringPers = 'Personnes connues susceptibles d\'être la même personne:' . "\n" . implode("\n", $values['person_exists_select_list']);
            $stringPers = (strlen($stringPers) > 512) ? substr($stringPers, 0, 500) . ' ...' : $stringPers;
            $customer->comment = $stringPers;
        }
        $customer->categories = implode(',', $values['new_sal_risques']);
        $allContratsVersCategory = GetAllFunctions::getAllContratsVersCategory();
        $type_de_contrat_new_trav = $allContratsVersCategory[$values['new_sal_employement_type']];
        // $allEmplTypeCat = GetAllFunctions::getAllEmplTypeCat();
        // $type_de_contrat_new_trav2 = $allEmplTypeCat[$type_de_contrat_new_trav];
        $customer->travEmploymentTypeCategory = $type_de_contrat_new_trav;
        $customer->travPdc = $values['new_sal_periodicite_indicative'];
        $customer->function = htmlspecialchars($values['new_sal_function_trav']);
        $customer->planificationActive = 'Y';
        InternalFunctions::setupTraceInfos($customer);
        //
        $svc->AddToTrav($customer);
        $svc->SaveChanges();
        return $customer->trav;
    }

    private function createPostesTrav(WebformSubmissionInterface &$webform_submission, $trav) {
        $user = User::load(\Drupal::currentUser()->id());
        $entreprise = $user->get('field_active_adherent_oid')->value;
        $values = $webform_submission->getData();
        // save poste details
        $allpostesPcs = GetAllFunctions::getAllPostesPcsFull();
        $allpostesPcsLabel = GetAllFunctions::getAllPostesPcsLabel();
        $entrPostesPcs = GetAllFunctions::getAllEntrPostesPcs($entreprise);
        $entrPostesPcsLabel = GetAllFunctions::getAllEntrPostesPcsLabel($entreprise);

        // TODO
        for ($i = 1; $i < 4; $i++) {
            if (!empty($values['new_sal_poste_codes_pcs_' . $i]))
                $posteCode = $values['new_sal_poste_codes_pcs_' . $i];
            else
                $posteCode = $values['new_sal_poste_new_code_' . $i];
            if (!empty($posteCode)) {
                $svc = GepsisOdataWriteClass::prepareWriteClass();
                $customer = PosteTrav::CreatePosteTrav(null);
                $customer->trav = $trav;
                // $customer -> pcsCode = $allpostesPcs[$posteCode];
                $customer->pcsLabel = !empty($allpostesPcsLabel[$posteCode]) ? $allpostesPcsLabel[$posteCode] : $entrPostesPcsLabel[$posteCode];
                $customer->pcsCode = !empty($allpostesPcs[$posteCode]) ? $allpostesPcs[$posteCode] : $entrPostesPcs[$posteCode];
                $customer->comment = htmlspecialchars($values['new_sal_poste_comment_' . $i]);
                // $customer -> startDate = tarbesFormatDate($values['new_sal_poste_start_date_' . $i]);
                $customer->startDate = $values['new_sal_poste_start_date_' . $i] . 'T00:00:00';
                InternalFunctions::setupTraceInfos($customer);
                $svc->AddToPosteTrav($customer);
                $svc->SaveChanges();
            }
        }
    }

    private function insertAdressePerson(WebformSubmissionInterface &$webform_submission, $trav) {
        $values = $webform_submission->getData();

        $svc = GepsisOdataWriteClass::prepareWriteClass();
        $query = $svc->TravPersonAddress()->filter("trav eq " . $trav);
        try {
            $customer = $query->Execute()->Result[0];
        } catch (\Throwable $e) {
            return;
        }

        $customer->addressVoieNo = $values['new_sal_no_adresse'];
        $customer->addressVoieTyp = $values['new_sal_type_voie'];
        $customer->addressVoieNom = $values['new_sal_nom_adresse'];
        $customer->addressBatiment = $values['new_sal_batiment'];
        $customer->addressEscalier = $values['new_sal_escalier'];
        $customer->addressEtage = $values['new_sal_etage'];
        $customer->addressPorte = $values['new_sal_porte'];
        $customer->adrCity = $values['new_sal_city_et_code_value'];
        $customer->addressCompl1 = $values['new_sal_complement_1'];
        $customer->addressCompl2 = $values['new_sal_complement_2'];
        $customer->ssn = $values['new_sal_numero_securite_social'];
        $customer->GSMADH = $values['new_sal_numero_tel_portable'];
        // $customer -> email =
        // $customer -> country = 0;
        // $customer -> fixePhone =
        // $customer -> gsmPhone =

        InternalFunctions::setupTraceInfos($customer);
        $svc->UpdateObject($customer);
        $svc->SaveChanges();
    }

    private function checkPersonHomonym(array &$form, FormStateInterface &$form_state, WebformSubmissionInterface &$webform_submission) {
        $values = $form_state->getValues();

        if (isset($_GET['activate'])) {
            $form_state['new_pers_multistep_values']['exact'] = trim($_GET['pers']);
            // if trav exists then redirect to person/trav details form
            $viewDetailPerson = views_get_view_result('v1_entr_travs', 'page_2', $form_state['multistep_values']['exact']);
            $travActive = 0;
            foreach ($viewDetailPerson as $valueTrav) {
                if ($valueTrav->IS_ACTIVE == 'Y')
                    $travActive = 1;
            }
            if ($viewDetailPerson && $travActive == 1) {
                // if DUE then set to Y
                if (isset($_GET['due'])) {
                    $svc = prepareWriteClass();
                    $query = $svc->DueStatus()->filter("id eq " . $_GET['due']);
                    $customer = $query->Execute()->Result[0];
                    $customer->status = 'Y';
                    setupTraceInfos($customer);
                    $svc->UpdateObject($customer);
                    $svc->SaveChanges();
                }
                //
                drupal_goto('trav-details/' . $viewDetailPerson[0]->TRAV_O_ID);
            }
        } else {
            $createdFilter = self::createFilter($values);
            if (!$createdFilter)
                return;
            $svc = GepsisOdataWriteClass::prepareWriteClass();
            $query = $svc->PersonHomonymes()->filter($createdFilter, TRUE);
            try {
                $resultQuery = $query->Execute()->Result;
            } catch (\Throwable $e) {
                return;
            }

            if (!empty($resultQuery)) { // found an exact or homonym person
                foreach ($resultQuery as $value) {
                    // if EXACT person save it and search for trav
                    if ($value->exact == 'Y') {
                        // https://www.drupal.org/node/2310411
                        $webform_submission->setData(array(
                            'new_pers_multistep_values' => array(
                                'exact' => $value->person
                            )
                        ));
                        // if trav exists then redirect to person/trav details form
                        $viewDetailPerson = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_TRAVS', "PERS_O_ID eq " . $value->person);
                        $travActive = null;
                        foreach ($viewDetailPerson as $valueTrav) {
                            if ($valueTrav->IS_ACTIVE == 'Y')
                                $travActive = $valueTrav->TRAV_O_ID;
                        }
                        if ($viewDetailPerson && $travActive) {
                            // if DUE then set to Y
                            if (isset($_GET['due'])) {
                                $svc = prepareWriteClass();
                                $query = $svc->DueStatus()->filter("id eq " . $_GET['due']);
                                $customer = $query->Execute()->Result[0];
                                $customer->status = 'Y';
                                setupTraceInfos($customer);
                                $svc->UpdateObject($customer);
                                $svc->SaveChanges();
                            }
                            //
                            return $travActive;
                        }
                    } else {
                        // if not exact then homonymes add to the select list for page 2
                        // @formatter:off
                        $stringValue = $value->code . ' - ' .
                            (!empty($value->maritalName) ? $value->maritalName : $value->lastName) .
                            (!empty($value->maritalName) ? ' ( ' . $value->lastName . ' ) ' : ' ') .
                            $value->firstName . ' - ' . $value->sex . ' - ' . substr($value->birthDate, 0, 10);
                        // @formatter:on
                        $webform_submission->setData(array(
                            'person_exists_select_list' => array(
                                $value->person => $stringValue
                            )
                        ));
                    }
                }
            }
        }
    }

    private function createFilter(&$values) {
        if (empty($values['new_sal_sexe']) || empty($values['new_sal_birth_date']) || empty($values['new_sal_nom_usage']) || empty($values['new_sal_first_name']))
            return NULL;
        // @formatter:off
        $returnFilter = "sex eq '" . $values['new_sal_sexe'] . "' and birthDate eq '" . $values['new_sal_birth_date'] . 'T00:00:00' .
            "' and lastName eq '" . urlencode(str_replace("'", "''", $values['new_sal_nom_usage'])) .
            "' and firstName eq '" . urlencode(str_replace("'", "''", $values['new_sal_first_name'])) . "'";
        // @formatter:on
        return $returnFilter;
    }

    private function tarbes_annuler_due($dueOid) {
        $svc = prepareWriteClass();
        $query = $svc->DueStatus()->filter("id eq " . $dueOid);
        $customer = $query->Execute()->Result[0];
        $customer->status = 'A';
        setupTraceInfos($customer);
        $svc->UpdateObject($customer);
        $svc->SaveChanges();
        drupal_goto('ursaf');
    }
}

