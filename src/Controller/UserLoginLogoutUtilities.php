<?php

namespace Drupal\gepsis\Controller;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\gepsis\Utility\InternalFunctions;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\user\Entity\User;
use odataPhp\EntLogin;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;

class UserLoginLogoutUtilities
{

    public static function redirectContactFacturationEmpty($userLogged) {
        // $userLogged = User::load(\Drupal::currentUser()->id()); florin
        if (!empty($_SESSION) && !$userLogged->hasRole('administrator') && $userLogged->hasRole('adherent')) {
            $current_path = \Drupal::service('path.current')->getPath();
            $current_uri = \Drupal::request()->getRequestUri();
            $goTo = FALSE;

            $skipPages = array('adherent',
                'logout',
                'login',
                'utilisateur',
                'accueil',
                'contacts-service');

            foreach ($skipPages as $fn) {
                if (strpos($current_uri, $fn)) {
                    $goTo = TRUE;
                }
            }

            // https://codimth.com/blog/web/drupal/how-redirect-anonymous-user-login-form-after-403-error-drupal-8-9
            if (!$goTo) {
                if (empty($_SESSION['factContactEmail']['CONTACT'])) {
                    $redirect = new RedirectResponse(Url::fromUserInput('/adherent#lb-tabs-tabs-2')->toString());;
                    $redirect->send();
                    \Drupal::messenger()->addWarning(t('Votre contact facturation n\'est pas créé. Veuillez svp. le créer et lui donner une adresse e-mail valide.'), TRUE);
                } else if (empty($_SESSION['factContactEmail']['EMAIL'])) {
                    $redirect = new RedirectResponse(Url::fromUserInput('/adherent#lb-tabs-tabs-2')->toString());;
                    $redirect->send();
                    \Drupal::messenger()->addWarning(t('Votre contact facturation est créé mais n\'a pas d\'adresse e-mail valide. Veuillez svp. compléter les informations de ce contact'));
                }
            }
        }
    }

