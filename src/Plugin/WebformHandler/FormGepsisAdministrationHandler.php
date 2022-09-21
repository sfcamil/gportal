<?php

namespace Drupal\gepsis\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\gepsis\Controller\GepsisOdataReadClass;
use Drupal\gepsis\Utility\InternalFunctions;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\user\Entity\User;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Create a new node entity from a webform submission.
 *
 * @WebformHandler(
 *   id = "Gepsis administration Form",
 *   label = @Translation("Gepsis administration form"),
 *   category = @Translation("Gepsis administration webform handler"),
 *   description = @Translation("Gepsis administration"),
 *   cardinality =
 *     \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *     results =
 *     \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *     submission =
 *     \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class FormGepsisAdministrationHandler extends WebformHandlerBase
{

    /**
     *
     * {@inheritdoc}
     */
    public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        $user = User::load(\Drupal::currentUser()->id());
        $formElements = InternalFunctions::getFlattenedForm($form['elements']);

        $config_factory = \Drupal::configFactory();
        $configList = $config_factory->listAll('odata');
        $config = $config_factory->getEditable($configList[0]);
        $origValue = $config->get('odata_endpoint_uri');
        $formElements['odata_config_from_db']['#text'] = 'Actual: ' . $origValue;

        $form['elements']['odata_ip']['flexbox_ip']['actions']['#type'] = 'actions';
        $form['elements']['odata_ip']['flexbox_ip']['actions']['actions_odata_ip'] = [
            '#type' => 'submit',
            '#value' => $this->t('Envoyer Odata IP'),
            '#submit' => array([$this, 'submitOdataIp']),
            '#validate' => array([$this, 'validateOdataIp']),
            '#limit_validation_errors' => array(),
        ];

        $form['elements']['adherents_for_user']['flexbox_adherents_for_user']['actions']['#type'] = 'actions';
        $form['elements']['adherents_for_user']['flexbox_adherents_for_user']['actions']['actions_delete_users'] = [
            '#type' => 'submit',
            '#value' => $this->t('Delete all users'),
            '#submit' => array([$this, 'submitDeleteAllUsers']),
            '#validate' => array([$this, 'validateDeleteAllUsers']),
            '#limit_validation_errors' => array(),
        ];
    }

    public function validateDeleteAllUsers(array &$form, FormStateInterface $form_state) {
    }

    public function submitDeleteAllUsers(array &$form, FormStateInterface $form_state) {
        $userInput = $form_state->getUserInput();
        $query = \Drupal::database()->select('users', 'u');
        $query->addField('u', 'uid');
        $query->condition('u.uid', '0', '>');
        $query->orderBy('u.uid');
        $all_users = $query->execute()->fetchAll();

        $batch = [
            'title' => t('Delete all users'),
            'operations' => [],
            'init_message' => t('delete process is starting.'),
            'progress_message' => t('Processed @current out of @total. Estimated time: @estimate.'),
            'error_message' => t('The process has encountered an error.'),
            // 'finished' => '\Drupal\gepsis\Controller\ImportAdherentsController::finishImportAdherents',
        ];


        foreach ($all_users as $value) {
            if ($value->uid == 1 || $value->uid == 0)
                continue;
            // The third argument can be one of the following:
            //   - user_cancel_block: disable user, leave content
            //   - user_cancel_block_unpublish: disable user, unpublish content
            //   - user_cancel_reassign: delete user, reassign content to uid=0
            //   - user_cancel_delete: delete user, delete content

            $batch['operations'][] = ['\Drupal\gepsis\Plugin\WebformHandler\FormGepsisAdministrationHandler::batchDeleteUser', [$value->uid]];
        }
        batch_set($batch);
    }

    public static function batchDeleteUser($uid) {
        user_cancel([], $uid, 'user_cancel_delete');
    }

    public function validateOdataIp(array &$form, FormStateInterface $form_state) {
        $userInput = $form_state->getUserInput();
        $newValueOdataLink = $userInput['nouvelle_valeur_pour_odata_link']['other'] ? $userInput['nouvelle_valeur_pour_odata_link']['other'] : $userInput['nouvelle_valeur_pour_odata_link']['select'];
        $newOdataLink = preg_match_all("/([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})\:?([0-9]{1,5})?/", $newValueOdataLink, $match);

        if (!$newOdataLink) {
            $form_state->setErrorByName('nouvelle_valeur_pour_odata_link', $this->t('Format not ok.', [
                '%value' => $newValueOdataLink,
            ]));
        }
    }

    public function submitOdataIp(array &$form, FormStateInterface $form_state) {
        $formElements = InternalFunctions::getFlattenedForm($form['elements']);
        $userInput = $form_state->getUserInput();
        $oldDataLink = $formElements['odata_config_from_db']['#text'];
        $oldDataLink = preg_match_all("/(http|https):\/\/([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})\:?([0-9]{1,5})?/", $oldDataLink, $match);
        $oldDataLink = $match[0][0];
        $newOdataLink = $userInput['nouvelle_valeur_pour_odata_link']['other'] ? $userInput['nouvelle_valeur_pour_odata_link']['other'] : $userInput['nouvelle_valeur_pour_odata_link']['select'];

        $config_factory = \Drupal::configFactory();
        $configList = $config_factory->listAll('odata');
        foreach ($configList as $value) {
            $config = $config_factory->getEditable($value);
            $origValue = $config->get('odata_endpoint_uri');
            $destValue = str_replace($oldDataLink, $newOdataLink, $origValue);
            $config->set('odata_endpoint_uri', $destValue);
            $config->save(TRUE);
        }
        drupal_flush_all_caches();
    }

}