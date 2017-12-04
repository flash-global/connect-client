<?php

require __DIR__ . '/../../vendor/autoload.php';

use \Fei\Service\Connect\Client\UserAttribution;

$start_time = microtime(true);

//$userAttribution = new UserAttribution([UserAttribution::OPTION_BASEURL => 'http://connect.test.flash-global.net']);
$userAttribution = new UserAttribution([UserAttribution::OPTION_BASEURL => 'http://127.0.0.1:9010']);

try {
    // Remove Attributions for User with username = boris - No filter on Application
    // WARNING: This code delete all the User Attributions
    //$userAttribution->removeAll('boris', null);

    // Remove Attributions for User with username = boris - For Application with ID = 28
    //$userAttribution->removeAll('boris', '28');

    // Remove Attributions of User with username = boris - For Application with entity ID = http://127.0.0.1:8020
    $userAttribution->removeAll('boris', 'http://127.0.0.1:8020');

    echo 'Attribution(s) successfully removed.' . PHP_EOL;
} catch (\Exception $e) {
    echo "An error occurred: " . $e->getMessage() . PHP_EOL;
}

echo "Execution time: ", bcsub(microtime(true), $start_time, 2), "\n";
