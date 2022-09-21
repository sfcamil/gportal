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
class PosteAutoCompleteController extends ControllerBase {

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
        $query = $svc->V1_ALL_POSTES_PCS()->filter('substringof(\'' . strtoupper($string) . '\',POSTE_CODE)+eq+true+or+substringof(\'' . strtoupper($string) . '\',toupper(POSTE_LABEL))+eq+true');
        try {
            $customer = $query->Top('20')->Execute() -> Result;
        } catch ( Exception $e ) {
            return;
        }

        $finalListeAllCityes = array();
        foreach($customer as $value) {
            $strFound = $value -> POSTE_CODE . ' - ' . $value -> POSTE_LABEL ;
            $finalListeAllCityes[] = [
                    'value' => $value -> POSTE_O_ID,
                    // 'label' => Html::escape($strFound)
                    'label' => $strFound
            ];
        }
        $response = new JsonResponse($finalListeAllCityes);
        return $response;
    }
}