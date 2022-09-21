<?php

namespace Drupal\gepsis\Plugin\WebformHandler;

use Drupal\Core\Database\Database;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\gepsis\Controller\GepsisOdataWriteClass;
use Drupal\gepsis\Utility\InternalFunctions;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\user\Entity\User;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;

/**
 * Create a new node entity from a webform submission.
 *
 * @WebformHandler(
 *   id = "Subuser Form",
 *   label = @Translation("Subuser form"),
 *   category = @Translation("My webform handler"),
 *   description = @Translation("Subuser Form"),
 *   cardinality =
 *     \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *     results =
 *     \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *     submission =
 *     \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class FormSubuserHandler extends WebformHandlerBase
{

    /**
     *
     * {@inheritdoc}
     */
    public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        $subuserVid = \Drupal::request()->query->get('subuserVid'); // form param
        $userParent = User::load(\Drupal::currentUser()->id());
        $userSubuser = User::load($subuserVid);
        $formElements = InternalFunctions::getFlattenedForm($form['elements']);
        $roles = \Drupal::currentUser()->getRoles();

        if (!in_array('assistante', $roles) && !in_array('administrator', $roles)) {
            $formElements['utilisateur_maitre_de_cet_utilisateur_']['#access'] = FALSE;
            $formElements['utilisateur_maitre_de_cet_utilisateur_']['#required'] = FALSE;
        } else {
            $user_names = InternalFunctions::getAllNamePortailUsers();
            foreach ($user_names as $key => $id) {
                if (User::load($key)->get('field_user_subuser')->value)
                    unset($user_names[$key]);
            }

            $formElements['utilisateur_maitre_de_cet_utilisateur_']['#options'] = $user_names;
        }

        if ($subuserVid && $userSubuser) {
            $formElements['adresse_de_courriel']['#default_value'] = $userSubuser->getEmail();
            $formElements['user_name_subuser']['#default_value'] = $userSubuser->getAccountName();
            $formElements['user_name_subuser']['#disabled'] = TRUE;

            $form['actions']['delete'] = array(
                '#type' => 'submit',
                '#submit' => array(
                    [
                        $this,
                        'subuser_delete'
                    ]
                ),
                '#validate' => array(
                    [
                        $this,
                        'subuser_delete_validate'
                    ]
                ),
                '#value' => t('Effacer cet sous-utilisateur'),
                '#weight' => 50
            );
        }
        $form['#cache']['max-age'] = 0;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function subuser_delete_validate(array &$form, FormStateInterface $form_state) {
        return;
        $dataState = $form_state->getUserInput();
        $user_name = $webform_submission->getElementData('user_name_subuser');
        $user_mail = $webform_submission->getElementData('adresse_de_courriel');
        return;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function subuser_delete(&$form, FormStateInterface $form_state) {
        $dataState = $form_state->getUserInput();
        $userMaster = User::load(\Drupal::currentUser()->id());
        $userUid = \Drupal::entityQuery('user')->condition('name', $form_state->getValue('user_name_subuser'))->execute();
        $userUid = reset($userUid);
        $account = User::load($userUid);

        user_cancel([], $userUid, 'user_cancel_reassign');

        return new RedirectResponse('user/' . $userMaster->id() . '/subuser');

        // delete from subusers table --- is executed by gepsis_entity_delete in gepsis.module
        $query = \Drupal::database()->delete('geps_subusers');
        $query->condition('uid', $userMaster->id());
        $query->condition('vid', $userUid);
        $query->execute();

        // $url = Url::fromUserInput('/user/' . $userMaster->id() . '/subuser');
        // $form_state->setRedirect($url);
    }

    /**
     *
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
        $userInput = $form_state->getUserInput();
        $user_name = $webform_submission->getElementData('user_name_subuser');
        $user_mail = $webform_submission->getElementData('adresse_de_courriel');

        $violations = user_validate_name($user_name);
        if ($violations && $violations->count() > 0) {
            $form_state->setErrorByName('user_name_subuser', $this->t("The username is invalid."));
            return;
        }

        if (!\Drupal::service('email.validator')->isValid($user_mail) || !filter_var($user_mail, FILTER_VALIDATE_EMAIL)) {
            $form_state->setErrorByName('adresse_de_courriel', $this->t('Email address is not a valid one.'));
            return;
        }

        $users = \Drupal::entityQuery('user')->condition('mail', $user_mail)->execute();
        if (!empty($users)) {
            $form_state->setErrorByName('adresse_de_courriel', $this->t('Email exists.'));
            return;
        }
        return;
    }

    /**
     *
     * {@inheritdoc}
     */
    public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
        $userLogged = User::load(\Drupal::currentUser()->id());

        if ($userMaster = $webform_submission->getElementData('utilisateur_maitre_de_cet_utilisateur_'))
            $userMaster = User::load($userMaster);
        else
            $userMaster = $userLogged;


        $user_name = $webform_submission->getElementData('user_name_subuser');
        $user_mail = $webform_submission->getElementData('adresse_de_courriel');

        // see if the user already exists
        $user = \Drupal::entityQuery('user')->condition('name', $user_name)->execute();
        $user = User::load(reset($user));
        if (!empty($user)) {
            // update the emails and return
            $user->setEmail($user_mail);
            $user->save();
            return;
        }

        // create subuser
        $userToCreate = User::create();
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
        // Mandatory user creation settings
        $userToCreate->enforceIsNew();
        $userToCreate->setPassword(base64_encode(random_bytes(10)));
        $userToCreate->setEmail($user_mail);
        $userToCreate->setUsername($user_name); // This username must be unique and accept only a-Z,0-9, - _ @ .
        $userToCreate->set("langcode", $language);
        // Optional settings
        $userToCreate->set("init", $user_mail);
        $userToCreate->set("preferred_langcode", $language);
        $userToCreate->set("preferred_admin_langcode", $language);
        $userToCreate->set("field_user_subuser", TRUE);

        // Validate the user for possible errors.
        $violations = $userToCreate->validate();
        if (count($violations) > 0) {
            $property = $violations[0]->getPropertyPath();
            $msg = $violations[0]->getMessage();
            \Drupal::messenger()->addError(t('Utilisateur not valid'));
            return FALSE;
        }

        // finally create the subuser
        $userToCreate->activate();
        $userToCreate->addRole('adherent');
        $userToCreate->save();

        // insert into subusers table
        $query = Database::getConnection();
        $query->insert('geps_subusers')->fields([
            'uid' => $userMaster->id(),
            'vid' => $userToCreate->id(),
            'created' => $userToCreate->getCreatedTime()
        ])->execute();

        // Add user adherents to szubuser
        $fields_adherents = $userMaster->get('field_adherents')->getValue();
        foreach ($fields_adherents as $item) {
            $paragraph = Paragraph::load($item['target_id']);
            if ($paragraph->getType() === 'field_adherents' && !$paragraph->field_adherent_oid->isEmpty()) {
                $adherentOid = $paragraph->get('field_adherent_oid')->first()->getValue();
                $adherentCode = $paragraph->get('field_code_adherent')->first()->getValue();
                $adherentName = $paragraph->get('field_nom_adherent')->first()->getValue();

                // create paragraph record (multi) and save to user
                $paragraph = Paragraph::create([
                    'type' => 'field_adherents'
                ]);
                $paragraph->set('field_adherent_oid', $adherentOid);
                $paragraph->set('field_code_adherent', $adherentCode);
                $paragraph->set('field_nom_adherent', $adherentName);
                $paragraph->isNew();
                $paragraph->save();

                $userSubuser = \Drupal\user\Entity\User::load($userToCreate->id());
                $fields_adherents = $userSubuser->field_adherents->getValue();
                $fields_adherents[] = array(
                    'target_id' => $paragraph->id(),
                    'target_revision_id' => $paragraph->getRevisionId()
                );
                $userSubuser->set('field_adherents', $fields_adherents);
                $userSubuser->save();

            }
        }

        // add to active adherent of user
        $userSubuser->set('field_active_adherent_oid', $adherentOid);
        $userSubuser->set('field_active_adherent_code', $adherentCode);
        $userSubuser->set('field_active_adherent_name', $adherentName);
        $userSubuser->save();

        \Drupal::logger('gepsis')
            ->notice('User ' . $userLogged->getAccountName() . ' create subuser ' . $user_name . ' - ' . $user_mail);

        _user_mail_notify('register_admin_created', $userToCreate);
        if ($webform_submission->getElementData('utilisateur_maitre_de_cet_utilisateur_'))
            return new RedirectResponse('/subusers');
        else
            return new RedirectResponse('user/' . $userLogged->id() . '/subuser');
        // $form_state->setRedirect('entity.user.canonical', ['user' => \Drupal::currentUser()->id()]);
        // $form_state->setRedirect('user/' . \Drupal::currentUser()->id() . '/subuser');
        // user/[current-user:uid]/subuser
        return;
    }

}
