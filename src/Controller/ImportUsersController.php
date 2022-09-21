<?php
/**
 * @file
 * contains \Drupal\gepsis\Controller\UserImportController.
 */

namespace Drupal\gepsis\Controller;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\Role;
use \Drupal\user\Entity\User;
use \Drupal\file\Entity\File;
use \Drupal\Core\Entity\EntityStorageException;

class ImportUsersController
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
    public static function processUploadUsers(File $file, array $config, array &$form, FormStateInterface $form_state) {
        $destination = $file->toArray()['uri'][0]['value'];
        $handle = fopen($destination, 'r');
        $nbToProcess = $form_state->getValue(['config', 'import_nb']);
        $count = 1;

        $batch = [
            'title' => t('Importing ausers'),
            'operations' => [],
            'init_message' => t('Import process is starting.'),
            'progress_message' => t('Processed @current out of @total. Estimated time: @estimate.'),
            'error_message' => t('The process has encountered an error.'),
            'finished' => '\Drupal\gepsis\Controller\ImportUsersController::finishImportUsers',
        ];

        while ($row = fgetcsv($handle, 0, ';')) {
            if ($nbToProcess != 'all')
                if ($count > intval($nbToProcess)) break;

            if ($values = self::prepareRow($row, $config)) {
                if (!self::usernameExists($values['name'])) {
                    $batch['operations'][] = ['\Drupal\gepsis\Controller\ImportUsersController::createUser', [$values, $form, $form_state, $row]];
                    $count++;
                }
            }
        }
        batch_set($batch);
    }

    // What to do after batch completed. Display success or error message.
    public static function finishImportUsers($success, $results, $operations) {
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

    /**
     * Prepares a new user from an upload row and current config.
     *
     * @param $row
     *   A row from the currently uploaded file.
     *
     * @param $config
     *   An array of configuration containing:
     *   - roles: an array of role ids to assign to the user
     *
     * @return array
     *   New user values suitable for User::create().
     */
    public static function prepareRow(array $row, array $config) {
        return [
            'uid' => NULL,
            'name' => $row[1],
            'field_name_first' => $row[2],
            'field_name_last' => $row[3],
            'pass' => [
                'value' => $row[4],
                'pre_hashed' => TRUE,
            ],
            'init' => $row[10] ? $row[10] : $row[5],
            'mail' => $row[5],
            'created' => $row[6],
            'access' => $row[7],
            'login' => $row[8],
            'langcode' => 'fr',
            'preferred_langcode' => 'fr',
            'preferred_admin_langcode' => 'fr',
            'timezone' => 'Europe/Paris',
            'status' => 1,
            // 'roles' => array_values(explode(', ', $row[11])),
        ];
    }


    /**
     * Returns user whose name matches $username.
     *
     * @param string $username
     *   Username to check.
     *
     * @return array
     *   Users whose names match username.
     */
    private static function usernameExists($username) {
        return \Drupal::entityQuery('user')->condition('name', $username)->execute();
    }

    /**
     * Creates a new user from prepared values.
     *
     * @param $values
     *   Values prepared from prepareRow().
     *
     * @return \Drupal\user\Entity\User
     *
     */
    public static function createUser($values, array $form, FormStateInterface $form_state, $row, &$context) {
        $addRoles = array_values(explode(', ', $row[11]));
        $user = User::create($values);
        foreach ($addRoles as $value) {
            $role = strtolower(trim($value));
            if ($role != 'adherent')
                $user->addRole($role);
        }
        try {
            if ($user->save()) {
                $context['results'][] = $user->id();
                // return $user->id();
            }
        } catch (EntityStorageException $e) {
            $context['results']['error'][] = t('@user not created: $e', array('@user' => $values['name'], '@e' => $e));
        }
    }
}