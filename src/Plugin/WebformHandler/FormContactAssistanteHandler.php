<?php

namespace Drupal\gepsis\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\gepsis\Controller\GepsisOdataReadClass;
use Drupal\gepsis\Controller\GepsisOdataWriteClass;
use Drupal\user\Entity\User;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\gepsis\Utility\GetAllFunctions;
use Drupal\gepsis\Utility\InternalFunctions;

/**
 * Create a new node entity from a webform submission.
 *
 * @WebformHandler(
 *   id = "Contact Assistante Form",
 *   label = @Translation("Contact Assistante form"),
 *   category = @Translation("Gepsis webform handler"),
 *   description = @Translation("Contact Assistante form"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class FormContactAssistanteHandler extends WebformHandlerBase {

    /**
     *
     * {@inheritdoc}
     */
    public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        $form['#disable_inline_form_errors'] = TRUE;
        $user = User::load(\Drupal::currentUser()->id());
        $adherentOid = $user->get('field_active_adherent_oid') -> value;
        $travOid = \Drupal::routeMatch()->getParameters()->get('travOid');
        if(empty($travOid))
            $travOid = \Drupal::request()->get('travOid');

        $formElements = InternalFunctions::getFlattenedForm($form['elements']);

        $viewDetailPerson = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_TRAVS', "TRAV_O_ID eq " . $travOid, TRUE);
        if(!empty($viewDetailPerson)) {

            if(!empty($viewDetailPerson)) {
                $bDate = \Drupal::service('date.formatter')->format(strtotime($viewDetailPerson -> PERS_BIRTH_DATE), 'html_date');
                $formElements['cont_assis_subject']['#disabled'] = TRUE;
                // @formatter:off
                $formElements['cont_assis_subject']['#default_value'] =
                'Message concernant le salarié ' . $viewDetailPerson -> PERS_NAME . ' ' . $viewDetailPerson -> PERS_FIRST_NAME .
                ' (' . $viewDetailPerson -> PERS_MARITAL_NAME . ')  né le ' . $bDate . ' (' . $viewDetailPerson -> TRAV_EMPL_TYPE_LABEL . ') ' .
                '.';
                // @formatter:on
            }
        }

        // hidden field email assitente also send to adherent himself
        $formElements['cont_assis_email']['#default_value'] = InternalFunctions::getEmailAssistante() . ',' . $user->getEmail();

        $viewEntr = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_INFO', "ENTR_O_ID eq " . $adherentOid, TRUE);
        $formElements['cont_assis_adherent']['#default_value'] = $viewEntr -> ENTR_CODE . ' - ' . $viewEntr -> ENTR_NOM;

        $formElements['cont_assis_message']['#title'] = $formElements['cont_assis_message']['#title'] . ' sera envoyé à: '.InternalFunctions::getEmailAssistante();
        /*
         * if($form_state['rebuild'] != TRUE)
         * $form_state['initialValues'] = _find_all_children_elements($form['submitted']);
         */
        // Disable caching
        $form['#cache']['max-age'] = 0;

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
        if($form_state->getTriggeringElement()['#id'] == 'edit-actions-retour-submit')
            return new RedirectResponse('v1-entr-travs');

        $data = $form_state->getUserInput();
        if(!empty($data['sal_numero_tel_portable']) && preg_match('/^[0-9]{10}$/m', $data['sal_numero_tel_portable']) === 0) {
            $form_state->setErrorByName('sal_numero_tel_portable', 'Le format de Mobile transmis par adh�rent n\'est pas respect�: %value.', [
                    '%value' => $data['sal_numero_tel_portable']
            ]);
        }

        if(!empty($data['sal_numero_securite_social'])) {
            $res = InternalFunctions::checkNumSecu($data['sal_numero_securite_social']);
            if(!$res) {
                \Drupal::logger('nss')->notice('NSS INVALID: ' . $data['sal_numero_securite_social']);
                $form_state->setErrorByName('sal_numero_securite_social', 'Le num�ro de s�curit� sociale n\'est pas valide ! <p><b>Vous pouvez laisser la zone vide et continuer la modification du salari�.</b>: %value</p>.', [
                        '%value' => $data['sal_numero_securite_social']
                ]);
                return;
            }
        }

        if(empty($data['sal_birth_date']))
            $form_state->setErrorByName('sal_birth_date', $this->t('La date de naissance n\'est pas complete'));
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
        if($form_state->getTriggeringElement()['#id'] == 'edit-actions-retour-submit')
            return new RedirectResponse('v1-entr-travs');
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
        $query = $svc->TravPersonAddress()->filter("trav eq " . $data['sal_trav_oid_details']);
        try {
            $customer = $query->Execute() -> Result[0];
        } catch ( \Throwable $e ) {
            return;
        }

        // person names
        // $customer -> personTitle = $data['sal_title'];
        // $customer -> sexe = $data['sal_sexe'];
        // $customer -> lastName = $data['sal_nom_usage'];
        // $customer -> firstName = $data['sal_first_name'];
        // $customer -> maritalName = $data['sal_marital_name'];
        $customer -> birthDate = $data['sal_birth_date'] . 'T00:00:00';
        $customer -> ssn = $data['sal_numero_securite_social'];
        // $customer -> GSMADH = $data['sal_numero_tel_portable'];
        // $customer -> email = $data['person_details_fieldset'];

        $vTyp = GetAllFunctions::getAllVoieTyp();
        $customer -> addressVoieNo = $data['sal_no_adresse'];
        $customer -> addressVoieNom = $data['sal_nom_adresse'];
        $customer -> addressVoieTyp = $vTyp[$data['sal_type_voie']];
        $customer -> addressBatiment = $data['sal_batiment'];
        $customer -> addressEscalier = $data['sal_escalier'];
        $customer -> addressEtage = $data['sal_etage'];
        $customer -> addressPorte = $data['sal_porte'];
        $customer -> addressCompl1 = $data['sal_complement_1'];
        $customer -> addressCompl2 = $data['sal_complement_2'];
        $customer -> adrCity = $data['sal_city_et_code_value'];
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
