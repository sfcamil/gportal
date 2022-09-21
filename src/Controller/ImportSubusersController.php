<?php
/**
 * @file
 * contains \Drupal\gepsis\Controller\UserImportController.
 */

namespace Drupal\gepsis\Controller;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\user\Entity\Role;
use \Drupal\user\Entity\User;
use \Drupal\file\Entity\File;
use \Drupal\Core\Entity\EntityStorageException;
use SplFileObject;

class ImportSubusersController
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
    public static function processUploadASubusers(File $file, array &$form, FormStateInterface $form_state) {
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
            'title' => t('Importing subusers'),
            'operations' => [],
            'init_message' => t('Import process is starting.'),
            'progress_message' => t('Processed @current out of @total. Estimated time: @estimate.'),
            'error_message' => t('The process has encountered an error.'),
            'finished' => '\Drupal\gepsis\Controller\ImportSubusersController::finishImportSubusers',
        ];

        $subusersArray = [];
        while ($row = fgetcsv($handle, 0, ';')) {
            $subusersArray[$row[1]] = $row;
        }

        // get all users
        $query = \Drupal::entityTypeManager()->getStorage('user')->getQuery();
        $uids = $query->execute();
        $users = User::loadMultiple($uids);

        foreach ($users as $key => $valueUser) {
            if (array_key_exists($valueUser->getAccountName(), $subusersArray))
                $subuserRow = $subusersArray[$valueUser->getAccountName()];
            else
                continue;
            $batch['operations'][] = ['\Drupal\gepsis\Controller\ImportSubusersController::importSubusers', [$subuserRow, $valueUser]];
        }
        batch_set($batch);
    }

    // What to do after batch completed. Display success or error message.
    public static function finishImportSubusers($success, $results, $operations) {
        if ($success)
            \Drupal::messenger()->addMessage('Successfully done for ' . count($results) . ' users.');
        else
            \Drupal::messenger()->addMessage('Finished with an error.');
    }


    public static function importSubusers($row, $user, &$context) {
        $subusersForUser = explode(',', $row[2]);
        $createdSubusers = explode(',', $row[3]);

        foreach ($subusersForUser as $key => $value) {
            $subUser = user_load_by_name(trim($value));
            if ($subUser) {
                // check if exists
                // if deleted a master then delete subusers also
                $query = \Drupal::database()->query('SELECT vid from geps_subusers where uid = :value1 and vid = :value2', array(':value1' => $user->id(), ':value2' => $subUser->id()));
                $data = $query->fetchAll();

                if (!$data) {
                    // insert into subusers table
                    $query = Database::getConnection();
                    $query->insert('geps_subusers')->fields([
                        'uid' => $user->id(),
                        'vid' => $subUser->id(),
                        'created' => $createdSubusers[$key]
                    ])->execute();
                    $subUser->set("field_user_subuser", TRUE);
                    $user->save();
                }
            }
        }


    }
}