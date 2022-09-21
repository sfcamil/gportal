<?php
/**
 * @file
 * contains \Drupal\gepsis\Controller\UserImportController.
 */

namespace Drupal\gepsis\Controller;

use Drupal\Core\Form\FormStateInterface;
use Drupal\gepsis\Utility\GetAllFunctions;
use Drupal\gepsis\Utility\InternalFunctions;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\user\Entity\Role;
use \Drupal\user\Entity\User;
use \Drupal\file\Entity\File;
use \Drupal\Core\Entity\EntityStorageException;
use SplFileObject;

class ImportAdherentsController
{

    // https://github.com/steveoliver/user_import

    public static function importPage() {
        $form = \Drupal::formBuilder()->getForm('Drupal\gepsis\Form\UserImportForm');
        return $form;
    }

    /**
     * Processes an uploaded CSV file, creating a new user for each row of values.
     *
     * @param \Drupal\file\Entity\File $file
     *   The File entity to process.
     *
     * @param array $config
     *   An array of configuration containing:
     *   - roles: an array of role ids to assign to the user
     *
     * @return array
     *   An associative array of values from the filename keyed by new uid.
     */
    public static function processUploadAdherents(File $file, array &$form, FormStateInterface $form_state) {
        $destination = $file->toArray()['uri'][0]['value'];
        $handle = fopen($destination, 'r');
        $totalFile = new SplFileObject($destination, 'r');
        $totalFile->setFlags(
            SplFileObject::READ_CSV |
            SplFileObject::READ_AHEAD |
            SplFileObject::SKIP_EMPTY |
            SplFileObject::DROP_NEW_LINE
        );
        $totalFile->seek(PHP_INT_MAX);
        $totalRows = $totalFile->key() + 1;

        $batch = [
            'title' => t('Importing adherents for user'),
            'operations' => [],
            'init_message' => t('Import process is starting.'),
            'progress_message' => t('Processed @current out of @total. Estimated time: @estimate.'),
            'error_message' => t('The process has encountered an error.'),
            'finished' => '\Drupal\gepsis\Controller\ImportAdherentsController::finishImportAdherents',
        ];

        $adherentsArray = [];
        while ($row = fgetcsv($handle, 0, ';')) {
            $adherentsArray[$row[1]] = $row;
        }

        // get all users
        $query = \Drupal::entityTypeManager()->getStorage('user')->getQuery();
        $uids = $query->execute();
        $users = User::loadMultiple($uids);

        foreach ($users as $key => $valueUser) {
            if (array_key_exists($valueUser->getAccountName(), $adherentsArray))
                $adherentRow = $adherentsArray[$valueUser->getAccountName()];
            else
                continue;
            $batch['operations'][] = ['\Drupal\gepsis\Controller\ImportAdherentsController::importAdherents', [$adherentRow, $valueUser]];
        }
        unset($_SESSION['finalListeAllAdherentsByCode']);
        batch_set($batch);
    }

    // What to do after batch completed. Display success or error message.
    public static function finishImportAdherents($success, $results, $operations) {
        if ($success)
            \Drupal::messenger()->addMessage('Successfully done for ' . count($results) . ' users.');
        else {
            $error_operation = reset($operations);
            \Drupal::messenger()->addMessage(t('An error occurred while processing @operation with arguments : @args', array(
                '@operation' => $error_operation[0],
                '@args' => print_r($error_operation[0], TRUE),
            )));
        }
    }


    public static function importAdherents($row, $user, &$context) {
        $fields_adherents = $user->get('field_adherents')->getValue();
        if (!empty($row[2]))
            $adherentsToAssociate = explode(',', $row[2]);
        else
            $adherentsToAssociate = explode(',', $row[3]); // adh actif
        foreach ($adherentsToAssociate as $valAdh) {
            $valAdh = trim($valAdh);
            $create = TRUE;
            if ($fields_adherents) {
                foreach ($fields_adherents as $item) {
                    $paragraph = Paragraph::load($item['target_id']);
                    if ($paragraph->getType() === 'field_adherents') {
                        if ($paragraph->get('field_code_adherent')->first()) {
                            $adherentCode = $paragraph->get('field_code_adherent')->first()->getValue();
                            if ($adherentCode['value'] == $valAdh)
                                $create = FALSE;
                        }
                    }
                }
            }

            $addActif = FALSE;
            if ($create && $valAdh) {
                $result = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_INFO', "ENTR_CODE eq '" . $valAdh . '\'', 1);
                // $adherents = GetAllFunctions::getAllAdherentsByCode();
                // $result = $adherents[trim($valAdh)];
                if ($result) {
                    // create paragraph record (multi) and save to user
                    $paragraph = InternalFunctions::createParagraphAdherent($result, $user);
                    $addActif = TRUE;
                } else
                    $context['results']['error'][] = t('@adh not exists.', array('@adh' => $valAdh));
                $context['results'][] = $row;
            }
        }

        if ($addActif && $paragraph) {
            // add actif adherent for user
            InternalFunctions::setAdherentActifByParagraph($paragraph, $user);
        }
    }
}