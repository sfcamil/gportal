<?php

namespace Drupal\gepsis\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\gepsis\Controller\GepsisOdataReadClass;
use Drupal\gepsis\Utility\InternalFunctions;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Exception;

/**
 * Create a new node entity from a webform submission.
 *
 * @WebformHandler(
 *   id = "Associate User Adherent Form",
 *   label = @Translation("Associate adherent form"),
 *   category = @Translation("Gepsis webform handler"),
 *   description = @Translation("Associer un adherent a un utilisateur portail"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class FormAssocAdhHandler extends WebformHandlerBase
{

    /**
     *
     * {@inheritdoc}
     */
    public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        // $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        // $adherentOid = $user->get('field_active_adherent_oid') -> value;

        $user_names = InternalFunctions::getAllNamePortailUsers();
        $form['elements']['utilisateur']['#options'] = $user_names;
        // $form['elements']['utilisateur']['#empty_option'] = t('Select user');

        $form['#attached']['library'][] = 'gepsis/gepsis.replace-autocomplete';
        $form['elements']['selectionner_un_adherent']['#attributes']['class'] = array(
            'replaceAutocomplete'
        );
        $form['elements']['selectionner_un_adherent']['#autocomplete_route_name'] = 'gepsis.autocomplete.adherents';

        // field value
        $form['elements']['selectionner_un_adherent_value'] = array(
            '#type' => 'hidden'
        );

        // Disable caching
        $form['#cache']['max-age'] = 0;
        // $form['#tree'] = TRUE; // When this is set to false, the submit method gets no results through getValues().
    }

    /**
     *
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        $userInput = $form_state->getUserInput();
        if (!$userInput['utilisateur'] || empty($userInput['selectionner_un_adherent_value']) && empty($userInput['importer_un_fichier']['fids'])) {
            $form_state->setErrorByName($form_state->getValue('selectionner_un_adherent'), t('Utilisateur et adherent est obligatoire.'));
        }

        // check if allready associated
        $user = \Drupal\user\Entity\User::load($userInput['utilisateur']);
        $fields_adherents = $user->get('field_adherents')->getValue();
        foreach ($fields_adherents as $item) {
            $paragraph = Paragraph::load($item['target_id']);
            if ($paragraph->getType() === 'field_adherents' && !$paragraph->field_adherent_oid->isEmpty()) {
                if ($paragraph->field_adherent_oid->value == $userInput['selectionner_un_adherent_value'])
                    $form_state->setErrorByName($form_state->getValue('selectionner_un_adherent_value'), t('Allready associated to this adherent.'));
            }
        }
        return;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        $userInput = $form_state->getUserInput();

        $svc = GepsisOdataReadClass::prepareReadClass();
        $query = $svc->V1_ENTR_INFO()->filter("ENTR_O_ID eq " . $userInput['selectionner_un_adherent_value']);
        try {
            $result = $query->Execute()->Result[0];
        } catch (Exception $e) {
            return;
        }

        // create paragraph record (multi) and save to user
        $user = \Drupal\user\Entity\User::load($userInput['utilisateur']);
        $paragraph = createParagraphAdherent($result, $user);

        // add to active adherent of user
        setAdherentActifByParagraph($paragraph, $user);

        $adherentCode = $paragraph->get('field_code_adherent')->first()->getValue();
        $adherentName = $paragraph->get('field_nom_adherent')->first()->getValue();

        // Logs a notice
        \Drupal::logger('gepsis')->notice('User ' . $user->getAccountName() . ' associated to adhérent ' . $adherentCode['value'] . ' - ' . $adherentName['value']);
        return;
    }
}

