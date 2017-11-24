<?php

namespace Fei\Service\Connect\Client;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Fei\Service\Connect\Client\Config\Config;
use Fei\Service\Connect\Client\Config\ConfigConsistency;
use Fei\Service\Connect\Client\Handler\DeleteAdminHandler;
use Fei\Service\Connect\Client\Handler\PingAdminHandler;
use Fei\Service\Connect\Client\Handler\ProfileAssociationHandler;
use Fei\Service\Connect\Client\Handler\RegisterAdminHandler;
use Fei\Service\Connect\Client\Handler\SamlLogoutHandler;
use Fei\Service\Connect\Client\Handler\SamlResponseHandler;
use Fei\Service\Connect\Common\Entity\User;
use Fei\Service\Connect\Common\Exception\ResponseExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;

/**
 * Class Connect
 *
 * @package Fei\Service\Connect\Client
 */
class Connect
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
     * @var bool
     */
    protected $isConfigConsistent = false;

    /**
     * Connect constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (isset($_SESSION['user'])) {
            $this->setUser(new User($_SESSION['user']));
        }

        $this->setConfig($config);

        $this->init();

        $this->initDispatcher();
    }

    /**
     * Delegate constructor
     */
    public function init()
    {
        if ($this->isConfigConsistent()) {
            $this->setSaml(new Saml(
                Metadata::load(
                    $this->getConfig()->getSamlMetadataBaseDir() . '/' .$this->getConfig()->getIdpMetadataFile(),
                    $this->getConfig()->getSamlMetadataBaseDir() . '/' .$this->getConfig()->getSpMetadataFile()
                ),
                $this->getConfig()->getPrivateKey()
            ));
        } else {
            $metadata = new Metadata();
            $metadata->setIdentityProvider(
                $metadata->createEntityDescriptor(
                    file_get_contents(
                        $this->getConfig()->getSamlMetadataBaseDir() . '/' .$this->getConfig()->getIdpMetadataFile()
                    )
                )
            );

            $this->setSaml(new Saml($metadata, $this->getConfig()->getPrivateKey()));
        }
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
     * Returns if config is consistent
     *
     * @return bool
     */
    public function isConfigConsistent()
    {
        return $this->isConfigConsistent;
    }

    /**
     * @param bool $isConfigConsistent
     * @return Connect
     */
    public function setIsConfigConsistent($isConfigConsistent)
    {
        $this->isConfigConsistent = $isConfigConsistent;
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
        $this->checkConfigConsistency($config);

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
        if (!$this->isConfigConsistent() && false) {
            throw new \LogicException('The client configuration is not consistent');
        }

        $pathInfo = $requestUri;

        if (false !== $pos = strpos($pathInfo, '?')) {
            $pathInfo = substr($pathInfo, 0, $pos);
        }

        $pathInfo = rawurldecode($pathInfo);

        $info = $this->getDispatcher()->dispatch($requestMethod, $pathInfo);

        if ($info[0] == Dispatcher::FOUND) {
            try {
                $response = $info[1]($this);

                if ($response instanceof ResponseInterface) {
                    $this->setResponse($response);
                }
            } catch (ResponseExceptionInterface $e) {
                $this->setResponse($e->getResponse());
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
        if ($this->isConfigConsistent()) {
            $this->setDispatcher(
                \FastRoute\simpleDispatcher(function (RouteCollector $r) {
                    $r->addRoute('POST', $this->getSaml()->getAcsLocation(), new SamlResponseHandler());
                    $r->addRoute(['POST', 'GET'], $this->getSaml()->getLogoutLocation(), new SamlLogoutHandler());
                    $r->addRoute(['GET'], $this->getConfig()->getAdminPathInfo(), new PingAdminHandler());
                    $r->addRoute(['DELETE'], $this->getConfig()->getAdminPathInfo(), new DeleteAdminHandler());
                    $r->addRoute(['POST'], $this->getConfig()->getAdminPathInfo(), new RegisterAdminHandler());

                    if ($this->getConfig()->hasProfileAssociationCallback()) {
                        $r->addRoute(
                            'POST',
                            $this->getConfig()->getProfileAssociationPath(),
                            new ProfileAssociationHandler($this->getConfig()->getProfileAssociationCallback())
                        );
                    }
                })
            );
        } else {
            $this->setDispatcher(
                \FastRoute\simpleDispatcher(function (RouteCollector $r) {
                    $r->addRoute(['GET'], $this->getConfig()->getAdminPathInfo(), new PingAdminHandler());
                    $r->addRoute('POST', $this->getConfig()->getAdminPathInfo(), new RegisterAdminHandler());
                })
            );
        }
    }

    /**
     * Check the config consistency
     *
     * @param Config $config
     */
    protected function checkConfigConsistency(Config $config)
    {
        $this->setIsConfigConsistent((new ConfigConsistency($config))->validate());
    }
}
