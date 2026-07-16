<?php

namespace Authentication\Service;

use General\Service\GeneralService;
use General\Entity\Settings;
use Laminas\Http\Client;
use stdClass;

class AuthMailtrapService
{
    /**
     * Undocumented variable
     *
     * @var Settings
     */
    private $mailtrapconfig;

    private $appConfig;

    public function passwordResetMail($data)
    {
        try {
            $config = $this->mailtrapconfig;

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


    public function sendReceiptMail($data)
    {
        $config = $this->mailtrapconfig;

        try {
            $param = [

                "from" => [
                    "email" => GeneralService::EMAIL_NOTIFiER,
                    "name" => GeneralService::COMPANY_NAME,
                ],
                "to" => [
                    ["email" => $data["to"]]
                ],
                "template_uuid" => "6bb4715f-380e-4ea8-9855-bf394762796b",
                "template_variables" => [

                    "company_url" => GeneralService::COMPANY_URL,
                    "logo" => $data["logo"],
                    "fullname" => $data["fullname"],
                    "desc" => $data["desc"],
                    "tRef" => $data["tRef"],
                    "amount" => $data["amount"],
                    "total" => $data["total"],
                    "company_name" => GeneralService::COMPANY_NAME,
                    "company_address" => GeneralService::COMPANY_ADDRESS,

                ]

            ];


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


    public function welcomemail($data)
    {
        try {
            $config = $this->mailtrapconfig;

            $param = [
                // $from,
                // $to,
                // $template_uid,
                // $template_variables
                "from" => [
                    "email" => $this->appConfig["company"]["notification_email"],
                    "name" => $this->appConfig["company"]["name"],
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


    public function sendMobileVerifyCode($data)
    {

        try {
            $config = $this->mailtrapconfig;

            $param = [

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
                CURLOPT_POSTFIELDS => json_encode($param),
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
     * Get the value of mailtrapconfig
     */
    public function getMailtrapconfig()
    {
        return $this->mailtrapconfig;
    }

    /**
     * Set the value of mailtrapconfig
     *
     * @return  self
     */
    public function setMailtrapconfig($mailtrapconfig)
    {
        $this->mailtrapconfig = $mailtrapconfig;

        return $this;
    }

    /**
     * Get the value of appConfig
     */
    public function getAppConfig()
    {
        return $this->appConfig;
    }

    /**
     * Set the value of appConfig
     *
     * @return  self
     */
    public function setAppConfig($appConfig)
    {
        $this->appConfig = $appConfig;

        return $this;
    }
}
