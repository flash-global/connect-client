<?php

namespace Fei\Service\Connect\Client;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Fei\ApiClient\AbstractApiClient;
use Fei\ApiClient\ApiClientException;
use Fei\ApiClient\RequestDescriptor;
use Fei\Service\Connect\Common\Entity\User;
use Fei\Service\Connect\Common\Exception\ResponseExceptionInterface;
use Fei\Service\Connect\Common\ProfileAssociation\Exception\ProfileAssociationException;
use Fei\Service\Connect\Common\ProfileAssociation\Message\Extractor\MessageExtractor;
use Fei\Service\Connect\Common\ProfileAssociation\Message\Hydrator\MessageHydrator;
use Fei\Service\Connect\Common\ProfileAssociation\Message\RequestMessageInterface;
use Fei\Service\Connect\Common\ProfileAssociation\Message\ResponseMessageInterface;
use Fei\Service\Connect\Common\ProfileAssociation\ProfileAssociationMessageExtractor;
use Fei\Service\Connect\Common\ProfileAssociation\ProfileAssociationResponse;
use Fei\Service\Connect\Common\ProfileAssociation\ProfileAssociationServerRequestFactory;
use Fei\Service\Connect\Common\Token\Tokenizer;
use Guzzle\Http\Exception\BadResponseException;
use Lcobucci\JWT\Token;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;

/**
 * Class Connect
 *
 * @package Fei\Service\Connect\Client
 */
class Connect extends AbstractApiClient
{
    /**
     * @var User
     */
    protected $user;

    /**
     * @var Saml
     */
    protected $saml;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * @var string
     */
    protected $role;

    /**
     * @var string
     */
    protected $localUsername;

    /**
     * Connect constructor.
     *
     * @param Saml   $saml
     * @param Config $config
     * @param array  $option
     */
    public function __construct(Saml $saml, Config $config, array $option = [])
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (isset($_SESSION['user'])) {
            $this->setUser(new User($_SESSION['user']));
        }

        $this->setSaml($saml);
        $this->setConfig($config);

        $this->initDispatcher();

