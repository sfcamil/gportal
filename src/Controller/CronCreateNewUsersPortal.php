<?php

namespace Drupal\gepsis\Controller;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\gepsis\Utility\InternalFunctions;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\user\Entity\User;


class CronCreateNewUsersPortal
{

    /**
     * {@inheritdoc}
     */
    public static function process() {
        return;
        $userLogged = User::load(\Drupal::currentUser()->id());
        \Drupal::logger('gepsis_cron')->notice('CRON Executed at time: ' . date('H:i:s'));

        $newUsers = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_PORTAL_USER', "ENTP_EMAIL_SENT eq 'Y'");
        foreach ($newUsers as $key => $value) { // just create list of all adherents from Geps
            if (hash('sha1', $value->ENTP_PASSWORD) === hash('sha1', 'a')) {
                unset($newUsers[$key]);
                continue;
            }
            $adherentInfo = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_INFO', "ENTR_CODE eq '" . trim($value->ENTP_USER_ID) . '\'');
            if ($adherentInfo)
                $resultListaAdherents[$key] = $adherentInfo[0];
            else
                \Drupal::logger('gepsis_cron')->notice('Adherent for user ' . trim($value->ENTP_USER_ID) . ' not exists !');
        }

        foreach ($newUsers as $key => $value) {
            $createParagraph = FALSE;
            if ($value->IS_FILLE != 'Y') {
                $account = user_load_by_name($value->ENTP_USER_ID);
                // !\Drupal::entityQuery('user')->condition('name', $value->ENTP_USER_ID)->execute()
                if (!$account) {
                    // user not exists  continue to create
                    $userToCreate = array(
                        'name' => $value->ENTP_USER_ID,
                        'pass' => $value->ENTP_PASSWORD,
                        'mail' => $value->ENTP_SENT_TO,
                        'langcode' => 'fr',
                        'preferred_langcode' => 'fr',
                        'preferred_admin_langcode' => 'fr',
                        'timezone' => 'Europe/Paris',
                        'status' => 1,
                    );
                    $account = User::create($userToCreate);
                    $createParagraph = TRUE;
                    \Drupal::logger('gepsis_cron')->notice('Acount ' . $value->ENTP_USER_ID . ' created. EntOid,EntCode,User,pass,email: ' . $resultListaAdherents[$key]->ENTR_O_ID . ',' . $resultListaAdherents[$key]->ENTR_CODE . ',' . $value->ENTP_USER_ID . ',' . $value->ENTP_PASSWORD . ',' . $value->ENTP_SENT_TO);
                } else { // user exists update pass
                    // TODO
                    $hashPassGeps = hash('sha1', $value->ENTP_PASSWORD);
                    if (!empty($account->field_hashpassgeps['und'][0]['value']) && $hashPassGeps !== $account->field_hashpassgeps['und'][0]['value']) {
                        $account->pass = $value->ENTP_PASSWORD;
                        $account->mail = $value->ENTP_SENT_TO;
                        $account->field_hashpassgeps = array(
                            'und' => array(
                                0 => array(
                                    'value' => $hashPassGeps
                                )
                            )
                        );
                        \Drupal::logger('gepsis_cron')->notice('Acount (UPDATE) ' . $value->ENTP_USER_ID . ' . EntOid,EntCode,User,pass,email: ' . $resultListaAdherents[$key]->ENTR_O_ID . ',' . $resultListaAdherents[$key]->ENTR_CODE . ',' . $value->ENTP_USER_ID . ',' . $value->ENTP_PASSWORD . ',' . $value->ENTP_SENT_TO);
                    };
                }
                try {
                    $account->save();
                } catch (EntityStorageException $e) {
                    $context['results']['error'][] = t('@user not created: $e', array('@user' => $value->ENTP_USER_ID, '@e' => $e));
                }
                $adherent = $resultListaAdherents[$key];
            } else { // is FILLE then update MERE user acount
                foreach ($resultListaAdherents as $key2 => $value2) {
                    if ($value2->ENTR_O_ID == $value->ENTP_MOTHER_O_ID)
                        break;
                }
                $adherent = $resultListaAdherents[$key];
                $account = user_load_by_name(trim($resultListaAdherents[$key2]->ENTR_CODE));
                $createParagraph = TRUE;
            }

            $addActif = FALSE;
            if ($createParagraph && $adherent) {
                // create paragraph record (multi) and save to user
                $paragraph = InternalFunctions::createParagraphAdherent($adherent, $account);
                $addActif = TRUE;
            }

            if ($addActif && $paragraph) {
                // add actif adherent for user
                InternalFunctions::setAdherentActifByParagraph($paragraph, $account);
            }
        }
    }
}