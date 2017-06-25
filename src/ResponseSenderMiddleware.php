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

/**
 * @author Dusan Vejin <dutekvejin@gmail.com>
 */
class ResponseSenderMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, DelegateInterface $delegate): ResponseInterface
    {
        $response = $delegate->process($request);

        if (!headers_sent()) {
            $http = sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            );

            header($http, true, $response->getStatusCode());

            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
        }

        $stream = $response->getBody();

        if ($stream->isSeekable()) {
            $stream->rewind();
        }

        while (false === $stream->eof()) {
            echo $stream->read(1024 * 8);
        }

        return $response;
    }
}
