<?php
require 'vendor/autoload.php';

use Laminas\ApiTools\ApiProblem\ApiProblemResponse;
use Laminas\Http\Response;

$response = new ApiProblemResponse(new \Laminas\ApiTools\ApiProblem\ApiProblem(400, ''));
$newResponse = new Response();
$newResponse->setStatusCode(400);
$newResponse->setContent('{"success": false}');

echo get_class($newResponse) . "\n";
echo $newResponse->getContent();
