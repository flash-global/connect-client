<?php

include __DIR__ . '/../vendor/autoload.php';

use Fei\ApiClient\Transport\BasicTransport;
use Fei\Service\Connect\Client\Config\Config;
use Fei\Service\Connect\Client\Connect;
use Fei\Service\Connect\Client\Token;
use Fei\Service\Connect\Client\UserAdmin;
use Fei\Service\Connect\Common\Entity\User;
use Fei\Service\Connect\Common\Message\Exception\MessageException;
use Fei\Service\Connect\Common\ProfileAssociation\Message\ResponseMessage;
use Fei\Service\Connect\Common\ProfileAssociation\Message\UsernamePasswordMessage;

$config = (new Config())
    ->setEntityID('http://127.0.0.1:8080')
    ->setIdpEntityID('http://127.0.0.1:9010')
    ->setSamlMetadataBaseDir(__DIR__ . '/test/metadata')
    ->setPrivateKeyFilePath(__DIR__ . '/test/keys/sp.pem')
    ->setDefaultTargetPath('/')
    ->setLogoutTargetPath('/')
    ->registerProfileAssociation(
        function (UsernamePasswordMessage $message) {
            if ($message->getUsername() != 'test' || $message->getPassword() != 'test') {
                throw new MessageException('Profile not found!', 400);
            }

            return (new ResponseMessage())->setRole($message->getRoles()[0]);
        },
        '/connect/profile-association'
    )
    ->setAdminPathInfo('/connect/admin');

$connect = new Connect($config);
$tokenClient = new Token([
    Token::OPTION_BASEURL => 'http://127.0.0.1:9010'
]);
$tokenClient->setTransport(new BasicTransport());
$userAdmin = new UserAdmin($connect, $tokenClient, [UserAdmin::OPTION_BASEURL => 'http://127.0.0.1:9060']);
$userAdmin->setTransport(new BasicTransport());


echo "##### CREATING A USER named 'testRetrieve' ######" . PHP_EOL;

$user = (new User())
    ->setUserName('testRetrieve')
    ->setEmail('test@test.com')
    ->setFirstName('test')
    ->setLastName('test');

echo "##### RETRIEVING A USER NAMED 'testRetrieve' ######" . PHP_EOL;
try {
    $retrieved = $userAdmin->retrieve('test2');
} catch (\Exception $e) {
    echo $e->getFile() . PHP_EOL ;
    echo $e->getLine() . PHP_EOL ;
    echo $e->getPrevious()->getMessage() . PHP_EOL ;
    echo $e->getMessage() . PHP_EOL;
}
