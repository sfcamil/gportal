<?php

namespace Drupal\gepsis\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Create a new node entity from a webform submission.
 *
 * @WebformHandler(
 *   id = "Change User Actif Adherent Form",
 *   label = @Translation("Change adherent actif for user"),
 *   category = @Translation("Gepsis webform handler"),
 *   description = @Translation("Changer l'adherent actif pour un utilisateur"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class FormChangeActifAdherentHandler extends WebformHandlerBase {

    /**
     *
     * {@inheritdoc}
     */
    public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        // hide submit bouton on change adherent actif. Auto submit on change
        $form['elements']['adherent_actif_container']['adherent_actif']['#options'] = null;
        $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        $fields_adherents = $user->get('field_adherents')->getValue();
        foreach($fields_adherents as $item) {
            $paragraph = Paragraph::load($item['target_id']);
            if($paragraph->getType() === 'field_adherents' && !$paragraph -> field_adherent_oid->isEmpty()) {
                $adherentOid = $paragraph->get('field_adherent_oid')->first()->getValue();
                $adherentCode = $paragraph->get('field_code_adherent')->first()->getValue();
                $adherentName = $paragraph->get('field_nom_adherent')->first()->getValue();
                $form['elements']['adherent_actif_container']['adherent_actif']['#options'][$adherentOid['value']] = $adherentCode['value'] . ' - ' . $adherentName['value'];
            }
        }
        // $form['#attached']['library'][] = 'gepsis/custom-style';
        //$form['elements']['adherent_actif_container']['#attributes']['class'][] = 'webform-container-inline';

        unset($form['elements']['adherent_actif_container']['adherent_actif']['#empty_option']);
        $actifAdh = $user->get('field_active_adherent_oid') -> value;
        $form['elements']['adherent_actif_container']['adherent_actif']['#value'] = $actifAdh;
        $form['elements']['adherent_actif_container']['adherent_actif']['#attributes'] = array(
                'onchange' => 'this.form.submit();'
        );
        // $form['elements']['adherent_actif']['#attributes']['class'][] = 'container-inline';
        $form['actions']['submit']['#attributes']['class'][] = 'visually-hidden';
    }

    /**
     *
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        $userInput = $form_state->getUserInput();
        $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());

        if(!$userInput['adherent_actif'] || empty($userInput['adherent_actif'])) {
            $userInput['adherent_actif'] = $user->get('field_active_adherent_oid') -> value;
        }
    }

    /**
     *
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        $userInput = $form_state->getUserInput();
        $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        $fields_adherents = $user->get('field_adherents')->getValue();
        foreach($fields_adherents as $item) {
            $paragraph = Paragraph::load($item['target_id']);
            if($paragraph->getType() === 'field_adherents' && !$paragraph -> field_adherent_oid->isEmpty()) {
                $adherentOid = $paragraph->get('field_adherent_oid')->first()->getValue();
                $adherentCode = $paragraph->get('field_code_adherent')->first()->getValue();
                $adherentName = $paragraph->get('field_nom_adherent')->first()->getValue();
                if($adherentOid['value'] == $userInput['adherent_actif']) {
                    $user->set('field_active_adherent_oid', $adherentOid);
                    $user->set('field_active_adherent_code', $adherentCode);
                    $user->set('field_active_adherent_name', $adherentName);
                    $user->save();
                    // Logs a notice
                    \Drupal::logger('gepsis')->notice('User ' . $user->getAccountName() . ' change to adhérent ' . $adherentCode['value'] . ' - ' . $adherentName['value']);
                }
            }
        }
        // redirect if the curent page is not a view
        // $current_path = \Drupal::service('path.current')->getPath();
        // $alias = \Drupal::service('path_alias.manager')->getAliasByPath($current_path);
        $current_route_name = \Drupal::service('current_route_match')->getRouteName();
        $myViews = array(
                'v1_entr_meetings',
                'v1_entr_adresses',
                'v1_entr_adherent_contact',
                'v1_entr_facturation',
                'v1_entr_meetings',
                'v1_entr_travs',
            'documents',
            'page_manager.page_view_rendez_vous_rendez_vous-layout_builder-0'
        );
        $redirect = TRUE;
        foreach($myViews as $value) {
            if(strpos($current_route_name, $value) == TRUE) {
                $redirect = FALSE;
                break;
            }
        }
        if($redirect == TRUE)
            $form_state->setRedirect('<front>');
    }
}


