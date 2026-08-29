<?php

namespace Consultant\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Authentication\Service\ApiAuthenticateService;
use Consultant\Service\ConsultantService;
use Consultant\Entity\Consultant;

class ConsultantController extends AbstractActionController
{
    /**
     * @var ConsultantService
     */
    private $consultantService;

    /**
     * @var ApiAuthenticateService
     */
    private $apiAuthService;

    public function __construct(ConsultantService $consultantService, ApiAuthenticateService $apiAuthService)
    {
        $this->consultantService = $consultantService;
        $this->apiAuthService = $apiAuthService;
    }

    public function registerAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();

        if (!$request->isPost()) {
            $response->setStatusCode(405);
            $jsonModel->setVariables([
                "success"     => false,
                "error"       => "MethodNotAllowed",
                "description" => "Method Not Allowed. Use POST."
            ]);
            return $jsonModel;
        }

        try {
            // Note: Register consultant can be authenticated or unauthenticated depending on config.
            // Let's enforce authentication using standard bearer token checks, but fallback gracefully if needed.
            $identity = $this->apiAuthService->getContainerIdentity();
            if (empty($identity)) {
                $response->setStatusCode(401);
                $jsonModel->setVariables([
                    "success" => false,
                    "error" => "Unauthorized",
                    "description" => "User identity not found in request context."
                ]);
                return $jsonModel;
            }

            $json = $request->getContent();
            $postData = (array) json_decode($json, true);

            $consultant = $this->consultantService->register($postData);

            $response->setStatusCode(201);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $this->mapEntityToArray($consultant),
                "description" => "Successfully registered Consultant profile."
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "ConsultantRegistrationError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    public function listAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();

        if (!$request->isGet()) {
            $response->setStatusCode(405);
            $jsonModel->setVariables([
                "success"     => false,
                "error"       => "MethodNotAllowed",
                "description" => "Method Not Allowed. Use GET."
            ]);
            return $jsonModel;
        }

        try {
            $identity = $this->apiAuthService->getContainerIdentity();
            if (empty($identity)) {
                $response->setStatusCode(401);
                $jsonModel->setVariables([
                    "success" => false,
                    "error" => "Unauthorized",
                    "description" => "User identity not found in request context."
                ]);
                return $jsonModel;
            }

            $consultants = $this->consultantService->listConsultants();
            $data = [];
            foreach ($consultants as $consultant) {
                $data[] = $this->mapEntityToArray($consultant);
            }

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $data
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "ConsultantListError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    public function viewAction()
    {
        $jsonModel = new JsonModel();
        $request = $this->getRequest();
        $response = $this->getResponse();

        if (!$request->isGet()) {
            $response->setStatusCode(405);
            $jsonModel->setVariables([
                "success"     => false,
                "error"       => "MethodNotAllowed",
                "description" => "Method Not Allowed. Use GET."
            ]);
            return $jsonModel;
        }

        try {
            $identity = $this->apiAuthService->getContainerIdentity();
            if (empty($identity)) {
                $response->setStatusCode(401);
                $jsonModel->setVariables([
                    "success" => false,
                    "error" => "Unauthorized",
                    "description" => "User identity not found in request context."
                ]);
                return $jsonModel;
            }

            $id = $this->params()->fromRoute('id');
            if (empty($id)) {
                $id = $this->params()->fromQuery('id') ?? $this->params()->fromQuery('uuid');
            }

            if (empty($id)) {
                throw new \Exception("Consultant identifier (id or uuid) is required.");
            }

            $consultant = $this->consultantService->getConsultantInfo($id);

            $response->setStatusCode(200);
            $jsonModel->setVariables([
                "success" => true,
                "data" => $this->mapEntityToArray($consultant)
            ]);

        } catch (\Throwable $th) {
            $response->setStatusCode(400);
            $jsonModel->setVariables([
                "success" => false,
                "error" => "ConsultantViewError",
                "description" => $th->getMessage()
            ]);
        }

        return $jsonModel;
    }

    private function mapEntityToArray(Consultant $consultant): array
    {
        return [
            "id" => $consultant->getId(),
            "uuid" => $consultant->getUuid(),
            "fullname" => $consultant->getFullname(),
            "email" => $consultant->getEmail(),
            "phone" => $consultant->getPhone(),
            "specialization" => $consultant->getSpecialization(),
            "bio" => $consultant->getBio(),
            "status" => $consultant->getStatus(),
            "user" => $consultant->getUser() ? [
                "id" => $consultant->getUser()->getId(),
                "fullname" => $consultant->getUser()->getFullname(),
                "email" => $consultant->getUser()->getEmail()
            ] : null,
            "created_on" => $consultant->getCreatedOn()->format('Y-m-d H:i:s'),
            "updated_on" => $consultant->getUpdatedOn()->format('Y-m-d H:i:s'),
        ];
    }
}
