<?php

namespace Drupal\gepsis\Utility;

use Drupal\gepsis\Controller\GepsisOdataReadClass;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\webform\Utility\WebformElementHelper;


/**
 * User: Stoica Florin
 * Date: 4 mai 2021
 * Time: 11:53:40
 */
class InternalFunctions
{

    public static function setAdherentActifByParagraph($paragraph, $account) {
        try {
            $adherentOid = $paragraph->get('field_adherent_oid')->first()->getValue();
            $adherentCode = $paragraph->get('field_code_adherent')->first()->getValue();
            $adherentName = $paragraph->get('field_nom_adherent')->first()->getValue();

            $account->set('field_active_adherent_oid', $adherentOid);
            $account->set('field_active_adherent_code', $adherentCode);
            $account->set('field_active_adherent_name', $adherentName);
            $account->addRole('adherent');
            $account->save();
        } catch (\Error $e) {
            $context['results']['error'][] = t('Error add actif for @adh: @e', array('@adh' => $adherentCode, '@e' => $e));
        }
    }

    public static function createParagraphAdherent($adherent, $account) {
        try {
            $paragraph = Paragraph::create([
                'type' => 'field_adherents'
            ]);
            $paragraph->set('field_adherent_oid', $adherent->ENTR_O_ID);
            $paragraph->set('field_code_adherent', trim($adherent->ENTR_CODE));
            $paragraph->set('field_nom_adherent', trim($adherent->ENTR_NOM));
            $paragraph->isNew();
            $paragraph->save();

            // $user = \Drupal\user\Entity\User::load($userInput['utilisateur']);
            $fields_adherents = $account->field_adherents->getValue();
            $fields_adherents[] = array(
                'target_id' => $paragraph->id(),
                'target_revision_id' => $paragraph->getRevisionId()
            );
            $account->set('field_adherents', $fields_adherents);
            $account->save();
        } catch (\Error $e) {
            // message
            $context['results']['error'][] = t('Error create paragraph @adh: @e', array('@adh' => $adherent->ENTR_CODE, '@e' => $e));
        }
        return $paragraph;
    }

    public static function getAllNamePortailUsers() {
        $query = \Drupal::database()->select('users', 'u');
        $query->addField('u', 'uid');
        $query->condition('u.uid', '0', '>');
        $query->orderBy('u.uid');
        $all_users = $query->execute()->fetchAll();
        foreach ($all_users as $value) {
            $uids[] = $value->uid;
        }
        $all_users = \Drupal\user\Entity\User::loadMultiple($uids);

        foreach ($all_users as $key => $value) {
            $user_names[$key] = $value->getAccountName();
        }
        return $user_names;
    }

    public static function calculateAge($bithdayDate) {
        $request_time = date('Y');
        $date = date('Y', strtotime($bithdayDate));
        return $request_time - $date;
    }

    public static function internalFormatDate($date) {
        return \Drupal::service('date.formatter')->format(strtotime($date), 'html_date');
    }

    /**
     * Flatten a nested array to references of webform elements.
     *
     * @param array $elements
     *            An array of elements.
     *
     * @return array A flattened array of elements.
     */
    public static function getFlattenedForm(&$elements) {
        $flattened_elements = [];
        foreach ($elements as $key => &$element) {
            if (!WebformElementHelper::isElement($element, $key))
                continue;
            $flattened_elements[$key] = &$element;
            $flattened_elements += self::getFlattenedForm($element);
        }
        return $flattened_elements;
    }

    public static function setupTraceInfos(&$customer) {
        if (is_object($customer))
            $val = $customer;
        else
            $val = $customer[0];

        $val->remoteAddr = empty($_SERVER['REMOTE_ADDR']) ? '' : $_SERVER['REMOTE_ADDR'];
        $request = \Drupal::request();
        $val->remoteSession = $request->getSession()->getId();
        $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        $name = $user->get('name')->value;

        // @formatter:off
        $remoteUser = str_replace(array('+', '\\', '?', '%', '#', '&', '/', '$', '\''), '', $name);
        // @formatter:on
        $val->remoteUser = htmlspecialchars($remoteUser);
        $customer = $val;
    }

    public static function getEmailAssistante() {
        return 'fs@ocara.com';
        $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        $adherentOid = $user->get('field_active_adherent_oid')->value;

        // $typeContact = 'I';
        // $viewListeAllAssistante = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_SERVICE_CONTACT_ALL', 'CONTACT_TYPE eq \'' . $typeContact . '\' AND ENTR_O_ID eq \'' . $entrOid . '\'');
        $viewListeAllAssistante = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_SERVICE_CONTACT_ALL', 'ENTR_O_ID eq ' . $adherentOid);

        if (empty($viewListeAllAssistante)) {
            \Drupal::messenger()->addError(t('No result returned. Please check your query and the endpoint status (' . __FUNCTION__ . ').'));
            return;
        }
        $finalListeAssistante = array();
        foreach ($viewListeAllAssistante as $value) {
            if ($value->CONTACT_TYPE == 'I')
                $finalListeAssistante[] = $value->CONTACT_E_MAIL;
        }
        return implode(',', $finalListeAssistante);
    }

