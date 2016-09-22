<?php

namespace Fei\Service\Connect\Client;

use Fei\Service\Connect\Client\Exception\SamlException;

/**
 * Class SamlResponseHandler
 *
 * @package Fei\Service\Connect\Client
 */
class SamlResponseHandler
{
    /**
     * @var Saml
     */
    protected $saml;

    public function __construct(Saml $saml)
    {
        $this->setSaml($saml);
    }

    /**
     * @return Saml
     */
    public function getSaml()
    {
        return $this->saml;
    }

    /**
     * @param Saml $saml
     *
     * @return $this
     */
    public function setSaml(Saml $saml)
    {
        $this->saml = $saml;

        return $this;
    }

    public function __invoke()
    {
        throw new SamlException('Exception!', 400);
    }
}
