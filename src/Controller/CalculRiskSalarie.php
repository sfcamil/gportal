<?php

namespace Drupal\gepsis\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\gepsis\Utility\GetAllFunctions;

/**
 * Defines a route controller for watches autocomplete form elements.
 */
class CalculRiskSalarie extends ControllerBase {

    /**
     * Handler for autocomplete request.
     */
    public function tarbes_calculate_risk_period_pers_details() {
        // sfc: https://stackoverflow.com/questions/8171538/how-do-i-send-an-ajax-post-request-to-a-specific-module-in-drupal6
        $typeContr = $_POST['typeContr'];
        $risqContr = $_POST['risqContr'];
        $age = $_POST['age'];

        $customer = GepsisOdataWriteClass::getOdataClassValues('Decret2017', "categories eq '" . $risqContr . "' and employement_type_category eq '" . $typeContr . "' and age eq " . $age, 1);

        $allExamType = GetAllFunctions::getAllExamenType();
        $examTypeCalcule = $allExamType[$customer -> examen_type];

        $response = new JsonResponse(array(
                'typeCalcule' => $examTypeCalcule,
                'pdcCalcule' => $customer -> pdc,
                'examTypeOid' => $customer -> examen_type,
                'typeContrCateg' => $typeContr
        ));
        return $response;
    }
}