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
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * @var \Drupal\shibboleth\Authentication\ShibbolethAuthManager
   */
  private $shibAuthManager;

  /**
   * Constructs a WebformExceptionHtmlSubscriber object.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The HTTP kernel.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect destination service.
   * @param \Symfony\Component\Routing\Matcher\UrlMatcherInterface $access_unaware_router
   *   A router implementation which does not check access.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration object factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   * @param \Drupal\webform\WebformTokenManagerInterface $token_manager
   *   The webform token manager.
   */
  public function __construct(/*HttpKernelInterface $http_kernel, LoggerInterface $logger, RedirectDestinationInterface $redirect_destination, UrlMatcherInterface $access_unaware_router, AccountInterface $account, */ConfigFactoryInterface $config_factory, ShibbolethAuthManager $shib_auth) {
    // parent::__construct($http_kernel, $logger, $redirect_destination, $access_unaware_router);

    // $this->account = $account;
    $this->configFactory = $config_factory;
    $this->shibAuthManager = $shib_auth;
    // $this->renderer = $renderer;
    // $this->messenger = $messenger;
    // $this->tokenManager = $token_manager;
  }

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
    dpm($access_rules, 'access rules');
    $shibboleth_required = $access_rules['create']['shibboleth'];
    if ($shibboleth_required) {
      \Drupal::messenger()->addStatus('shibboleth session is required');
      if (!$this->shibAuthManager->sessionExists()) {
        $auth_redirect = $this->shibAuthManager->getAuthenticateUrl();
        $response = new TrustedRedirectResponse($auth_redirect->toString());
        $event->setResponse($response);
        return;
      }
    }
    // \Drupal::messenger()->addStatus('webform access check event subscriber');
    // dpm($event, 'webform_shibboleth request event');
    dpm($route_parameters, 'webform_shibboleth route_parameters');
  }
  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      // Perform before ShibbolethPath access check.
      KernelEvents::REQUEST => ['onRequestCheckWebformAccess', 36],
      // Perform before AuthenticationSubscriber->onExceptionAccessDenied()
      // KernelEvents::EXCEPTION => ['onShibbolethSessionException', 70],
    ];
  }

}
