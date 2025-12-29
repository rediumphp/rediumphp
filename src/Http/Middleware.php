<?php

namespace Redium\Http;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

abstract class Middleware
{
    /**
     * Process the request and pass to next middleware
     * 
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    abstract public function process(Request $request, Response $response, callable $next): Response;
}
