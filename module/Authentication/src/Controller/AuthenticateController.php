<?php

namespace Authentication\Controller;

use Actors\Entity\AdminTraining;
use Authentication\Entity\User;
use Authentication\Entity\UserState;
use Authentication\Service\AuthenticationService as ServiceAuthenticationService;
use Doctrine\ORM\EntityManager;
use General\Service\GeneralService;
use Laminas\Authentication\AuthenticationService;
use Laminas\Http\Response;
use Laminas\InputFilter\InputFilter;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Session\Container;
use Laminas\Session\SessionManager;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;
use Actors\Document\Test;
// use Doctrine\ODM\MongoDB\DocumentManager;

class AuthenticateController extends AbstractActionController
{
    /**
     * Undocumented variable
     *
     * @var AuthenticationService
     */
    private $authenticateService;

    /**
     * Undocumented variable
     *
     * @var EntityManager
     */
    private $em;

    private $objectManager;


    private $loginForm;

    private $rabbitProducer;

    /**
     * Undocumented variable
     *
     * @var
     */
    private $authService;


    public function policyAction()
    {
        $this->layout()->setTemplate("others-layout");
        $viewModel = new ViewModel();
        return $viewModel;
    }

    public function deactivateAction()
    {
        $this->layout()->setTemplate("others-layout");
        $viewModel = new ViewModel();
        return $viewModel;
    }

    public function deleteAccountAction()
    {
        $this->layout()->setTemplate("others-layout");
        $viewModel = new ViewModel();
        return $viewModel;
    }

    public function privacyPolicyAction()
    {
        $this->layout()->setTemplate("others-layout");
        $viewModel = new ViewModel();
        return $viewModel;
    }


    public function trainingAction()
    {
        $this->layout()->setTemplate("login-layout");
        $this->getEventManager()->trigger("dump");
        $viewModel = new ViewModel();
        $em = $this->em;
        $data = $em->getRepository(AdminTraining::class)->createQueryBuilder("a")
            ->select("a")->where("a.isActive = :active")
            ->setParameters([
                "active" => TRUE
            ])->getQuery()->getArrayResult();
        $viewModel->setVariables([
            "data" => $data
        ]);
        return $viewModel;
    }

