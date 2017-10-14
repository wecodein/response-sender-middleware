<?php
/**
 * This file is part of the response-sender-middleware package.
 *
 * Copyright (c) Dusan Vejin
 *
 * For full copyright and license information, please refer to the LICENSE file,
 * located at the package root folder.
 */

declare(strict_types=1);

namespace WeCodeIn\Http\Server\Middleware\Tests;

use Http\Factory\Guzzle\ResponseFactory;
use Http\Factory\Guzzle\ServerRequestFactory;
use Http\Factory\Guzzle\StreamFactory;
use Interop\Http\Factory\ResponseFactoryInterface;
use Interop\Http\Factory\ServerRequestFactoryInterface;
use Interop\Http\Server\MiddlewareInterface;
use Interop\Http\Server\RequestHandlerInterface;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use WeCodeIn\Http\Server\Middleware\CallableMiddleware;
use WeCodeIn\Http\Server\Middleware\ResponseSenderMiddleware;
use WeCodeIn\Http\Server\RequestHandler;

class ResponseSenderMiddlewareTest extends TestCase
{
    use PHPMock;

    public function testProcessSendsHeaders()
    {
        $headersSent = $this->getFunctionMock('WeCodeIn\Http\Server\Middleware', 'headers_sent');
        $headersSent->expects($this->any())
            ->willReturn(false);

        $header = $this->getFunctionMock('WeCodeIn\Http\Server\Middleware', 'header');
        $header->expects($this->exactly(2))
            ->withConsecutive(
                ['HTTP/1.1 200 OK', true, 200],
                ['Content-Type: text/plain', false]
            );

        $callable = function () {
            return $this->createResponse()
                ->withHeader('Content-Type', 'text/plain');
        };

        $middleware = new CallableMiddleware($callable);

        $request = $this->createServerRequest();
        $handler = $this->createRequestHandler($middleware);

        $responseSenderMiddleware = new ResponseSenderMiddleware();
        $responseSenderMiddleware->process($request, $handler);
    }

    public function testProcessOutputBody()
    {
        $headersSent = $this->getFunctionMock('WeCodeIn\Http\Server\Middleware', 'headers_sent');
        $headersSent->expects($this->any())
            ->willReturn(false);

        $header = $this->getFunctionMock('WeCodeIn\Http\Server\Middleware', 'header');
        $header->expects($this->any());

        $body = 'Response Body';

        $callable = function () use ($body) {
            $streamFactory = new StreamFactory();

            return $this->createResponse()
                ->withBody($streamFactory->createStream($body));
        };

        $middleware = new CallableMiddleware($callable);

        $request = $this->createServerRequest();
        $handler = $this->createRequestHandler($middleware);

        ob_start();
        $responseSenderMiddleware = new ResponseSenderMiddleware();
        $responseSenderMiddleware->process($request, $handler);
        $output = ob_get_clean();

        $this->assertSame($body, $output);
    }

    public function testProcessNotSendsHeadersWhenHeadersAlreadySent()
    {
        $headersSent = $this->getFunctionMock('WeCodeIn\Http\Server\Middleware', 'headers_sent');
        $headersSent->expects($this->any())
            ->willReturn(true);

        $header = $this->getFunctionMock('WeCodeIn\Http\Server\Middleware', 'header');
        $header->expects($this->never());

        $callable = function () {
            return $this->createResponse()
                ->withHeader('Content-Type', 'text/plain');
        };

        $middleware = new CallableMiddleware($callable);

        $request = $this->createServerRequest();
        $handler = $this->createRequestHandler($middleware);

        $responseSenderMiddleware = new ResponseSenderMiddleware();
        $responseSenderMiddleware->process($request, $handler);
    }

    protected function getServerRequestFactory() : ServerRequestFactoryInterface
    {
        return new ServerRequestFactory();
    }

    protected function createServerRequest() : ServerRequestInterface
    {
        return $this->getServerRequestFactory()
            ->createServerRequest('GET', 'http://example.com');
    }

    protected function getResponseFactory() : ResponseFactoryInterface
    {
        return new ResponseFactory();
    }

    protected function createResponse(int $code = 200) : ResponseInterface
    {
        return $this->getResponseFactory()
            ->createResponse($code);
    }

    protected function createRequestHandler(MiddlewareInterface ...$middlewares) : RequestHandlerInterface
    {
        return new RequestHandler($this->getResponseFactory(), ...$middlewares);
    }
}
