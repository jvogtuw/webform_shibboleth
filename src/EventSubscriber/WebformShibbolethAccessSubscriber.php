<?php

namespace Drupal\webform_shibboleth\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\shibboleth\Authentication\ShibbolethAuthManager;
use Drupal\webform\Entity\Webform;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Route;

class WebformShibbolethAccessSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\shibboleth\Authentication\ShibbolethAuthManager
   */
  private $shibAuthManager;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\shibboleth\Authentication\ShibbolethAuthManager $shib_auth
   *   The Shibboleth authentication manager.
   */
  public function __construct(ShibbolethAuthManager $shib_auth) {
    $this->shibAuthManager = $shib_auth;
  }

  /**
   * Checks if a Webform requires
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function onRequestCheckWebformAccess(RequestEvent $event) {
    $url = Url::fromUserInput($event->getRequest()->getPathInfo());
    if (!$url) {
      return;
    }

    $route_parameters = $url->isRouted() ? $url->getRouteParameters() : [];
    if (empty($route_parameters['webform']) || $url->getRouteName() != 'entity.webform.canonical') {
      return;
    }

    $webform = Webform::load($route_parameters['webform']);
    $access_rules = $webform->getAccessRules();
    $shibboleth_required = $access_rules['create']['shibboleth'];
    if ($shibboleth_required) {
      if (!$this->shibAuthManager->sessionExists()) {
        $auth_redirect = $this->shibAuthManager->getAuthenticateUrl();
        $response = new TrustedRedirectResponse($auth_redirect->toString());
        $event->setResponse($response);
        return;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      // Perform before ShibbolethPath access check.
      KernelEvents::REQUEST => ['onRequestCheckWebformAccess', 36],
    ];
  }

}
