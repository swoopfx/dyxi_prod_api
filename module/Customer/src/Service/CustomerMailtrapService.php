<?php

namespace Customer\Service;

use General\Entity\Settings;
use General\Service\GeneralService;

class CustomerMailtrapService
{
    /**
     * Undocumented variable
     *
     * @var Setting
     */
    private $mailtrapConfig;


    /**
     * Undocumented variable
     *
     * @var [type]
     */
    private $appConfig;

    /**
     * Notifies a customer by email about a credit on wallet
     *
     * @param [type] $data
     * @return void
     */
    public function creditCustomerWallet($data)
    {
        try {
            $config = $this->mailtrapConfig;

            $param = [
                // $from,
                // $to,
                // $template_uid,
                // $template_variables
                "from" => [
                    "email" => GeneralService::EMAIL_NOTIFiER,
                    "name" => GeneralService::COMPANY_NAME,
                ],
                "to" => [
                    ["email" => $data["to"]]
                ],
                "template_uuid" => "17608483-803f-4acc-842d-d340bf1c2cff",
                "template_variables" => [
                    "user_email" => $data["to"],
                    "pass_reset_link" => $data['fulllink']
                ]

            ];
            // $client->setRawBody(json_encode($param));
            // $client->setUri("https://send.api.mailtrap.io/api/send");
            // $client->setHeaders($header);
            // $client->send();

            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => GeneralService::MAILTRAP_LIVE_URL,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($param), //'{"from":{"email":"no-reply@aibltd.insure","name":"Advocate Insurance Brokers"},"to":[{"email":"ezekiel_a@yahoo.com"}],"template_uuid":"17608483-803f-4acc-842d-d340bf1c2cff","template_variables":{"user_email":"Test_User_email","pass_reset_link":"Test_Pass_reset_link"}}',
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $config->getMailtrapToken(),
                    'Content-Type: application/json'
                ],
            ]);

            $response = curl_exec($curl);

            curl_close($curl);
            echo $response;
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage());
        }
    }


    /**
     * Set the value of mailtrapConfig
     *
     * @return  self
     */
    public function setMailtrapConfig($mailtrapConfig)
    {
        $this->mailtrapConfig = $mailtrapConfig;

        return $this;
    }

    /**
     * Set undocumented variable
     *
     * @param  [type]  $appConfig  Undocumented variable
     *
     * @return  self
     */
    public function setAppConfig($appConfig)
    {
        $this->appConfig = $appConfig;

        return $this;
    }
}
