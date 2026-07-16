<?php

namespace General\Service\Postmark;

use Application\Service\ApplicationService;
use General\Service\GeneralService;
use Postmark\PostmarkClient;

class AuthenticationEmailService implements PostmarkEmailInterface
{
    private $postmarkConfig;

    private $sender;

    private $data;

    private $apiToken;


    public function test()
    {


        $fromEmail = "app@recyclepoints.com";
        $toEmail = "app@recyclepoints.com";
        $subject = "Hello from Postmark";
        $htmlBody = "<strong>Hello</strong> dear Postmark user.";
        $textBody = "Hello dear Postmark user.";
        $tag = "example-email-tag";
        $trackOpens = true;
        $trackLinks = "None";
        $messageStream = "confirm-email";

        $client = new PostmarkClient($this->apiToken);
        // Send an email:
        $sendResult = $client->sendEmail(
            $fromEmail,
            $toEmail,
            $subject,
            $htmlBody,
            $textBody,
            $tag,
            $trackOpens,
            NULL, // Reply To
            NULL, // CC
            NULL, // BCC
            NULL, // Header array
            NULL, // Attachment array
            $trackLinks,
            NULL, // Metadata array
            $messageStream
        );
    }

    /**
     * Sends a confirmation Email vis postmark on web UI
     *
     * @return void
     */
    public function confirmEmailWeb($data = "")
    {
        if ($data == null) {
            throw new \Exception("SetData must be set before calling Mail function ");
        }

        $client = new PostmarkClient($this->apiToken);
        // $data = $data;
        // Send an email:
        $sendResult = $client->sendEmailWithTemplate(
            $this->sender,
            $data["email"],
            32538803,
            [

                "action_url" => $data["link"],
                "company_name" => ApplicationService::APP_COMPANY_NAME,
                "company_address" => ApplicationService::APP_COMPANY_ADDRESS,
                "name" => $data['fullname'],

            ]
        );
    }


    /**
     * Sends a confirmation Email vis postmark on web UI
     *
     * @return void
     */
    public function confirmEmailMobile($data)
    {
        // if ($this->data == null) {
        //     throw new \Exception("SetData must be set before calling Mail function ");
        // }

        $client = new PostmarkClient($this->apiToken);
        // $data = $this->data;
        // Send an email:
        $sendResult = $client->sendEmailWithTemplate(
            $this->sender,
            $data["email"],
            32539328,
            [

                "action_url" => $data["code"],
                "company_name" => ApplicationService::APP_COMPANY_NAME,
                "company_address" => ApplicationService::APP_COMPANY_ADDRESS,
                "name" => $data['fullname'],

            ]
        );
    }

    public function welcome($data)
    {
        $client = new PostmarkClient($this->apiToken);
        // $data = $this->data;
        // Send an email:
        $sendResult = $client->sendEmailWithTemplate(
            $this->sender,
            $data["to"],
            32539328,
            [

                "action_url" => "",
                "company_name" => ApplicationService::APP_COMPANY_NAME,
                "company_address" => ApplicationService::APP_COMPANY_ADDRESS,
                "name" => $data['fullname'],

            ]
        );
    }

    /**
     * To be used when admin registeres the user
     *
     * @return void
     */
    public function welcomeByAdmin($data)
    {
        $client = new PostmarkClient($this->apiToken);
        $sendResult = $client->sendEmailWithTemplate(
            $this->sender,
            $data["email"],
            32559170,
            [
                "product_url" => GeneralService::COMPANY_URL,
                "product_name" => GeneralService::APP_NAME,
                "name" => $data["fullname"],
                "action_url" => GeneralService::COMPANY_URL . "login",
                "login_url" => GeneralService::COMPANY_URL . "login",
                "username" => $data["email"],
                "password" => $data["password"],
                "support_email" => "app@recyclepoints.com",
                "sender_name" => GeneralService::EMAIL_NOTIFiER,
                "help_url" => GeneralService::COMPANY_URL,
                "company_name" => GeneralService::COMPANY_NAME,
                "company_address" => GeneralService::COMPANY_ADDRESS,
            ]
        );
    }

    public function resetpassword($data)
    {
        // Send an email:
        $client = new PostmarkClient($this->apiToken);
        $sendResult =  $client->sendEmailWithTemplate(
            "app@recyclepoints.com",
            $data["to"],
            34403259,
            [


                "product_name" => "Waste Credits",
                "name" => $data["toName"],
                "action_url" => $data["fulllink"],

                "company_name" => GeneralService::COMPANY_NAME,
                "company_address" => GeneralService::COMPANY_ADDRESS,



            ]
        );
    }


    public function execute() {}

    public function getApiToken()
    {
        return $this->apiToken;
    }

    public function setApiToken($token)
    {
        $this->apiToken = $token;
        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Get the value of postmarkConfig
     */
    public function getPostmarkConfig()
    {
        return $this->postmarkConfig;
    }

    /**
     * Set the value of postmarkConfig
     *
     * @return  self
     */
    public function setPostmarkConfig($postmarkConfig)
    {
        $this->postmarkConfig = $postmarkConfig;

        return $this;
    }

    /**
     * Get the value of sender
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * Set the value of sender
     *
     * @return  self
     */
    public function setSender($sender)
    {
        $this->sender = $sender;

        return $this;
    }
}
