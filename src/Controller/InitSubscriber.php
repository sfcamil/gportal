<?php

namespace Drupal\gepsis\Controller;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use \Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;


class InitSubscriber implements EventSubscriberInterface
{

    /**
     * @inheritDoc
     *
     * https://symfony.com/doc/current/reference/events.html#kernel-controller
     */
    public static function getSubscribedEvents() {
        $events[KernelEvents::REQUEST][] = ['onLoad'];
        return $events;
    }

    public function onLoad(RequestEvent $event) {
        // return;
        $userLogged = User::load(\Drupal::currentUser()->id());
        // if ($userLogged->hasRole('administrator'))  return;

        if (!empty($_SESSION) && $userLogged->hasRole('adherent') && !$userLogged->hasRole('administrator')) {
            $route_name = \Drupal::routeMatch()->getRouteName();
            // $current_path = \Drupal::service('path.current')->getPath();
            // $current_uri = \Drupal::request()->getRequestUri();
            $request = $event->getRequest();
            $current_uri = $request->getPathInfo();
            if ($current_uri == '/') return;
            $goTo = TRUE;

            $skipPages = array('adherent',
                'logout',
                'login',
                'utilisateur',
                'accueil',
                'contacts-service',
                'adherent',
                'profiler',
                'form/contact',
                '/contextual/render');

            foreach ($skipPages as $fn) {
                if (strstr($current_uri, $fn)) {
                    $goTo = FALSE;
                }
            }

            // https://codimth.com/blog/web/drupal/how-redirect-anonymous-user-login-form-after-403-error-drupal-8-9
            $redirect = FALSE;
            if ($goTo == TRUE) {
                if (empty($_SESSION['factContactEmail']['CONTACT'])) {
                    $redirect = TRUE;
                    \Drupal::messenger()->addWarning(t('Votre contact facturation n\'est pas créé. Veuillez svp. le créer et lui donner une adresse e-mail valide.'));
                } else if (empty($_SESSION['factContactEmail']['EMAIL'])) {
                    $redirect = TRUE;
                    \Drupal::messenger()->addWarning(t('Votre contact facturation est créé mais n\'a pas d\'adresse e-mail valide. Veuillez svp. compléter les informations de ce contact'));
                }

                if ($redirect) {
                    $returnResponse = new RedirectResponse('/adherent#lb-tabs-tabs-2');
                    $event->setResponse($returnResponse);
                    $event->stopPropagation();
                    // $redirect->send();
                }
            }
        }


        // TODO
        // checkSuspended
        // checkFacturation contact
    }
}