<?php

namespace Drupal\gepsis\Breadcrumb;

use Drupal;
use Drupal\Core\Breadcrumb\Breadcrumb;
use Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\gepsis\Controller\GepsisOdataReadClass;

class GepsisBreadcrumbBuilder implements \Drupal\Core\Breadcrumb\BreadcrumbBuilderInterface
{

    /**
     * @inheritDoc
     */
    public function applies(\Drupal\Core\Routing\RouteMatchInterface $route_match) {
        // This breadcrumb will apply for all nodes.
        $parameters = $route_match->getParameters()->all();

        //Checking if node then only calling our builder otherwise loading default loader
        if (isset($parameters['base_route_name']) && $parameters['base_route_name'] === 'page_manager.page_view_salarie') {
            return TRUE;
        }

        return FALSE;
    }

    /**
     * @inheritDoc
     */
    public function build(\Drupal\Core\Routing\RouteMatchInterface $route_match) {
        $current_parameters = \Drupal::routeMatch()->getParameters();
        $travOid = $current_parameters->get('travOid');
        $viewDetailPerson = GepsisOdataReadClass::getOdataClassValues('V1_ENTR_TRAVS', "TRAV_O_ID eq " . $travOid, TRUE);
        $persBirthDate = \Drupal::service('date.formatter')->format(strtotime($viewDetailPerson->PERS_BIRTH_DATE), 'short_reverse');
        $pers = $viewDetailPerson->PERS_NAME . ' ' . $viewDetailPerson->PERS_FIRST_NAME . ' (' . $persBirthDate . ' - ' . $viewDetailPerson->PERS_SEXE . ')';

        $breadcrumb = new Breadcrumb();
        $breadcrumb->addLink(Link::createFromRoute(t('Home'), '<front>'));
        $breadcrumb->addLink(Link::createFromRoute(t('Salariés'), 'view.v1_entr_travs.page_1'));
        $breadcrumb->addLink(Link::createFromRoute($pers, '<none>'));

        //Adding cache control,otherwise all breadcrumb will be the same for all pages.
        //By setting a "cache context" to the "url", each requested URL gets it's
        //own cache. This way a single breadcrumb isn't cached for all pages on the
        //site.
        $breadcrumb->addCacheContexts(["url"]);
        // $breadcrumb->addCacheTags($pers);
        return $breadcrumb;
    }
}