    public function loginAction()
    {
        $viewModel = new ViewModel();

        $obj = $this->objectManager;
      

        $jsonModel = new JsonModel();

        $response = $this->getResponse();

        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = $request->getPost()->toArray();
            $inputFilter = new  InputFilter();
            $inputFilter->add([
                'name' => 'phoneOrEmail',
                'required' => true,
                'allow_empty' => false,
                'filters' => [
                    [
                        'name' => 'StripTags'
                    ],
                    [
                        'name' => 'StringTrim'
                    ]
                ],
                'validators' => [
                    [
                        'name' => 'NotEmpty',
                        'options' => [
                            'messages' => [
                                'isEmpty' => 'Phone Number or email is required'
                            ]
                        ]
                    ]
                ]
            ]);

            $inputFilter->add([
                'name' => 'password',
                'required' => true,
                'allow_empty' => false,
                'filters' => [
                    [
                        'name' => 'StripTags'
                    ],
                    [
                        'name' => 'StringTrim'
                    ]
                ],
                'validators' => [
                    [
                        'name' => 'NotEmpty',
                        'options' => [
                            'messages' => [
                                'isEmpty' => 'Password is required'
                            ]
                        ]
                    ]
                ]
            ]);
            $rememberme = filter_var($this->params()->fromPost('rememberme'), FILTER_VALIDATE_BOOLEAN);

            $inputFilter->setData($post);
            if ($inputFilter->isValid()) {
                $data = $inputFilter->getValues();

                $authService = $this->authService;
                $adapter = $authService->getAdapter();
                $phoneOrEmail = $data["phoneOrEmail"];

                try {
                    $user = $this->em->createQuery("SELECT u FROM Authentication\Entity\User u WHERE u.email = '$phoneOrEmail' OR u.username = '$phoneOrEmail'")->getResult(\Doctrine\ORM\Query::HYDRATE_OBJECT);

                    // $user = $this->user->selectUserDQL($phoneOrEmail);
                    if (count($user) == 0) {
                        throw new \Exception("These credentials are not valid, please register");
                    } else {
                        $user = $user[0];
                    }


                    if (!$user->getEmailConfirmed() == 1) {
                        throw new \Exception('You are yet to confirm your account, please go to the registered email to confirm your account');
                    }
                    if ($user->getState()->getId() > 1) {
                        throw new \Exception("Your username is disabled. Please contact an administrator.");
                    }
                    if ($user->getRole()->getId() < 500) {
                        $response->setStatusCode(401);
                        throw new \Exception("NOt Authorized");
                    }

                    $adapter->setIdentity($user->getEmail());
                    $adapter->setCredential($data["password"]);

                    $authResult = $authService->authenticate();

                    if ($authResult->isValid()) {
                        $identity = $authResult->getIdentity();
                        $authService->getStorage()->write($identity);

                        /**
                         * @var User
                         */
                        $userEntity = $this->identity();
                        if ($rememberme) {
                            $time = 1209600; // 14 days (1209600/3600 = 336 hours => 336/24 = 14 days)
                            $sessionManager = new SessionManager();
                            $sessionManager->rememberMe($time);
                        }
                        $uri = $this->getRequest()->getUri();
                        // var_dump($uri);
                        $fullUrl = sprintf('%s://%s', $uri->getScheme(), $uri->getHost());
                        $referContainer = new Container("refer");
                        $redirect = $fullUrl . $userEntity->getRole()->getDropPage();
                        if ($referContainer->location != "") {
                            $redirect = $referContainer->location;
                        }
                        $response->setStatusCode(201);
                        $jsonModel->setVariables([
                            "redirect" => $redirect
                        ]);
                        return $jsonModel;
                        // return $this->redirect()->toRoute($this->options->getLoginRedirectRoute());
                    } else {
                        throw new \Exception('Invalid Credentials');
                    }
                } catch (\Exception $e) {
                    $response->setStatusCode(Response::STATUS_CODE_400);
                    return $jsonModel->setVariables([
                        "messages" => $e->getMessage(),
                        // "data" => $e->getTrace(),
                    ]);
                }
            } else {
                $response->setStatusCode(498);
                $response->setReasonPhrase('Invalid token!');
                return $jsonModel->setVariables([
                    "messages" => "The username or email is not valid!"
                ]);
            }

            return $jsonModel;
        }

        $this->layout()->setTemplate("login-layout");

