<?php

/**
 * @file
 * Contains \Drupal\gepsis\Form\UserImportForm.
 */

namespace Drupal\gepsis\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\gepsis\Controller\ImportAdherentsController;
use Drupal\gepsis\Controller\ImportSubusersController;
use Drupal\gepsis\Plugin\WebformHandler\FormGepsisAdministrationHandler;
use Drupal\user\RoleInterface;
use Drupal\gepsis\Controller\ImportUsersController;

class UserImportForm extends FormBase
{

    /**
     * Implements \Drupal\Core\Form\FormInterface::getFormID().
     */
    public function getFormID() {
        return 'user_import_form';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEditableConfigNames() {
        return [];
    }

    /**
     * Implements \Drupal\Core\Form\FormInterface::buildForm().
     *
     * @param array $form
     *   The form array.
     * @param \Drupal\Core\Form\FormStateInterface $form_state
     *   The form state.
     *
     * @return array $form
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['#tree'] = TRUE;
        $form['config'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Options')
        ];
        $roles = user_role_names();
        unset($roles['anonymous']);
        $form['config']['roles'] = [
            '#type' => 'checkboxes',
            '#title' => $this->t('Roles'),
            '#options' => $roles
        ];
        $typeImport = array('users' => 'Users',
            'adherents' => 'Adherents associate',
            'subusers' => 'SubUsers relations',
        );
        $form['config']['import_type'] = [
            '#type' => 'radios',
            '#title' => $this->t('Type import'),
            '#options' => $typeImport
        ];

        $nbImport = array('all' => '- ALL -',
            50 => 50,
            100 => 100,
            500 => 500,
            1000 => 100,
        );
        $form['config']['import_nb'] = [
            '#type' => 'select',
            '#title' => $this->t('Nb. users to import'),
            '#default_value' => 'all',
            '#options' => $nbImport
        ];

        // Special handling for the inevitable "Authenticated user" role.
        $form['config']['roles'][RoleInterface::AUTHENTICATED_ID] = array(
            '#default_value' => TRUE,
            '#disabled' => TRUE,
        );
        $form['file'] = [
            '#type' => 'file',
            '#title' => 'CSV file upload',
            // '#required' => TRUE,
            '#upload_validators' => [
                'file_validate_extensions' => ['csv']
            ]
        ];
        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->t('Import users'),
            '#button_type' => 'primary',
        );

        // By default, render the form using theme_system_config_form().
        $form['#theme'] = 'system_config_form';
        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {
        $typeImport = $form_state->getValue(['config', 'import_type']);
        if ($typeImport == 'users') {
            // Validate options.
            $roles = $form_state->getValue(['config', 'roles']);
            $roles_selected = array_filter($roles, function ($item) {
                return ($item);
            });
            if (empty($roles_selected)) {
                $form_state->setErrorByName('roles', $this->t('Please select at least one role to apply to the imported user(s).'));
            }
        }
        // Validate file.
        $this->file = file_save_upload('file', $form['file']['#upload_validators']);
        if (!$this->file[0]) {
            $form_state->setErrorByName('file', $this->t('Please select at least one file.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $file = $this->file[0];
        $typeImport = $form_state->getValue(['config', 'import_type']);

        if ($typeImport == 'users') {
            $roles = $form_state->getValue(['config', 'roles']);
            $config = [
                'roles' => array_filter($roles, function ($item) {
                    return ($item);
                })
            ];
            if ($created = ImportUsersController::processUploadUsers($file, $config, $form, $form_state)) {
                //  drupal_set_message(t('Successfully imported @count users.', ['@count' => count($created)]));
                // \Drupal::messenger()->addMessage($this->t('Successfully imported @count users.', ['@count' => count($created)]));
            } else {
                //drupal_set_message(t('No users imported.'));
                \Drupal::messenger()->addMessage($this->t('No users imported.'));
            }
        } elseif ($typeImport == 'adherents') {
            if ($count = ImportAdherentsController::processUploadAdherents($file, $form, $form_state)) {
                // \Drupal::messenger()->addMessage($this->t('Successfully done for @count users.', ['@count' => $count]));
            } else {
                //drupal_set_message(t('No users imported.'));
                \Drupal::messenger()->addMessage($this->t('No users imported.'));
            }
        } elseif ($typeImport == 'subusers') {
            if ($count = ImportSubusersController::processUploadASubusers($file, $form, $form_state)) {
                // \Drupal::messenger()->addMessage($this->t('Successfully done for @count users.', ['@count' => $count]));
            } else {
                //drupal_set_message(t('No users imported.'));
                \Drupal::messenger()->addMessage($this->t('No users imported.'));
            }
        }
        // $form_state->setRedirectUrl(new Url('gepsis.admin_upload'));
    }

}