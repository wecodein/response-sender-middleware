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

namespace WeCodeIn\Http\ServerMiddleware\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @author Dusan Vejin <dutekvejin@gmail.com>
 */
class ResponseSenderMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, DelegateInterface $delegate) : ResponseInterface
    {
        $response = $delegate->process($request);

        $this->sendHeaders($response);
        $this->sendOutput($response->getBody());

        return $response;
    }

    protected function sendHeaders(ResponseInterface $response)
    {
        if (headers_sent()) {
            return;
        }

        $protocolVersion = $response->getProtocolVersion();
        $statusCode = $response->getStatusCode();
        $reasonPhrase = $response->getReasonPhrase();

        header("HTTP/$protocolVersion $statusCode $reasonPhrase", true, $statusCode);

        foreach ($response->getHeaders() as $name => $values) {
            foreach ($values as $value) {
                header("$name: $value", false);
            }
        }
    }

    protected function sendOutput(StreamInterface $stream)
    {
        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        while (false === $stream->eof()) {
            echo $stream->read(1024 * 8);
        }
    }
}
