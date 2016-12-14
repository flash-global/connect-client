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
use LightSaml\Model\Assertion\NameID;
use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Metadata\KeyDescriptor;
use LightSaml\Model\Protocol\AuthnRequest;
use LightSaml\Model\Protocol\LogoutRequest;
use LightSaml\Model\Protocol\LogoutResponse;
use LightSaml\Model\Protocol\Response;
use LightSaml\Model\Protocol\SamlMessage;
use LightSaml\Model\Protocol\Status;
use LightSaml\Model\Protocol\StatusCode;
use LightSaml\Model\XmlDSig\AbstractSignatureReader;
use LightSaml\Model\XmlDSig\Signature;
use LightSaml\Model\XmlDSig\SignatureWriter;
use LightSaml\SamlConstants;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use Symfony\Component\HttpFoundation\Request;
use Zend\Diactoros\Response\HtmlResponse;
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
     * Get Logout Service location path info
     *
     * @return string
     */
    public function getLogoutLocation()
    {
        return parse_url($this->getMetadata()->getFirstLogout()->getLocation(), PHP_URL_PATH);
    }

    /**
     * Receives the IdP Response from globals variables
     *
     * @return Response
     */
    public function receiveSamlResponse()
    {
        $context = $this->receiveMessage();

        return $context instanceof MessageContext ? $context->asResponse() : null;
    }

    /**
     * Receives the IdP LogoutResponse from globals variable
     *
     * @return \LightSaml\Model\Protocol\LogoutResponse
     */
    public function receiveLogoutResponse()
    {
        $context = $this->receiveMessage();

        return $context instanceof MessageContext ? $context->asLogoutResponse() : null;
    }

    /**
     * Receives the IdP LogoutRequest from globals variable
     *
     * @return \LightSaml\Model\Protocol\LogoutRequest
     */
    public function receiveLogoutRequest()
    {
        $context = $this->receiveMessage();

        return $context instanceof MessageContext ? $context->asLogoutRequest() : null;
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
     * Create a Saml Logout Response
     *
     * @return LogoutResponse|SamlMessage
     */
    public function createLogoutResponse()
    {
        return (new LogoutResponse())
            ->setStatus(new Status(new StatusCode(SamlConstants::STATUS_SUCCESS)))
            ->setID(Helper::generateID())
            ->setVersion(SamlConstants::VERSION_20)
            ->setIssuer(new Issuer($this->getMetadata()->getServiceProvider()->getID()))
            ->setIssueInstant(new \DateTime());
    }

    /**
     * Prepare a LogoutResponse to be send
     *
     * @param LogoutRequest $request
     *
     * @return LogoutResponse
     */
    public function prepareLogoutResponse(LogoutRequest $request)
    {
        $response = $this->createLogoutResponse();

        $idp = $this->getMetadata()->getIdentityProvider();

        $location = $idp->getFirstSingleLogoutService()->getResponseLocation()
            ? $idp->getFirstSingleLogoutService()->getResponseLocation()
            : $idp->getFirstSingleLogoutService()->getLocation();

        $response->setDestination($location);
        $response->setRelayState($request->getRelayState());

        $this->signMessage($response);

        return $response;
    }

    /**
     * Create a Saml Logout Request
     *
     * @return LogoutRequest|SamlMessage
     */
    public function createLogoutRequest()
    {
        return (new LogoutRequest())
            ->setID(Helper::generateID())
            ->setVersion(SamlConstants::VERSION_20)
            ->setIssueInstant(new \DateTime())
            ->setIssuer(new Issuer($this->getMetadata()->getServiceProvider()->getID()));
    }

    /**
     * Prepare LogoutRequest before sending
     *
     * @param User $user
     *
     * @return LogoutRequest|SamlMessage
     */
    public function prepareLogoutRequest(User $user)
    {
        $request = $this->createLogoutRequest();

        $request->setNameID(
            new NameID(
                $user->getUserName(),
                SamlConstants::NAME_ID_FORMAT_UNSPECIFIED
            )
        );

        $request->setDestination(
            $this->getMetadata()->getIdentityProvider()->getFirstSingleLogoutService()->getLocation()
        );

        $request->setRelayState(Helper::generateID());

        $this->signMessage($request);

        return $request;
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
     * Returns the HtmlResponse with the form of PostBinding as content
     *
     * @param SamlMessage $message
     *
     * @return HtmlResponse
     */
    public function getHttpPostBindingResponse(SamlMessage $message)
    {
        $bindingFactory = new BindingFactory();
        $postBinding = $bindingFactory->create(SamlConstants::BINDING_SAML2_HTTP_POST);

        $messageContext = new MessageContext();
        $messageContext->setMessage($message);

        $httpResponse = $postBinding->send($messageContext);

        return new HtmlResponse($httpResponse->getContent());
    }

    /**
     * Sign a Saml Message
     *
     * @param SamlMessage $message
     *
     * @return SamlMessage
     */
    public function signMessage(SamlMessage $message)
    {
        return $message->setSignature(
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
     * @param SamlMessage $message
     *
     * @throws SamlException
     */
    public function validateIssuer(SamlMessage $message)
    {
        if ($this->getMetadata()->getIdentityProvider()->getID() != $message->getIssuer()->getValue()) {
            throw new SamlException('urn:oasis:names:tc:SAML:2.0:status:RequestDenied');
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
     * @param string   $relayState
     *
     * @throws SamlException
     */
    public function validateRelayState(Response $response, $relayState)
    {
        if ($response->getRelayState() != null && $response->getRelayState() != $relayState) {
            throw new SamlException('urn:oasis:names:tc:SAML:2.0:status:RequestDenied');
        }
    }

    /**
     * Validate a LogoutRequest NameId
     *
     * @param LogoutRequest $request
     * @param User          $user
     *
     * @throws SamlException
     */
    public function validateNameId(LogoutRequest $request, User $user)
    {
        if ($request->getNameID()->getValue() != $user->getUserName()) {
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
     * Validate a LogoutRequest
     *
     * @param LogoutRequest $request
     * @param User          $user
     */
    public function validateLogoutRequest(LogoutRequest $request, User $user)
    {
        $this->validateIssuer($request);
        $this->validateNameId($request, $user);

        if ($this->hasSignature($request)) {
            $this->validateSignature(
                $request,
                KeyHelper::createPublicKey(
                    $this->getMetadata()->getIdentityProvider()
                        ->getFirstKeyDescriptor(KeyDescriptor::USE_SIGNING)
                        ->getCertificate()
                )
            );
        }
    }

    /**
     * Validate a LogoutResponse
     *
     * @param LogoutResponse $response
     */
    public function validateLogoutResponse(LogoutResponse $response)
    {
        $this->validateIssuer($response);

        if ($this->hasSignature($response)) {
            $this->validateSignature(
                $response,
                KeyHelper::createPublicKey(
                    $this->getMetadata()->getIdentityProvider()
                        ->getFirstKeyDescriptor(KeyDescriptor::USE_SIGNING)
                        ->getCertificate()
                )
            );
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

    /**
     * Receive Saml message from globals variables
     *
     * @return MessageContext
     */
    protected function receiveMessage()
    {
        $request = Request::createFromGlobals();

        try {
            $binding = (new BindingFactory())->getBindingByRequest($request);
        } catch (\Exception $e) {
            return null;
        }

        $context = new MessageContext();
        $binding->receive($request, $context);

        return $context;
    }
}
