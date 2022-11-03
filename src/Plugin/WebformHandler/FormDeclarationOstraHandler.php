<?php

namespace Drupal\gepsis\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\webform\Annotation\WebformHandler;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\gepsis\Controller\GepsisOdataReadClass;
use Drupal\gepsis\Controller\GepsisOdataWriteClass;
use Drupal\gepsis\Utility\GetAllFunctions;
use Drupal\gepsis\Utility\InternalFunctions;
use odataPhp\Context\SaveChangesOptions;
use odataPhp\Exception\DataServiceRequestException;
use odataPhp\Exception\InvalidOperation;
use odataPhp\Exception\ODataServiceException;
use odataPhp\GepsFranceEntities;
use odataPhp\GepsFranceWriteEntities;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Create a new node entity from a webform submission.
 *
 * @WebformHandler(
 *   id = "Declaration Ostra Form",
 *   label = @Translation("Declaration Ostra Form"),
 *   category = @Translation("Gepsis webform handler"),
 *   description = @Translation("Prepopulate declaration Ostra form"),
 *   cardinality =
 *     \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *     results =
 *     \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *     submission =
 *     \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class FormDeclarationOstraHandler extends WebformHandlerBase
{

    /**
     * class, filter, count = true for single result
     */
    public static function getOdataClassValues($class, $filter = null, $count = null) {
        $result = null;
        $lnk = 'http://192.168.69.8:8083/gportalgateway/GepsFrance/GepsFrance.svc';

        // netsh interface portproxy add v4tov4 listenaddress=192.168.69.69 listenport=8083 connectaddress=10.22.31.12 connectport=8083
        // netsh interface portproxy delete v4tov4 listenaddress=127.0.0.1 listenport=8083
        // http://10.22.31.12:8083/gportalgateway/GepsFrance/GepsFrance.svc
        // $filter =  'ENTREPRISE_O_ID eq 4260461000';

        try {
            $svc = new GepsFranceEntities($lnk);
            $query = $filter ? $svc->$class()->filter($filter) : $svc->$class();
            $result = $count ? $query->Top($count)->Execute()->Result : $query->Execute()->Result;
        } catch (\Throwable $e) { // Use Throwable instead of Exception here
            \Drupal::logger('gepsis')->error('Error getOdataClassValues read: ' . $e->getMessage());
            return;
        } catch (ODataServiceException $e) {
            drupal_set_message("Error:   " . $e->getError() . "<br>" . "Detailed Error:" . $e/* -> getDetailedError()*/, 'error');
        } catch (DataServiceRequestException $e) {
            drupal_set_message($e->Response->getError(), 'error');
        } catch (InvalidOperation $e) {
            drupal_set_message($e->getError(), 'error');
        }
        if ($result)
            return $count == 1 ? $result[0] : $result;
        return FALSE;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        //$form['submitted']['adh_code_decla_2019']['#value'] = $account->field_adherent_code['und'][0]['value'];
        $account = User::load(\Drupal::currentUser()->id());
        $entreprise = $account->get('field_active_adherent_oid')->value;
        $entreprise = '4260461000';
        $formElements = InternalFunctions::getFlattenedForm($form['elements']);

        $storage = $form_state->getStorage(); // $form_state['storage'],  you can later access on the next steps.
        $page = $storage['current_page'];
        switch ($page) {
            case 'webform_start' :
                // check if decla for parent or child
                $adh = self::getOdataClassValues('V1_DECLA_PERIOD_ENT_GLOBAL_ENTS_FICTIF', "ENTREPRISE_O_ID eq " . $entreprise);
                if ($adh[0]->CODE == 'P') {
                    return new RedirectResponse('content/déclaration-adhérent-principal');
                }

                // check if decla dispo
                $decla = self::getOdataClassValues('V1_ENTR_DECLA', "ENTREPRISE eq " . $entreprise);
                if (empty($decla)) {
                    return new RedirectResponse('content/pas-de-déclaration');
                }

                $owner = $decla[0]->O_ID; // get owner
                $annee = substr(trim($decla[0]->PERIODE), 0, 4); // get year

                // remplace text w<ith year of decla
                $formElements['dec_an_titre1']['#text'] = str_replace('xxxx', $annee, $formElements['dec_an_titre1']['#text']);
                $formElements['dec_an_titre2']['#text'] = str_replace('xxxx', $annee - 1, $formElements['dec_an_titre2']['#text']);
                // $formElements['dec_an_effectif_global']['#title'] = str_replace('xxxx', $annee - 1, $formElements['dec_an_effectif_global']['#title']);

                $translArray = array(
                    'FN21EFFCO' => 'effectif_eff_hors_contrat_ccda_decla_xxxx',
                    'FN21APRCO' => 'total_apr_collecte_decla_xxxx',
                    'FN21MTEFF' => 'montant_eff_htva_decla_xxxx',
                    'FN21MTAPR' => 'montant_apr_htva_decla_xxxx',
                    'FN21TOTHTVA' => 'montant_total_hors_tva_decla_xxxx',
                    'FN21TVA' => 'montant_tva_decla_decla_xxxx',
                    'FN21TOTTTC' => 'montant_total_de_la_facture_decla_xxxx'
                );

                // add js and css
                $form['#attached']['library'][] = 'gepsis/calculateDeclaOstra';

                // get all values of decla and put in array. do some rounds
                $declaLine = self::getOdataClassValues('V1_ENTR_DECLA_LINE', "OWNER eq " . $owner . ' and ENTR_O_ID eq ' . $entreprise);
                if (empty($declaLine))
                    return;

                $lineValues = array();
                foreach ($declaLine as $key => $value) {
                    if ($value->CODE == 'FNQUIPORT' || $value->CODE == 'FNDTEMAJPORT')
                        $vl = $value->LINE_VALUE_RAW;
                    else if ($value->CODE != 'FNOTEFF' && $value->CODE != 'FNOTAPR')
                        $vl = round((float)$value->LINE_VALUE);
                    else
                        $vl = $value->LINE_VALUE;

                    $lineValues[$value->CODE] = array(
                        'value' => $vl,
                        'o_id' => $value->O_ID
                    );
                }

                foreach ($translArray as $key => $value) {
                    $formElements[$value]['#default_value'] = $lineValues[$key]['value'];
                    if ($lineValues['FVALIDATED']['value'] == 1)
                        $formElements[$value]['#disabled'] = TRUE;
                }

                if (!empty($lineValues['FNOAPRDC']['value']))
                    $formElements['total_apr_collecte_decla_xxxx']['#default_value'] = $lineValues['FNOAPRDC']['value'];
                if (!empty($lineValues['FNOEFFDC']['value']))
                    $formElements['effectif_eff_hors_contrat_ccda_decla_xxxx']['#default_value'] = $lineValues['FNOEFFDC']['value'];
                if (!empty($lineValues['FNOTEFF']['value']))
                    $formElements['tarif_unitaire_hors_tva_toteff_decla_xxxx']['#default_value'] = $lineValues['FNOTEFF']['value'];
                if (!empty($lineValues['FNOTAPR']['value']))
                    $formElements['tarif_unitaire_hors_tva_totapr_decla_xxxx']['#default_value'] = $lineValues['FNOTAPR']['value'];

                $form['actions']['miseajour'] = array(
                    '#type' => 'submit',
                    '#value' => t('Je vérifie / modifie mon effectif'),
                    '#access' => TRUE,
                    '#submit' => [[$this, 'gportal_ostra_decla_goto']],
                    '#validate' => [
                        '::noValidate'
                    ],
                    // '#limit_validation_errors' => array(),
                    '#weight' => -50
                );

                $form['actions']['rafraichir'] = array(
                    '#type' => 'submit',
                    '#value' => t('Je rafraîchis ma déclaration'),
                    '#access' => TRUE,
                    '#submit' => [[$this, 'gportal_ostra_decla_details_rafraichir']],
                    '#validate' => [
                        '::noValidate'
                    ],
                    // '#limit_validation_errors' => array(),
                    '#weight' => -50,
                );

                // $lineValues['FVALIDATED']['value'] = 1; // TEST

                // if validated the stop to modif
                if ($lineValues['FVALIDATED']['value'] == 1) {
                    unset($form['actions']);
                }

                // if validated print message
                if ($lineValues['FVALIDATED']['value'] == 1) {
                    unset($form['actions']);
                    $messToPrint = $formElements['message_decla_submitted_user_decla_xxxx']['#text'];
                    $quiUser = $lineValues['FNQUIPORT']['value'] ? $lineValues['FNQUIPORT']['value'] : 'Test USer';
                    $quandUser = $lineValues['FNDTEMAJPORT']['value'] ? $lineValues['FNDTEMAJPORT']['value'] : '99/99/9999';
                    $messToPrint = str_replace('xxxxxxx', $quiUser, $messToPrint);
                    $messToPrint = str_replace('yyyyyyy', $quandUser, $messToPrint);
                    $formElements['message_decla_submitted_user_decla_xxxx']['#text'] = $messToPrint;
                } else
                    unset($form['elements']['message_decla_submitted_user_decla_xxxx']);

                // save values for latter use
                $_SESSION['lineValues'] = $lineValues;
                $_SESSION['owner'] = $owner;
                $_SESSION['$translArray'] = $translArray;
                $_SESSION['annee'] = $annee;
                $_SESSION['entreprise'] = $entreprise;

                break;

            case 'webform_preview' :
                // dpm($form['actions']);
                break;

        }

        // css to boutons
        $form['actions']['submit']['#attributes']['class'][] = 'declaOstraClassSubmit';
        $form['actions']['next']['#attributes']['class'][] = 'declaOstraClassSubmit';
        $form['actions']['previous']['#attributes']['class'][] = 'declaOstraClassSubmit';
        $form['actions']['rafraichir']['#attributes']['class'][] = 'declaOstraClassSubmit';
        $form['actions']['miseajour']['#attributes']['class'][] = 'declaOstraClassSubmit';
        $form['actions']['preview_next']['#attributes']['class'][] = 'declaOstraClassSubmit';
        $form['actions']['preview_prev']['#attributes']['class'][] = 'declaOstraClassSubmit';


    }

    public function gportal_ostra_decla_goto(&$form, FormStateInterface $form_state) {
        $form_state->setRedirect('view.v1_entr_travs.page_1');
        return;
    }

    public function gportal_ostra_decla_details_rafraichir(&$form, FormStateInterface $form_state) {
        $values = $form_state->getUserInput();
        $account = User::load(\Drupal::currentUser()->id());
        $entreprise = $_SESSION['entreprise'];
        $owner = $_SESSION['owner'];
        $annee = $_SESSION['annee'];

        $lnk = 'http://192.168.69.8:8083/gportalgateway/GepsFranceWrite/GepsFrance.svc';


        try {
            $svc = new GepsFranceWriteEntities($lnk);
            $svc->SetSaveChangesOptions(SaveChangesOptions::None);
            $svc -> UsePostTunneling = TRUE;
            $query = $svc->EntDeclaRefresh()->filter("id eq " . $owner);
            $customer = $query->Execute()->Result[0];
        } catch (\Throwable $e) {
            return;
        }

        $customer->entrepriseId = $entreprise;
        $customer->createLineInTravListeMisAJour = 'Y';
        InternalFunctions::setupTraceInfos($customer);
        $svc->UpdateObject($customer);
        $svc->SaveChanges();
        return new RedirectResponse('form/declaration-annuelle-ostra');
    }

    /**
     *
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        // $data = &$form_state->getUserInput();
        $data = $webform_submission->getData();
        $translArray = $_SESSION['$translArray'];

        $mntEff = intval(preg_replace('/[^0-9]/', "", $data[$translArray['FN21APRCO']]));
        $mntDecla = intval(preg_replace('/[^0-9]/', "", $data[$translArray['FN21EFFCO']]));

        // @formatter:off
        if ((!empty($mntDecla) && !is_numeric($mntDecla)) ||
            (!empty($mntEff) && !is_numeric($mntEff))) {
            $form_state->setErrorByName('poste_date_desactivation', 'Les valeurs introduites devraient etres numeriques !');
            return;
        }

        if ($mntEff <= 0 && $mntDecla <= 0) {
            $form_state->setErrorByName('poste_date_desactivation', 'Les valeurs introduites devraient etre plus grandes ou égal à 0 !');
            return;
        }
        // @formatter:on
    }

    /**
     *
     * {@inheritdoc}
     */

    // Function to be fired after submitting the Webform.
    public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
        $data = $webform_submission->getData();
        // $elements = $webform_submission->getWebform()->getElementsDecoded();
        $account = User::load(\Drupal::currentUser()->id());
        $entreprise = $_SESSION['entreprise'];
        $lineValues = $_SESSION['lineValues'];
        $translArray = $_SESSION['$translArray'];
        $owner = $_SESSION['owner'];
        $annee = $_SESSION['annee'];

        $fn21aprcoOid = $lineValues['FNOAPRDC']['o_id'];
        $fn21aprcoVal = intval(preg_replace('/[^0-9]/', "", $data[$translArray['FN21APRCO']]));

        $fvalidatedOid = $lineValues['FVALIDATED']['o_id'];
        $fvalidatedVal = 1;

        $fndtemajportOid = $lineValues['FNDTEMAJPORT']['o_id'];
        $fndtemajportVal = date('d/m/Y');

        $fnquiportOid = $lineValues['FNQUIPORT']['o_id'];
        $fnquiportVal = $account->getAccountName();

        $fnBrefValidOid = $lineValues['FNOEFFDC']['o_id'];
        $fnBrefValidVal = intval(preg_replace('/[^0-9]/', "", $data[$translArray['FN21EFFCO']]));


        $lnk = 'http://192.168.69.8:8083/gportalgateway/GepsFranceWrite/GepsFrance.svc';
        try {
            $svc = new GepsFranceWriteEntities($lnk);
            $svc->SetSaveChangesOptions(SaveChangesOptions::None);
            $svc -> UsePostTunneling = TRUE;
            $query = $svc->EntDeclaLine()->Filter("id eq " . $fnBrefValidOid);
            $customer = $query->Execute()->Result[0];
        } catch (\Throwable $e) {
            return;
        }
        $customer->lineValue = $fnBrefValidVal;
        // @formatter:off
        $customer->lineOtherLineValueString = $fvalidatedOid . ',' . $fvalidatedVal . ',' . $fndtemajportOid . ',' .
            $fndtemajportVal . ',' . $fnquiportOid . ',' . $fnquiportVal . ',' . $fn21aprcoOid . ',' . $fn21aprcoVal;
        // @formatter:on
        $customer->lineValueDate = NULL;
        $customer->lineValueTime = NULL;
        $customer->lineValueString = NULL;
        $customer->owner = $owner;
        InternalFunctions::setupTraceInfos($customer);
        $svc->UpdateObject($customer);
        $svc->SaveChanges();
    }
}
