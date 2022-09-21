<?php

namespace Drupal\gepsis\Plugin\WebformHandler;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\gepsis\Controller\GepsisOdataReadClass;
use Drupal\gepsis\Utility\InternalFunctions;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\webform\Ajax\WebformCloseDialogCommand;
use Drupal\webform\Ajax\WebformRefreshCommand;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Create a new node entity from a webform submission.
 *
 * @WebformHandler(
 *   id = "Acces Autres Users Form",
 *   label = @Translation("Acces Autres Users form"),
 *   category = @Translation("My webform handler"),
 *   description = @Translation("Acces Autres Users Form"),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
class FormAccesAutresUsersHandler extends WebformHandlerBase
{

  /**
   *
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $formElements = InternalFunctions::getFlattenedForm($form['elements']);
    // $current_parameters = \Drupal::routeMatch()->getParameters();
    // $adherent = $current_parameters->get('adherent');
    // $otherUser = $current_parameters->get('otheruser');
    // $page = $current_parameters->get('page');

    $adherent = \Drupal::request()->query->get('adherent');
    $otherUser = \Drupal::request()->query->get('otheruser');
    $page = \Drupal::request()->query->get('page');

    switch ($page) {
      case 'block_2':
        $formElements['aau_text_cabinet']['#text'] = '';
        break;
      case 'block_1':
        $formElements['aau_text_other_user']['#text'] = '';
        break;
    }

    $formElements['aau_adherent']['#default_value'] = $adherent;
    $formElements['aau_other_user']['#default_value'] = $otherUser;
    $formElements['aau_page']['#default_value'] = $page;

    $form['actions']['cancel'] = [
      '#type' => 'button',
      '#value' => 'Annuler',
      '#attributes' => [
        'class' => [
          'use-ajax',
          'cancel',
        ],
      ],
      '#ajax' => [
        'callback' => [$this, 'closeModalForm'],
        'event' => 'click',
      ],
    ];

    // $form['actions']['submit']['#attributes']['class'][] = 'use-ajax-submit';
    //        $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    // $form['#attached']['library'][] = 'core/jquery.form';
    // $form['#attached']['library'][] = 'core/drupal.ajax';

    // Disable caching
    $form['#cache']['max-age'] = 0;
    // $form['#tree'] = TRUE; // When this is set to false, the submit method gets no results through getValues().
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
   *
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    // $userInput = $form_state->getUserInput();
    return;
  }

  /**
   *
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {

  }

  public function submitModalFormAjax(array $form, FormStateInterface $form_state) {
    // return;
    $userInput = $form_state->getValues();
    $aau_adherent = $userInput['aau_adherent'];
    $aau_other_user = $userInput['aau_other_user'];
    $aau_page = $userInput['aau_page'];
    $userLoaded = user_load_by_name(trim($aau_other_user));
    $fields_adherents = $userLoaded->field_adherents->getValue();

    if ($aau_page == 'block_2') {
      foreach ($fields_adherents as $key => $item) {
        $paragraph = Paragraph::load($item['target_id']);
        if ($paragraph->getType() === 'field_adherents' && !$paragraph->field_adherent_oid->isEmpty()) {
          if ($paragraph->field_code_adherent->value == $aau_adherent) {
            unset($userLoaded->field_adherents[$key]);
            $userLoaded->save();
            $paragraph->delete();
            \Drupal::logger('gepsis')
              ->notice("RGPD: Adherent %adherent a ete efface de l'utilisateur %utilisateur.", [
                '%adherent' => $aau_adherent,
                '%utilisateur' => $userLoaded->getAccountName(),
              ]);
          }
        }
      }
    } elseif ($aau_page == 'block_1') {
      \Drupal::logger('gepsis')
        ->notice("RGPD: L'utilisateur %utilisateur a demandé la desassociation de cabinet %cabinet.", [
          '%cabinet' => $aau_other_user,
          '%utilisateur' => $userLoaded->getAccountName(),
        ]);
    }

    return new RedirectResponse('/utilisateur#lb-tabs-tabs-4');

    /** @var \Drupal\webform\WebformSubmissionForm $form_object */
    $form_object = $form_state->getFormObject();

    /** @var \Drupal\Core\Ajax\AjaxResponse $response */
    if ($form_state->hasAnyErrors()) {
      $response = $form_object->submitAjaxForm($form, $form_state);
    } else {
      $response = new AjaxResponse();
      $response->addCommand(new CloseModalDialogCommand());
      $response->addCommand(new WebformRefreshCommand(new RedirectResponse("/utilisateur#lb-tabs-tabs-4")));
    }
    return $response;
  }

  /**
   *
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $aau_adherent = $webform_submission->getElementData('aau_adherent');
    $aau_other_user = $webform_submission->getElementData('aau_other_user');
    $aau_page = $webform_submission->getElementData('aau_page');

    $userLoaded = user_load_by_name(trim($aau_other_user));
    $fields_adherents = $userLoaded->field_adherents->getValue();

    if ($aau_page == 'block_2') {
      foreach ($fields_adherents as $key => $item) {
        $paragraph = Paragraph::load($item['target_id']);
        if ($paragraph->getType() === 'field_adherents' && !$paragraph->field_adherent_oid->isEmpty()) {
          if ($paragraph->field_code_adherent->value == $aau_adherent) {
            unset($userLoaded->field_adherents[$key]);
            $userLoaded->save();
            $paragraph->delete();
            \Drupal::logger('gepsis')
              ->notice("RGPD: Adherent %adherent a ete efface de l'utilisateur %utilisateur.", [
                '%adherent' => $aau_adherent,
                '%utilisateur' => $userLoaded->getAccountName(),
              ]);
          }
        }
      }
    } elseif ($aau_page == 'block_1') {
      \Drupal::logger('gepsis')
        ->notice("RGPD: L'utilisateur %utilisateur a demandé la desassociation de cabinet %cabinet.", [
          '%cabinet' => $aau_other_user,
          '%utilisateur' => $userLoaded->getAccountName(),
        ]);
    }

    return new RedirectResponse('/utilisateur#lb-tabs-tabs-4');
  }

}

