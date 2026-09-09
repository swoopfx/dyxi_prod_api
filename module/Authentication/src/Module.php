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
        $eventManager->attach(MvcEvent::EVENT_FINISH, [$this, 'onFinish'], 100);
    }

    public function onFinish(MvcEvent $e)
    {
        $response = $e->getResponse();
        
        if ($response instanceof \Laminas\ApiTools\ApiProblem\ApiProblemResponse) {
            $problem = $response->getApiProblem();
            if ($problem->status == 400 && str_contains((string)$problem->detail, 'JSON decoding error')) {
                $newResponse = new \Laminas\Http\Response();
                $newResponse->setStatusCode(400);
                $newResponse->getHeaders()->addHeaderLine('Content-Type', 'application/json');
                $newResponse->setContent(json_encode([
                    'success' => false,
                    'error' => 'ValidationError',
                    'description' => 'Invalid JSON payload format'
                ]));
                $e->setResponse($newResponse);
            }
        }
    }

    public function onRoute(MvcEvent $e)
    {
        $application = $e->getApplication();
        $routeMatch = $e->getRouteMatch();
        $sm = $application->getServiceManager(); // service Manager
        $controller = $routeMatch->getParam("controller");
        $action = $routeMatch->getParam("action");
        $interface = $routeMatch->getParam("interface");
        $response = $e->getResponse();
        $request = $e->getRequest();
        $path = method_exists($request, 'getUri') ? $request->getUri()->getPath() : '';
        $routeName = $routeMatch ? (string)$routeMatch->getMatchedRouteName() : '';
        $isApi = ($interface === "api")
            || str_starts_with($path, '/api/')
            || str_starts_with($path, '/auth/ipa/')
            || str_starts_with($routeName, 'api-');

        if ($isApi) {
            $publicRoutes = [
                'login',
                'authenticate',
                'register',
                'verify',
                'resendMobileCode',
                'resend-mobile-code',
                'refresh',
                'logout',
                'google',
                'swagger',
                'swaggerJson',
                'swagger-json',
                'doc',
                'legalInfo',
                'legal-info',
                'legalinfo'
            ];

            if ($action && in_array($action, $publicRoutes, true)) {
                return;
            }

            if (str_contains($path, '/auth/ipa/login')
                || str_contains($path, '/auth/ipa/register')
                || str_contains($path, '/auth/google')
                || str_contains($path, '/api/docs')
                || str_contains($path, '/legal-info')
                || str_starts_with($path, '/admin')) {
                return;
            }

            try {
                // get apiAuthService
                /**
                 * @var ApiAuthenticateService
                 */
                $api_auth = $sm->get("api_authentication_service");
                $api_auth->setRequestObject($request);
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
        } 
        elseif ($interface == "web") {
            try {
                $referContainer = new Container("refer");
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
