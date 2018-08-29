<?php

use Fei\Service\Connect\Client\ApiToken;

include __DIR__ . '/../vendor/autoload.php';

$working = 'http://127.0.0.1:8090';
$not     = 'http://127.0.0.1:8207';

$token = '19e3ef7e333519672d2a350e5319e720b13796028330e6e929e53fe405fe69d8b2a48c9ac9c5edac37a52e6a60b9257616b4254b13c40b4964bf495e0e902713';


echo "##### Checking access ######" . PHP_EOL;

$apiToken = new ApiToken(
    [ApiToken::OPTION_BASEURL => 'http://127.0.0.1:9010']
);

try {
    $return = $apiToken->hasAccess(
        $token,
        $working
    );
    var_dump($return);
} catch (\Exception $e) {
    echo $e->getFile() . PHP_EOL ;
    echo $e->getLine() . PHP_EOL ;
    echo $e->getPrevious()->getMessage() . PHP_EOL ;
    echo $e->getMessage() . PHP_EOL;
}

