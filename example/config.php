<?php

include __DIR__ . '/../vendor/autoload.php';

use Fei\ApiClient\Transport\BasicTransport;
use Fei\Service\Connect\Client\Cache\Adapter\ZendCacheAdapter;
use Fei\Service\Connect\Client\Config\Config;
use Fei\Service\Connect\Client\Connect;
use Fei\Service\Connect\Client\Token;
use Fei\Service\Connect\Common\Message\Exception\MessageException;
use Fei\Service\Connect\Common\ProfileAssociation\Message\ResponseMessage;
use Fei\Service\Connect\Common\ProfileAssociation\Message\UsernamePasswordMessage;
use Zend\Cache\StorageFactory;

$config = (new Config())
    ->setEntityID('http://client.dev:8084')
    ->setIdpEntityID('http://idp.dev:8080')
    ->setSamlMetadataBaseDir(__DIR__ . '/test/metadata')
    ->setPrivateKeyFilePath(__DIR__ . '/test/keys/sp.pem')
    ->setDefaultTargetPath('/resource.php')
    ->setLogoutTargetPath('/')
    ->registerProfileAssociation(
        function (UsernamePasswordMessage $message) {
            if ($message->getUsername() != 'test' || $message->getPassword() != 'test') {
                throw new MessageException('Profile not found!', 400);
            }

            return (new ResponseMessage())->setRole($message->getRoles()[0]);
        },
        '/profile-association.php'
    )
    ->setAdminPathInfo('/admin.php');

$connect = new Connect($config);

$connect->handleRequest($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'])->emit();

$token = new Token([Token::OPTION_BASEURL => 'http://idp.dev:8080']);
$token->setTransport(new BasicTransport());
$token->setCache(new ZendCacheAdapter(StorageFactory::factory([
    'adapter' => 'filesystem'
])));
