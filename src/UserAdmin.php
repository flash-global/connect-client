<?php

namespace Fei\Service\Connect\Client;

use Fei\ApiClient\AbstractApiClient;
use Fei\ApiClient\RequestDescriptor;
use Fei\Service\Connect\Client\Exception\UserException;
use Fei\Service\Connect\Common\Admin\Message\UserMessage;
use Fei\Service\Connect\Common\Cryptography\Cryptography;
use Fei\Service\Connect\Common\Entity\User as UserEntity;
use Fei\Service\Connect\Common\Entity\User;
use Fei\Service\Connect\Common\Message\Extractor\MessageExtractor;
use Fei\Service\Connect\Common\Message\Http\MessageRequest;
use Fei\Service\Connect\Common\Message\Hydrator\MessageHydrator;
use GuzzleHttp\Exception\BadResponseException;
use LightSaml\Model\Context\DeserializationContext;
use LightSaml\Model\Metadata\EntityDescriptor;
use LightSaml\Model\Metadata\KeyDescriptor;
use Zend\Diactoros\Response\JsonResponse;

class UserAdmin extends AbstractApiClient implements UserAdminInterface
{
    const API_USERS_PATH_INFO = '/api/users';
    const OPTION_ADMIN_METADATA_FILE = 'sp.xml';

    /**
     * @var string $certificate
     */
    protected $certificate;

    /**
     * @var Connect
     */
    protected $connect;

    /**
     * @var Token
     */
    protected $token;

    /**
     * @var string
     */
    protected $adminSpMetadataFile = 'sp.xml';

    /**
     * UserAdmin constructor.
     *
     * @param Connect $connect
     * @param Token   $token
     * @param array   $options
     */
    public function __construct(Connect $connect, Token $token, array $options = array())
    {
        parent::__construct($options);

        $this->setConnect($connect);
        $this->setToken($token);
        $this->setCertificate($this->fetchCertificate());
    }

    /**
     * @return string
     */
    public function getCertificate()
    {
        return $this->certificate;
    }

    /**
     * @param string $certificate
     *
     * @return UserAdmin
     */
    public function setCertificate($certificate)
    {
        $this->certificate = $certificate;

        return $this;
    }

    /**
     * Get Token
     *
     * @return Token
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Set Token
     *
     * @param Token $token
     *
     * @return $this
     */
    public function setToken($token)
    {
        $this->token = $token;

        return $this;
    }

    /**
     * Get SpPathInfo
     *
     * @return string
     */
    public function getAdminSpMetadataFile()
    {
        return $this->adminSpMetadataFile;
    }

    /**
     * Set SpPathInfo
     *
     * @param string $adminSpMetadataFile
     *
     * @return $this
     */
    public function setAdminSpMetadataFile($adminSpMetadataFile)
    {
        $this->adminSpMetadataFile = $adminSpMetadataFile;

        return $this;
    }

    /**
     * Get Connect
     *
     * @return Connect
     */
    public function getConnect()
    {
        return $this->connect;
    }

