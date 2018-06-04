<?php

namespace Test\Fei\Service\Connect\Client;

use Fei\Service\Connect\Client\Metadata;
use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Metadata\AssertionConsumerService;
use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Model\Metadata\IdpSsoDescriptor;
use LightSaml\Model\Metadata\SingleLogoutService;
use LightSaml\Model\Metadata\SingleSignOnService;
use LightSaml\Model\Metadata\SpSsoDescriptor;
use PHPUnit\Framework\TestCase;

/**
 * Class MetadataTest
 *
 * @package Test\Fei\Service\Connect\Client
 */
class MetadataTest extends TestCase
{
    public function testIdentityProviderAccessors()
    {
        $metadata = new Metadata();

        $idp = $this->getMockBuilder(EntityDescriptor::class)->getMock();

        $metadata->setIdentityProvider($idp);

        $this->assertEquals($idp, $metadata->getIdentityProvider());
        $this->assertAttributeEquals($metadata->getIdentityProvider(), 'identityProvider', $metadata);

        $this->assertInstanceOf(EntityDescriptor::class, (new Metadata())->getIdentityProvider());
    }

    public function testServiceProviderAccessors()
    {
        $metadata = new Metadata();

        $ps = $this->getMockBuilder(EntityDescriptor::class)->getMock();

        $metadata->setServiceProvider($ps);

        $this->assertEquals($ps, $metadata->getServiceProvider());
        $this->assertAttributeEquals($metadata->getServiceProvider(), 'serviceProvider', $metadata);

        $this->assertInstanceOf(EntityDescriptor::class, (new Metadata())->getServiceProvider());
    }

    public function testGetFirstSso()
    {
        $sso = new SingleSignOnService();

        $idp = new IdpSsoDescriptor();
        $idp->addSingleSignOnService($sso);

        $metadata = new Metadata();
        $metadata->setIdentityProvider((new EntityDescriptor())->addItem($idp));

        $this->assertEquals($sso, $metadata->getFirstSso());
    }

    public function testGetFirstSsoFailWithNoIdentityProviderDescriptor()
    {
        $metadata = new Metadata();
        $metadata->setIdentityProvider(new EntityDescriptor());

        $this->expectException(\LogicException::class, 'A Identity Provider descriptor must be registered');

        $metadata->getFirstSso();
    }

    public function testGetFirstSsoFailWithNoSsoService()
    {
        $metadata = new Metadata();
        $metadata->setIdentityProvider(
            (new EntityDescriptor())
                ->addItem(new IdpSsoDescriptor())
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The Identity Provider descriptor must have one SSO registered');

        $metadata->getFirstSso();
    }

    public function testGetFirstSpSsoDescriptor()
    {
        $metadata = new Metadata();

        $sp = new SpSsoDescriptor();

        $metadata->setServiceProvider((new EntityDescriptor())->addItem($sp));

        $this->assertEquals($sp, $metadata->getFirstSpSsoDescriptor());
    }

    public function testGetFirsSpSsoDescriptorFail()
    {
        $metadata = new Metadata();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('A Service Provider descriptor must be registered');

        $metadata->getFirstSpSsoDescriptor();
    }

    public function testGetFirstAcs()
    {
        $metadata = new Metadata();

        $sp = new SpSsoDescriptor();
        $acs = new AssertionConsumerService();

        $sp->addAssertionConsumerService($acs);

        $metadata->setServiceProvider((new EntityDescriptor())->addItem($sp));

        $this->assertEquals($acs, $metadata->getFirstAcs());
    }

    public function testGetFirstAcsFail()
    {
        $metadata = new Metadata();

        $sp = new SpSsoDescriptor();

        $metadata->setServiceProvider((new EntityDescriptor())->addItem($sp));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The Service Provider must have one ACS service registered');

        $metadata->getFirstAcs();
    }

    public function testGetFirstLogout()
    {
        $metadata = new Metadata();

        $sp = new SpSsoDescriptor();
        $logout = new SingleLogoutService();

        $sp->addSingleLogoutService($logout);

        $metadata->setServiceProvider((new EntityDescriptor())->addItem($sp));

        $this->assertEquals($logout, $metadata->getFirstLogout());
    }

    public function testGetFirstLogoutFail()
    {
        $metadata = new Metadata();

        $sp = new SpSsoDescriptor();

        $metadata->setServiceProvider((new EntityDescriptor())->addItem($sp));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The Service Provider must have one Logout service registered');

        $metadata->getFirstLogout();
    }
}
