<?php

namespace Fei\Service\Connect\Client;

use Fei\Service\Connect\Client\Exception\SamlException;
use Zend\Diactoros\Response\RedirectResponse;

/**
 * Class SamlResponseHandler
 *
 * @package Fei\Service\Connect\Client
 */
class SamlResponseHandler
{
    /**
     * Handle Saml Response
     *
     * @param Connect $connect
     *
     * @return RedirectResponse
     *
     * @throws SamlException
     */
    public function __invoke(Connect $connect)
    {
        $response = $connect->getSaml()->receiveSamlResponse();

        if (!$response) {
            throw new SamlException('urn:oasis:names:tc:SAML:2.0:status:RequestDenied');
        }

        $connect->getSaml()->validateResponse(
            $response,
            isset($_SESSION['SAML_RelayState']) ? $_SESSION['SAML_RelayState'] : null
        );

        $connect->setUser(
            $connect->getSaml()->retrieveUserFromAssertion($response->getFirstAssertion())
        );

        $targetedPath = isset($_SESSION['targeted_path'])
            ? $_SESSION['targeted_path']
            : $connect->getConfig()->getDefaultTargetPath();

        unset($_SESSION['targeted_path']);
        unset($_SESSION['SAML_RelayState']);

        return new RedirectResponse($targetedPath);
    }
}
