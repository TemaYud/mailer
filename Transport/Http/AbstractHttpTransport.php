<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Mailer\Transport\Http;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\Exception\HttpTransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @author Victor Bocharsky <victor@symfonycasts.com>
 */
abstract class AbstractHttpTransport extends AbstractTransport
{
    protected $client;

    public function __construct(HttpClientInterface $client = null, EventDispatcherInterface $dispatcher = null, LoggerInterface $logger = null)
    {
        $this->client = $client;
        if (null === $client) {
            if (!class_exists(HttpClient::class)) {
                throw new \LogicException(sprintf('You cannot use "%s" as the HttpClient component is not installed. Try running "composer require symfony/http-client".', __CLASS__));
            }

            $this->client = HttpClient::create();
        }

        parent::__construct($dispatcher, $logger);
    }

    abstract protected function doSendHttp(SentMessage $message): ResponseInterface;

    protected function doSend(SentMessage $message): void
    {
        $response = null;
        try {
            $response = $this->doSendHttp($message);
        } catch (HttpTransportException $e) {
            $response = $e->getResponse();

            throw $e;
        } finally {
            if (null !== $response) {
                $message->appendDebug($response->getInfo('debug'));
            }
        }
    }
}
