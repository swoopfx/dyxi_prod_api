<?php

namespace General\Service;

use Application\Service\ApplicationService;
use Postmark\PostmarkClient;

class PostmarkGeneralMailService
{

    /**
     * Undocumented variable
     *
     * @var 
     */
    private $postmarkConfig;

    private $sender;

    private $apiToken;


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
     * Set the value of sender
     *
     * @return  self
     */
    public function setSender($sender)
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * Set the value of apiToken
     *
     * @return  self
     */
    public function setApiToken($apiToken)
    {
        $this->apiToken = $apiToken;

        return $this;
    }
}
