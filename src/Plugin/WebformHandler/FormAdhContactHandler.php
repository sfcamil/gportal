<?php

namespace Drupal\gepsis\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\gepsis\Controller\GepsisOdataReadClass;
use Drupal\gepsis\Controller\GepsisOdataWriteClass;
use Drupal\gepsis\Controller\UserLoginLogoutUtilities;
use Drupal\user\Entity\User;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\gepsis\Utility\GetAllFunctions;
use Drupal\gepsis\Utility\InternalFunctions;
use odataPhp\EntAddress;
use odataPhp\EntContact;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Create a new node entity from a webform submission.
 *
 * @WebformHandler(
 *   id = "Modifier Adherent Contact Form",
 *   label = @Translation("Modify adherent contact form"),
 *   category = @Translation("My webform handler"),
 *   description = @Translation("Modifier Adherent Contact Form"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class FormAdhContactHandler extends WebformHandlerBase
{

    /**
     *
     * {@inheritdoc}
     */
    public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        $user = User::load(\Drupal::currentUser()->id());
        $adherentOid = $user->get('field_active_adherent_oid')->value;
        $contactOid = \Drupal::request()->get('idContact');

        $formElements = InternalFunctions::getFlattenedForm($form['elements']);

        // message if last contact cannot be deleted
        $formElements['contact_general_delete']['#access'] = FALSE;

        // populate titres
        $allTitres = GetAllFunctions::getAllTitres();
        $formElements['contact_title']['#options'] = $allTitres;

        // valeurs des champs de selection
        $listeContactTypes = GetAllFunctions::getAllTypesRolesLabel();
        $formElements['contact_type']['#options'] = $listeContactTypes;
        unset($formElements['contact_type']['#options'][array_search('Newsletter', $formElements['contact_type']['#options'])]); // remove Newsletter

        // populate list types addresses and unset if is already used
        $allTAdresses = GetAllFunctions::getAllTypesAdresses();
        $allAdhTAdresses = GetAllFunctions::getAllAdherentOidTypesAdressesWithType($adherentOid);
        $diffArray = array_diff_key($allTAdresses, $allAdhTAdresses);
        $formElements['contact_type_adresse']['#options'] = $diffArray;

        // valeurs des champs de selection
        $formElements['adrss_type_adresse']['#options'] = GetAllFunctions::getAllTypesAdresses();
        // unset($formElements['contact_type_adresse']['#options'][21179]); // Siege social
        // unset($formElements['contact_type_adresse']['#options'][21184]); // Adresse exploitation
        $formElements['contact_adrss_country']['#options'] = GetAllFunctions::getAllCountries();
        $formElements['contact_adrss_country']['#default_value'] = 21224; // force to France
        $formElements['contact_adrss_country']['#disabled'] = TRUE;
        $formElements['contact_type_voie']['#options'] = GetAllFunctions::getAllVoieTyp();

        // populate address liees lista, concatenate to not lost insert 'create new'
        $getAllAdherentOidTypeAdressesTmp = GetAllFunctions::getAllAdherentOidAdresses($adherentOid);
        $formElements['contact_adresse']['#options'] = $formElements['contact_adresse']['#options'] + $getAllAdherentOidTypeAdressesTmp;

        // autocomplete city field
        $form['#attached']['library'][] = 'gepsis/gepsis.replace-autocomplete';
        $formElements['contact_city_et_code']['#attributes']['class'] = array(
            'replaceAutocomplete'
        );
        $formElements['contact_city_et_code']['#autocomplete_route_name'] = 'gepsis.autocomplete';
        $formElements['contact_city_et_code']['#autocomplete_route_parameters'] = array(
            'key' => 'CITY_O_ID',
            'class' => 'V1_ALL_CITYES',
            'searchValues' => array(
                'CITY_CODE',
                'CITY_LABEL'
            )
        );

        if ($contactOid) {
            $contact = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_ADHERENT_CONTACT', "CONTACT_O_ID eq " . $contactOid);
            $contactRoles = array();
            foreach ($contact as $value) {
                $contactRoles[] = array_search($value->ROLE_LABEL, $listeContactTypes);
            }
            $contact = $contact[0];
            $formElements['contact_type']['#default_value'] = $contactRoles;
            $formElements['contact_title']['#default_value'] = array_search($contact->PERS_TITRE, $formElements['contact_title']['#options']);
            $formElements['contact_fonction']['#default_value'] = $contact->FONCTION;
            $formElements['contact_last_name']['#default_value'] = $contact->PERS_NAME;
            $formElements['contact_first_name']['#default_value'] = $contact->PERS_FIRST_NAME;
            $formElements['contact_email']['#default_value'] = $contact->E_MAIL;
            $formElements['contact_telephone_portable']['#default_value'] = $contact->PHONE;
            $formElements['contact_adresse']['#default_value'] = $contact->ADR_O_ID;

            // hidden values
            $formElements['contact_adherent_oid_details']['#default_value'] = $contact->CONTACT_O_ID;
            $formElements['contact_adh_code']['#default_value'] = $contact->ROLE_CODE;

            // minimum one contact general ! deactivate delete if just one contact general
            $contactGenOid = array_search(strtolower('Général'), array_map('strtolower', $listeContactTypes));
            $viewDetailGenContact = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_ADHERENT_CONTACT', "ENTR_O_ID eq " . $adherentOid . " and ROLE_CODE eq 'GEN'");
            if (count($viewDetailGenContact) > 1 || (count($viewDetailGenContact) <= 1 && !in_array($contactGenOid, $contactRoles))) {
                $form['actions']['delete'] = array(
                    '#type' => 'submit',
                    '#value' => 'Suppression compléte de ce contact',
                    '#validate' => array(
                        [
                            $this,
                            'gportal_adherent_contacts_delete_validate'
                        ]
                    ),
                    '#submit' => array(
                        [
                            $this,
                            'gportal_adherent_contacts_delete_delete'
                        ]
                    ),
                    '#limit_validation_errors' => array(),
                    '#weight' => 100
                );
            } else if (count($viewDetailGenContact) <= 1 && in_array($contactGenOid, $contactRoles)) {
                // message if last contact cannot be deleted
                $formElements['contact_general_delete']['#access'] = TRUE;
                $formElements['contact_general_delete']['#text'] = '<p><strong><span style="color:#FF0000">' . 'Vous ne pouvez pas effacer le dernier contact général !' . '</span></strong></p>';
                $formElements['contact_type'][$contactGenOid]['#attributes']['disabled'] = TRUE;
                $formElements['contact_type'][$contactGenOid]['#value'] = $contactGenOid;
            }
        } else {
            $form['#title'] = $this->t('Nouveau contact');
            // if not facturation contact then select by fdefault
            if (empty($_SESSION['factContactEmail']) || count($_SESSION['factContactEmail']) == 0) {
                $cleFact = array_search('Facturation', $listeContactTypes);
                $formElements['contact_type']['#default_value'][] = $cleFact;
            }
        }

        $formElements['contact_fire_email']['#default_value'] = InternalFunctions::getEmailAssistante();

        // Disable caching
        $form['#cache']['max-age'] = 0;
        // $form['#tree'] = TRUE; // When this is set to false, the submit method gets no results through getValues().
    }

    /**
     *
     * {@inheritdoc}
     */
    public function gportal_adherent_contacts_delete_validate(array &$form, FormStateInterface $form_state) {
        $formElements = InternalFunctions::getFlattenedForm($form['elements']);
    }

    /**
     *
     * {@inheritdoc}
     */
    public function gportal_adherent_contacts_delete_delete(array &$form, FormStateInterface $form_state) {
        $data = $form_state->getUserInput();

        $svc = GepsisOdataWriteClass::prepareWriteClass();
        $query = $svc->EntContact()->filter("id eq " . $data['contact_adherent_oid_details']);
        try {
            $customer = $query->Execute()->Result[0];
        } catch (\Throwable $e) {
            return;
        }
        $svc->UsePostTunneling = FALSE;
        $svc->DeleteObject($customer);
        InternalFunctions::setupTraceInfos($customer);
         $svc->SaveChanges();
        // return new RedirectResponse('/adherent#lb-tabs-tabs-2');
        $redirect = new RedirectResponse(Url::fromUserInput('/adherent#lb-tabs-tabs-2')->toString());
        $redirect->send();
        // TODO
        // checkAndSetSessFactEmail();
    }

    /**
     *
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        $data = $webform_submission->getData();
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

    /**
     *
     * {@inheritdoc}
     */

    // Function to be fired after submitting the Webform.
    public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
        $data = $webform_submission->getData();
        $user = User::load(\Drupal::currentUser()->id());
        $adherentOid = $user->get('field_active_adherent_oid')->value;

        $svc = GepsisOdataWriteClass::prepareWriteClass();
        if (!empty($data['contact_city_et_code_value'])) {
            $customerAdress = EntAddress::CreateEntAddress(null);
            self::setContactAdressValues($customerAdress, $data);
            InternalFunctions::setupTraceInfos($customerAdress);
            $customerAdress->entreprise = $adherentOid;
            $svc->AddToEntAddress($customerAdress);
            $svc->SaveChanges();
            $data['contact_adresse'] = $customerAdress->id; // save inserted address oid to this contact
        }
        if (!empty($data['contact_adherent_oid_details'])) { // UPDATE
            $query = $svc->EntContact()->filter("id eq " . $data['contact_adherent_oid_details']);
            try {
                $customer = $query->Execute()->Result[0];
            } catch (\Throwable $e) {
                return;
            }
            self::setAdherentContactsValues($customer, $data);
            InternalFunctions::setupTraceInfos($customer);
            $svc->UpdateObject($customer);
        } else { // INSERT
            $customer = EntContact::CreateEntContact(null);
            self::setAdherentContactsValues($customer, $data);
            InternalFunctions::setupTraceInfos($customer);
            $customer->entreprise = $adherentOid;
            $svc->AddToEntContact($customer);
        }
        $svc->SaveChanges();
        UserLoginLogoutUtilities::checkAndSetSessFactEmail($user);
        return new RedirectResponse('/adherent#lb-tabs-tabs-2');
    }

    private function setContactAdressValues(&$customer, $data) {
        $customer->type = $data['contact_type_adresse'];
        $customer->country = $data['contact_adrss_country'];
        $customer->city = $data['contact_city_et_code_value'];

        $customer->VOIE_NO = $data['contact_no_adresse'];
        $customer->VOIE_TYP = $data['contact_type_voie'];
        $customer->VOIE_NOM = $data['contact_nom_adresse'];

        $customer->BATIMENT = $data['contact_batiment'];
        $customer->ESCALIER = $data['contact_escalier'];
        $customer->ETAGE = $data['contact_etage'];
        $customer->PORTE = $data['contact_porte'];

        $customer->COMPL1 = $data['contact_complement_1'];
        $customer->COMPL2 = $data['contact_complement_2'];
    }

    private function setAdherentContactsValues(&$customer, &$data) {
        $data['contact_type'] = array_filter($data['contact_type']); // delete empty values from array if any
        $customer->title = $data['contact_title'];
        $customer->roles = implode(';', $data['contact_type']);
        $customer->name = htmlspecialchars($data['contact_last_name']);
        $customer->firstName = htmlspecialchars($data['contact_first_name']);
        $customer->fonction = htmlspecialchars($data['contact_fonction']);
        $customer->email = $data['contact_email'];
        $customer->phone = $data['contact_telephone_portable'];
        $customer->address = $data['contact_adresse'] ?: $data['contact_adresse'];
    }
}
