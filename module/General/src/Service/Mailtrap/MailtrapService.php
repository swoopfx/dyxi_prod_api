<?php

namespace General\Service\Mailtrap;

class MailtrapService
{
    private $appConfig;

    private $entityManager;

    private $mailtrapconfig;

    private $generalService;

    /**
     * Sends Email notification to the registered email for confirmation
     * Take an array of parameters which includes a mobile code
     * This is specifically for mobile devices
     *
     * @param array $data
     * @return void
     */
    public function confirmEmail($data)
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
                    "code" => $data['code']
                ]

            ];
            // $client->setRawBody(json_encode($param));
            // $client->setUri("https://send.api.mailtrap.io/api/send");
            // $client->setHeaders($header);
            // $client->send();

            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => $config["url"],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($param), //'{"from":{"email":"no-reply@aibltd.insure","name":"Advocate Insurance Brokers"},"to":[{"email":"ezekiel_a@yahoo.com"}],"template_uuid":"17608483-803f-4acc-842d-d340bf1c2cff","template_variables":{"user_email":"Test_User_email","pass_reset_link":"Test_Pass_reset_link"}}',
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $config["token"],
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

    public function welcomeEmail($data)
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
                CURLOPT_URL => $config["url"],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($param), //'{"from":{"email":"no-reply@aibltd.insure","name":"Advocate Insurance Brokers"},"to":[{"email":"ezekiel_a@yahoo.com"}],"template_uuid":"17608483-803f-4acc-842d-d340bf1c2cff","template_variables":{"user_email":"Test_User_email","pass_reset_link":"Test_Pass_reset_link"}}',
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $config["token"],
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
                CURLOPT_URL => $config["url"],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($param), //'{"from":{"email":"no-reply@aibltd.insure","name":"Advocate Insurance Brokers"},"to":[{"email":"ezekiel_a@yahoo.com"}],"template_uuid":"17608483-803f-4acc-842d-d340bf1c2cff","template_variables":{"user_email":"Test_User_email","pass_reset_link":"Test_Pass_reset_link"}}',
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $config["token"],
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
     * Get the value of entityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * Set the value of entityManager
     *
     * @return  self
     */
    public function setEntityManager($entityManager)
    {
        $this->entityManager = $entityManager;

        return $this;
    }

    /**
     * Get the value of generalService
     */
    public function getGeneralService()
    {
        return $this->generalService;
    }

    /**
     * Set the value of generalService
     *
     * @return  self
     */
    public function setGeneralService($generalService)
    {
        $this->generalService = $generalService;

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
