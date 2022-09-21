<?php

namespace Drupal\gepsis\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\gepsis\Controller\GepsisOdataReadClass;
use Drupal\gepsis\Controller\GepsisOdataWriteClass;
use Drupal\gepsis\Utility\GetAllFunctions;
use Drupal\gepsis\Utility\InternalFunctions;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Create a new node entity from a webform submission.
 *
 * @WebformHandler(
 *   id = "Declaration Form",
 *   label = @Translation("Declaration Form"),
 *   category = @Translation("Gepsis webform handler"),
 *   description = @Translation("Prepopulate declaration form"),
 *   cardinality =
 *     \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *     results =
 *     \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *     submission =
 *     \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class FormDeclarationHandler extends WebformHandlerBase {

    /**
     *
     * {@inheritdoc}
     */
    public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        //$form['submitted']['adh_code_decla_2019']['#value'] = $account->field_adherent_code['und'][0]['value'];
        $account = User::load(\Drupal::currentUser()->id());
        $entreprise = $account->get('field_active_adherent_oid')->value;
        $formElements = InternalFunctions::getFlattenedForm($form['elements']);

        $adh = GepsisOdataReadClass::getOdataClassValues('V1_DECLA_PERIOD_ENT_GLOBAL_ENTS_FICTIF', "ENTREPRISE_O_ID eq " . $entreprise);
        if ($adh[0]->CODE == 'P') {
            return new RedirectResponse('content/déclaration-adhérent-principal');
        }

        $decla = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_DECLA', "ENTREPRISE eq " . $entreprise);
        // dpm($decla);
        if (empty($decla)) {
            return new RedirectResponse('content/pas-de-déclaration');
        }
        $owner = $decla[0]->O_ID;

        $annee = substr(trim($decla[0]->PERIODE), 0, 4);
        // list($annee, $trim) = explode("/", trim($decla[0]->PERIODE));
        $formElements['dec_an_titre1']['#text'] = str_replace('xxxx', $annee, $formElements['dec_an_titre1']['#text']);
        $formElements['dec_an_titre2']['#text'] = str_replace('xxxx', $annee - 1, $formElements['dec_an_titre2']['#text']);
        $formElements['dec_an_effectif_global']['#title'] = str_replace('xxxx', $annee - 1, $formElements['dec_an_effectif_global']['#title']);

        $translArray = [
            'FNCTRTOTUS' => 'dec_an_effectif_global',
        ];

        // drupal_add_js(drupal_get_path('module', 'tarbes') . '/js/calculateDeclaOstra.js');

        $declaLine = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_DECLA_LINE', "OWNER eq " . $owner . ' and ENTR_O_ID eq ' . $entreprise);
        if (empty($declaLine)) {
            return;
        }

        $lineValues = [];
        foreach ($declaLine as $key => $value) {
            $lineValues[$value->CODE] = [
                'value' => round((int) $value->LINE_VALUE),
                'o_id' => $value->O_ID,
            ];
        }

        $formElements['dec_an_effectif_global']['#default_value'] = $lineValues['FNCTRTOTUS']['value'];

        $form['actions']['rafraichir'] = [
            '#type' => 'submit',
            '#value' => t('Je rafraîchis ma déclaration'),
            '#access' => TRUE,
            '#submit' => [[$this, 'tarbes_person_details_rafraichir']],
            // '#limit_validation_errors' => array(),
            '#weight' => -50,
        ];

        if ($lineValues['FVALIDATED']['value'] == 1) {
            // return new RedirectResponse('content/déclaration-validée');
            unset($form['actions']);
        }

        $_SESSION['lineValues'] = $lineValues;
        $_SESSION['owner'] = $owner;
        $_SESSION['$translArray'] = $translArray;
        $_SESSION['annee'] = $annee;
        $_SESSION['entreprise'] = $entreprise;
    }

    public function tarbes_person_details_rafraichir(&$form, FormStateInterface $form_state) {
        $values = $form_state->getUserInput();
        $account = User::load(\Drupal::currentUser()->id());
        $entreprise = $_SESSION['entreprise'];
        $owner = $_SESSION['owner'];
        $annee = $_SESSION['annee'];

        $svc = GepsisOdataWriteClass::prepareWriteClass();
        $query = $svc->EntDeclaRefresh()->filter("id eq " . $owner);
        try {
            $customer = $query->Execute()->Result[0];

        } catch (\Throwable $e) {
            return;
        }

        $customer->entrepriseId = $entreprise;
        $customer->createLineInTravListeMisAJour = 'Y';
        InternalFunctions::setupTraceInfos($customer);
        $svc->UpdateObject($customer);
        $svc->SaveChanges();
        return new RedirectResponse('form/declaration-annuelle');
    }

    /**
     *
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        return;
        $form_state['webform_completed'] = TRUE;
        $data = &$form_state['values']['submitted'];

        $mntEff = intval(preg_replace('/[^0-9]/', "", $data['votre_effectif_total_est_de_decla_ostra_2018']));
        $mntDecla = intval(preg_replace('/[^0-9]/', "", $data['montant_decla_ostra_2018']));

        // @formatter:off
        if (/* (!empty($mntDecla) && !is_numeric($mntDecla))|| */
        (!empty($mntEff) && !is_numeric($mntEff))) {
            form_error($form, t('Les valeurs introduites devraient etres numeriques.'));
            return;
        }

        if ($mntEff <= 0 /*|| ($form_state['declaCode'] == 0  && $mntDecla <= 0 )*/) {
            form_error($form, t('Les valeurs introduites devraient etre plus grandes ou égal à 0'));
            return;
        }
        // @formatter:on

        if ($form_state['declaCode'] == 0) {
            $varDiv = $mntDecla / $mntEff;
            // dpm('varDiv (1): ' . $varDiv);
            if ($varDiv < 10000 || $varDiv > 40000) {
                $form_state['values']['submitted']['show_page_2_collecte'] = 1;
            }
        }

        $form_state['webform_completed'] = TRUE;
    }

    /**
     *
     * {@inheritdoc}
     */

    // Function to be fired after submitting the Webform.
    public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
        $values = $webform_submission->getData();
        // $elements = $webform_submission->getWebform()->getElementsDecoded();
        $account = User::load(\Drupal::currentUser()->id());
        $entreprise = $_SESSION['entreprise'];
        $lineValues = $_SESSION['lineValues'];
        $translArray = $_SESSION['$translArray'];
        $owner = $_SESSION['owner'];
        $annee = $_SESSION['annee'];

        $fvalidatedOid = $lineValues['FVALIDATED']['o_id'];
        $fvalidatedVal = 1;

        $fndtemajportOid = $lineValues['FNDTEMAJPORT']['o_id'];
        $fndtemajportVal = date('d/m/Y');

        $fnquiportOid = $lineValues['FNQUIPORT']['o_id'];
        $fnquiportVal = $account->get('name')->value;

        $fnBrefValidOid = $lineValues['FNCTRTOTUS']['o_id'];
        $fnBrefValidVal = intval(preg_replace('/[^0-9]/', "", $values[$translArray['FNCTRTOTUS']]));

        $svc = GepsisOdataWriteClass::prepareWriteClass();
        $query = $svc->EntDeclaLine()->filter("id eq " . $fnBrefValidOid);
        try {
            $customer = $query->Execute()->Result[0];

        } catch (\Throwable $e) {
            return;
        }

        $customer->lineValue = $fnBrefValidVal;
        // @formatter:off
        $customer->lineOtherLineValueString = $fvalidatedOid . ',' . $fvalidatedVal . ',' . $fndtemajportOid . ',' .
            $fndtemajportVal . ',' . $fnquiportOid . ',' . $fnquiportVal;
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
