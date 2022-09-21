<?php

namespace Drupal\gepsis\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Exception;
use Drupal\Component\Utility\Xss;

/**
 * Defines a route controller for watches autocomplete form elements.
 */
class AdherentsAutoCompleteController extends ControllerBase {

    /**
     * Handler for autocomplete request.
     */
    public function handleAutocomplete(Request $request) {
        $results = [];
        $input = $request -> query->get('q');

        // Get the typed string from the URL, if it exists.
        if(!$input) {
            return new JsonResponse($results);
        }

        // @formatter:off
        $string = str_replace(array(
                '+','\\','?','%','#','&','/','$'
        ), '', Xss::filter($input));
        // @formatter:on
        
        $svc = GepsisOdataReadClass::prepareReadClass();
        $query = $svc->V1_ENTR_INFO()->filter('substringof(\'' . strtoupper($string) . '\',ENTR_CODE)+eq+true+or+substringof(\'' . strtoupper($string) . '\',toupper(ENTR_NOM))+eq+true+or+substringof(\'' . strtoupper($string) . '\',toupper(ENTR_DESCR))+eq+true');
        try {
            $customer = $query->Top('20')->Execute() -> Result;
        } catch ( Exception $e ) {
            return;
        }

        $finalListeAllAdherents = array();
        foreach($customer as $value) {
            $strFound = $value -> ENTR_CODE . ' - ' . $value -> ENTR_NOM . ' (' . $value -> ENTR_DESCR . ')';
            $finalListeAllAdherents[] = [
                    'value' => $value -> ENTR_O_ID,
                    // 'label' => Html::escape($strFound)
                    'label' => $strFound
            ];
        }
        $response = new JsonResponse($finalListeAllAdherents);
        return $response;
    }
}