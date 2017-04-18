<?php

namespace Fei\Service\Connect\Client\Message;

use UnexpectedValueException;
use Zend\Diactoros\ServerRequestFactory;

/**
 * Class ProfileAssociationRequestFactory
 *
 * @package Fei\Service\Connect\Client\Message
 */
class ProfileAssociationRequestFactory extends ServerRequestFactory
{
    /**
     * {@inheritdoc}
     */
    public static function fromGlobals(
        array $server = null,
        array $query = null,
        array $body = null,
        array $cookies = null,
        array $files = null
    ) {
        $server  = static::normalizeServer($server ?: $_SERVER);
        $files   = static::normalizeFiles($files ?: $_FILES);
        $headers = static::marshalHeaders($server);

        return new ProfileAssociationRequest(
            $server,
            $files,
            static::marshalUriFromServer($server, $headers),
            static::get('REQUEST_METHOD', $server, 'GET'),
            'php://input',
            $headers,
            $cookies ?: $_COOKIE,
            $query ?: $_GET,
            $body ?: $_POST,
            static::marshalProtocolVersion($server)
        );
    }

    /**
     * {@inheritdoc}
     *
     * _Reimplemented_ this static method due to the private visibility
     */
    protected static function marshalProtocolVersion(array $server)
    {
        if (! isset($server['SERVER_PROTOCOL'])) {
            return '1.1';
        }

        if (! preg_match('#^(HTTP/)?(?P<version>[1-9]\d*(?:\.\d)?)$#', $server['SERVER_PROTOCOL'], $matches)) {
            throw new UnexpectedValueException(sprintf(
                'Unrecognized protocol version (%s)',
                $server['SERVER_PROTOCOL']
            ));
        }

        return $matches['version'];
    }
}
