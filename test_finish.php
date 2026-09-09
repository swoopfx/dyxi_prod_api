<?php
require 'vendor/autoload.php';

use Laminas\ApiTools\ApiProblem\ApiProblem;
use Laminas\ApiTools\ApiProblem\ApiProblemResponse;

$problem = new ApiProblem(400, 'JSON decoding error: Syntax error, malformed JSON');
$response = new ApiProblemResponse($problem);

echo $response->getContent();
