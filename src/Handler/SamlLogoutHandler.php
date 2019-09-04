<?php

namespace Fei\Service\Connect\Client\Handler;

use Fei\Service\Connect\Client\Connect;
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
            $this->deleteSessionCookie();
            session_destroy();

            return $connect->getSaml()->getHttpPostBindingResponse(
                $connect->getSaml()->prepareLogoutResponse($request)
            );
        }

        $response = $connect->getSaml()->receiveLogoutResponse();

        if ($response) {
            $connect->getSaml()->validateLogoutResponse($response);
            $this->deleteSessionCookie();
            session_destroy();

            return new RedirectResponse($connect->getConfig()->getLogoutTargetPath());
        }

        return $connect->getSaml()->getHttpPostBindingResponse(
            $connect->getSaml()->prepareLogoutRequest($connect->getUser(), $connect->getSessionIndex())
        );
    }

    /**
     * @return bool
     */
    public function deleteSessionCookie(): bool
    {
        $params = session_get_cookie_params();

        return setcookie(session_name(), session_id(), time() - 1, $params['domain'], $params['secure'], $params['httponly']);
    }
}
