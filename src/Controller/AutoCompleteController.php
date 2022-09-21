<?php

namespace Drupal\gepsis\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Xss;

/**
 * Defines a route controller for watches autocomplete form elements.
 */
class AutoCompleteController extends ControllerBase {

    /**
     * Handler for autocomplete request.
     */
    public function handleAutocomplete(Request $request) {
        $results = [];
        $input = $request -> query->get('q');
        $key = $request -> query->get('key');
        $class = $request -> query->get('class');
        $searchValues = $request -> query->get('searchValues');

        // Get the typed string from the URL, if it exists.
        if(!$input) {
            return new JsonResponse($results);
        }

        // @formatter:off
        $string = str_replace(array(
                '+','\\','?','%','#','&','/','$'
        ), '', Xss::filter($input));
        // @formatter:on

        $filter = null;
        foreach($searchValues as $value) {
            if(empty($filter))
                $filter = 'substringof(\'' . strtoupper($string) . '\',' . $value . ')+eq+true';
            else
                $filter .= '+or+substringof(\'' . strtoupper($string) . '\',' . $value . ')+eq+true';
        }

        $elements = GepsisOdataReadClass::getOdataClassValues($class, $filter, 20);
        $finalListe = array();
        foreach($elements as $valElement) {
            $strFound = null;
            foreach($searchValues as $valSearch) {
                $strFound .= $strFound ? ' - ' . $valElement -> $valSearch : $valElement -> $valSearch;
            }
            $finalListe[] = [
                    'value' => $valElement -> $key,
                    'label' => $strFound
            ];
        }
        $response = new JsonResponse($finalListe);
        return $response;
    }
}