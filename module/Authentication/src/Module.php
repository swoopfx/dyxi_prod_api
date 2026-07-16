<?php

declare(strict_types=1);

namespace Authentication;

use Authentication\Exceptions\EmptyTokenException;
use Authentication\Exceptions\ExpiredAuthDateException;
use Authentication\Exceptions\InvalidTokenException;
use Authentication\Service\ApiAuthenticateService;
use Exception;
use Laminas\Mvc\MvcEvent;
use Laminas\Session\Container;

class Module
{
    public function getConfig(): array
    {
        /** @var array $config */
        $config = include __DIR__ . '/../config/module.config.php';
        return $config;
    }

    // public function getServiceConfig(){
    //     "Laminas\Authentication\AuthenticationService"=>function()
    // }

    public function onBootstrap(MvcEvent $e)
    {
        $application = $e->getApplication();
        $eventManager = $application->getEventManager();
        $eventManager->attach("route", [$this, 'onRoute'], -50);
    }

    public function onRoute(MvcEvent $e)
    {
        $application = $e->getApplication();
        $routeMatch = $e->getRouteMatch();
        $sm = $application->getServiceManager(); // service Manager
        $controller = $routeMatch->getParam("controller");
        $action = $routeMatch->getParam("action");
        $interface = $routeMatch->getParam("interface");
        $referContainer = new Container("refer");
        $response = $e->getResponse();
        $request = $e->getRequest();
        if ($interface == "api") {
            try {
                // get apiAuthService
                /**
                 * @var ApiAuthenticateService
                 */
                $api_auth = $sm->get("api_authentication_service");
                $generalService = $sm->get("general_service");
                $identityContainer = new Container("api_identity");
                $data = $api_auth->getIdentity();
                $api_auth->setContainerIdentity($data);

                // throw new InvalidTokenException("Expi");
                // throw new EmptyTokenException("edd");
            } catch (ExpiredAuthDateException $eap) {
                $response->setStatusCode(401);
                $response->getHeaders()->addHeaderLine('Content-Type', 'application/json');
                $response->setContent(json_encode([
                    "error" => "Unauthorized",
                    "description" => "date_expired",
                ]));
                return $response;
            } catch (InvalidTokenException $ivt) {
                $response->setStatusCode(401);
                $response->getHeaders()->addHeaderLine('Content-Type', 'application/json');
                $response->setContent(json_encode([
                    "error" => "Unauthorized",
                    "description" => "invalid_token",
                    "desc" => $ivt->getMessage()
                ]));
                return $response;
            } catch (EmptyTokenException $emp) {
                $response->setStatusCode(401);
                $response->getHeaders()->addHeaderLine('Content-Type', 'application/json');
                $response->setContent(json_encode([
                    "error" => "Unauthorized",
                    "description" => "empty_token",
                ]));
                return $response;
            } catch (Exception $e) {
                $response->setStatusCode(401);
                $response->getHeaders()->addHeaderLine('Content-Type', 'application/json');
                $response->setContent(json_encode([
                    "error" => "Unauthorized",
                    "description" => "Something went wrong",
                    "desc" => $e->getMessage()
                ]));
                return $response;
            }


            // get token from header
            // retrieve claim
            // verify
            // return required status code
        } elseif ($interface == "web") {
            try {
                $generalService = $sm->get("general_service");
                $authService = $generalService->getAuthService();
                $referContainer->location = "";
                if (!$authService->hasIdentity()) {
                    $response->setStatusCode(301);
                    $referContainer->location = $request->getUriString();
                    $controller = $e->getTarget();
                    $uri = $request->getUri();
                    $fullLink = sprintf('%s://%s', $uri->getScheme(), $uri->getHost());

                    $response->getHeaders()->addHeaderLine('Location', $fullLink);

                    // $e->stopPropagation();
                }
            } catch (\Throwable $th) {
                //throw $th;
                print_r($th->getMessage());
            }
        } else {
        }
    }
}
