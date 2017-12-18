<?php

require __DIR__ . '/../../vendor/autoload.php';

use \Fei\Service\Connect\Client\UserAttribution;

$start_time = microtime(true);

//$userAttribution = new UserAttribution([UserAttribution::OPTION_BASEURL => 'http://connect.test.flash-global.net']);
$userAttribution = new UserAttribution([UserAttribution::OPTION_BASEURL => 'http://127.0.0.1:9010']);

try {
    // Add Attribution for User with username = boris - Application with ID = 28 - Role with ID = 1 - Attribution is default
    $attribution = $userAttribution->add('boris', '28', '1', true);

    // Add Attribution for User with username = boris - Application with ID = 28 - Role with role = 'ADMIN' - Attribution is default
    //$attribution = $userAttribution->add('boris', '28', 'ADMIN', true);

    // Add Attribution for User with username = boris - Application with entity ID = http://127.0.0.1:8020 - Role with role = 'ADMIN'
    //$attribution = $userAttribution->add('boris', 'http://127.0.0.1:8020', 'ADMIN');

    // Add Attribution for User with username = boris - Application with name = Filer - Role with role = 'ADMIN' - Attribution is not default - Local username = 'bobo'
    //$attribution = $userAttribution->add('boris', 'Filer', 'ADMIN', false, 'bobo');

    if ($attribution) {
        echo 'Attribution successfully added.' . PHP_EOL;
        var_dump($attribution);
    } else {
        echo 'Attribution not added.' . PHP_EOL;
    }
} catch (\Exception $e) {
    echo "An error occurred: " . $e->getMessage() . PHP_EOL;
}

echo "Execution time: ", bcsub(microtime(true), $start_time, 2), "\n";
