<?php

require __DIR__ . '/../../vendor/autoload.php';

use \Fei\Service\Connect\Client\UserAttribution;

$start_time = microtime(true);

//$userAttribution = new UserAttribution([UserAttribution::OPTION_BASEURL => 'http://connect.test.flash-global.net']);
$userAttribution = new UserAttribution([UserAttribution::OPTION_BASEURL => 'http://127.0.0.1:9010']);

try {
    // Get Attributions of User with username = boris - No filter on Application
    $attributions = $userAttribution->get('boris', null);

    // Get Attributions of User with username = boris - For Application with ID = 28
    //$attributions = $userAttribution->get('boris', 28);

    // Get Attributions of User with username = boris - For Application with entity ID = http://127.0.0.1:8020
    //$attributions = $userAttribution->get('boris', 'http://127.0.0.1:8020');

    echo count($attributions) . ' attributions found' . PHP_EOL;
    var_dump($attributions);
} catch (\Exception $e) {
    echo "An error occurred: " . $e->getMessage() . PHP_EOL;
}

echo "Execution time: ", bcsub(microtime(true), $start_time, 2), "\n";