        return  $viewModel;
    }

    public function registerAction()
    {
        $response = $this->getResponse();
        $this->layout()->setTemplate("login-layout");
        $viewModel = new ViewModel();
        $request = $this->getRequest();

        if ($request->isPost()) {
            $post = $request->getPost()->toArray();
            try {
                //code...
            } catch (\Throwable $th) {
                //throw $th;
            }
        }




        //     // $form->setValidationGroup('username', 'email', 'password', 'passwordVerify', 'question', 'answer', 'csrf');
        //     // $post = $request->getPost()->toArray();
        //     $inputFilter->setData($post);

        //     if ($inputFilter->isValid()) {

        //         $data = $inputFilter->getValues();
        //         $entityManager = $this->entityManager;
        //         $user->setState($entityManager->find('CsnUser\Entity\State', UserService::USER_STATE_ENABLED));
        //         $user->setUsername(str_replace("-", "", $data["phoneNumber"]));
        //         $user->setPassword(UserService::encryptPassword($data["password"]));
        //         $user->setRegistrationToken(md5(uniqid(mt_rand(), true)));
        //         $user->setUid(UserService::createUserUid());
        //         $user->setFullName($data["fullname"]);
        //         $user->setEmail($data['email']);
        //         $user->setRole($entityManager->find("CsnUser\Entity\Role", UserService::USER_ROLE_CUSTOMER));
        //         $user->setRegistrationDate(new \DateTime());
        //         $user->setUpdatedOn(new \DateTime());
        //         $user->setEmailConfirmed(false);
        //         // var_dump("LLLa");

        //         try {
        //             $fullLink = $this->url()->fromRoute('user-register', array(
        //                 'action' => 'confirm-email',
        //                 'id' => $user->getRegistrationToken()
        //             ), array(
        //                 'force_canonical' => true
        //             ));

        //             $logo = $this->url()->fromRoute('home', array(), array(
        //                 'force_canonical' => true
        //             )) . "assets/img/logo.png";

        //             // $mailer = $this->mail;

        //             $var = [
        //                 'logo' => $logo,
        //                 'confirmLink' => $fullLink
        //             ];

        //             $template['template'] = "email-app-user-registration";
        //             $template['var'] = $var;

        //             $messagePointer['to'] = $user->getEmail();
        //             $messagePointer['fromName'] = "BAU CARS";
        //             $messagePointer['subject'] = "BAU CARS: Confirm Email";

        //             $entityManager->persist($user);
        //             $entityManager->flush();


        //             $response->setStatusCode(Response::STATUS_CODE_201);



        //             $this->generalService->sendMails($messagePointer, $template);
        //             return $jsonModel;
        //         } catch (\Exception $e) {
        //             $response->setStatusCode(Response::STATUS_CODE_400);
        //             $jsonModel->setVariables([
        //                 "messages" => "Something went wrong, please try again later"
        //             ]);
        //             return $jsonModel;
        //             // retguter an error log report
        //             // return $this->errorView->createErrorView('Something went wrong when trying to send activation email! Please, try again later.', $e, $this->options->getDisplayExceptions());
        //             // $this->options->getNavMenu()
        //         }
        //     }
        // } else {
        //     $response->setStatusCode(Response::STATUS_CODE_422);
        //     $jsonModel->setVariables([
        //         "messages" => $inputFilter->getMessages()
        //     ]);
        // }

        // return $viewModel;
    }


    // public function resetPasswordAction()
    // {
    //     $viewmodel = new ViewModel();
    //     return $viewmodel;
    // }

    // public function resetAction()
    // {
    //     $jsonModel = new JsonModel();
    //     $request = $this->getRequest();

    //     $response = $this->getResponse();
    //     if ($request->isPost()) {
    //         $post = $request->getPost();
    //         $email = $post["email"];
    //         /**
    //          * @var User
    //          */
    //         $userEntity = $this->entityManager->getRepository(User::class)->findOneBy([
    //             "email" => $email
    //         ]);
    //         if ($userEntity == null) {
    //             $response->setStatusCode(423);
    //             return $jsonModel;
    //         } else {
    //             try {
    //                 // generate new Token
    //                 $token = md5(uniqid(mt_rand(), true));
    //                 // Hy
    //                 $userEntity->setRegistrationToken($token)->setUpdatedOn(new \Datetime());

    //                 $fullLink = $this->getBaseUrl() . $this->url()->fromRoute('admin-auth', [
    //                     'action' => 'newpassword',
    //                     'id' => $userEntity->getRegistrationToken()

    //                 ]);

    //                 // send email

    //                 $this->entityManager->persist($userEntity);
    //                 $mailData["to"] = $userEntity->getEmail();
    //                 $mailData["subject"] = "AIB Reset Password";
    //                 $mailData["toName"] = $userEntity->getFullname();
    //                 $mailData["template"] = "reset-password-mail";
    //                 $mailData["fulllink"] = $fullLink;
    //                 // $mailData["var"] = [
    //                 //     "link" => $fullLink
    //                 // ];

    //                 // $this->mailService->execute($mailData);

    //                 $this->mailtrap->passwordResetMail($mailData);

    //                 $this->entityManager->flush();
    //                 $response->setStatusCode(201);
    //             } catch (\Throwable $th) {
    //                 $response->setStatusCode(400);
    //                 $jsonModel->setVariables([
    //                     "messages" => $th->getMessage(),
    //                     "data" => $th->getTrace()
    //                 ]);
    //             }
    //         }
    //     }
    //     return $jsonModel;
    // }

    public function newpasswordAction()
    {
        $viewmodel = new ViewModel();
        $jsonmodel = new JsonModel();
        $response = $this->getResponse();
        $token = $this->params()->fromRoute('id');
        try {
            $entityManager = $this->entityManager;
            // if ($token !== '' && $user = $entityManager->getRepository(User::class)->findOneBy(array(
            //     'registrationToken' => $token
            // ))) {
            $request = $this->getRequest();
            if ($request->isPost()) {
                $post = $request->getPost();
                // var_dump($post);
                $inputFilter = new InputFilter();

                $inputFilter->add([
                    'name' => 'password',
                    'required' => true,
                    'allow_empty' => false,
                    'filters' => [
                        [
                            'name' => 'StripTags'
                        ],
                        [
                            'name' => 'StringTrim'
                        ]
                    ],
                    'validators' => [
                        [
                            'name' => 'NotEmpty',
                            'options' => [
                                'messages' => [
                                    'isEmpty' => 'password is required'
                                ]
                            ]
                        ]
                    ]
                ]);

                $inputFilter->add([
                    'name' => 'token',
                    'required' => true,
                    'allow_empty' => false,
                    'filters' => [
                        [
                            'name' => 'StripTags'
                        ],
                        [
                            'name' => 'StringTrim'
                        ]
                    ],
                    'validators' => [
                        [
                            'name' => 'NotEmpty',
                            'options' => [
                                'messages' => [
                                    'isEmpty' => 'Token is required'
                                ]
                            ]
                        ]
                    ]
                ]);

                $inputFilter->add([
                    'name' => 'verifypassword',
                    'required' => true,
                    'allow_empty' => false,
                    'filters' => [
                        [
                            'name' => 'StripTags'
                        ],
                        [
                            'name' => 'StringTrim'
                        ]
                    ],
                    'validators' => [
                        [
                            'name' => 'NotEmpty',
                            'options' => [
                                'messages' => [
                                    'isEmpty' => 'Verified Password is required'
                                ]
                            ]
                        ],
                        [
                            'name' => 'Identical',
                            'options' => [
                                'token' => 'password',
                                "messages" => [
                                    Identical::NOT_SAME => "The Passwords are not identical"
                                ]
                            ]
                        ]
                    ]
                ]);
                $inputFilter->setData($post);
                if ($inputFilter->isValid()) {
                    $data = $inputFilter->getValues();
                    /**
                     * @var User
                     */
                    $userEntity = $entityManager->getRepository(User::class)->findOneBy([
                        'registrationToken' => $data["token"]
                    ]);
                    if ($userEntity) {
                        $userEntity->setPassword(UserService::encryptPassword($data["password"]))->setUpdatedOn(new \Datetime());

                        $entityManager->persist($userEntity);
                        $entityManager->flush();

                        // Send a success mail

                        // return $this->redirect()->toRoute("login");
                        $response->setStatusCode(201);
                        // $jsonmodel->setVariables([

                        // ]);
                        return  $jsonmodel;
                    }
                } else {
                    $response->setStatusCode(400);
                    $jsonmodel->setVariables([
                        "messages" => $inputFilter->getMessages()
                    ]);
                    return  $jsonmodel;
                }
            }
            // }
        } catch (\Throwable $th) {
            //throw $th;
            $response->setStatusCode(400);
            $jsonmodel->setVariables([
                "messages" => $th->getMessage()
            ]);
            return  $jsonmodel;
        }

        $viewmodel->setVariables([
            // "data"=>[
            "token" => $token,

            // ]
        ]);
        return $viewmodel;
    }



    // public function submitnewPasswordAction(){
    //     $jsonModel = new JsonModel();
    //     $request = $this->getRequest();
    //     if ($request->isPost()) {
    //         // $post
    //     }
    //     return $jsonModel;
    // }

    /**
     * Confirm Email Change Action
     *
     * Confirms password change through given token
     *
     * @return Laminas\View\Model\ViewModel
     */
    public function confirmEmailChangePasswordAction()
    {
        $token = $this->params()->fromRoute('id');
        try {
            $entityManager = $this->entityManager;
            if (
                $token !== '' && $user = $entityManager->getRepository(User::class)->findOneBy([
                    'registrationToken' => $token
                ])
            ) {
                $user->setRegistrationToken(md5(uniqid(mt_rand(), true)));
                $password = $this->generatePassword();
                $user->setPassword(UserService::encryptPassword($password));
                $email = $user->getEmail();
                $fullLink = $this->getBaseUrl() . $this->url()->fromRoute('user-index', [
                    'action' => 'login'
                ]);

                // send email here
                // $this->sendEmail($user->getEmail(), 'Your password has been changed!', sprintf($this->translator->translate('Hello again %s. Your new password is: %s. Please, follow this link %s to log in with your new password.'), $user->getUsername(), $password, $fullLink));

                $entityManager->persist($user);
                $entityManager->flush();

                $viewModel = new ViewModel([
                    'email' => $email,

                ]);
                return $viewModel;
            } else {
                return $this->redirect()->toRoute('admin');
            }
        } catch (\Exception $e) {
            // return $this->getServiceLocator()->get('csnuser_error_view')->createErrorView(
            // $this->getTranslatorHelper()->translate('An error occured during the confirmation of your password change! Please, try again later.'),
            // $e,
            // $this->options->getDisplayExceptions(),
            // $this->options->getNavMenu()
            // );
        }
    }


    public function logoutAction()
    {
        $auth = $this->authService;
        if ($auth->hasIdentity()) {
            $auth->clearIdentity();
            $sessionManager = new SessionManager();
            $sessionManager->forgetMe();
            $sessionManager->destroy();
        }

        return $this->redirect()->toRoute("authentication");
    }



    public function forgotPasswordAction()
    {
        $this->layout()->setTemplate("login-layout");

        $viewmodel = new ViewModel();
        return $viewmodel;
    }

    public function resetAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();

        $response = $this->getResponse();
        if ($request->isPost()) {
            $post = $request->getPost();
            $email = $post["email"];
            /**
             * @var User
             */
            $userEntity = $this->em->getRepository(User::class)->findOneBy([
                "email" => $email
            ]);
            if ($userEntity == null) {
                $response->setStatusCode(423);
                return $jsonModel;
            } else {
                try {
                    // generate new Token 
                    $token = md5(uniqid(mt_rand(), true));
                    // Hy
                    $userEntity->setRegistrationToken($token)->setUpdatedOn(new \Datetime());

                    $fullLink = $this->getBaseUrl() . $this->url()->fromRoute('auth', array(
                        'action' => 'newpassword',
                        'id' => $userEntity->getRegistrationToken()

                    ));

                    // send email

                    $this->entityManager->persist($userEntity);
                    $mailData["to"] = $userEntity->getEmail();
                    $mailData["subject"] = "Terces Academy Reset Password";
                    $mailData["toName"] = $userEntity->getFullname();
                    // $mailData["template"] = "reset-password-mail";
                    $mailData["fulllink"] = $fullLink;
                    // $mailData["var"] = [
                    //     "link" => $fullLink
                    // ];

                    // $this->mailService->execute($mailData);

                    // $this->mailtrap->passwordResetMail($mailData);
                    $this->postmarkService->resetPassword($mailData);

                    $this->entityManager->flush();
                    $response->setStatusCode(201);
                } catch (\Throwable $th) {
                    $response->setStatusCode(400);
                    $jsonModel->setVariables([
                        "messages" => $th->getMessage(),
                        "data" => $th->getTrace()
                    ]);
                }
            }
        }
        return $jsonModel;
    }

    private function getBaseUrl()
    {
        $uri = $this->getRequest()->getUri();
        return sprintf('%s://%s', $uri->getScheme(), $uri->getHost());
    }

    public function confirmEmailAction()
    {
        $this->layout()->setTemplate("login-layout");
        $token = $this->params()->fromRoute('id');
        $viewModel = new ViewModel();
        try {
            $entityManager = $this->em;
            if ($token !== '' && $user = $entityManager->getRepository(User::class)->findOneBy(array(
                'registrationToken' => $token
            ))) {
                if ($user->getEmailConfirmed() == TRUE) {
                    $this->flashmessenger()->addErrorMessage("This email has been confirmed already");
                    $this->redirect()->toRoute("authentication");
                }
                $user->setRegistrationToken(md5(uniqid(mt_rand(), true)));
                $user->setState($entityManager->find(UserState::class, ServiceAuthenticationService::USER_STATE_ENABLED));
                $user->setEmailConfirmed(1);
                $entityManager->persist($user);
                $entityManager->flush();

                $this->flashmessenger()->addSuccessMessage("Email successfully confirmed and registration completed");
                $this->redirect()->toRoute("authentication");
                // $viewModel = new ViewModel(array(
                // 'navMenu' => $this->options->getNavMenu()
                // ));

                // $viewModel->setTemplate('csn-user/registration/confirm-email-success');
                // return $viewModel;
                return $this;
            } else {
                $this->flashmessenger()->addErrorMessage("There was a problem confirrming your email");
                return $this->redirect()->toRoute('authentication', array());
            }
        } catch (\Exception $e) {
            // return $this->getServiceLocator()->get('csnuser_error_view')->createErrorView(
            // $this->getTranslatorHelper()->translate('Something went wrong during the activation of your account! Please, try again later.'),
            // $e,
            // $this->options->getDisplayExceptions(),
            // $this->options->getNavMenu()
            // );
            $this->flashmessenger()->addErrorMessage("There was a problem consfirming your email");
            return $this->redirect()->toRoute('login', array());
        }
        return $viewModel;
    }



    /**
     * Get the value of authenticateService
     */
    public function getAuthenticateService()
    {
        return $this->authenticateService;
    }

    /**
     * Set the value of authenticateService
     *
     * @return  self
     */
    public function setAuthenticateService($authenticateService)
    {
        $this->authenticateService = $authenticateService;

        return $this;
    }

    /**
     * Get the value of em
     */
    public function getEm()
    {
        return $this->em;
    }

    /**
     * Set the value of em
     *
     * @return  self
     */
    public function setEm($em)
    {
        $this->em = $em;

        return $this;
    }

    /**
     * Get the value of rabbitProducer
     */
    public function getRabbitProducer()
    {
        return $this->rabbitProducer;
    }

    /**
     * Set the value of rabbitProducer
     *
     * @return  self
     */
    public function setRabbitProducer($rabbitProducer)
    {
        $this->rabbitProducer = $rabbitProducer;

        return $this;
    }

    /**
     * Get undocumented variable
     *
     * @return  AuthenticationService
     */
    public function getAuthService()
    {
        return $this->authService;
    }

    /**
     * Set undocumented variable
     *
     * @param  AuthenticationService  $authService  Undocumented variable
     *
     * @return  self
     */
    public function setAuthService(AuthenticationService $authService)
    {
        $this->authService = $authService;

        return $this;
    }

    /**
     * Set the value of objectManager
     *
     * @return  self
     */
    public function setObjectManager($objectManager)
    {
        $this->objectManager = $objectManager;

        return $this;
    }
}
