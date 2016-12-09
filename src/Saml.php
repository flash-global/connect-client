<?php

namespace Fei\Service\Connect\Client;

use Fei\Service\Connect\Client\Exception\SamlException;
use Fei\Service\Connect\Client\Exception\SecurityException;
use Fei\Service\Connect\Common\Entity\User;
use LightSaml\Binding\BindingFactory;
use LightSaml\ClaimTypes;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Credential\KeyHelper;
use LightSaml\Helper;
use LightSaml\Model\Assertion\Assertion;
use LightSaml\Model\Assertion\EncryptedElement;
use LightSaml\Model\Assertion\Issuer;
use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Metadata\KeyDescriptor;
use LightSaml\Model\Protocol\AuthnRequest;
use LightSaml\Model\Protocol\Response;
use LightSaml\Model\Protocol\SamlMessage;
use LightSaml\Model\XmlDSig\AbstractSignatureReader;
use LightSaml\Model\XmlDSig\Signature;
use LightSaml\Model\XmlDSig\SignatureWriter;
use LightSaml\SamlConstants;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use Symfony\Component\HttpFoundation\Request;
use Zend\Diactoros\Response\RedirectResponse;

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
     * Get Service Provider ACS location path info
     *
     * @return string
     */
    public function getAcsLocation()
    {
        return parse_url($this->getMetadata()->getFirstAcs()->getLocation(), PHP_URL_PATH);
    }

    /**
     * Receives the IdP Response from globals variables
     *
     * @return Response|null
     */
    public function receiveSamlResponse()
    {
        $request = Request::createFromGlobals();

        $binding = (new BindingFactory())->getBindingByRequest($request);
        $context = new MessageContext();
        $binding->receive($request, $context);

        return $context->asResponse();
    }

    /**
     * Returns a AuthnRequest
     *
     * @return \LightSaml\Model\Protocol\SamlMessage
     */
    public function buildAuthnRequest()
    {
        $authnRequest = (new AuthnRequest())
            ->setAssertionConsumerServiceURL($this->getMetadata()->getFirstAcs()->getLocation())
            ->setProtocolBinding($this->getMetadata()->getFirstAcs()->getBinding())
            ->setID(Helper::generateID())
            ->setIssueInstant(new \DateTime())
            ->setDestination($this->getMetadata()->getFirstSso()->getLocation())
            ->setIssuer(new Issuer($this->getMetadata()->getServiceProvider()->getID()))
            ->setRelayState(Helper::generateID());

        if ($this->getMetadata()->getIdentityProvider()->getWantAuthnRequestsSigned()) {
            $authnRequest->setSignature(
                new SignatureWriter(
                    $this->getMetadata()
                        ->getServiceProvider()
                        ->getFirstKeyDescriptor(KeyDescriptor::USE_SIGNING)
                        ->getCertificate(),
                    KeyHelper::createPrivateKey($this->getMetadata()->getServiceProviderPrivateKey(), '')
                )
            );
        }

        return $authnRequest;
    }

    /**
     * Returns the AuthnRequest using HTTP Redirect Binding to Identity Provider SSO location
     *
     * @param AuthnRequest $request
     *
     * @return RedirectResponse
     */
    public function getHttpRedirectBindingResponse(AuthnRequest $request = null)
    {
        if (is_null($request)) {
            $request = $this->buildAuthnRequest();
        }

        $context = new MessageContext();
        $context->setMessage($request);

        $binding = (new BindingFactory())->create(SamlConstants::BINDING_SAML2_HTTP_REDIRECT);

        return new RedirectResponse($binding->send($context)->getTargetUrl());
    }

    /**
     * Validate a signed message
     *
     * @param SamlMessage|Assertion $message
     * @param XMLSecurityKey        $key
     *
     * @return bool
     *
     * @throws SamlException|SecurityException
     */
    public function validateSignature($message, XMLSecurityKey $key)
    {
        $reader = $message->getSignature();
        if ($reader instanceof AbstractSignatureReader) {
            try {
                return $reader->validate($key);
            } catch (\Exception $e) {
                throw new SamlException('urn:oasis:names:tc:SAML:2.0:status:RequestDenied', $e);
            }
        } else {
            throw new SecurityException('Saml message signature is not specified');
        }
    }

    /**
     * Tells if a message has signature
     *
     * @param SamlMessage|Assertion $message
     *
     * @return bool
     */
    public function hasSignature($message)
    {
        return $message->getSignature() instanceof Signature;
    }

    /**
     * Validate IdP Response issuer
     *
     * @param Response $response
     *
     * @throws SamlException
     */
    public function validateIssuer(Response $response)
    {
        if ($this->getMetadata()->getIdentityProvider()->getID() != $response->getIssuer()->getValue()) {
            throw new SamlException('urn:oasis:names:tc:SAML:2.0:status:InvalidNameIDPolicy');
        }
    }

    /**
     * Validate IdP Response destination
     *
     * @param Response $response
     *
     * @throws SamlException
     */
    public function validateDestination(Response $response)
    {
        if ($this->getMetadata()->getServiceProvider()->getFirstAssertionConsumerService()->getLocation()
            != $response->getDestination()
        ) {
            throw new SamlException('urn:oasis:names:tc:SAML:2.0:status:RequestDenied');
        }
    }

    /**
     * Validate IdP Response RelayState
     *
     * @param Response $response
     * @param          $relayState
     *
     * @throws SamlException
     */
    public function validateRelayState(Response $response, $relayState)
    {
        if ($response->getRelayState() != $relayState) {
            throw new SamlException('urn:oasis:names:tc:SAML:2.0:status:RequestDenied');
        }
    }

    /**
     * Validate a IdP Response
     *
     * @param Response $response
     * @param          $relayState
     */
    public function validateResponse(Response $response, $relayState)
    {
        $this->validateIssuer($response);
        $this->validateDestination($response);
        if ($relayState) {
            $this->validateRelayState($response, $relayState);
        }

        $public = KeyHelper::createPublicKey(
            $this->getMetadata()->getIdentityProvider()
                ->getFirstKeyDescriptor(KeyDescriptor::USE_SIGNING)
                ->getCertificate()
        );

        if ($this->hasSignature($response)) {
            $this->validateSignature($response, $public);
        }

        if ($response->getAllEncryptedAssertions()) {
            $private = KeyHelper::createPrivateKey($this->getMetadata()->getServiceProviderPrivateKey(), null);
            /** @var EncryptedElement $encryptedAssertion */
            foreach ($response->getAllEncryptedAssertions() as $encryptedAssertion) {
                $assertion = $encryptedAssertion->decryptAssertion($private, new DeserializationContext());

                if ($this->hasSignature($assertion)) {
                    $this->validateSignature($assertion, $public);
                }

                $response->addAssertion($assertion);
            }
        }
    }

    /**
     * Retrieves a User instance from Response Assertion send by IdP
     *
     * @param Assertion $assertion
     *
     * @return User
     */
    public function retrieveUserFromAssertion(Assertion $assertion)
    {
        $user = null;
        $role = null;

        foreach ($assertion->getAllAttributeStatements() as $attributeStatement) {
            foreach ($attributeStatement->getAllAttributes() as $attribute) {
                switch ($attribute->getName()) {
                    case 'user_entity':
                        $user = new User(\json_decode($attribute->getFirstAttributeValue(), true));
                        break;
                    case ClaimTypes::ROLE:
                        $role = $attribute->getFirstAttributeValue();
                }
            }
        }

        if ($user instanceof User) {
            $user->setCurrentRole($role);
        }

        return $user;
    }
}
