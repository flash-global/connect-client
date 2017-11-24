<?php

namespace Fei\Service\Connect\Client\Handler;

use Fei\Service\Connect\Client\Connect;
use Fei\Service\Connect\Common\Message\Extractor\EncryptedMessageExtractor;
use Fei\Service\Connect\Common\Message\Extractor\MessageExtractor;
use Fei\Service\Connect\Common\Message\Http\MessageResponse;
use Fei\Service\Connect\Common\Message\Http\MessageServerRequestFactory;
use Fei\Service\Connect\Common\Message\Hydrator\MessageHydrator;
use Fei\Service\Connect\Common\Message\Exception\MessageException;
use Fei\Service\Connect\Common\ProfileAssociation\Message\RequestMessageInterface;
use Fei\Service\Connect\Common\ProfileAssociation\Message\ResponseMessageInterface;

/**
 * Class ProfileAssociationHandler
 *
 * @package Fei\Service\Connect\Client\Handler
 */
class ProfileAssociationHandler
{
    /**
     * @var callable
     */
    protected $profileAssociationCallback;

    /**
     * ProfileAssociationHandler constructor.
     *
     * @param callable $profileAssociationCallback
     */
    public function __construct(callable $profileAssociationCallback)
    {
        $this->setProfileAssociationCallback($profileAssociationCallback);
    }

    /**
     * Get ProfileAssociationCallback
     *
     * @return callable
     */
    public function getProfileAssociationCallback()
    {
        return $this->profileAssociationCallback;
    }

    /**
     * Set ProfileAssociationCallback
     *
     * @param callable $profileAssociationCallback
     *
     * @return $this
     */
    public function setProfileAssociationCallback($profileAssociationCallback)
    {
        $this->profileAssociationCallback = $profileAssociationCallback;

        return $this;
    }

    /**
     * Handle Profile Association Request and Response
     *
     * @param Connect $connect
     *
     * @return \Psr\Http\Message\ResponseInterface
     *
     * @throws MessageException
     */
    public function __invoke(Connect $connect)
    {
        $certificate = $connect->getSaml()
            ->getMetadata()
            ->getFirstIdpSsoDescriptor()
            ->getFirstKeyDescriptor()
            ->getCertificate()->toPem();

        try {
            /** @var RequestMessageInterface $message */
            $message = MessageServerRequestFactory::fromGlobals()
                ->setEncryptedMessageExtractor(
                    (new EncryptedMessageExtractor())->setMessageExtractor(
                        (new MessageExtractor())->setHydrator(new MessageHydrator())
                    )
                )
                ->extract($connect->getConfig()->getPrivateKey());

            $response = $this->getProfileAssociationCallback()($message);

            if (!$response instanceof ResponseMessageInterface) {
                throw new \LogicException(
                    sprintf(
                        'Profile association callback must return a instance of %s, %s returned.',
                        ResponseMessageInterface::class,
                        is_object($response) ? get_class($response) : gettype($response)
                    )
                );
            }

            if (!in_array($response->getRole(), $message->getRoles())) {
                throw new \LogicException(
                    sprintf(
                        'Role provided by response message "%s" is not in roles "%s"',
                        $response->getRole(),
                        implode(', ', $message->getRoles())
                    )
                );
            }

            return (new MessageResponse($response))->buildEncrypted($certificate);
        } catch (MessageException $e) {
            throw $e->setCertificate($certificate);
        } catch (\Throwable $e) {
            throw (new MessageException($e->getMessage(), $e->getCode(), $e))->setCertificate($certificate);
        }
    }
}
