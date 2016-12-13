<?php

namespace Fei\Service\Connect\Client;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Fei\Service\Connect\Client\Exception\SamlException;
use Fei\Service\Connect\Common\Entity\User;
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
     * Connect constructor.
     *
     * @param Saml   $saml
     * @param Config $config
     */
    public function __construct(Saml $saml, Config $config)
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

        if ($this->user instanceof User) {
            $_SESSION['user'] = $this->user->toArray();
            $this->setRole($this->user->getCurrentRole());
        }

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
     * Handle connect request
     *
     * @param string $requestUri
     * @param string $requestMethod
     *
     * @return $this
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
            try {
                $response = $info[1]($this);

                if ($response instanceof ResponseInterface) {
                    $this->setResponse($response);
                }
            } catch (SamlException $e) {
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
        $this->setDispatcher(
            \FastRoute\simpleDispatcher(function (RouteCollector $r) {
                $r->addRoute('POST', $this->getSaml()->getAcsLocation(), new SamlResponseHandler());
                $r->addRoute(['POST', 'GET'], $this->getSaml()->getLogoutLocation(), new SamlLogoutHandler());
            })
        );
    }
}