    public static function populateFields(&$elements, $result, $settings) {
        if (!is_array($elements)) {
            return;
        }
        $allowed_types = array(
            'email',
            'textfield',
            'select',
            'radios',
            'checkbox',
            'checkboxes',
            'tel',
            'webform_select_other',
            'textarea'
        );
        foreach ($elements as $key => &$element) {
            if (!empty($element['#type'])) {
                if (in_array($element['#type'], $allowed_types) && !is_numeric($key)) {
                    $field = array_search($key, $settings, TRUE);
                    if ($field && $result->hasField($field)) {
                        $element['#default_value'] = $result->get($field)->value;
                    }
                } elseif ($element['#type'] == 'fieldset' || $element['#type'] == 'container' || $element['#type'] == 'webform_flexbox') {
                    GetAllFunctions::populateFields($element, $result, $settings);
                }
            }
        }
    }

    /**
     * Vérifie le numéro de sécurité sociale.
     * S'il est valide, renvoit un tableau des infos
     * sinon renvoie FALSE
     *
     * @param string $numero
     *            à 15 chiffre
     * @return mixed array avec les infos récupérées du num de sécu ou FALSE
     * @author Webu (Dylann Cordel <d.cordel@webu.fr>)
     */
    public static function checkNumSecu($numero) {
        // https://www.developpez.net/forums/d677820/php/langage/verification-numero-securite-sociale/#post5858807
        //
        // Expression de base d'Antoun et SNAFU (http://www.developpez.net/forums/d677820/php/langage/regex/verification-numero-securite-sociale/#post3969560),
        // mais corigée par mes soins pour respecter plus scrupuleusement le format
        // @formatter:off
        $regexp = '/^                                           # début de chaîne
            (?<sexe>[1278])                                             # 1 et 7 pour les hommes ou 2 et 8 pour les femmes
            (?<annee>[0-9]{2})                                          # année de naissance
            (?<mois>0[1-9]|1[0-2]|20)                                   # mois de naissance (si >= 20, c\'est qu\'on ne connaissait pas le mois de naissance de la personne
            (?<departement>[02][1-9]|2[AB]|[1345678][0-9]|9[012345789]) # le département : 01 à 19, 2A ou 2B, 21 à 95, 99 (attention, cas particulier hors métro traité hors expreg)
            (?<numcommune>[0-9]{3})                                     # numéro d\'ordre de la commune (attention car particuler pour hors métro  traité hors expression régulière)
            (?<numacte>00[1-9]|0[1-9][0-9]|[1-9][0-9]{2})               # numéro d\'ordre d\'acte de naissance dans le mois et la commune ou pays
            (?<clef>0[1-9]|[1-8][0-9]|9[1-7])?                          # numéro de contrôle (facultatif)
            $                                                           # fin de chaîne
            /x';
        // @formatter:on
        // références : http://fr.wikipedia.org/wiki/Num%C3%A9ro_de_s%C3%A9curit%C3%A9_sociale_en_France#Signification_des_chiffres_du_NIR

        if (!preg_match($regexp, $numero, $match)) {
            return FALSE;
        }
        /*
         * attention à l'overflow de php :)
         * i.e :
         * $test = '1850760057018' ;
         * $clef = 97 - (substr($test, 0, 13) % 97) ;
         * // => clef = 32 car l'opérande "%" travaille avec des entiers, et sur une archi 32 bits, 1850760057018 est transformé en 2147483647 ^_^
         * $clef = 97 - fmod(substr($test, 0, 13), 97) ;
         * // => clef = 18 (la valeur correcte, car fmod travaille avec des flottants)
         */

        $return = array(
            'sexe' => $match['sexe'], // 7,8 => homme et femme ayant un num de sécu temporaire
            'annee' => $match['annee'], // année de naissance + ou - un siècle uhuh
            'mois' => $match['mois'], // 20 = inconnu
            'departement' => $match['departement'], // 99 = étranger
            'numcommune' => $match['numcommune'], // 990 = inconnu
            'numacte' => $match['numacte'], // 001 à 999
            'clef' => isset($match['clef']) ? $match['clef'] : NULL, // 00 à 97
            'pays' => 'fra' // par défaut, on change que pour le cas spécifique
        );

        // base du calcul par défaut pour la clef (est modifié pour la corse)
        $aChecker = floatval(substr($numero, 0, 13));

        /* Traitement des cas des personnes nées hors métropole ou en corse */
        switch (true) {
            // départements corses. Le calcul de la cles est différent
            case $return['departement'] == '2A' :
                $aChecker = floatval(str_replace('A', 0, substr($numero, 0, 13)));
                $aChecker -= 1000000;
                break;
            case $return['departement'] == '2B' :
                $aChecker = floatval(str_replace('B', 0, substr($numero, 0, 13)));
                $aChecker -= 2000000;
                break;

            case $return['departement'] == 97 || $return['departement'] == 98 :
                $return['departement'] .= substr($return['numcommun'], 0, 1);
                $return['numcommune'] = substr($return['numcommun'], 1, 2);
                if ($return['numcommune'] > 90) { // 90 = commune inconnue
                    return FALSE;
                }
                break;

            case $return['departement'] == 99 :
                $return['pays'] = $match['numcommune'];
                if ($return['numcommune'] > 990) { // 990 = pays inconnu
                    return FALSE;
                }
                break;

            default :
                if ($return['numcommune'] > 990) { // 990 = commune inconnue
                    return FALSE;
                }
                break;
        }

        $clef = 97 - fmod($aChecker, 97);

        if (empty($return['clef'])) {
            $return['clef'] = $clef; // la clef est optionnelle, si elle n'est pas spécifiée, le numéro est valide, mais on rajoute la clef
        }
        if ($clef != $return['clef']) {
            return FALSE;
        }

        return $return;
    }
}

