<?php

include __DIR__ . '/../vendor/autoload.php';

$client = new \Fei\Service\Connect\Client\Admin\UserAdmin([
    \Fei\Service\Connect\Client\Admin\UserAdmin::OPTION_BASEURL => 'http://localhost:8084',
]);
$client->setTransport(new \Fei\ApiClient\Transport\BasicTransport());

var_dump($token = $client->generateResetPasswordToken('test'));
var_dump($client->validateResetPasswordToken('toto'));
