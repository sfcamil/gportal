<?php

namespace Drupal\gepsis\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\paragraphs\Entity\Paragraph;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CodimthConfirmDeleteForm extends \Drupal\Core\Form\ConfirmFormBase
{

    // https://codimth.com/blog/web/drupal/how-use-confirmformbase-confirm-action-delete-nodes-drupal-8
    /**
     * @param array $form
     * @param FormStateInterface $form_state
     * @return array
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $current_parameters = \Drupal::routeMatch()->getParameters();
        $adherent = $current_parameters->get('adherent');
        $otherUser = $current_parameters->get('otheruser');
        $page = $current_parameters->get('page');

        $form['#title'] = $this->getQuestion();
        $form['#attributes']['class'][] = 'confirmation';
        $form['description'] = ['#markup' => $this->getDescription()];
        $form[$this->getFormName()] = ['#type' => 'hidden', '#value' => 1];

        $message = null;
        if ($page = 'block_2')
            $message = "Vous-etes en train de desasscoier l'adherent " . $adherent . " pour l'utilisateur " . $otherUser;
        if ($page = 'block_1')
            $message = "Vous-etes en train de demander la desasscoiaition de l'adherent " . $adherent . " pour l'utilisateur " . $otherUser;


        $form['description'] = [
            '#type' => 'item',
            '#markup' => Markup::create($message),
        ];

        $form['adherent'] = array(
            '#type' => 'hidden',
            '#value' => $adherent,
        );

        $form['otherUser'] = array(
            '#type' => 'hidden',
            '#value' => $otherUser,
        );

        $form['page'] = array(
            '#type' => 'hidden',
            '#value' => $page,
        );

        $form['actions'] = ['#type' => 'actions'];
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->getConfirmText(),
            '#button_type' => 'primary',
        ];

        $form['actions']['cancel'] = [
            '#type' => 'submit',
            '#value' => $this->getCancelText(),
            '#attributes' => [
                'class' => [
                    'use-ajax',
                    'cancel',
                ],
            ],
            '#ajax' => [
                'callback' => array($this, 'closeModalForm'),
                'event' => 'click',
            ],
        ];
        $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

        // By default, render the form using theme_confirm_form().
        if (!isset($form['#theme'])) {
            $form['#theme'] = 'confirm_form';
        }
        return $form;
    }

    /**
     * @return \Drupal\Core\Ajax\AjaxResponse
     */
    public function closeModalForm() {
        $command = new CloseModalDialogCommand();
        $response = new AjaxResponse();
        $response->addCommand($command);
        return $response;
    }

    /**
     * @inheritDoc
     */
    public function getQuestion() {
        return t('Please confirm your action');
    }

    /**
     * @inheritDoc
     * https://www.hashbangcode.com/article/drupal-9-programmatically-creating-and-using-urls-and-links
     */
    public function getCancelUrl() {
        return;
        // return Url::fromUri('internal:/utilisateur');
    }

    /**
     * @inheritDoc
     */
    public function getFormId() {
        return 'codimth_form_api_confirm_deletion';
    }

    /**
     * @inheritDoc
     * @throws \Drupal\Core\Entity\EntityStorageException
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $userInput = $form_state->getUserInput();
        $userLoaded = user_load_by_name(trim($userInput['otherUser']));
        $fields_adherents = $userLoaded->field_adherents->getValue();

        foreach ($fields_adherents as $key => $item) {
            $paragraph = Paragraph::load($item['target_id']);
            if ($paragraph->getType() === 'field_adherents' && !$paragraph->field_adherent_oid->isEmpty()) {
                if ($paragraph->field_code_adherent->value == $userInput['adherent']) {
                    unset($userLoaded->field_adherents[$key]);
                    $userLoaded->save();
                    $paragraph->delete();
                }
            }
        }


        return;

        $user->set('field_active_adherent_oid', $adherentOid);
        $user->set('field_active_adherent_code', $adherentCode);
        $user->set('field_active_adherent_name', $adherentName);

        $message = 'New signup email address: ' . $form['email']; // Body of your email here.
        drupal_mail('my_sign_up_form', $send_form_to, 'New signup', $message, variable_get('site_mail', 'an@example.com'));


        $title = $form_state->getValue('title');
        $result = \Drupal::entityQuery("node")
            ->condition('title', $title)
            ->execute();
        $storage_handler = \Drupal::entityTypeManager()->getStorage("node");
        $entities = $storage_handler->loadMultiple($result);
        $storage_handler->delete($entities);

        return;
        $this
            ->messenger()
            ->addStatus($this
                ->t('The forum %label and all sub-forums have been deleted.', [
                    '%label' => $this->taxonomyTerm
                        ->label(),
                ]));
        $this
            ->logger('gepsis')
            ->notice('forum: deleted %label and all its sub-forums.', [
                '%label' => $this->taxonomyTerm
                    ->label(),
            ]);

        // \Drupal::messenger()->addStatus(t('node @title deleted.', ['@title' => $title]));
        return Url::fromUri('internal:/utilisateur#lb-tabs-tabs-4');
    }
}