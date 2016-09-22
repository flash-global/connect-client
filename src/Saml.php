<?php

namespace Fei\Service\Connect\Client;

use LightSaml\Binding\BindingFactory;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Credential\KeyHelper;
use LightSaml\Helper;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Metadata\KeyDescriptor;
use LightSaml\Model\Protocol\AuthnRequest;
use LightSaml\Model\XmlDSig\SignatureWriter;
use LightSaml\SamlConstants;
use Zend\Diactoros\Response;

/**
 * Class Saml
 *
 * @package Fei\Service\Connect\Client
 */
class Saml
{
    /**
     * @var Metadata
     */
    protected $metadata;

    /**
     * Saml constructor.
     *
     * @param Metadata $metadata
     */
    public function __construct(Metadata $metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * Get Metadata
     *
     * @return Metadata
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Set Metadata
     *
     * @param Metadata $metadata
     *
     * @return $this
     */
    public function setMetadata($metadata)
    {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * Get Service Provicer ACS location path info
     *
     * @return string
     */
    public function getAcsLocation()
    {
        return parse_url($this->getMetadata()->getFirstAcs()->getLocation(), PHP_URL_PATH);
    }

    /**
     * Returns a AuthnRequest
     *
     * @return \LightSaml\Model\Protocol\SamlMessage
     */
    public function buildAuthnRequest()
    {
        return (new AuthnRequest())
            ->setAssertionConsumerServiceURL($this->getMetadata()->getFirstAcs()->getLocation())
            ->setProtocolBinding($this->getMetadata()->getFirstAcs()->getBinding())
            ->setID(Helper::generateID())
            ->setIssueInstant(new \DateTime())
            ->setDestination($this->getMetadata()->getFirstSso()->getLocation())
            ->setIssuer(new Issuer('http://test.sp:8081'))
            ->setRelayState(Helper::generateID())
            ->setSignature(
                new SignatureWriter(
                    $this->getMetadata()
                        ->getServiceProvider()
                        ->getFirstKeyDescriptor(KeyDescriptor::USE_SIGNING)
                        ->getCertificate(),
                    KeyHelper::createPrivateKey($this->getMetadata()->getServiceProviderPrivateKey(), '')
                )
            );
    }

    /**
     * Returns the AuthnRequest using HTTP Redirect Binding to Identity Provider SSO location
     *
     * @return Response\RedirectResponse
     */
    public function getHttpRedirectBindingResponse()
    {
        $context = new MessageContext();
        $context->setMessage($this->buildAuthnRequest());

        $binding = (new BindingFactory())->create(SamlConstants::BINDING_SAML2_HTTP_REDIRECT);

        return new Response\RedirectResponse($binding->send($context)->getTargetUrl());
    }
}
