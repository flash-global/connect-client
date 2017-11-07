<?php

namespace Fei\Service\Connect\Client;

use LightSaml\Helper;
use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Context\SerializationContext;
use LightSaml\Model\Metadata\AssertionConsumerService;
use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Model\Metadata\KeyDescriptor;
use LightSaml\Model\Metadata\SingleLogoutService;
use LightSaml\Model\Metadata\SpSsoDescriptor;
use LightSaml\SamlConstants;

/**
 * Class MetadataBuilder
 *
 * @package Fei\Service\Connect\Client
 */
class MetadataBuilder
{
    /**
     * @param $path
     * @return array
     */
    public function getInformations($path)
    {
        if (file_exists($path)) {
            $xml = file_get_contents($path);

            $deserializationContext = new DeserializationContext();
            $deserializationContext->getDocument()->loadXML($xml);

            $entityDescriptor = new EntityDescriptor();
            $entityDescriptor->deserialize($deserializationContext->getDocument()->firstChild, $deserializationContext);

            $acs    = $entityDescriptor->getFirstSpSsoDescriptor()->getFirstAssertionConsumerService()->getLocation();
            $logout = $entityDescriptor->getFirstSpSsoDescriptor()->getFirstSingleLogoutService()->getLocation();

            return [
                'acs' => $acs,
                'logout' => $logout
            ];
        }
    }

    /**
     * Build the SP SAML metadata
     *
     * @param $entityID
     * @param $acs
     * @param $logout
     * @param $certificate
     *
     * @return string
     */
    public function build($entityID, $acs, $logout, $certificate)
    {
        $entityDescriptor = (new EntityDescriptor())
            ->setEntityID($entityID)
            ->setID(Helper::generateID());

        $spSsoDescriptor = (new SpSsoDescriptor())
            ->addKeyDescriptor(
                (new KeyDescriptor())
                    ->setUse(KeyDescriptor::USE_SIGNING)
                    ->setCertificate($certificate)
            )
            ->addKeyDescriptor(
                (new KeyDescriptor())
                    ->setUse(KeyDescriptor::USE_ENCRYPTION)
                    ->setCertificate($certificate)
            )
            ->setWantAssertionsSigned(true)
            ->setAuthnRequestsSigned(true)
            ->addSingleLogoutService(
                $logout = (new SingleLogoutService())
                    ->setBinding(SamlConstants::BINDING_SAML2_HTTP_REDIRECT)
                    ->setLocation($logout)
            )
            ->addAssertionConsumerService(
                $acs = (new AssertionConsumerService())
                    ->setBinding(SamlConstants::BINDING_SAML2_HTTP_POST)
                    ->setLocation($acs)
            );

        $entityDescriptor->addItem($spSsoDescriptor);

        $serializationContext = new SerializationContext();

        $entityDescriptor->serialize($serializationContext->getDocument(), $serializationContext);

        $data = $serializationContext->getDocument()->saveXML();

        return $data;
    }
}
