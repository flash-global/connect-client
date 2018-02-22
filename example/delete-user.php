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
    ->setEntityID('http://127.0.0.1:9060')
    ->setName('http://127.0.0.1:9060')
    ->setIdpEntityID('http://192.168.1.35:9010')
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
    Token::OPTION_BASEURL => 'http://192.168.1.35:9010'
]);
$tokenClient->setTransport(new BasicTransport());

$userAdmin = new UserAdmin($connect, $tokenClient, [UserAdmin::OPTION_BASEURL => 'http://192.168.1.35:9060']);
$userAdmin->setTransport(new BasicTransport());

$user = (new User())
    ->setUserName('testDelete')
    ->setEmail('test@test.com')
    ->setFirstName('testDeleteFirstName')
    ->setLastName('testDeleteLastName');

echo "##### CREATING A USER ######";
print_r($userAdmin->persist($user));

echo "##### DELETING THE USER ######";
print_r($userAdmin->delete($user));
