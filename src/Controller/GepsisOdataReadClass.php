<?php

namespace Drupal\gepsis\Controller;

use odataPhp\GepsFranceEntities;
use odataPhp\Exception\DataServiceRequestException;
use odataPhp\Exception\InvalidOperation;
use odataPhp\Exception\ODataServiceException;

class GepsisOdataReadClass
{

    function __construct() {
        // self::_odata_setup_include_path();
        // require_once drupal_get_path('module', 'gepsis').'/src/lib/odataPhp/GepsFranceEntities.php';
    }

    /**
     * class, filter, count = true for single result
     */
    public static function getOdataClassValues($class, $filter = null, $count = null) {
        $result = null;
        try {
            $svc = self::prepareReadClass();
            $query = $filter ? $svc->$class()->filter($filter) : $svc->$class();
            $result = $count ? $query->Top($count)->Execute()->Result : $query->Execute()->Result;
        } catch (\Throwable $e) { // Use Throwable instead of Exception here
            \Drupal::logger('gepsis')->error('Error getOdataClassValues read: ' . $e->getMessage());
            // \Drupal::messenger()->addError($e->getMessage());
            // $form_state->setRedirect('<front>');
            return;
        }
        if ($result)
            return $count == 1 ? $result[0] : $result;
        return FALSE;
    }

    public static function prepareReadClass() {
        // $path = ini_get('include_path');
        $lnk = self::getReadClassServer();

        try {
            $svc = new GepsFranceEntities($lnk);
            // $svc -> UsePostTunneling = TRUE;
        } catch (ODataServiceException $e) {
            drupal_set_message("Error:   " . $e->getError() . "<br>" . "Detailed Error:" . $e/* -> getDetailedError()*/, 'error');
        } catch (DataServiceRequestException $e) {
            drupal_set_message($e->Response->getError(), 'error');
        } catch (InvalidOperation $e) {
            drupal_set_message($e->getError(), 'error');
        }
        return $svc;
    }

    private static function getReadClassServer() {
        if (!isset($_SESSION['finalReadClassServer'])) {
            $server = array();
            /** @var \Drupal\Core\Entity\EntityTypeManager $etm */
            $etm = \Drupal::service('entity_type.manager');
            $odata_enities = $etm->getStorage('odata_entity')->loadMultiple();

            /** @var \Drupal\odata\Entity\OdataEntity $entity */
            foreach ($odata_enities as $entity) {
                if (!str_contains($entity->getEndpointUrl(), 'GepsFranceWrite'))
                    $server = $entity->getEndpointUrl();
            }

            if (empty($server)) {
                dpm(t('No finalReadClassServer.'));
                return;
            }
            $_SESSION['finalReadClassServer'] = $server;
        }
        return $_SESSION['finalReadClassServer'];
    }

    function _odata_setup_include_path() {
        $path = ini_get('include_path');
        // Ensure the PHP OData SDK is available
        $lib = realpath($_SERVER['DOCUMENT_ROOT'] . base_path() . drupal_get_path('module', 'gepsis') . '/src/lib/odataPhp') . DIRECTORY_SEPARATOR;
        \Drupal::state()->set('odata_sdk_path', $path);

        if (!strstr($path, $lib)) {
            ini_set('include_path', $path . PATH_SEPARATOR . $lib);
        }
        $path = ini_get('include_path');
    }
}

