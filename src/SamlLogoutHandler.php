<?php

namespace Fei\Service\Connect\Client;

use Zend\Diactoros\Response\RedirectResponse;

/**
 * Class SamlLogoutHandler
 *
 * @package Fei\Service\Connect\Client
 */
class SamlLogoutHandler
{
    /**
     * Handle Logout Request and Response
     *
     * @param Connect $connect
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(Connect $connect)
    {
        if (!$connect->getUser()) {
            return new RedirectResponse($connect->getConfig()->getLogoutTargetPath());
        }

        $request = $connect->getSaml()->receiveLogoutRequest();

        if ($request) {
            $connect->getSaml()->validateLogoutRequest($request, $connect->getUser());

            session_destroy();

            return $connect->getSaml()->getHttpPostBindingResponse(
                $connect->getSaml()->prepareLogoutResponse($request)
            );
        }

        $response = $connect->getSaml()->receiveLogoutResponse();

        if ($response) {
            $connect->getSaml()->validateLogoutResponse($response);

            session_destroy();

            return new RedirectResponse($connect->getConfig()->getLogoutTargetPath());
        }

        return $connect->getSaml()->getHttpPostBindingResponse(
            $connect->getSaml()->prepareLogoutRequest($connect->getUser())
        );
    }
}
