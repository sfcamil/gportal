<?php

namespace Drupal\gepsis\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Jaspersoft\Client\Client;
use Jaspersoft\Exception\RESTRequestException;
use Symfony\Component\HttpFoundation\Response;
use Jaspersoft\Service\Criteria\RepositorySearchCriteria;

/**
 * Defines a route controller for watches autocomplete form elements.
 */
class JasperReportCall extends ControllerBase {

    /**
     * Handler for autocomplete request.
     */
    public function callCurlJasperReport() {
        // $typeContr = $_POST['typeContr'];
        //Regular output: html, pdf, csv, docx, pptx, xls, xlsx, rtf, odt, ods, xml
        //Metadata output: data_csv, data_xls, data_json
        $format = 'html';
        $monJAva = null;
        $userDrupal = User::load(\Drupal::currentUser()->id());
        $adhCode = $userDrupal->get('field_active_adherent_code') -> value;

        $url = "http://localhost:8085/jasperserver-pro";
        $user = 'admin_ocara';
        $pass = 'sndcjasper';
        $organisation = 'organization_1';
        $resource = '/OCARA/TESTS/test_portail_suivi_graphique_Rapport';

        // https://community.jaspersoft.com/wiki/php-client-sample-code#Running_a_Report
        // JS visualize// https://community.jaspersoft.com/documentation/tibco-jasperreports-server-visualizejs-guide/v790/api-reference-visualizejs

        try {
            $jasperClient = new Client($url, $user, $pass, $organisation);

            // Search for specific items in repository
            $criteria = new RepositorySearchCriteria();
            // $criteria -> q = "Liste des";
            $criteria -> folderUri = '/OCARA/TESTS';
            $results = $jasperClient->repositoryService()->searchResources($criteria);
            $controls = $jasperClient->reportService()->getReportInputControls($resource);

            $reportLista = array();
            foreach($results -> items as $value) {
                // $controls = $jasperClient->reportService()->getReportInputControls($value -> uri);
                $reportLista[] = array(
                        'uri' => $value -> uri,
                        'label' => $value -> label,
                        'description' => $value -> label
                    //'controls' => $controls
                );
            }

            $stringSelectList = '<option value="" selected disabled hidden>Choose here</option>';
            foreach($reportLista as $value) {
                $stringSelectList = $stringSelectList . '<option value="' . $value['uri'] . '">' . $value['label'] . '</option>';
            }
            $stringSelectList = '<select id="selected_resource" disabled="true" name="report">' . $stringSelectList . '</select>';
            /*
             $controls = array(
             'code_1' => array(
             $adhCode
             ),
             'baseUrl' => array(
             $url
             )
             );

             $report = $jasperClient->reportService()->runReport($resource, $format, null, null, $controls);
             */
        } catch ( RESTRequestException $e ) {
            echo 'RESTRequestException:';
            echo 'Exception message:   ', $e->getMessage(), "\n";
            echo 'Set parameters:      ', $e -> parameters, "\n";
            echo 'Expected status code:', $e -> expectedStatusCodes, "\n";
            echo 'Error code:          ', $e -> errorCode, "\n";
        }

        $response = new Response();
        if($format == 'pdf') {
            $response -> headers->set('Content-Type', 'application/pdf');
            $response->setContent($monJAva);
            return $response;
        } else if($format == 'html') {
            $build = array(
                    '#type' => 'inline_template',
                    // '#theme' => 'jasperReportTheme',
                    '#template' => '{{ jasperHtml | raw }}',
                    '#context' => [
                            'jasperHtml' => $stringSelectList . '<div id="containerJasperReport"></div>'
                    ],
                    '#attached' => [
                            'library' => [
                                    'gepsis/includeJasper'
                            ],
                            'drupalSettings' => [
                                    'gepsis' => [
                                            'url' => $url,
                                            'user' => $user,
                                            'pass' => $pass,
                                            'organisation' => $organisation,
                                            'resource' => $resource,
                                            'adhCode' => $adhCode
                                    ]
                            ]
                    ]
            );
            return $build;
        }
        return;
    }
}