<?php
namespace Umg\EventListener;

use Symfony\Component\HttpFoundation\Cookie;
use Pimcore\Event\Model\DataObjectEvent;
use Psr\Http\Client\ClientExceptionInterface;
use Shopify\Auth\FileSessionStorage;
use Shopify\Auth\OAuth;
use Shopify\Clients\Rest;
use Shopify\Context;
use Shopify\Exception\CookieNotFoundException;
use Shopify\Exception\MissingArgumentException;
use Shopify\Exception\UninitializedContextException;
use Shopify\Utils;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Psr\Log\LoggerInterface;
use Shopify\Auth\OAuthCookie;

class PostUpdateListener
{
    // couldn't get the simple one to play ball for some reason but this works fine
    protected LoggerInterface $logger;

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    /**
     * @throws CookieNotFoundException
     * @throws ClientExceptionInterface
     * @throws UninitializedContextException
     * @throws MissingArgumentException
     */
    public function __invoke(DataObjectEvent $event): bool
    {
        // I'd  separate this logic out to thin this out if I had more time.
        $object = $event->getObject();
        if ($object instanceof \Pimcore\Model\DataObject\Product) {
            $objectName = $object->getName();
            $this->logger->debug('Name: ' . print_r($objectName, true));
            $objectProductType = $object->getProductType();
            $this->logger->debug('Type: ' . print_r($objectProductType, true));
            $objectPrice = $object->getPrice();
            $this->logger->debug('Price: ' . print_r($objectPrice, true));


            $headers = [
                'Content-Type' => 'application/json',
                'X-Shopify-Access-Token' => $_ENV['SHOPIFY_ADMIN_API_ACCESS_TOKEN'],
                'Authorization' => 'Bearer ' . $_ENV['SHOPIFY_ADMIN_API_ACCESS_TOKEN']
            ];

            $this->logger->debug('$headers: ' . print_r($headers, true));

            Context::initialize(
                apiKey: $_ENV['SHOPIFY_API_KEY'],
                apiSecretKey: $_ENV['SHOPIFY_API_SECRET_KEY'],
                scopes: $_ENV['SHOPIFY_APP_SCOPES'],
                hostName: $_ENV['SHOPIFY_APP_HOST_NAME'],
                sessionStorage: new FileSessionStorage('/tmp/php_sessions'), // wouldn't use this on a real prod app
                apiVersion: '2023-04',
                isEmbeddedApp: true,
                isPrivateApp: false,
            );

            $oauthUrl = OAuth::begin($_ENV['SHOPIFY_APP_HOST_NAME'],'auth/callback',true);

            // for whatever reason this always fails to find the session id first time it runs, if I add a few seconds wait, the issue goes away
          //  sleep(2);
            $this->logger->debug('$oauthUrl: ' . print_r($oauthUrl, true));
            $this->logger->debug('cookies: ' . print_r($_COOKIE, true));

            $query = Utils::getQueryParams($oauthUrl);

            // discovered some weirdness with Shopify\Auth\isCallbackQueryValid. If I dd() the conditions in the end I'm struggling to
            // see how that would be true unless I'm screwing something up. I did a bit of research and there are some
            // recent posts with other people having similar issues.
            $session = OAuth::callback($_COOKIE, $query);
/*            $session = Utils::loadCurrentSession(
                $headers,
                $cookies,
                true
            );*/
            $this->logger->debug('$session: ' . print_r($session, true));
//            $session = Utils::loadCurrentSession(
//                $headers,
//                $cookies,
//                $isOnline
//            );
//
//            $client = new Rest(
//                $session->getShop(),
//                $session->getAccessToken()
//            );
//
//            $response = $client->get('shop');


        }
    }
}
