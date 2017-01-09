<?php

namespace Test\Fei\Service\Connect\Client;

use Fei\Service\Connect\Client\Metadata;
use Fei\Service\Connect\Client\Saml;
use LightSaml\Credential\X509Certificate;
use LightSaml\Model\Metadata\AssertionConsumerService;
use LightSaml\Model\Metadata\IdpSsoDescriptor;
use LightSaml\Model\Metadata\KeyDescriptor;
use LightSaml\Model\Metadata\SingleLogoutService;
use LightSaml\Model\Metadata\SingleSignOnService;
use LightSaml\Model\Metadata\SpSsoDescriptor;
use LightSaml\SamlConstants;
use PHPUnit\Framework\TestCase;

class SamlTest extends TestCase
{
    public function testMetadataAccessors()
    {
        $saml = new Saml(new Metadata());

        $this->assertEquals($saml->getMetadata(), new Metadata());
        $this->assertAttributeEquals($saml->getMetadata(), 'metadata', $saml);
    }

    public function testGetAcsLocation()
    {
        $saml = new Saml($this->getMetadata());

        $this->assertEquals($saml->getAcsLocation(), '/acs.php');
    }

    public function testGetLogoutLocation()
    {
        $saml = new Saml($this->getMetadata());

        $this->assertEquals($saml->getLogoutLocation(), '/logout.php');
    }

    public function testReceiveSamlResponse()
    {
        $saml = new Saml($this->getMetadata());

        $this->assertNull($saml->receiveSamlResponse());
    }

    protected function getMetadata()
    {
        $metadata = new Metadata();
        $metadata
            ->setIdentityProvider(
                (new IdpSsoDescriptor())
                    ->setID('http://idp.dev:8080')
                    ->setWantAuthnRequestsSigned(true)
                    ->addSingleSignOnService(
                        new SingleSignOnService('http://idp.dev:8080/sso', SamlConstants::BINDING_SAML2_HTTP_REDIRECT)
                    )
                    ->addSingleLogoutService(
                        new SingleLogoutService('http://idp.dev:8080/logout', SamlConstants::BINDING_SAML2_HTTP_POST)
                    )
                    ->addKeyDescriptor(new KeyDescriptor(
                        KeyDescriptor::USE_SIGNING,
                        X509Certificate::fromFile(__DIR__ . '/../example/keys/idp/idp.crt')
                    ))
            )->setServiceProvider(
                (new SpSsoDescriptor())
                    ->setID('http://sp.dev:8080')
                    ->addAssertionConsumerService(
                        new AssertionConsumerService(
                            'http://sp.dev:8080/acs.php',
                            SamlConstants::BINDING_SAML2_HTTP_POST
                        )
                    )
                    ->addSingleLogoutService(
                        new SingleLogoutService(
                            'http://sp.dev:8080/logout.php',
                            SamlConstants::BINDING_SAML2_HTTP_POST
                        )
                    )
                    ->addKeyDescriptor(new KeyDescriptor(
                        KeyDescriptor::USE_SIGNING,
                        X509Certificate::fromFile(__DIR__ . '/../example/keys/sp.crt')
                    ))
                    ->addKeyDescriptor(new KeyDescriptor(
                        KeyDescriptor::USE_ENCRYPTION,
                        X509Certificate::fromFile(__DIR__ . '/../example/keys/sp.crt')
                    )),
                file_get_contents(__DIR__ . '/../example/keys/sp.pem')
            );

        return $metadata;
    }
}