    /**
     * Set Connect
     *
     * @param Connect $connect
     *
     * @return $this
     */
    public function setConnect($connect)
    {
        $this->connect = $connect;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function persist(UserEntity $user)
    {
        $request = (new RequestDescriptor())
            ->setUrl($this->buildUrl(self::API_USERS_PATH_INFO))
            ->setMethod('POST');

        $message = new UserMessage();
        $message->setUser($user);

        $messageRequest = (new MessageRequest($message))->buildEncrypted($this->getCertificate());
        $messageRequest->getBody()->rewind();

        $request->setRawData($messageRequest->getBody()->getContents());
        $token = $this->createToken();
        $request->addHeader('token', $token);
        try {
            $user->setId((int) json_decode($this->send($request)->getBody(), true)['created']);

            return $user;
        } catch (\Exception $e) {
            $previous = $e->getPrevious();
            if ($previous instanceof BadResponseException) {
                $error = json_decode($previous->getResponse()->getBody()->getContents(), true);

                throw new UserException($error['error'], $previous->getCode(), $previous);
            }

            throw new UserException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function edit(UserEntity $formerUser, UserEntity $newUser)
    {
        $request = (new RequestDescriptor())
            ->setMethod('PUT')
            ->setUrl($this->buildUrl(self::API_USERS_PATH_INFO . '/' . $formerUser->getUserName()));

        $message = new UserMessage();
        $message->setUser($newUser);
        $messageRequest = (new MessageRequest($message))->buildEncrypted($this->getCertificate());
        $messageRequest->getBody()->rewind();

        $request->setRawData($messageRequest->getBody()->getContents());

        $request->addHeader('token', $this->createToken());

        try {
            $this->send($request)->getBody();
        } catch (\Exception $e) {
            $previous = $e->getPrevious();

            if ($previous instanceof BadResponseException) {
                $error = json_decode($previous->getResponse()->getBody()->getContents(), true);

                throw new UserException($error['error'], $previous->getCode(), $previous);
            }

            throw new UserException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }

        return $newUser;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(UserEntity $user)
    {
        $request = (new RequestDescriptor())
            ->setMethod('DELETE')
            ->setUrl($this->buildUrl(self::API_USERS_PATH_INFO . '/' . $user->getUserName()));

        $request->addHeader('token', $this->createToken());

        try {
            return $this->send($request)->getBody();
        } catch (\Exception $e) {
            $previous = $e->getPrevious();

            if ($previous instanceof BadResponseException) {
                $error = json_decode($previous->getResponse()->getBody()->getContents(), true);

                throw new UserException($error['error'], $previous->getCode(), $previous);
            }

            throw new UserException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * @param $username
     * @return UserEntity
     * @throws UserException
     */
    public function retrieve($username)
    {
        $keyDescriptor = $this->getConnect()
            ->getSaml()
            ->getMetadata()
            ->getFirstSpSsoDescriptor()
            ->getFirstKeyDescriptor(KeyDescriptor::USE_ENCRYPTION);

        if (!$keyDescriptor) {
            $keyDescriptor = $this->getConnect()
                ->getSaml()
                ->getMetadata()
                ->getFirstSpSsoDescriptor()
                ->getFirstKeyDescriptor(KeyDescriptor::USE_SIGNING);
        }

        $request = (new RequestDescriptor())
            ->setMethod('GET')
            ->setUrl($this
                ->buildUrl(self::API_USERS_PATH_INFO . '/' . $username.'?certificate='.base64_encode($keyDescriptor
                        ->getCertificate()
                        ->toPem())));

        $request->addHeader('token', $this->createToken());

        try {
            $userCrypted = base64_decode(json_decode($this->send($request)->getBody(), true)[0]);

            $extractor = (new MessageExtractor())->setHydrator(new MessageHydrator());

            $privateKey = $this->connect->getSaml()->getPrivateKey();

            $message = json_decode((new Cryptography())->decrypt($userCrypted, $privateKey), true);

            $message = $extractor->extract($message);

            /** @var UserMessage $message */
            $user = $message->getUser();

            if (!$user instanceof User && is_array($user)) {
                $user = new User($user);
            }

            return $user;
        } catch (\Exception $e) {
            $previous = $e->getPrevious();

            if ($previous instanceof BadResponseException) {
                $error = json_decode($previous->getResponse()->getBody()->getContents(), true);

                throw new UserException($error['error'], $previous->getCode(), $previous);
            }

            throw new UserException($e->getMessage(), $e->getCode(), $e->getPrevious());
        }
    }

    /**
     * Fetch Connect Admin SP metadata an return encryption certificate
     *
     * @return string
     */
    protected function fetchCertificate()
    {
        $metadata = file_get_contents($this->getBaseUrl() . $this->getAdminSpMetadataFile());
        $deserializationContext = new DeserializationContext();
        $deserializationContext->getDocument()->loadXML($metadata);

        $ed = new EntityDescriptor();

        $ed->deserialize($deserializationContext->getDocument(), $deserializationContext);

        $certificate = $ed->getFirstSpSsoDescriptor()->getFirstKeyDescriptor(KeyDescriptor::USE_ENCRYPTION)
            ? $ed->getFirstSpSsoDescriptor()->getFirstKeyDescriptor(KeyDescriptor::USE_ENCRYPTION)
                ->getCertificate()
                ->toPem()
            : $ed->getFirstSpSsoDescriptor()->getFirstKeyDescriptor(KeyDescriptor::USE_SIGNING)
                ->getCertificate()
                ->toPem();

        return $certificate;
    }

    /**
     * Get a token
     *
     * @return string
     */
    protected function createToken()
    {
        $token = $this->getToken()->createApplicationToken(
            $this->connect->getSaml()->getMetadata()->getServiceProvider()->getEntityID(),
            $this->connect->getSaml()->getPrivateKey()
        );
        return $token['token'];
    }
}
