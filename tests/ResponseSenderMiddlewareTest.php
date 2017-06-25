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

namespace WeCodeIn\Http\ServerMiddleware\Middleware\Tests;

use Http\Factory\Guzzle\ResponseFactory;
use Http\Factory\Guzzle\ServerRequestFactory;
use Http\Factory\Guzzle\StreamFactory;
use Interop\Http\ServerMiddleware\DelegateInterface;
use phpmock\phpunit\PHPMock;
use PHPUnit\Framework\TestCase;
use WeCodeIn\Http\ServerMiddleware\Middleware\ResponseSenderMiddleware;

/**
 * @author Dusan Vejin <dutekvejin@gmail.com>
 */
class ResponseSenderMiddlewareTest extends TestCase
{
    use PHPMock;

    /**
     * @group ServerMiddleware
     */
    public function testSend()
    {
        $serverRequestFactory = new ServerRequestFactory();
        $request = $serverRequestFactory->createServerRequest('GET', 'http://localhost/');

        $streamFactory = new StreamFactory();
        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse(200)
            ->withProtocolVersion('1.1')
            ->withHeader('Content-Type', 'text/plain')
            ->withBody($streamFactory->createStream($body = 'Http body'));

        $delegate = $this->createMock(DelegateInterface::class);
        $delegate->expects($this->any())
            ->method('process')
            ->with($request)
            ->willReturn($response);

        $headersSent = $this->getFunctionMock('WeCodeIn\Http\ServerMiddleware\Middleware', 'headers_sent');
        $headersSent->expects($this->once())
            ->willReturn(false);

        $header = $this->getFunctionMock('WeCodeIn\Http\ServerMiddleware\Middleware', 'header');
        $header->expects($this->exactly(2))
            ->withConsecutive(
                ['HTTP/1.1 200 OK', true, 200],
                ['Content-Type: text/plain', false]
            );

        ob_start();
        $responseSenderMiddleware = new ResponseSenderMiddleware();
        $return = $responseSenderMiddleware->process($request, $delegate);
        $output = ob_get_clean();

        $this->assertSame($response, $return);
        $this->assertSame($output, $body);
    }

    /**
     * @group ServerMiddleware
     */
    public function testSendWhenHeadersAlreadySent()
    {
        $serverRequestFactory = new ServerRequestFactory();
        $request = $serverRequestFactory->createServerRequest('GET', 'http://localhost/');

        $streamFactory = new StreamFactory();
        $responseFactory = new ResponseFactory();
        $response = $responseFactory->createResponse(200)
            ->withProtocolVersion('1.1')
            ->withHeader('Content-Type', 'text/plain')
            ->withBody($streamFactory->createStream($body = 'Http body'));

        $delegate = $this->createMock(DelegateInterface::class);
        $delegate->expects($this->any())
            ->method('process')
            ->with($request)
            ->willReturn($response);

        $headersSent = $this->getFunctionMock('WeCodeIn\Http\ServerMiddleware\Middleware', 'headers_sent');
        $headersSent->expects($this->once())
            ->willReturn(true);

        $header = $this->getFunctionMock('WeCodeIn\Http\ServerMiddleware\Middleware', 'header');
        $header->expects($this->never());

        ob_start();
        $responseSenderMiddleware = new ResponseSenderMiddleware();
        $return = $responseSenderMiddleware->process($request, $delegate);
        $output = ob_get_clean();

        $this->assertSame($response, $return);
        $this->assertSame($output, $body);
    }
}
