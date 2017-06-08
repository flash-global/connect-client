# Connect-Client

The role of Connect-Client is to integrate SAML standard protocol into your application.

It will allow you to validate an user's authentication with a SSO (Single Sign-On) device, get specific information
about him, and define his authorizations through assertions.

Check out `connect-idp` documentation for more information about SAML standard protocol.

## Installation & prerequisites

Connect-Client needs **PHP 5.5** or up, with the extension `mcrypt` plugged to run correctly.

You will have to integrate it to your project with `composer require fei/connect-client`

## Integration

Here is an example on how it works (See `/example` folder):

```php
$metadata = new Metadata();

// Configure your metadata... (See next chapter)

$config = (new Config())
    ->setDefaultTargetPath('/resource.php')
    ->setLogoutTargetPath('/');

$connect = new Connect(new Saml($metadata), $config);
$connect->handleRequest($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD'])->emit();
```

After you created a new `Metadata` instance, and configured it (cf **Setting up your metadata**), create a new `Connect` object which will take two parameter:

- A new `SAML` instance (which allow you to use every SAML methods) which will take our metadata as parameter:
- A `Config` which has to be filled with:
    - `defaultTargetPath` which is an URI where the user will be redirected to, if the login response doesn't contain one
    - `logoutTargetPath` which will be used to redirect the user after he logged out

Default path for both setters is `/`

Finally, using the method `handleRequest` from the newly `Connect` object will validate (or not) the request, and redirect the user.

## Setting up your metadata

To fill the `Metadata` instance, two objects are necessary: the `Identity Provider` and the `Service Provider` descriptors.

```php
$metadata->setIdentityProvider(
    (new IdpSsoDescriptor())
        ->setID('http://idp.dev:8080')
        ->setWantAuthnRequestsSigned(true)
        ->addSingleSignOnService(
            new SingleSignOnService('http://idp.dev:8080/sso', SamlConstants::BINDING_SAML2_HTTP_REDIRECT)
        )
        ->addSingleLogoutService(
            new SingleLogoutService('http://idp.dev:8080/logout', SamlConstants::BINDING_SAML2_HTTP_POST)
        )
        ->addKeyDescriptor(new KeyDescriptor(
            KeyDescriptor::USE_SIGNING,
            X509Certificate::fromFile(__DIR__ . '/keys/idp/idp.crt')
        ))
)->setServiceProvider(
    (new SpSsoDescriptor())
        ->setID('http://' . $_SERVER['HTTP_HOST'])
        ->addAssertionConsumerService(
            new AssertionConsumerService(
                'http://' . $_SERVER['HTTP_HOST'] . '/acs.php',
                SamlConstants::BINDING_SAML2_HTTP_POST
            )
        )
        ->addSingleLogoutService(
            new SingleLogoutService(
                'http://' . $_SERVER['HTTP_HOST'] . '/logout.php',
                SamlConstants::BINDING_SAML2_HTTP_POST
            )
        )
        ->addKeyDescriptor(new KeyDescriptor(
            KeyDescriptor::USE_SIGNING,
            X509Certificate::fromFile(__DIR__ . '/keys/sp.crt')
        ))
        ->addKeyDescriptor(new KeyDescriptor(
            KeyDescriptor::USE_ENCRYPTION,
            X509Certificate::fromFile(__DIR__ . '/keys/sp.crt')
        )),
    file_get_contents(__DIR__ . '/keys/sp.pem')
);
```

### Identity Provider

As shown above, we need to fill an `IdpSsoDescriptor` with a few directives:

- `setID` set an unique ID corresponding to the Identity Provider created in Connect-IDP
- `setWantAuthnRequestsSigned` takes a single bool parameter and indicates if we want the Service Provider to sign every sent AuthnRequests
- `addSingleSignOnService` takes a SingleSignOnService as parameter, which has two properties:
    - The endpoint which will handle the request
    - A constant which describes the way the request will be sent
- `addSingleLogoutService` works as the same way as `setSingleSignOnService`, but with a SingleLogoutService, instanciated which an endpoint and a constant to indicate how the request is sent.
- `addKeyDescriptor` is used to associate a certificate to the SsoDescriptor. Those certificates will be used to:
    - Sign the AuthnRequest
    - Decrypt assertions.

    First `addKeyDescriptor` parameter is a constant contained in `KeyDescriptor`, describing how the key will be used, and the second one indicates the used certificate's path (via `X509Certificate fromFile()` static method)

### Service Provider

The service provider setter has two parameters:

**The first one** is the `SpSsoDescriptor`, and **the second one** constitutes the private key that has been generated to sign AuthnRequests.

As the `IdpSsoDescriptor`, the `SpSsoDescriptor` must be filled with different properties:

- `setID` Set an unique ID corresponding to the Service Provider created in Connect-IDP
- `addAssertionConsumerService` takes an AssertionConsumerService as parameter, which has two properties:
    - The first one describes an endpoint which tell the client where it should listen for IDP responses
    - A constant describing the request binding
- `addSingleLogoutService` takes a SingleLogoutService as parameter, which has two properties:
    - An endpoint describing where the client should listen to receive logout demands
    - A constant describing the request binding (POST in the example above)
- `addKeyDescriptor` is used to associate a certificate to the SsoDescriptor. Those certificates will be used to:
    - Sign the AuthnRequest
    - Decrypt assertions.

### Profile Association

You could register with `Config::registerProfileAssociation(callable $callback, $profileAssociationPath = '/connect/profile-association')`
a profile association callback for handling request provided by Connect-IDP. The callback must have one parameter which
must implement `Fei\Service\Connect\Common\ProfileAssociation\Message\RequestMessageInterface` and must return a instance of
`Fei\Service\Connect\Common\ProfileAssociation\Message\ResponseMessageInterface` :

```php
$config = (new Config())
    ->registerProfileAssociation(
        function (UsernamePasswordMessage $message) {
            if ($message->getUsername() != 'test' || $message->getPassword() != 'test') {
                throw new ProfileAssociationException('Profile not found', 400);
            }

            // Get allowed roles
            $roles = $message->getRoles();

            return (new ResponseMessage())->setRole('USER');
        },
        '/connect-profile-association'
    );
```

Role that the association profile message must set is provided by the RequestMessage. If the role returned is not valid
(not provided by the RequestMessage) a \LogicException will be throw.

If you decide that a request from Connect-IDP is not valid you must throw a `Fei\Service\Connect\Common\ProfileAssociation\Exception\ProfileAssociationException`
instance with a message and a HTTP error code which will be transmitted to Connect-IPD.

All messages between Connect-IPD and your Connect-client integration are encrypted so you must set private and public
keys for IDP and your Service Provider with metadata configuration directive.
 
#### Get role and local username

If the current user which the client provide with the method `Client::getUser()` is the result of a profile association,
you could get the local username and role with respectively `Client::getLocalUsername()` and `Client::getRole()`.
