<?php

require __DIR__ . '/../../vendor/autoload.php';

use \Fei\Service\Connect\Client\UserAttribution;

$start_time = microtime(true);

//$userAttribution = new UserAttribution([UserAttribution::OPTION_BASEURL => 'http://connect.test.flash-global.net']);
$userAttribution = new UserAttribution([UserAttribution::OPTION_BASEURL => 'http://127.0.0.1:9010']);

try {
    // Remove Attribution for User with username = boris - Application with ID = 28 - Role with ID = 1
    $userAttribution->remove('boris', '28', '1');

    // Remove Attribution for User with username = boris - Application with ID = 28 - Role with role = 'ADMIN'
    //$userAttribution->remove('boris', '28', 'ADMIN');

    // Remove Attribution for User with username = boris - Application with entity ID = http://127.0.0.1:8020 - Role with role = 'ADMIN'
    //$userAttribution->remove('boris', 'http://127.0.0.1:8020', 'ADMIN');

    // Remove Attribution for User with username = boris - Application with name = Filer - Role with role = 'ADMIN' - Local username = 'bobo'
    //$userAttribution->remove('boris', 'Filer', 'ADMIN', 'bobo');

    echo 'Attribution successfully removed.' . PHP_EOL;
} catch (\Exception $e) {
    echo "An error occurred: " . $e->getMessage() . PHP_EOL;
}

echo "Execution time: ", bcsub(microtime(true), $start_time, 2), "\n";
