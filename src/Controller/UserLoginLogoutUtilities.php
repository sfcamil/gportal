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
        return;
        $account = user_load_by_name($account->getAccountName());
        $activeAdherentOid = $account->get('field_active_adherent_oid')->value;
        $listaParagraphAdherents = $account->get('field_adherents')->getValue();
        $roleAdherent = $account->hasRole('adherent');
        $paragraph = null;

        // create a lista of attached adherents and check if alive
        if (!empty($listaParagraphAdherents)) {
            for ($cnt = 0; $cnt < count($listaParagraphAdherents); $cnt++) {
                $paragraph = Paragraph::load($listaParagraphAdherents[$cnt]['target_id']);
                if ($paragraph->getType() === 'field_adherents' && !$paragraph->field_adherent_oid->isEmpty()) {
                    if (empty($filter))
                        $filter = "(ENTR_O_ID eq " . $paragraph->field_adherent_oid->value . " and ENTR_IS_ACTIVE eq 'Y')";
                    else
                        $filter = $filter . " or (ENTR_O_ID eq " . $paragraph->field_adherent_oid->value . " and ENTR_IS_ACTIVE eq 'Y')";
                }
            }
            $listaGepsUserAdherents = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_INFO', $filter);
        }

        // check if activ adherent alive and set name
        if (!empty($activeAdherentOid)) {
            // $customer = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_INFO', "ENTR_O_ID eq " . $activeAdherentOid . " and ENTR_IS_ACTIVE eq 'Y'", 1);
            $key = array_search($activeAdherentOid, array_column($listaGepsUserAdherents, 'ENTR_O_ID'));
            if ($key === false) {
                // actif adherent is innactive in Geps
                $account->set('field_active_adherent_oid', '');
                $account->set('field_active_adherent_code', '');
                $account->set('field_active_adherent_name', '');
                $account->save();
            } else {
                // Check if the name of activeAdherent was changed in Geps
                $customer = $listaGepsUserAdherents[$key];
                if ($customer->ENTR_NOM != $account->get('field_active_adherent_name')->value) {
                    $account->set('field_active_adherent_name', $customer->ENTR_NOM);
                    $account->save();
                }
            }
        }

        // hasListaAdherents TRUE / hasActiveAdherents TRUE check if activAdherent in lista
        if (!empty($listaParagraphAdherents) && !empty($activeAdherentOid)) {
            $existsInLista = FALSE;
            foreach ($listaParagraphAdherents as $k => $item) {
                $customer = null;
                $paragraphDeleteFromUser = FALSE;
                $paragraph = Paragraph::load($item['target_id']);
                if ($paragraph->getType() === 'field_adherents') {
                    if (!$paragraph->field_adherent_oid->isEmpty()) {
                        if ($paragraph->field_adherent_oid->value == $activeAdherentOid)
                            $existsInLista = TRUE;

                        // check name of adherent
                        $key = array_search($paragraph->field_adherent_oid->value, array_column($listaGepsUserAdherents, 'ENTR_O_ID'));
                        if ($key !== false) {
                            $customer = $listaGepsUserAdherents[$key];
                            if ($customer->ENTR_NOM != $paragraph->field_nom_adherent->value) {
                                $paragraph->set('field_nom_adherent', $customer->ENTR_NOM);
                                $paragraph->save();
                            }
                        } else {
                            $paragraphDeleteFromUser = TRUE;
                        }
                    } else
                        $paragraphDeleteFromUser = TRUE;
                }

                if ($paragraphDeleteFromUser === TRUE) {
                    $paragraph->delete();
                    unset($listaParagraphAdherents[$k]);
                    $account->set('field_adherents', $listaParagraphAdherents);
                    $account->save();
                }
            }
            // if active not in lista then insert
            if ($existsInLista == FALSE) {
                $paragraph = InternalFunctions::createParagraphAdherent($customer, $account);
            }
        }

        // get last situation
        $listaParagraphAdherents = $account->get('field_adherents')->getValue();
        $activeAdherentOid = $account->get('field_active_adherent_oid')->value;

        // last check
        if (empty($listaParagraphAdherents) && empty($activeAdherentOid)) {
            // !hasListaAdherents && !hasActiveAdherents: logout
            if ($roleAdherent) // if hasRoleAdherent remove before logout
                $account->removeRole('adherent');
            user_logout();
        } else
            if (!empty($listaParagraphAdherents) && !empty($activeAdherentOid)) {
                // hasListaAdherents TRUE / hasActiveAdherents TRUE  but not role adherent then insert role
                if (!$roleAdherent) {
                    $account->addRole('adherent');
                    $account->save();
                }
            } else if (!empty($listaParagraphAdherents) && empty($activeAdherentOid)) {
                // if !hasActiveAdherents insert last one from above
                // $item = current(reset($listaParagraphAdherents[0]['target_id']));
                $paragraph = Paragraph::load($listaParagraphAdherents[0]['target_id']);
                InternalFunctions::setAdherentActifByParagraph($paragraph, $account);
            }
    }

    /*
     * SET session status adherent (suspended, innactive ...)
     */
    public
    static function checkAndSetSessAdherentStatus($account) {
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

    public
    static function checkAndSetSessFactEmail($account) {
        if (isset($_SESSION)) {
            unset($_SESSION['factContactEmail']);
            $account = user_load_by_name($account->getAccountName());
            $oidAdh = $account->get('field_active_adherent_oid')->value;
            $viewDetailCPTContact = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_ADHERENT_CONTACT', "ENTR_O_ID eq " . $oidAdh . " and ROLE_CODE eq 'CPT'");

            if (!empty($viewDetailCPTContact)) {
                foreach ($viewDetailCPTContact as $key => $value) {
                    $_SESSION['factContactEmail']['CONTACT'] = $value->CONTACT_O_ID;
                    if (!empty($value->E_MAIL)) {
                        $_SESSION['factContactEmail']['EMAIL'] = $value->E_MAIL;
                    }
                }
            }
        }
    }

    /**
     * Write to geps user was IN
     */
    public
    static function setFlagActiveUser(&$account, $state) {
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
                try {
                    $svcWrite->SaveChanges();
                } catch (\Throwable $e) {
                    return;
                }
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
