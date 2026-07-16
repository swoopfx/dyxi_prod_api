<?php

namespace  General\Service\Postmark;

interface PostmarkEmailInterface
{
    public function execute();

    public function getApiToken();

    public function setApiToken($token);

    public function getData();

    public function setData($data);
}