        parent::__construct($option);
    }

    /**
     * Tells if user is authenticated
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        return !empty($this->getUser());
    }

    /**
     * Get User
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set User
     *
     * @param User $user
     *
     * @return $this
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        unset($_SESSION['user']);

        $_SESSION['user'] = $this->user->toArray();
        $this->setRole($this->user->getCurrentRole());
        $this->setLocalUsername($this->user->getLocalUsername());

        return $this;
    }

    /**
     * Get Saml
     *
     * @return Saml
     */
    public function getSaml()
    {
        return $this->saml;
    }

    /**
     * Set Saml
     *
     * @param Saml $saml
     *
     * @return $this
     */
    public function setSaml(Saml $saml)
    {
        $this->saml = $saml;

        return $this;
    }

    /**
     * Get Config
     *
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Set Config
     *
     * @param Config $config
     *
     * @return $this
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Get Response
     *
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Set Response
     *
     * @param ResponseInterface $response
     *
     * @return $this
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Get Dispatcher
     *
     * @return Dispatcher
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * Set Dispatcher
     *
     * @param Dispatcher $dispatcher
     *
     * @return $this
     */
    public function setDispatcher(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;

        return $this;
    }

    /**
     * Get Role
     *
     * @return string
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set Role
     *
     * @param string $role
     *
     * @return $this
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get LocalUsername
     *
     * @return string
     */
    public function getLocalUsername()
    {
        return $this->localUsername;
    }

    /**
     * Set LocalUsername
     *
     * @param string $localUsername
     *
     * @return $this
     */
    public function setLocalUsername($localUsername)
    {
        $this->localUsername = $localUsername;

        return $this;
    }

    /**
     * Create a JWT (JSON Web Token)
     *
     * @return Token
     */
    public function createToken()
    {
        $user = $this->getUser();

        if (!$user) {
            throw new \LogicException('Unable to create token: user is not set');
        }

        return (new Tokenizer())->createToken(
            $this->getUser(),
            $this->getSaml()->getMetadata()->getServiceProvider()->getID(),
            $this->getSaml()->getMetadata()->getServiceProviderPrivateKey()
        );
    }

    /**
     * Validate a JWT (JSON Web Token)
     *
     * @param string $token
     *
     * @return Token
     *
     * @throws ApiClientException
     */
    public function validateToken($token)
    {
        $request = (new RequestDescriptor())
            ->setUrl($this->buildUrl(sprintf('/api/token/validate?token=%s', (string) $token)))
            ->setMethod('GET');

        try {
            $token = json_decode($this->send($request)->getBody(), true)['token'];
        } catch (ApiClientException $e) {
            $previous = $e->getPrevious();
            if ($previous instanceof BadResponseException) {
                throw $previous;
            }
            throw $e;
        }

        return (new Tokenizer())->parseFromString($token);
    }

    /**
     * Handle connect request
     *
     * @param string $requestUri
     * @param string $requestMethod
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function handleRequest($requestUri = null, $requestMethod = null)
    {
        $pathInfo = $requestUri;

        if (false !== $pos = strpos($pathInfo, '?')) {
            $pathInfo = substr($pathInfo, 0, $pos);
        }

        $pathInfo = rawurldecode($pathInfo);

        $info = $this->getDispatcher()->dispatch($requestMethod, $pathInfo);

        if ($info[0] == Dispatcher::FOUND) {
            $certificate = $this->getSaml()
                ->getMetadata()
                ->getIdentityProvider()
                ->getFirstKeyDescriptor()
                ->getCertificate()->toPem();

            try {
                $response = null;

                if ($pathInfo == $this->getConfig()->getProfileAssociationPath()) {
                    /** @var RequestMessageInterface $requestMessage */
                    $requestMessage = ProfileAssociationServerRequestFactory::fromGlobals()
                        ->setProfileAssociationMessageExtractor(
                            (new ProfileAssociationMessageExtractor())
                                ->setMessageExtractor((new MessageExtractor())->setHydrator(new MessageHydrator()))
                        )
                        ->extract(
                            $this->getSaml()->getMetadata()->getServiceProviderPrivateKey()
                        );

                    $responseMessage = $info[1]($requestMessage);

                    if (!$responseMessage instanceof ResponseMessageInterface) {
                        throw new \LogicException(
                            sprintf(
                                'Profile association callback must return a instance of %s, %s returned.',
                                ResponseMessageInterface::class,
                                is_object($response) ? get_class($response) : gettype($response)
                            )
                        );
                    }

                    if (!in_array($responseMessage->getRole(), $requestMessage->getRoles())) {
                        throw new \LogicException(
                            sprintf(
                                'Role provided by response message "%s" is not in roles "%s"',
                                $responseMessage->getRole(),
                                implode(', ', $requestMessage->getRoles())
                            )
                        );
                    }

                    $response = (new ProfileAssociationResponse($responseMessage))->build($certificate);
                } else {
                    $response = $info[1]($this);
                }

                if ($response instanceof ResponseInterface) {
                    $this->setResponse($response);
                }
            } catch (\Exception $e) {
                if ($e instanceof ProfileAssociationException) {
                    $this->setResponse($e->setCertificate($certificate)->getResponse());
                } elseif ($e instanceof ResponseExceptionInterface) {
                    $this->setResponse($e->getResponse());
                } else {
                    throw $e;
                }
            }
        } elseif (!$this->isAuthenticated()) {
            if (strtoupper($requestMethod) == 'GET') {
                $_SESSION['targeted_path'] = $requestUri;
            }

            $request = $this->getSaml()->buildAuthnRequest();
            $_SESSION['SAML_RelayState'] = $request->getRelayState();

            $this->setResponse($this->getSaml()->getHttpRedirectBindingResponse($request));
        }

        return $this;
    }

    /**
     * Emit the client response if exists and die...
     */
    public function emit()
    {
        if (headers_sent($file, $line)) {
            throw new \LogicException('Headers already sent in %s on line %d', $file, $line);
        }

        if ($this->getResponse()) {
            (new Response\SapiEmitter())->emit($this->getResponse());
            exit();
        }
    }

    /**
     * Init the route for ACS dispatcher
     */
    protected function initDispatcher()
    {
        $this->setDispatcher(
            \FastRoute\simpleDispatcher(function (RouteCollector $r) {
                $r->addRoute('POST', $this->getSaml()->getAcsLocation(), new SamlResponseHandler());
                $r->addRoute(['POST', 'GET'], $this->getSaml()->getLogoutLocation(), new SamlLogoutHandler());

                if ($this->getConfig()->hasProfileAssociationCallback()) {
                    $r->addRoute(
                        'POST',
                        $this->getConfig()->getProfileAssociationPath(),
                        $this->getConfig()->getProfileAssociationCallback()
                    );
                }
            })
        );
    }
}