    /*
     *  check and set adherents lista and active adherent
     */
    public static function setupUserActiveAdherent($account) {
        $user = user_load_by_name($account->getAccountName());
        $adherentOid = $user->get('field_active_adherent_oid')->value;

        $activeAdherent = $account->get('field_active_adherent_oid')->value;
        $listaAdherents = $user->get('field_adherents')->getValue();
        $roleAdherent = $account->hasRole('adherent');


        // If hasListaAdherents FALSE and hasActiveAdherents FALSE check if hasRoleAdherent
        // if YES then remove and logout
        if (!$listaAdherents && !$activeAdherent && $roleAdherent) {
            $account->removeRole('adherent');
            // TODO : logout

        }

        // Check if the name of activeAdherent was changed in Geps
        $userActiveAdherentName = $account->get('field_active_adherent_name')->value;
        $customer = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_INFO', "ENTR_O_ID eq " . $activeAdherent, 1);
        if ($customer->ENTR_NOM != $userActiveAdherentName) {
            $account->set('field_active_adherent_name', $customer->ENTR_NOM);
            $account->save();
        }

        return;
        // test commit

        // If hasListaAdherents TRUE and hasActiveAdherents TRUE check if activAdherent in lista
        // if not copy to lista
        if ($listaAdherents && $activeAdherent) {
            foreach ($listaAdherents as $item) {
                $paragraph = Paragraph::load($item['target_id']);
                if ($paragraph->getType() === 'field_adherents' && !$paragraph->field_adherent_oid->isEmpty()) {
                    $adherentOid = $paragraph->get('field_adherent_oid')->first()->getValue();
                    $adherentCode = $paragraph->get('field_code_adherent')->first()->getValue();
                    $adherentName = $paragraph->get('field_nom_adherent')->first()->getValue();
                }
            }

        }

        // If hasListaAdherents FALSE and hasActiveAdherents TRUE copy activAdherent to lista

        // If hasListaAdherents TRUE and hasActiveAdherents FALSE copy ONE Adherent to activAdherent

        // If hasListaAdherents TRUE and hasActiveAdherents TRUE check if hasRoleAdherent
        // if NO then add role

        // BATCH - check if for every adherent name was not changed in Geps


        if (!empty($fields_adherents_lista)) {
            // check every adherent for this user if it's alive on geps
            $svc = prepareReadClass();
            foreach ($fields_adherents_lista as $key => $value) {
                $query = $svc->V1_ENTR_INFO()->filter("ENTR_O_ID eq " . $value->field_adherent_oid_collection['und'][0]['value']);
                $viewAdherent = $query->Execute()->Result;
                if (empty($viewAdherent[0]) || $viewAdherent[0]->ENTR_IS_ACTIVE == 'N') {
                    // if this adherent not exists or innactive on geps the deleted from list
                    unset($account->field_adherents['und'][$key]);
                    unset($fields_adherents_lista[$key]);
                    // check if the same with active adherent the delete active also
                    if ($account->field_adherent_oid['und'][0]['value'] == $value->field_adherent_oid_collection['und'][0]['value']) {
                        unset($account->field_adherent_code['und'][0]);
                        unset($account->field_adherent_oid['und'][0]);
                        unset($account->field_adherent_name['und'][0]);
                        $hasActiveAdherents = FALSE;
                    }
                    user_save($account);
                } else {
                    // test if the name of the adherent was changed
                    $entrName = trim($viewAdherent[0]->ENTR_NOM);
                    if (trim($value->field_adherent_name_collection['und'][0]['value']) != $entrName) {
                        $fields_adherents_lista[$key]->field_adherent_name_collection['und'][0]['value'] = $entrName;
                        $entity = entity_load_single('field_collection_item', $value->item_id);
                        $entity->field_adherent_name_collection['und'][0]['value'] = $entrName;
                        $entity->save();
                    }
                }
            }
        }

        // lista adherents is clear, we have only actives ones with updates names
        // if active adherent was dissabled on geps then it is also empty

        // we can have 2 situations
        if ($hasActiveAdherents == TRUE) {
            // adherent active missing from lista adherents for this user then insert
            $activeIsPresent = FALSE;
            if (!empty($fields_adherents_lista)) {
                foreach ($fields_adherents_lista as $key => $value) {
                    if ($account->field_adherent_oid['und'][0]['value'] == $value->field_adherent_oid_collection['und'][0]['value']) {
                        $activeIsPresent = TRUE;
                        // we found the active adherent in lista we chceck if the name is the same
                        if (trim($account->field_adherent_name['und'][0]['value']) != $value->field_adherent_name_collection['und'][0]['value']) {
                            $account->field_adherent_name['und'][0]['value'] = $value->field_adherent_name_collection['und'][0]['value'];
                            user_save($account);
                        }
                    }
                }
            }
            if ($activeIsPresent == FALSE) {
                $newAdherentForLista = array();
                $newAdherentForLista['field_name'] = 'field_adherents';
                $newAdherentForLista['field_adherent_oid_collection']['und'][0] = $account->field_adherent_oid['und'][0];
                $newAdherentForLista['field_adherent_code_collection']['und'][0] = $account->field_adherent_code['und'][0];
                $newAdherentForLista['field_adherent_name_collection']['und'][0] = $account->field_adherent_name['und'][0];

                $entitynewAdherentForLista = entity_create('field_collection_item', $newAdherentForLista);
                $entitynewAdherentForLista->setHostEntity('user', $account);
                $entitynewAdherentForLista->save();
            }
        } else if ($hasActiveAdherents == FALSE && !empty($fields_adherents_lista)) {
            // adherent active is empty, just pick one from lista
            $edit = array();
            $edit['field_adherent_oid']['und'][0] = $fields_adherents_lista[0]->field_adherent_oid_collection['und'][0];
            $edit['field_adherent_code']['und'][0] = $fields_adherents_lista[0]->field_adherent_code_collection['und'][0];
            $edit['field_adherent_name']['und'][0] = $fields_adherents_lista[0]->field_adherent_name_collection['und'][0];
            user_save($account, $edit);
            $hasActiveAdherents = TRUE;
        } else if ($hasActiveAdherents == FALSE && empty($fields_adherents_lista)) {
            // if we are here then no active and no lista so remove role adherent
            // and check if contract suspended
            $key = array_search('Adherent', $account->roles);
            if ($key == TRUE) {
                $roles = user_roles(TRUE);
                $ridA = array_search('Adherent', $roles);
                $ridC = array_search('Comptable', $roles);
                //if($ridA != FALSE || $ridC != FALSE) {
                if ($hasActiveAdherents == FALSE && empty($fields_adherents_lista)) {
                    // Make a copy of the roles array but without the Adherent role
                    $new_roles = array();
                    foreach ($account->roles as $id => $name) {
                        if ($id != $ridA /* || $id != $ridC */) {
                            $new_roles[$id] = $name;
                        }
                    }
                    user_save($account, array(
                        'roles' => $new_roles
                    ));
                }
            }

            // TODO goto page to inform the account was suspended
            if (!$source)
                drupal_goto();
            // $_GET['destination'] = '<front>';
        }

// add role adherent if not present
        if ($hasActiveAdherents == TRUE) {
            $key = array_search('Adherent', $account->roles);
            if ($key == FALSE) {
                if ($role = user_role_load_by_name('Adherent')) {
                    user_multiple_role_edit(array(
                        $account->uid
                    ), 'add_role', $role->rid);
                }
            }
            // update adherent name
            $entrCode = $account->field_adherent_code['und'][0]['value'];
            $entrOId = $account->field_adherent_oid['und'][0]['value'];
            $entrName = $account->field_adherent_name['und'][0]['value'];
            // setActiveAdherentInfo($account->uid, $entrCode, $entrOId, $entrName);
        }
        return;

    }

