<?php

namespace Fei\Service\Connect\Client\Handler;

use Fei\Service\Connect\Client\Connect;
use Zend\Diactoros\Response;

/**
 * Class DeleteAdminHandler
 *
 * @package Fei\Service\Connect\Client\Handler
 */
class DeleteAdminHandler
{
    /**
     * Handle Delete request
     *
     * @param Connect $connect
     *
     * @return Response
     */
    public function __invoke(Connect $connect)
    {
        $metadata = $connect->getConfig()->getSamlMetadataBaseDir() . '/' . $connect->getConfig()->getSpMetadataFile();
        if (is_file($metadata)) {
            @unlink($metadata);
        }

        $metadata = $connect->getConfig()->getSamlMetadataBaseDir() . '/' . $connect->getConfig()->getIdpMetadataFile();
        if (is_file($metadata)) {
            @unlink($metadata);
        }

        return new Response('php://memory', 204);
    }
}
