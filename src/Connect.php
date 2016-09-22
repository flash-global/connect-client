<?php

namespace Fei\Service\Connect\Client;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Fei\Service\Connect\Client\Exception\SamlException;
use Psr\Http\Message\ResponseInterface;
use Zend\Diactoros\Response;

/**
 * Class Connect
 *
 * @package Fei\Service\Connect\Client
 */
class Connect
{
    protected $user;

    /**
     * @var Saml
     */
    protected $saml;

    /**
     * @var Dispatcher
     */
    protected $dispatcher;

    /**
     * @var ResponseInterface
     */
    protected $response;

    /**
     * Connect constructor.
     *
     * @param Saml $saml
     */
    public function __construct(Saml $saml)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $this->setSaml($saml);
        $this->initDispatcher();
    }

    /**
     * @return bool
     */
    public function isAuthenticated()
    {
        return false;
    }

    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param $user
     *
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;

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
     * @return \FastRoute\Dispatcher
     */
    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    /**
     * @param \FastRoute\Dispatcher $dispatcher
     *
     * @return $this
     */
    public function setDispatcher(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;

        return $this;
    }

    public function handleRequest($requestUri = null, $requestMethod = null)
    {
        $info = $this->getDispatcher()->dispatch($requestMethod, $requestUri);
        if ($info[0] == Dispatcher::FOUND) {
            try {
                $this->setUser($info[1]());
            } catch (SamlException $e) {
                $this->response = $e->getResponse();
            }
        } elseif (!$this->isAuthenticated()) {
            $this->response = $this->getSaml()->getHttpRedirectBindingResponse();
        }

        return $this;
    }

    public function emit()
    {
        if (headers_sent($file, $line)) {
            throw new \LogicException('Headers already sent in %s on line %d', $file, $line);
        }

        if ($this->response) {
            (new Response\SapiEmitter())->emit($this->response);
            exit();
        }
    }

    protected function initDispatcher()
    {
        $this->setDispatcher(
            \FastRoute\simpleDispatcher(function (RouteCollector $r) {
                $r->addRoute('POST', $this->getSaml()->getAcsLocation(), new SamlResponseHandler($this->getSaml()));
            })
        );
    }
}
