<?php

namespace Drupal\gepsis\Controller;

use odataPhp\GepsFranceWriteEntities;
use odataPhp\Context\SaveChangesOptions;
use odataPhp\Exception\DataServiceRequestException;
use odataPhp\Exception\InvalidOperation;
use odataPhp\Exception\ODataServiceException;

class GepsisOdataWriteClass {

    public function __construct($uri = "") {
        // parent::__construct($uri);
    }

    /**
     * class, filter, id = true for single result
     */
    public static function getOdataClassValues($class, $filter = null, $count = null) {
        $result = null;
        try {
            $svc = self::prepareWriteClass();
            $query = $filter ? $svc->$class()->filter($filter) : $svc->$class();
            $result = $count ? $query->Top($count)->Execute() -> Result : $query->Execute() -> Result;
        } catch ( \Throwable $e ) { // Use Throwable instead of Exception here
            \Drupal::logger('gepsis')->error($e->getMessage());
            // \Drupal::messenger()->addError($e->getMessage());
            // $form_state->setRedirect('<front>');
            return;
        }
        return $count == 1 ? $result[0] : $result;
    }

    public static function prepareWriteClass() {
        // $path = ini_get('include_path');
        $lnk = self::getWriteClassServer();

        try {
            $svc = new GepsFranceWriteEntities($lnk);
            $svc->SetSaveChangesOptions(SaveChangesOptions::None);
            $svc -> UsePostTunneling = TRUE;
        } catch ( ODataServiceException $e ) {
            drupal_set_message("Error:   " . $e->getError() . "<br>" . "Detailed Error:" . $e/* -> getDetailedError()*/, 'error');
        } catch ( DataServiceRequestException $e ) {
            drupal_set_message($e -> Response->getError(), 'error');
        } catch ( InvalidOperation $e ) {
            drupal_set_message($e->getError(), 'error');
        }
        return $svc;
    }

    private static function getWriteClassServer() {
        if(!isset($_SESSION['finalWriteClassServer'])) {
            $server = array();
            /** @var \Drupal\Core\Entity\EntityTypeManager $etm */
            $etm = \Drupal::service('entity_type.manager');
            $odata_enities = $etm->getStorage('odata_entity')->loadMultiple();

            /** @var \Drupal\odata\Entity\OdataEntity $entity */
            foreach($odata_enities as $entity) {
                if(str_contains($entity->getEndpointUrl(), 'GepsFranceWrite'))
                    $server = $entity->getEndpointUrl();
            }
            if(empty($server)) {
                dpm(t('No finalWriteClassServer.'));
                return;
            }

            $_SESSION['finalWriteClassServer'] = $server;
        }
        return $_SESSION['finalWriteClassServer'];
    }
}