    /*
     * SET session status adherent (suspended, innactive ...)
     */
    public static function checkAndSetSessAdherentStatus($account) {
        if (isset($_SESSION)) {
            unset($_SESSION['sessAdherentStatus']);
            $account = user_load_by_name($account->getAccountName());
            $oidAdh = $account->get('field_active_adherent_oid')->value;
            $customer = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_INFO', "ENTR_O_ID eq " . $oidAdh, 1);

            $_SESSION['sessAdherentStatus'] = array();
            if (!empty($customer)) {
                $_SESSION['sessAdherentStatus']['isSuspended'] = $customer->IS_SUSPENDED;
                $_SESSION['sessAdherentStatus']['raisonSuspended'] = $customer->MOTIF_SUSPENSION;
                $_SESSION['sessAdherentStatus']['codeSuspended'] = $customer->SUSPENDED_CODE;
            }
        }
    }

    public static function checkAndSetSessFactEmail($account) {
        if (isset($_SESSION)) {
            unset($_SESSION['factContactEmail']);
            $account = user_load_by_name($account->getAccountName());
            $oidAdh = $account->get('field_active_adherent_oid')->value;
            $viewDetailCPTContact = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_ADHERENT_CONTACT', "ENTR_O_ID eq " . $oidAdh . " and ROLE_CODE eq 'CPT'");

            if (!empty($viewDetailCPTContact)) {
                foreach ($viewDetailCPTContact as $key => $value) {
                    $_SESSION['factContactEmail']['CONTACT'] = $value->CONTACT_O_ID;
                    if (!empty($value->E_MAIL)) {
                        // $_SESSION['factContactEmail'] = array();

                        $_SESSION['factContactEmail']['EMAIL'] = $value->E_MAIL;
                    }
                }
            }
        }
    }

    /**
     * Write to geps user was IN
     */
    public static function setFlagActiveUser(&$account, $state) {
        $eliminateRoles = array(
            'geps',
            'administrator',
            'assistante',
            'dirigeant'
        );
        $user = user_load_by_name($account->getAccountName());
        $roles = $account->getRoles();
        $fields_adherents = $user->get('field_adherents')->getValue();

        if (count(array_intersect(array_map('strtolower', $eliminateRoles), array_map('strtolower', $roles))) < 1 && count($fields_adherents) > 0) {
            $svcWrite = GepsisOdataWriteClass::prepareWriteClass();
            $request = \Drupal::request();
            $session_id = $request->getSession()->getId();

            if ($state == 'login') {
                $customer = EntLogin::CreateEntLogin(null);
                $customer->userId = $account->getAccountName();
                $customer->entreprise = $account->get('field_active_adherent_oid')->value;
                $customer->session_id = $session_id;
                $customer->active = 'Y';
                /*
                if (!empty($account->getN))
                    $customer->last_name = $account->field_nom['und'][0]['value'];
                if (!empty($account->field_prenom['und'][0]['value']))
                    $customer->first_name = $account->field_prenom['und'][0]['value'];
                */
                InternalFunctions::setupTraceInfos($customer);
                $svcWrite->AddToEntLogin($customer);
                $svcWrite->SaveChanges();
            } else if ($state == 'logout') {
                if ($user) {
                    $query = $svcWrite->EntLogin()->filter("entreprise eq " . $user->get('field_active_adherent_oid')->value . " and session_id eq '" . $session_id . "'");
                    try {
                        try {
                            $customer = $query->Execute()->Result;
                        } catch (\Throwable $e) {
                            return;
                        }
                        if (!empty($customer)) {
                            $customer[0]->active = 'N';
                            InternalFunctions::setupTraceInfos($customer);
                            $svcWrite->UpdateObject($customer);
                            $svcWrite->SaveChanges();
                        }
                    } catch (Exception $e) {
                        print_r($e);
                    }
                }
            }

        }
        return;
    }
}