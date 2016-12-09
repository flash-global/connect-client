<?php

include __DIR__ . '/../vendor/autoload.php';

use Fei\Service\Connect\Client\Connect;
use Fei\Service\Connect\Client\Metadata;
use Fei\Service\Connect\Client\Saml;
use LightSaml\Credential\X509Certificate;
use LightSaml\Model\Metadata\AssertionConsumerService;
use LightSaml\Model\Metadata\IdpSsoDescriptor;
use LightSaml\Model\Metadata\KeyDescriptor;
use LightSaml\Model\Metadata\SingleSignOnService;
use LightSaml\Model\Metadata\SpSsoDescriptor;
use LightSaml\SamlConstants;

$metadata = new Metadata();
$metadata
    ->setIdentityProvider(
        (new IdpSsoDescriptor())
            ->setID('http://idp.dev:8080')
            ->setWantAuthnRequestsSigned(true)
            ->addSingleSignOnService(
                new SingleSignOnService('http://idp.dev:8080/sso', SamlConstants::BINDING_SAML2_HTTP_REDIRECT)
            )
            ->addKeyDescriptor(new KeyDescriptor(
                KeyDescriptor::USE_SIGNING,
                X509Certificate::fromFile(__DIR__ . '/keys/idp/idp.crt')
            ))
    )->setServiceProvider(
        (new SpSsoDescriptor())
            ->setID('http://sp.dev:8081')
            ->addAssertionConsumerService(
                new AssertionConsumerService(
                    'http://sp.dev:8081/service-provider.php/acs',
                    SamlConstants::BINDING_SAML2_HTTP_POST
                )
            )
            ->addKeyDescriptor(new KeyDescriptor(
                KeyDescriptor::USE_SIGNING,
                X509Certificate::fromFile(__DIR__ . '/keys/sp.crt')
            ))
            ->addKeyDescriptor(new KeyDescriptor(
                KeyDescriptor::USE_ENCRYPTION,
                X509Certificate::fromFile(__DIR__ . '/keys/sp.crt')
            )),
        file_get_contents(__DIR__ . '/keys/sp.pem')
    );

$connect = new Connect(new Saml($metadata), '/service-provider.php');
$connect->handleRequest($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'])->emit();

echo 'My resource';

var_dump($connect);
