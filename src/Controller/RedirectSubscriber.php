<?php

namespace Drupal\gepsis\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class RedirectSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public function checkFrontRedirection(RequestEvent $event) {
        global $base_url;
        $roles = \Drupal::currentUser()->getRoles();
        $route_name = \Drupal::routeMatch()->getRouteName();

        if (\Drupal::currentUser()->isAnonymous()) {
            if ($route_name != 'user.login' &&
                $route_name != 'user.reset' &&
                $route_name != 'user.reset.form' &&
                $route_name != 'user.reset.login' &&
                $route_name != 'entity.node.canonical' &&
                $route_name != 'user.pass') {
                // add logic to check other routes you want available to anonymous users,
                // otherwise, redirect to login page.
                if (strpos($route_name, 'view') === 0 && strpos($route_name, 'rest_') !== FALSE) {
                    return;
                }
                $response = new RedirectResponse($base_url . '/user/login', 301);
                $event->setResponse($response);
                $event->stopPropagation();
                return;
            }
        } else if (in_array('adherent', $roles)) {
            if ($route_name == 'entity.user.canonical' && $route_name != 'user.logout') {
                $response = new RedirectResponse($base_url . '/accueil', 301);
                $event->setResponse($response);
                $event->stopPropagation();
                return;
                // page_manager.page_view_accueil_accueil-layout_builder-0
            }
        }
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents() {
        $events[KernelEvents::REQUEST][] = array('checkFrontRedirection');
        return $events;
    }
}