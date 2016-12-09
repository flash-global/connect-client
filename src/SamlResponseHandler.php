<?php

namespace Fei\Service\Connect\Client;

use Fei\Service\Connect\Common\Entity\User;
use LightSaml\Binding\BindingFactory;
use LightSaml\ClaimTypes;
use LightSaml\Context\Profile\MessageContext;
use LightSaml\Credential\KeyHelper;
use LightSaml\Model\Assertion\Attribute;
use LightSaml\Model\Assertion\AttributeStatement;
use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Metadata\KeyDescriptor;
use Symfony\Component\HttpFoundation\Request;

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

    /**
     * SamlResponseHandler constructor.
     *
     * @param Saml $saml
     */
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
        $response = $this->getSaml()->receiveSamlResponse();

        $this->getSaml()->validateResponse(
            $response,
            isset($_SESSION['SAML_RelayState']) ? $_SESSION['SAML_RelayState'] : null
        );

        return $this->getSaml()->retrieveUserFromAssertion($response->getFirstAssertion());
    }
}
